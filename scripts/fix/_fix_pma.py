import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Find phpMyAdmin config
    'find / -name "config.inc.php" -path "*/phpmyadmin/*" 2>/dev/null',
    # Check if setting already exists
    'grep -n "NavigationTreeDbSeparator\\|NavigationTreeEnableGrouping" /etc/phpmyadmin/config.inc.php 2>/dev/null',
    'grep -n "NavigationTreeDbSeparator\\|NavigationTreeEnableGrouping" /usr/share/phpmyadmin/config.inc.php 2>/dev/null',
]
for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'=== {cmd[:70]} ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
ssh.close()
