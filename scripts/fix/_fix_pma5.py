import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Maybe there's another config file that overrides, check all
    "find / -name 'config.inc.php' 2>/dev/null",
    "find / -name 'config.user.inc.php' 2>/dev/null",
    # Also add NavigationTreeEnableGrouping = false
    """printf "\\$cfg['NavigationTreeEnableGrouping'] = false;\\n" >> /usr/share/phpmyadmin/config.inc.php""",
    # Check if there's a config.default.php with the separator
    "grep -n 'NavigationTreeDbSeparator' /usr/share/phpmyadmin/libraries/config.default.php 2>/dev/null",
    # Check phpMyAdmin version
    "grep -i version /usr/share/phpmyadmin/libraries/classes/Version.php 2>/dev/null | head -3",
    "dpkg -l | grep phpmyadmin",
    # Final config
    'cat /usr/share/phpmyadmin/config.inc.php',
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
