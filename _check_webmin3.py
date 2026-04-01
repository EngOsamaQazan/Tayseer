import paramiko
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Check my.cnf - it's read by DBI via mariadb_read_default_file
    'cat /etc/mysql/my.cnf',
    'cat /etc/mysql/mariadb.conf.d/50-client.cnf 2>/dev/null',
    'cat /etc/mysql/mariadb.conf.d/50-mysql-clients.cnf 2>/dev/null',
    'cat /etc/mysql/mariadb.conf.d/50-mariadb-clients.cnf 2>/dev/null',
    'cat /etc/mysql/mariadb.conf.d/50-server.cnf 2>/dev/null',
    'cat /etc/mysql/conf.d/mysql.cnf 2>/dev/null',
    # Direct Perl test without read_default_file
    "perl -e 'use DBI; my $dbh = DBI->connect(\"DBI:MariaDB:database=mysql;host=localhost;mariadb_socket=/run/mysqld/mysqld.sock\", \"root\", \"\", {PrintError=>0}); if ($dbh) { print \"CONNECTED OK\\n\"; $dbh->disconnect; } else { print \"FAILED: \".DBI->errstr.\"\\n\"; }'",
    # Direct Perl test WITH read_default_file (this is what Webmin does)
    "perl -e 'use DBI; my $dbh = DBI->connect(\"DBI:MariaDB:database=mysql;host=localhost;mariadb_socket=/run/mysqld/mysqld.sock;mariadb_read_default_file=/etc/mysql/my.cnf\", \"root\", \"\", {PrintError=>0}); if ($dbh) { print \"CONNECTED OK\\n\"; $dbh->disconnect; } else { print \"FAILED: \".DBI->errstr.\"\\n\"; }'",
]
for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    print(f'=== {cmd[:70]} ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
ssh.close()
