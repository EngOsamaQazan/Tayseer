import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Restore original index.php
    "cp /usr/share/phpmyadmin/index.php.bak /usr/share/phpmyadmin/index.php; echo 'restored'",
    
    # 2. Create prepend file for debugging
    r"""cat > /usr/share/phpmyadmin/_debug_prepend.php << 'PHP'
<?php
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e) {
        error_log("PMA_SHUTDOWN_ERROR: type=" . $e['type'] . " msg=" . $e['message'] . " file=" . $e['file'] . " line=" . $e['line'] . "\n", 3, "/tmp/pma_debug.log");
    } else {
        error_log("PMA_SHUTDOWN: OK ob=" . ob_get_level() . " len=" . ob_get_length() . "\n", 3, "/tmp/pma_debug.log");
    }
});
error_log("PMA_START: " . $_SERVER['REQUEST_URI'] . "\n", 3, "/tmp/pma_debug.log");
PHP
echo "prepend file created"
""",
    
    # 3. Update .htaccess to use auto_prepend_file
    r"""cat > /usr/share/phpmyadmin/.htaccess << 'HTEOF'
php_flag display_errors On
php_flag log_errors On
php_value error_log /tmp/pma_errors.log
php_value error_reporting 32767
php_value auto_prepend_file /usr/share/phpmyadmin/_debug_prepend.php
HTEOF
echo ".htaccess updated"
""",
    
    # 4. Clear logs, restart
    "truncate -s 0 /tmp/pma_debug.log /tmp/pma_errors.log 2>/dev/null",
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 5. Test SQL page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -20",
    
    # 6. Check debug log
    "cat /tmp/pma_debug.log 2>/dev/null",
    
    # 7. Check error log
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 8. Test home page too
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php' 2>&1 | head -5",
    
    # 9. Check debug log after both requests
    "cat /tmp/pma_debug.log 2>/dev/null",
]

for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'=== {cmd[:80]} ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()

ssh.close()
