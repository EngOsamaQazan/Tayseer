import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Login to phpMyAdmin via curl and then access SQL page
    # First get the login page to get token
    """curl -sk -c /tmp/pma_jar.txt https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -o 'token:\"[^\"]*\"' | head -1""",
    
    # 2. Get the token value
    """TOKEN=$(curl -sk -c /tmp/pma_jar.txt https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -oP 'name="token" value="[^"]*"' | grep -oP 'value="[^"]*"' | cut -d'"' -f2) && echo "TOKEN=$TOKEN" && curl -sk -b /tmp/pma_jar.txt -c /tmp/pma_jar.txt -L -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" https://aqssat.co/phpmyadmin/index.php 2>&1 | head -5""",
    
    # 3. Check what MySQL password is for root
    "mysql -u root -pHAmAS12852 -e 'SELECT 1' 2>&1",
    
    # 4. Check MySQL root password
    "mysql -u root -e 'SELECT 1' 2>&1",
    
    # 5. Check if mysql login works without password
    "cat /root/.my.cnf 2>/dev/null",
    
    # 6. Test the SQL page with PHP directly (bypass login)
    """php -d display_errors=1 -r "
define('ROOT_PATH', '/usr/share/phpmyadmin/');
define('PHPMYADMIN', true);
\\\$_SERVER['REQUEST_URI'] = '/phpmyadmin/index.php?route=/database/sql&db=namaa_erp';
\\\$_SERVER['PHP_SELF'] = '/phpmyadmin/index.php';
\\\$_SERVER['SCRIPT_NAME'] = '/phpmyadmin/index.php';
\\\$_SERVER['SCRIPT_FILENAME'] = '/usr/share/phpmyadmin/index.php';
\\\$_SERVER['DOCUMENT_ROOT'] = '/usr/share/phpmyadmin';
\\\$_SERVER['HTTPS'] = 'on';
\\\$_SERVER['SERVER_PORT'] = '443';
\\\$_SERVER['REQUEST_SCHEME'] = 'https';
\\\$_GET['route'] = '/database/sql';
\\\$_GET['db'] = 'namaa_erp';
chdir('/usr/share/phpmyadmin');
require '/usr/share/phpmyadmin/index.php';
" 2>&1 | head -50""",
    
    # 7. Check if there's a specific SQL controller that might error
    "grep -rn 'database/sql' /usr/share/phpmyadmin/libraries/classes/Controllers/ 2>/dev/null | head -5",
    "ls /usr/share/phpmyadmin/libraries/classes/Controllers/Database/Sql* 2>/dev/null",
    
    # 8. Check the SQL controller for potential errors
    "cat /usr/share/phpmyadmin/libraries/classes/Controllers/Database/SqlController.php 2>/dev/null | head -50",
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
