import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Remove the broken line
    r"sed -i '/\\\\n\$cfg/d' /usr/share/phpmyadmin/config.inc.php",
    # Add properly with printf
    """printf "\n\$cfg['NavigationTreeDbSeparator'] = false;\n" >> /usr/share/phpmyadmin/config.inc.php""",
    # Verify
    'tail -5 /usr/share/phpmyadmin/config.inc.php',
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
