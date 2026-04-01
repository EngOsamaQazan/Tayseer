import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Enable display_errors for phpMyAdmin temporarily to catch the error
    r"""cat > /usr/share/phpmyadmin/.htaccess << 'HTEOF'
php_flag display_errors On
php_flag log_errors On
php_value error_log /tmp/pma_errors.log
php_value error_reporting 32767
HTEOF
echo ".htaccess written"
""",
    
    # 2. Clear error log
    "truncate -s 0 /tmp/pma_errors.log 2>/dev/null; echo 'cleared'",
    
    # 3. Reload apache
    "systemctl reload apache2 2>&1; echo 'reloaded'",
    
    # 4. Test - access SQL page (this will show login since no auth)
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | grep -i 'error\\|fatal\\|warning\\|notice\\|deprecated' | head -10",
    
    # 5. Check error log
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 6. Now let's test what the SQL page returns when AJAX-loaded with auth
    # We need a valid session. Let me use phpMyAdmin's auth method 
    # to create a session directly on the server
    """php -r '
session_start();
\$_SESSION["PMA_single_signon_user"] = "root";
\$_SESSION["PMA_single_signon_password"] = "";
\$_SESSION["PMA_single_signon_host"] = "localhost";
echo session_id();
session_write_close();
' 2>&1""",

    # 7. Try login via adding config for single signon temporarily
    # Actually, a simpler approach: switch to config auth temporarily
    r"""cat > /usr/share/phpmyadmin/config.inc.php << 'PHPCFG'
<?php
$cfg['blowfish_secret'] = '960a605ddc04967789cb19feae2cd29e';
$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'config';
$cfg['Servers'][$i]['user'] = 'root';
$cfg['Servers'][$i]['password'] = '';
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = true;
$cfg['TempDir'] = '/tmp';

$cfg['NavigationTreeDbSeparator'] = false;
$cfg['NavigationTreeEnableGrouping'] = false;
$cfg['PmaAbsoluteUri'] = 'https://aqssat.co/phpmyadmin/';
PHPCFG
echo "config auth set"
""",
    
    # 8. Now test the SQL page directly
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -30",
    
    # 9. Check for errors
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 10. Get more of the SQL page response
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | grep -c 'codemirror\\|sqlquery\\|SQL'",
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
