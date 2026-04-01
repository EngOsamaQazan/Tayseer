import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Set nodbi=1 to use command-line tools instead of Perl DBI
    'sed -i "s/^nodbi=0/nodbi=1/" /etc/webmin/mysql/config',
    # Remove host - not needed for socket connection
    'sed -i "/^host=/d" /etc/webmin/mysql/config',
    # Verify final config
    'cat /etc/webmin/mysql/config',
    # Restart Webmin
    'systemctl restart webmin',
    'sleep 2 && systemctl is-active webmin',
]
for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'=== {cmd[:60]} ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
ssh.close()
