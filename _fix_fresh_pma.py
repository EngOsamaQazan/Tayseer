import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Apply the Header.php fix for PHP 8.5 (rootPath and is_https)
    """sed -i "s/'is_https' => \\\$GLOBALS\\['config'\\] !== null && \\\$GLOBALS\\['config'\\]->isHttps()/'is_https' => \\\$GLOBALS['config'] !== null ? \\\$GLOBALS['config']->isHttps() : false/" /usr/share/phpmyadmin/libraries/classes/Header.php""",
    """sed -i "s/'rootPath' => \\\$GLOBALS\\['config'\\] !== null && \\\$GLOBALS\\['config'\\]->getRootPath()/'rootPath' => \\\$GLOBALS['config'] !== null ? \\\$GLOBALS['config']->getRootPath() : ''/" /usr/share/phpmyadmin/libraries/classes/Header.php""",
    
    # 2. Verify the fix
    "grep -n 'rootPath\\|is_https' /usr/share/phpmyadmin/libraries/classes/Header.php | head -5",
    
    # 3. Clear OPcache and restart
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 4. Test home page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php' 2>&1 | grep -o 'rootPath:\"[^\"]*\"\\|is_https:[a-z]*'",
    
    # 5. Test SQL page (should show login since no auth)
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | grep -o 'Welcome to phpMyAdmin\\|rootPath:\"[^\"]*\"\\|logged_in:[a-z]*'",
    
    # 6. Test login with root
    """rm -f /tmp/pma_final_test.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_final_test.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\\K[^"]+' | head -1)
LOGIN=$(curl -sk -b /tmp/pma_final_test.txt -c /tmp/pma_final_test.txt -L -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" 'https://aqssat.co/phpmyadmin/index.php' 2>/dev/null)
echo "Logged in: $(echo "$LOGIN" | grep -o 'logged_in:[a-z]*')"
""",
    
    # 7. After login, test SQL page
    """curl -sk -b /tmp/pma_final_test.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | grep -o 'logged_in:[a-z]*\\|codemirror\\|sqlquery' | head -5""",
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
