import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Enable PHP error logging to a specific file for phpMyAdmin
    """echo 'php_value error_log /tmp/pma_errors.log' >> /usr/share/phpmyadmin/.htaccess
echo 'php_flag log_errors on' >> /usr/share/phpmyadmin/.htaccess
echo 'php_flag display_errors off' >> /usr/share/phpmyadmin/.htaccess
cat /usr/share/phpmyadmin/.htaccess""",
    
    # 2. Clear old errors
    "rm -f /tmp/pma_errors.log 2>/dev/null; touch /tmp/pma_errors.log; chmod 666 /tmp/pma_errors.log; echo 'log created'",
    
    # 3. Restart Apache to apply
    "systemctl reload apache2 2>&1; echo 'apache reloaded'",
    
    # 4. Wait and hit the SQL page to trigger any errors
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' > /dev/null 2>&1; echo 'page accessed'",
    
    # 5. Check PHP errors
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 6. Also check - what PHP version is running the web request?
    """echo '<?php echo "PHP: " . PHP_VERSION . " SAPI: " . php_sapi_name() . " HTTPS: " . ($_SERVER["HTTPS"]??"off");' > /usr/share/phpmyadmin/version_check.php""",
    "curl -sk https://aqssat.co/phpmyadmin/version_check.php 2>&1",
    
    # 7. Test the PHP 8.5 compatibility of the full setCookie path for PHP < 7.3 branch
    # This branch should not execute, but let's verify PHP_VERSION_ID
    """echo '<?php echo "PHP_VERSION_ID=" . PHP_VERSION_ID . " PHP < 7.3 branch=" . (PHP_VERSION_ID < 70300 ? "YES" : "NO");' > /usr/share/phpmyadmin/php_check.php""",
    "curl -sk https://aqssat.co/phpmyadmin/php_check.php 2>&1",
    
    # 8. Check if there is a Twig cache issue
    "ls -la /tmp/twig/ 2>/dev/null | head -5",
    "ls -la /usr/share/phpmyadmin/tmp/ 2>/dev/null | head -5",
    
    # 9. Create tmp directory for phpMyAdmin if needed
    "mkdir -p /tmp/phpmyadmin && chown www-data:www-data /tmp/phpmyadmin && echo 'tmp dir ready'",
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
