import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Fix HTTPS mismatch - add ForceSSL to phpMyAdmin config
    r"""cat > /usr/share/phpmyadmin/config.inc.php << 'PHPCFG'
<?php
$cfg['blowfish_secret'] = '960a605ddc04967789cb19feae2cd29e';
$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['TempDir'] = '/tmp';

$cfg['NavigationTreeDbSeparator'] = false;
$cfg['NavigationTreeEnableGrouping'] = false;

// Force HTTPS detection
$cfg['ForceSSL'] = false;
$cfg['PmaAbsoluteUri'] = 'https://aqssat.co/phpmyadmin/';
PHPCFG
echo "config written"
""",
    
    # 2. Verify config
    "cat /usr/share/phpmyadmin/config.inc.php",
    
    # 3. Also fix the removeCookie function for PHP 8.5 compatibility
    # The old-style setcookie in removeCookie might also cause issues
    "sed -n '880,900p' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 4. Clear PHP sessions and OPcache
    "rm -f /var/lib/php/sessions/sess_* 2>/dev/null; echo 'sessions cleared'",
    """php -r "if(function_exists('opcache_reset')){opcache_reset();echo 'opcache cleared';}else{echo 'no opcache';}" 2>&1""",
    
    # 5. Restart Apache
    "systemctl restart apache2 2>&1; echo 'apache restarted'",
    
    # 6. Clean up test files
    "rm -f /usr/share/phpmyadmin/check_https.php /usr/share/phpmyadmin/.htaccess 2>/dev/null; echo 'cleaned'",
    
    # 7. Test HTTPS mismatch is gone
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -i 'mismatch\\|HTTPS'",
    
    # 8. Test with login - get token and session
    "curl -sk -c /tmp/pma_test.txt -D /tmp/pma_headers.txt https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -o 'token:\"[^\"]*\"\\|mismatch'",
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
