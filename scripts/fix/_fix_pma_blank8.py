import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Search for cfs-style in phpMyAdmin source
    "grep -rn 'cfs-style' /usr/share/phpmyadmin/ 2>/dev/null",
    
    # 2. Search for display: none in theme headers
    "grep -rn 'display.*none\\|display: none' /usr/share/phpmyadmin/templates/ 2>/dev/null | head -10",
    
    # 3. Check if it's from a theme
    "grep -rn 'cfs-style\\|display.*none' /usr/share/phpmyadmin/themes/ 2>/dev/null | head -10",
    
    # 4. Check the header template
    "find /usr/share/phpmyadmin/templates -name 'header*' -o -name 'head*' 2>/dev/null",
    
    # 5. Check header.twig
    "cat /usr/share/phpmyadmin/templates/header.twig 2>/dev/null | head -30",
    
    # 6. Check if the login form has js-show class (the form has 'hide js-show')
    "grep -rn 'js-show\\|hide.*js-show' /usr/share/phpmyadmin/templates/ 2>/dev/null | head -5",
    
    # 7. Check for the script that removes cfs-style
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -i 'cfs-style\\|display'",
    
    # 8. Check DNS resolution on the server
    "dig +short aqssat.co 2>/dev/null; echo '---'; curl -s ifconfig.me 2>/dev/null",
    
    # 9. Check the full source looking for the script that shows the page
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -A2 'cfs-style'",
    
    # 10. Look for any script that modifies cfs-style
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -i 'getElementById\\|cfs'",
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
