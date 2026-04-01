import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check root password hash
    "mysql -u root -e \"SELECT user, host, authentication_string FROM mysql.user WHERE user='root';\" 2>&1",
    
    # 2. Check if root actually has a password set
    "mysql -u root -e \"SELECT user, host, LENGTH(authentication_string) as pwd_len FROM mysql.user WHERE user='root';\" 2>&1",
    
    # 3. Test PHP mysqli with the password
    """php -r 'echo mysqli_connect("localhost", "root", "HAmAS12852") ? "OK" : "FAIL: ".mysqli_connect_error();' 2>&1""",
    
    # 4. Test without password
    """php -r 'echo mysqli_connect("localhost", "root", "") ? "OK" : "FAIL: ".mysqli_connect_error();' 2>&1""",
    
    # 5. Test with osama user
    "mysql -u root -e \"SELECT user, host FROM mysql.user WHERE user='osama';\" 2>&1",
    
    # 6. Check osama password
    """php -r 'echo @mysqli_connect("localhost", "osama", "HAmAS12852") ? "OK" : "FAIL: ".mysqli_connect_error();' 2>&1""",
    
    # 7. The real issue - the SQL page is blank after login
    # Let me check if the issue is that phpMyAdmin loads SQL page via AJAX
    # and the AJAX response fails
    "grep -rn 'ajax\\|isAjax\\|AJAX' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php 2>/dev/null | head -10",
    
    # 8. Check the HTTP request header that phpMyAdmin sends for AJAX
    "grep -rn 'X-Requested-With\\|isAjax' /usr/share/phpmyadmin/libraries/classes/ 2>/dev/null | head -10",
    
    # 9. Test if SQL page works with AJAX header
    """curl -sk -H 'X-Requested-With: XMLHttpRequest' 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp&ajax_request=1' 2>&1 | head -20""",
    
    # 10. Check what error is actually returned  
    """curl -sk -H 'X-Requested-With: XMLHttpRequest' 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp&ajax_request=1' 2>&1 | python3 -m json.tool 2>/dev/null | head -20""",
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
