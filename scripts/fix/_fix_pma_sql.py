import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check the HTTPS mismatch - what does phpMyAdmin think about HTTPS?
    "grep -rn 'isHttps\\|ForceSSL\\|PmaAbsoluteUri\\|https_mismatch\\|HTTPS' /usr/share/phpmyadmin/libraries/classes/Config.php 2>/dev/null | head -20",
    
    # 2. Check the isHttps function
    "sed -n '/function isHttps/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 3. Check Apache SSL/HTTPS config - is HTTPS being passed to PHP?
    "grep -rn 'HTTPS\\|SSL\\|RequestHeader' /etc/apache2/sites-enabled/aqssat.co-le-ssl.conf 2>/dev/null",
    
    # 4. Check PHP's $_SERVER['HTTPS'] value
    """php -r "echo 'HTTPS: ' . (\$_SERVER['HTTPS'] ?? 'NOT SET');" 2>&1""",
    
    # 5. Create a quick test to check HTTPS detection via web
    """echo '<?php echo "HTTPS=" . (\$_SERVER["HTTPS"] ?? "NOT_SET") . "\\n"; echo "SERVER_PORT=" . (\$_SERVER["SERVER_PORT"] ?? "NOT_SET") . "\\n"; echo "REQUEST_SCHEME=" . (\$_SERVER["REQUEST_SCHEME"] ?? "NOT_SET") . "\\n"; echo "HTTP_X_FORWARDED_PROTO=" . (\$_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "NOT_SET") . "\\n"; ?>' > /usr/share/phpmyadmin/check_https.php""",
    
    # 6. Test the HTTPS detection via curl
    "curl -sk https://aqssat.co/phpmyadmin/check_https.php 2>&1",
    
    # 7. Check the phpMyAdmin config for ForceSSL
    "cat /usr/share/phpmyadmin/config.inc.php",
    
    # 8. Check PHP error logs specifically for the SQL route
    "grep -i 'phpmyadmin\\|Fatal\\|error\\|setcookie' /var/log/apache2/error.log 2>/dev/null | tail -20",
    
    # 9. Test the SQL route specifically with a session cookie
    "curl -sk -c /tmp/pma_cookies.txt https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -o 'token:\"[^\"]*\"'",
    
    # 10. Check if there's a PHP error for the SQL route specifically
    """curl -sk -b /tmp/pma_cookies.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -20""",
    
    # 11. Check Apache error log for recent errors (last 5 minutes)
    "find /var/log/apache2 -name '*.log' -newer /tmp/pma_cookies.txt 2>/dev/null -exec tail -5 {} \\; 2>/dev/null | grep -i 'error\\|fatal\\|warning' | head -10",
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
