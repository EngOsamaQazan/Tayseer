import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check routes cache permissions
    "ls -la /usr/share/phpmyadmin/libraries/cache/ 2>/dev/null",
    
    # 2. Check routes cache content
    "head -5 /usr/share/phpmyadmin/libraries/cache/routes.cache.php 2>/dev/null",
    
    # 3. Delete the routes cache
    "rm -f /usr/share/phpmyadmin/libraries/cache/routes.cache.php 2>/dev/null; echo 'cache deleted'",
    
    # 4. Fix cache directory permissions
    "chown -R www-data:www-data /usr/share/phpmyadmin/libraries/cache/ 2>/dev/null; chmod 755 /usr/share/phpmyadmin/libraries/cache/ 2>/dev/null; echo 'perms fixed'",
    
    # 5. Also fix Twig cache
    "rm -rf /tmp/twig 2>/dev/null; mkdir -p /tmp/twig; chown www-data:www-data /tmp/twig; chmod 777 /tmp/twig; echo 'twig cache cleared'",
    
    # 6. Clear all PHP sessions
    "rm -f /var/lib/php/sessions/sess_* 2>/dev/null; echo 'sessions cleared'",
    
    # 7. Clear OPcache and restart
    """php -r "opcache_reset();" 2>/dev/null""",
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 8. Test SQL page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | wc -c",
    
    # 9. Test home page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php' 2>&1 | head -5",
    
    # 10. Check SQL page content
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -10",
    
    # 11. Check routes cache recreated?
    "ls -la /usr/share/phpmyadmin/libraries/cache/ 2>/dev/null",
    
    # 12. Check the /database/sql route definition in routes.php
    "grep -A2 'database.*sql' /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null | head -10",
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
