import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Show the cookie jar to see what's being set
    "cat /tmp/pma_full.txt 2>/dev/null",
    
    # 2. Login with verbose headers to see Set-Cookie
    """curl -sk -v -c /tmp/pma2.txt 'https://aqssat.co/phpmyadmin/index.php' 2>&1 | grep -i 'set-cookie'""",
    
    # 3. Get token from login page HTML
    """TOKEN=$(curl -sk -c /tmp/pma3.txt 'https://aqssat.co/phpmyadmin/index.php' 2>/dev/null | grep -oP 'name="token" value="\\K[^"]+' | head -1) && echo "Token: $TOKEN" """,
    
    # 4. Login and show ALL response headers
    """TOKEN=$(curl -sk -c /tmp/pma3.txt 'https://aqssat.co/phpmyadmin/index.php' 2>/dev/null | grep -oP 'name="token" value="\\K[^"]+' | head -1) && curl -sk -v -b /tmp/pma3.txt -c /tmp/pma3.txt -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" 'https://aqssat.co/phpmyadmin/index.php' 2>&1 | grep -iE 'set-cookie|location|http/' | head -20""",
    
    # 5. Show final cookie jar after login
    "cat /tmp/pma3.txt 2>/dev/null",
    
    # 6. Check the actual PHP setcookie error in PHP 8.5
    # removeCookie uses old-style setcookie - test if it works in PHP 8.5
    """php -d display_errors=1 -r 'var_dump(setcookie("test","",time()-3600,"/phpmyadmin/","",true));' 2>&1""",
    
    # 7. Check if setCookie with SameSite appended to path works in PHP 8.5
    """php -d display_errors=1 -r 'var_dump(setcookie("test","val",0,"/phpmyadmin/; SameSite=Strict","",true));' 2>&1""",
    
    # 8. Check PHP 8.5 setcookie with empty domain
    """php -d display_errors=1 -r 'var_dump(setcookie("test","val",time()-3600,"/phpmyadmin/","",true));' 2>&1""",
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
