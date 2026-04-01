import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Restore cookie auth with SameSite fix + AllowNoPassword
    r"""cat > /usr/share/phpmyadmin/config.inc.php << 'PHPCFG'
<?php
$cfg['blowfish_secret'] = '960a605ddc04967789cb19feae2cd29e';
$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = true;
$cfg['TempDir'] = '/tmp';

$cfg['NavigationTreeDbSeparator'] = false;
$cfg['NavigationTreeEnableGrouping'] = false;
$cfg['PmaAbsoluteUri'] = 'https://aqssat.co/phpmyadmin/';

// Fix SameSite to Lax to prevent cookie issues with navigation
$cfg['CookieSameSite'] = 'Lax';
PHPCFG
echo "config written with Lax cookies"
""",
    
    # 2. Clear all sessions
    "rm -f /var/lib/php/sessions/sess_* 2>/dev/null; echo 'sessions cleared'",
    
    # 3. Clear Twig cache 
    "rm -rf /tmp/twig 2>/dev/null; mkdir -p /tmp/twig; chown www-data:www-data /tmp/twig; chmod 777 /tmp/twig; echo 'twig cleared'",
    
    # 4. Clear OPcache
    """php -r "opcache_reset();" 2>/dev/null; echo 'opcache cleared'""",
    
    # 5. Restart Apache
    "systemctl restart apache2 2>&1; echo 'apache restarted'",
    
    # 6. Test login with curl
    """rm -f /tmp/pma_final.txt /tmp/pma_final_h.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_final.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\\K[^"]+' | head -1)
echo "Token: $TOKEN"
LOGIN=$(curl -sk -b /tmp/pma_final.txt -c /tmp/pma_final.txt -D /tmp/pma_final_h.txt -d "pma_username=root&pma_password=&server=1&token=$TOKEN" 'https://aqssat.co/phpmyadmin/index.php' 2>/dev/null)
echo "Login size: $(echo "$LOGIN" | wc -c)"
echo "Logged in: $(echo "$LOGIN" | grep -o 'logged_in:[a-z]*')"
echo "Auth cookie: $(grep pmaAuth /tmp/pma_final.txt 2>/dev/null)"
echo "Set-Cookie headers:"
grep -i 'set-cookie' /tmp/pma_final_h.txt 2>/dev/null | head -5
""",
    
    # 7. If logged in, test SQL page
    """curl -sk -b /tmp/pma_final.txt -c /tmp/pma_final.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | grep -o 'logged_in:[a-z]*'""",
    
    # 8. Check errors
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 9. Remove .htaccess error display (keep logging)
    r"""cat > /usr/share/phpmyadmin/.htaccess << 'HTEOF'
php_flag display_errors Off
php_flag log_errors On
php_value error_log /tmp/pma_errors.log
HTEOF
echo ".htaccess updated"
""",
    
    # 10. Reload apache
    "systemctl reload apache2 2>&1; echo 'reloaded'",
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
