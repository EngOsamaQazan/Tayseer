import paramiko
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Make sure there's no stale port anywhere
    'grep port /etc/webmin/mysql/config',
    # Restart Webmin to clear cached config
    'systemctl restart webmin',
    'systemctl status webmin --no-pager | head -10',
]
for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    print(f'=== {cmd} ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
ssh.close()
