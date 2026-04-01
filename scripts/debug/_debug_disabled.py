import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Show lines 300-340 of ResponseRenderer.php (around the isDisabled check at line 315)
    "sed -n '300,350p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php",
    
    # 2. Show the full response() method (around line 408)
    "sed -n '400,440p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php",
    
    # 3. Show lines 225-240 (where disable is called)
    "sed -n '220,240p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php",
    
    # 4. Look at who calls disable()
    "grep -rn 'disable()\|->disable\|isDisabled' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php",
    
    # 5. Now the key - look at the Header.php getDisplay() which might be the issue
    "sed -n '312,400p' /usr/share/phpmyadmin/libraries/classes/Header.php",
    
    # 6. Footer getDisplay
    "sed -n '260,320p' /usr/share/phpmyadmin/libraries/classes/Footer.php",
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
