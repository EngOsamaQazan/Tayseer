import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check the problematic line in Config.php
    "sed -n '970,990p' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 2. Check the setCookie function completely
    "grep -n 'function setCookie' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 3. Get the full setCookie function
    "sed -n '/function setCookie/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 4. Check phpMyAdmin version
    "cat /usr/share/phpmyadmin/RELEASE-DATE-*",
    
    # 5. Check Common.php around line 214
    "sed -n '200,230p' /usr/share/phpmyadmin/libraries/classes/Common.php",
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
