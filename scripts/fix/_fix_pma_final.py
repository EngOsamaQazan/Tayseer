import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check messages.php (it's a PHP file that generates JS, might have errors)
    "curl -sk 'https://aqssat.co/phpmyadmin/js/messages.php?l=en&v=5.2.3&lang=en' 2>&1 | head -5",
    
    # 2. Check where rootPath is set in the PHP/JS
    "grep -rn 'rootPath' /usr/share/phpmyadmin/libraries/classes/ 2>/dev/null | head -10",
    "grep -rn 'rootPath' /usr/share/phpmyadmin/templates/ 2>/dev/null | head -10",
    
    # 3. Check the footer template where CommonParams is set
    "grep -rn 'CommonParams' /usr/share/phpmyadmin/templates/ 2>/dev/null | head -10",
    "grep -rn 'CommonParams' /usr/share/phpmyadmin/libraries/classes/ 2>/dev/null | head -10",
    
    # 4. Find where rootPath is set in PHP
    "grep -rn 'rootPath' /usr/share/phpmyadmin/libraries/classes/Footer.php 2>/dev/null",
    "grep -rn 'rootPath' /usr/share/phpmyadmin/libraries/classes/Header.php 2>/dev/null",
    
    # 5. Check the Footer.php for the CommonParams setup
    "grep -A5 -B5 'rootPath' /usr/share/phpmyadmin/libraries/classes/Footer.php 2>/dev/null",
    
    # 6. Check Header.php for rootPath
    "grep -A5 -B5 'rootPath' /usr/share/phpmyadmin/libraries/classes/Header.php 2>/dev/null",
    
    # 7. Actually just test from the browser perspective - the full inline JS section
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -A3 'rootPath'",
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
