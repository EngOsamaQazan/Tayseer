import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check PHP-FPM status
    "systemctl status php*-fpm --no-pager 2>/dev/null | head -20",
    
    # 2. Check Apache/Nginx status
    "systemctl status apache2 --no-pager 2>/dev/null | head -10; systemctl status nginx --no-pager 2>/dev/null | head -10",
    
    # 3. Check PHP error log for phpMyAdmin errors
    "tail -30 /var/log/php*error*.log 2>/dev/null; tail -30 /var/log/php*/error.log 2>/dev/null",
    
    # 4. Check Apache error log
    "tail -30 /var/log/apache2/error.log 2>/dev/null; tail -30 /var/log/nginx/error.log 2>/dev/null",
    
    # 5. Check phpMyAdmin config
    "cat /usr/share/phpmyadmin/config.inc.php 2>/dev/null",
    
    # 6. Check PHP version and loaded modules
    "php -v 2>/dev/null; echo '---'; php -m 2>/dev/null | grep -i -E 'mysql|mbstring|json|session|curl|xml|gd'",
    
    # 7. Check if phpMyAdmin directory exists and permissions
    "ls -la /usr/share/phpmyadmin/index.php 2>/dev/null; ls -la /usr/share/phpmyadmin/ 2>/dev/null | head -10",
    
    # 8. Check Apache/Nginx config for phpMyAdmin
    "cat /etc/apache2/conf-enabled/phpmyadmin.conf 2>/dev/null; cat /etc/nginx/snippets/phpmyadmin.conf 2>/dev/null; grep -r 'phpmyadmin' /etc/apache2/ 2>/dev/null | head -5; grep -r 'phpmyadmin' /etc/nginx/ 2>/dev/null | head -5",
    
    # 9. Test PHP directly
    "php -r \"require '/usr/share/phpmyadmin/index.php';\" 2>&1 | head -30",
    
    # 10. Check tmp directory for phpMyAdmin
    "ls -la /var/lib/phpmyadmin/tmp/ 2>/dev/null; ls -la /tmp/phpmyadmin/ 2>/dev/null",
    
    # 11. Check PHP-FPM pool config
    "cat /etc/php/*/fpm/pool.d/www.conf 2>/dev/null | grep -E 'listen|pm\\.' | head -10",
    
    # 12. Check disk space
    "df -h / /tmp 2>/dev/null",
    
    # 13. Check if PHP session directory is writable
    "php -r \"echo ini_get('session.save_path');\" 2>/dev/null; echo; ls -la $(php -r \"echo ini_get('session.save_path');\" 2>/dev/null) 2>/dev/null | head -3",
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
