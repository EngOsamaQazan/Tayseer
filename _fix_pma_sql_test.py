import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Test if the original PHP error still occurs
    """cd /usr/share/phpmyadmin && php -d display_errors=1 -r 'require "index.php";' 2>&1 | head -10""",
    
    # 2. Properly login via curl with all cookies
    """curl -sk -c /tmp/pma_full.txt -L 'https://aqssat.co/phpmyadmin/index.php' > /tmp/pma_login.html 2>&1 && TOKEN=$(grep -oP 'name="token" value="\\K[^"]+' /tmp/pma_login.html | head -1) && echo "Token: $TOKEN" && curl -sk -b /tmp/pma_full.txt -c /tmp/pma_full.txt -L -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" 'https://aqssat.co/phpmyadmin/index.php' > /tmp/pma_after_login.html 2>&1 && head -5 /tmp/pma_after_login.html && echo "---SIZE---" && wc -c /tmp/pma_after_login.html && echo "---LOGGED_IN---" && grep -o 'logged_in:[a-z]*' /tmp/pma_after_login.html""",
    
    # 3. After login, try to access the SQL page
    """curl -sk -b /tmp/pma_full.txt -c /tmp/pma_full.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 > /tmp/pma_sql.html && head -5 /tmp/pma_sql.html && echo "---SIZE---" && wc -c /tmp/pma_sql.html && echo "---LOGGED_IN---" && grep -o 'logged_in:[a-z]*' /tmp/pma_sql.html && echo "---SQL_FORM---" && grep -c 'sqlquery\\|SQL_query\\|codemirror' /tmp/pma_sql.html""",
    
    # 4. Check if the SQL page has any error message
    """grep -i 'error\\|fatal\\|warning\\|exception' /tmp/pma_sql.html 2>/dev/null | head -5""",
    
    # 5. Check the JS mismatch detection code
    "sed -n '4005,4030p' /usr/share/phpmyadmin/js/dist/functions.js 2>/dev/null",
    
    # 6. Clean up test files
    "rm -f /var/www/html/test_ssl.php 2>/dev/null; echo 'cleaned'",
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
