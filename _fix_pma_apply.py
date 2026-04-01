import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Backup Header.php
    "cp /usr/share/phpmyadmin/libraries/classes/Header.php /usr/share/phpmyadmin/libraries/classes/Header.php.bak",
    
    # 2. Fix rootPath bug: change && to ternary operator
    """sed -i "s/'rootPath' => \\$GLOBALS\\['config'\\] !== null && \\$GLOBALS\\['config'\\]->getRootPath()/'rootPath' => \\$GLOBALS['config'] !== null ? \\$GLOBALS['config']->getRootPath() : ''/" /usr/share/phpmyadmin/libraries/classes/Header.php""",
    
    # 3. Also fix is_https with the same pattern (same bug on the line above)
    """sed -i "s/'is_https' => \\$GLOBALS\\['config'\\] !== null && \\$GLOBALS\\['config'\\]->isHttps()/'is_https' => \\$GLOBALS['config'] !== null ? \\$GLOBALS['config']->isHttps() : false/" /usr/share/phpmyadmin/libraries/classes/Header.php""",
    
    # 4. Verify the fix
    "grep -n 'rootPath\\|is_https' /usr/share/phpmyadmin/libraries/classes/Header.php",
    
    # 5. Clear OPcache
    """php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; } else { echo 'No OPcache'; }" 2>&1""",
    
    # 6. Restart Apache to clear any cached PHP
    "systemctl restart apache2 2>&1; echo 'Apache restarted'",
    
    # 7. Test the fix - check if rootPath is now a string
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -o 'rootPath:[^,]*'",
    
    # 8. Check if is_https is also fixed
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -o 'is_https:[^,]*'",
    
    # 9. Quick test the full page response
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | wc -c",
    
    # 10. Clean up debug file
    "rm -f /usr/share/phpmyadmin/test_debug.php 2>/dev/null; echo 'cleaned'",
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
