import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check aqssat error log for phpMyAdmin / setcookie errors
    "grep -i 'phpmyadmin\\|setcookie\\|ValueError\\|Fatal' /var/log/apache2/aqssat_error.log 2>/dev/null | tail -20",
    
    # 2. Check which PHP module is loaded in Apache
    "apache2ctl -M 2>/dev/null | grep php",
    
    # 3. Check PHP version being used by Apache
    "php -r \"phpinfo();\" 2>/dev/null | grep -i 'Server API'",
    
    # 4. Check which PHP is the Apache module
    "ls -la /etc/apache2/mods-enabled/php* 2>/dev/null",
    
    # 5. Check the aqssat error log tail
    "tail -30 /var/log/apache2/aqssat_error.log 2>/dev/null",
    
    # 6. Try curl with HTTPS to see actual response
    "curl -sk https://localhost/phpmyadmin/index.php 2>&1 | head -5",
    
    # 7. Check the HTML output of phpMyAdmin via actual web request
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | head -10",
    
    # 8. Check PHP 8.5 apache2 module php.ini
    "grep -n 'display_errors\\|error_reporting\\|error_log' /etc/php/8.5/apache2/php.ini 2>/dev/null | head -10",
    
    # 9. Check the PHP info page
    """echo '<?php phpinfo(); ?>' > /tmp/phpinfo_test.php && php /tmp/phpinfo_test.php 2>/dev/null | grep -i 'PHP Version\\|Server API\\|Loaded Configuration' | head -5 && rm /tmp/phpinfo_test.php""",
    
    # 10. Check all error logs for today for phpmyadmin
    "grep -rl 'setcookie\\|phpmyadmin.*Fatal\\|Config.php' /var/log/apache2/ 2>/dev/null",
    
    # 11. Check the PHP 8.5 specific error log
    "cat /var/log/php8.5-fpm.log 2>/dev/null | tail -10; cat /var/log/php_errors.log 2>/dev/null | tail -10",
    
    # 12. Enable error display for phpMyAdmin
    """echo '<?php ini_set("display_errors",1); error_reporting(E_ALL); require "index.php";' > /usr/share/phpmyadmin/test_debug.php""",
    
    # 13. Curl the debug page
    "curl -sk https://aqssat.co/phpmyadmin/test_debug.php 2>&1 | head -30",
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
