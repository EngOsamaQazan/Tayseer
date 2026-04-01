import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. See the full 188 bytes output
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1",
    
    # 2. Check the response headers fully
    "curl -sk -D - 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -30",
    
    # 3. Check index.php to see what was added
    "head -5 /usr/share/phpmyadmin/index.php",
    
    # 4. Fix the debug - add to top of index.php properly
    r"""cat > /tmp/pma_debug_header.php << 'PHP'
<?php
error_log("PMA_DEBUG: START route=" . ($_GET["route"] ?? "none") . " db=" . ($_GET["db"] ?? "none") . "\n", 3, "/tmp/pma_debug.log");
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e) {
        error_log("PMA_DEBUG: SHUTDOWN ERROR: " . $e['type'] . " " . $e['message'] . " in " . $e['file'] . ":" . $e['line'] . "\n", 3, "/tmp/pma_debug.log");
    } else {
        error_log("PMA_DEBUG: SHUTDOWN OK\n", 3, "/tmp/pma_debug.log");
    }
});
PHP
# Remove the opening <?php from the above, and prepend to index.php
tail -n +2 /tmp/pma_debug_header.php > /tmp/pma_debug_code.php
cp /usr/share/phpmyadmin/index.php.bak /usr/share/phpmyadmin/index.php
sed -i '1a\\' /usr/share/phpmyadmin/index.php
sed -i '2r /tmp/pma_debug_code.php' /usr/share/phpmyadmin/index.php
echo "debug code inserted"
head -15 /usr/share/phpmyadmin/index.php
""",
    
    # 5. Restart Apache
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 6. Clear logs
    "truncate -s 0 /tmp/pma_debug.log /tmp/pma_errors.log 2>/dev/null; echo 'logs cleared'",
    
    # 7. Test SQL page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | wc -c",
    
    # 8. Check debug log
    "cat /tmp/pma_debug.log 2>/dev/null",
    
    # 9. Check error log
    "cat /tmp/pma_errors.log 2>/dev/null",
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
