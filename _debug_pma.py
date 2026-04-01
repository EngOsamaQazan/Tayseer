import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check the ResponseRenderer for output buffering issues
    "grep -n 'ob_start\\|ob_end\\|ob_get\\|ob_clean\\|exit\\|die(' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php 2>/dev/null | head -20",
    
    # 2. Check the shutdown handler
    "grep -n 'register_shutdown\\|shutdown' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php 2>/dev/null | head -10",
    
    # 3. Look at the response method
    "sed -n '/function response/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php 2>/dev/null | head -40",
    
    # 4. Add debug logging directly to index.php
    r"""cp /usr/share/phpmyadmin/index.php /usr/share/phpmyadmin/index.php.bak
# Add error logging at the very top
sed -i '2i error_log("PMA_DEBUG: index.php started route=" . ($_GET["route"] ?? "none") . " db=" . ($_GET["db"] ?? "none"), 3, "/tmp/pma_debug.log");' /usr/share/phpmyadmin/index.php
echo "debug added to index.php"
""",
    
    # 5. Add debug to ResponseRenderer
    """grep -n 'function response' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php 2>/dev/null""",
    
    # 6. Check how output is sent
    "grep -n 'echo\\|print\\|header(' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php 2>/dev/null | head -20",
    
    # 7. Clear debug log
    "rm -f /tmp/pma_debug.log; touch /tmp/pma_debug.log; chmod 666 /tmp/pma_debug.log; echo 'debug log ready'",
    
    # 8. Restart Apache
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 9. Test the SQL page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | wc -c",
    
    # 10. Check debug log
    "cat /tmp/pma_debug.log 2>/dev/null",
    
    # 11. Check PHP error log
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
