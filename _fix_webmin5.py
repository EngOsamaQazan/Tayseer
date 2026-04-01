import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    'systemctl status webmin --no-pager 2>&1 | head -5',
    # Also set pass to empty since nopwd=0 might need it
    'grep -c "^pass=" /etc/webmin/mysql/config || echo "pass field missing"',
    # Add pass= empty if not there (root with no password)
    'grep -q "^pass=" /etc/webmin/mysql/config || echo "pass=" >> /etc/webmin/mysql/config',
    'cat /etc/webmin/mysql/config',
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
