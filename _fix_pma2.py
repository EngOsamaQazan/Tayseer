import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Show last 10 lines to see where to add
    'tail -10 /usr/share/phpmyadmin/config.inc.php',
    # Add the setting before the closing PHP tag or at end of file
    """grep -q 'NavigationTreeDbSeparator' /usr/share/phpmyadmin/config.inc.php || echo "\\n\\$cfg['NavigationTreeDbSeparator'] = false;" >> /usr/share/phpmyadmin/config.inc.php""",
    # Verify it was added
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
