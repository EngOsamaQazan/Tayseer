import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check route mapping
    "grep -rn '/database/sql' /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null | head -5",
    
    # 2. Check routing config
    "grep '/database/' /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null | head -10",
    
    # 3. Check Routing class
    "grep -rn 'class Routing' /usr/share/phpmyadmin/libraries/classes/ 2>/dev/null | head -5",
    
    # 4. Check if there's a Common.php that sets up the database context
    "grep -rn 'function boot\\|class Common' /usr/share/phpmyadmin/libraries/classes/Common.php 2>/dev/null | head -5",
    
    # 5. Check what Common.php does
    "head -100 /usr/share/phpmyadmin/libraries/classes/Common.php 2>/dev/null",
    
    # 6. Check if there's a setCookie call that might fail silently
    "grep -n 'setCookie\\|setcookie\\|removeCookie' /usr/share/phpmyadmin/libraries/classes/Config.php 2>/dev/null | head -20",
    
    # 7. Check the setCookie function for PHP 8.5 compatibility
    "sed -n '/function setCookie/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Config.php 2>/dev/null",
    
    # 8. Check the Header.php fix we applied earlier is still in place
    "grep -n 'rootPath\\|is_https' /usr/share/phpmyadmin/libraries/classes/Header.php 2>/dev/null | head -5",
    
    # 9. Check if ob_start is called
    "grep -rn 'ob_start' /usr/share/phpmyadmin/libraries/ 2>/dev/null | head -5",
    
    # 10. Check the OutputBuffering class
    "cat /usr/share/phpmyadmin/libraries/classes/OutputBuffering.php 2>/dev/null",
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
