import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check getRootPath function
    "grep -n 'function getRootPath' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 2. Get the full getRootPath function
    "sed -n '/function getRootPath/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 3. Test what getRootPath returns
    """php -r "
        define('ROOT_PATH', '/usr/share/phpmyadmin/');
        define('PHPMYADMIN', true);
        require '/usr/share/phpmyadmin/libraries/classes/Config.php';
    " 2>&1 | head -20""",
    
    # 4. Check the $PMA_PHP_SELF and cookie_path settings
    "grep -n 'PMA_PHP_SELF\\|cookie_path\\|getRootPath\\|RootPath' /usr/share/phpmyadmin/libraries/classes/Config.php | head -20",
    
    # 5. Simulate a quick test
    """php -r "
        \\\$path = '/phpmyadmin/';
        var_dump(\\\$path);
        setcookie('test', 'val', ['path' => \\\$path, 'expires' => 0]);
        echo 'OK';
    " 2>&1""",
    
    # 6. Check if there are newer versions of phpMyAdmin available
    "apt-cache policy phpmyadmin 2>/dev/null",
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
