import paramiko
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    'cat /etc/webmin/mysql/config',
    'dpkg -l | grep -i libdbd',
    'dpkg -l | grep -i perl.*mysql',
    'dpkg -l | grep -i perl.*mariadb',
    'grep -n "port" /usr/share/webmin/mysql/mysql-lib.pl 2>/dev/null | head -30',
    'head -80 /usr/share/webmin/mysql/mysql-lib.pl 2>/dev/null',
    'ls /etc/webmin/mysql/',
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
