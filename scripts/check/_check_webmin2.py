import paramiko
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Look at the DBI connection logic
    'sed -n "340,400p" /usr/share/webmin/mysql/mysql-lib.pl',
    # Look at dbi_driver_info function
    'grep -A 30 "sub dbi_driver_info" /usr/share/webmin/mysql/mysql-lib.pl',
    # Check what version file says
    'cat /etc/webmin/mysql/version 2>/dev/null',
    # Check if there's a module.info.override with port
    'cat /etc/webmin/mysql/module.info.override 2>/dev/null',
    # Try connecting with perl DBI directly to see the actual error
    'perl -MDBI -e "my $d = DBI->connect(q{DBI:MariaDB:database=mysql;host=localhost;mysql_socket=/run/mysqld/mysqld.sock}, q{root}, q{}, {PrintError=>1}) or die DBI->errstr" 2>&1',
]
for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    print(f'=== CMD: {cmd[:60]}... ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
ssh.close()
