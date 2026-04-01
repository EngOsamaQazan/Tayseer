import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check error log
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 2. Check apache error log - last 20 lines
    "tail -5 /var/log/apache2/aqssat_error.log 2>/dev/null",
    
    # 3. Check apache error log for phpmyadmin
    "tail -20 /var/log/apache2/error.log 2>/dev/null | grep -i 'phpmyadmin\\|Fatal\\|ValueError\\|setcookie' | tail -5",
    
    # 4. Actually, let me test with curl and auth session using proper cookies
    # First set config auth to test
    """curl -sk -c /tmp/pma_test2.txt -L 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -5""",
    
    # 5. Check full output
    """curl -sk -c /tmp/pma_test2.txt -L 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | wc -c""",
    
    # 6. Temporarily enable display_errors for full page
    r"""cat > /usr/share/phpmyadmin/.htaccess << 'HTEOF'
php_flag display_errors On
php_flag log_errors On
php_value error_log /tmp/pma_errors.log
php_value error_reporting 32767
HTEOF
echo ".htaccess set"
""",
    
    # 7. Reload apache
    "systemctl reload apache2 2>&1",
    
    # 8. Clear error log
    "truncate -s 0 /tmp/pma_errors.log 2>/dev/null",
    
    # 9. Now test SQL page with config auth
    r"""cat > /usr/share/phpmyadmin/config.inc.php << 'PHPCFG'
<?php
$cfg['blowfish_secret'] = '960a605ddc04967789cb19feae2cd29e';
$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'config';
$cfg['Servers'][$i]['user'] = 'root';
$cfg['Servers'][$i]['password'] = 'HAmAS12852';
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['TempDir'] = '/tmp';

$cfg['NavigationTreeDbSeparator'] = false;
$cfg['NavigationTreeEnableGrouping'] = false;
$cfg['PmaAbsoluteUri'] = 'https://aqssat.co/phpmyadmin/';
$cfg['CookieSameSite'] = 'Lax';
PHPCFG
echo "config auth set with password"
""",
    
    # 10. Clear OPcache and test
    """php -r "opcache_reset();" 2>/dev/null""",
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -40",
    
    # 11. Check errors
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
