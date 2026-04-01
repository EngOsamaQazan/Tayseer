import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Show all lines with /sql in routes.php
    "grep -n 'sql\\|Sql' /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null",
    
    # 2. Show the full /database group
    "grep -n 'database' /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null",
    
    # 3. Show lines 60-120 of routes.php (where database group likely is)
    "sed -n '60,120p' /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null",
    
    # 4. Check Routing::callControllerForCurrentRoute
    "grep -n 'function callControllerForCurrentRoute\\|Dispatcher::NOT_FOUND\\|Dispatcher::METHOD_NOT_ALLOWED' /usr/share/phpmyadmin/libraries/classes/Routing.php 2>/dev/null",
    
    # 5. Check what happens when route is found
    "sed -n '/callControllerForCurrentRoute/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Routing.php 2>/dev/null | head -50",
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
