import paramiko, sys, time
sys.stdout.reconfigure(encoding='utf-8')

OLD = {'host': '54.38.236.112', 'user': 'root', 'pass': 'Hussain@1986'}
NEW = {'host': '31.220.82.115', 'user': 'root', 'pass': 'HAmAS12852'}

def connect(info):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(info['host'], username=info['user'], password=info['pass'], timeout=15)
    return ssh

def run(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace').strip()

for label, info, sid in [('OLD', OLD, 1), ('NEW', NEW, 2)]:
    print(f'=== {label} SERVER ===')
    ssh = connect(info)
    sftp = ssh.open_sftp()

    config = f"""[mysqld]
server-id = {sid}
log_bin = mysql-bin
binlog_format = ROW
log_slave_updates = 1
expire_logs_days = 7

# Sequential IDs (no gaps)
auto_increment_increment = 1
auto_increment_offset = 1

# Only replicate our databases
binlog_do_db = namaa_jadal
binlog_do_db = namaa_erp
replicate_do_db = namaa_jadal
replicate_do_db = namaa_erp

slave_type_conversions = ALL_LOSSY,ALL_NON_LOSSY
"""
    with sftp.open('/etc/mysql/mariadb.conf.d/99-replication.cnf', 'w') as f:
        f.write(config)
    print(f'  Config updated (auto_increment = 1)')

    # Restart MariaDB
    run(ssh, 'systemctl restart mariadb')
    time.sleep(3)
    status = run(ssh, 'systemctl is-active mariadb')
    print(f'  MariaDB: {status}')

    # Verify
    out = run(ssh, "mysql -u root -N -e \"SHOW VARIABLES LIKE 'auto_increment_increment';\"")
    print(f'  {out}')
    out = run(ssh, "mysql -u root -N -e \"SHOW VARIABLES LIKE 'auto_increment_offset';\"")
    print(f'  {out}')

    sftp.close()
    ssh.close()
    time.sleep(2)

# Verify replication still working
print('\n=== Verify replication ===')
ssh_new = connect(NEW)
ssh_old = connect(OLD)

for label, ssh in [('NEW', ssh_new), ('OLD', ssh_old)]:
    stdin, stdout, _ = ssh.exec_command("mysql -u root -e 'SHOW SLAVE STATUS\\G'", timeout=15)
    status = stdout.read().decode('utf-8', errors='replace')
    for line in status.split('\n'):
        line = line.strip()
        if any(k in line for k in ['Slave_IO_Running:', 'Slave_SQL_Running:', 'Seconds_Behind']):
            if 'State' not in line:
                print(f'  {label}: {line}')

ssh_new.close()
ssh_old.close()
print('\n=== DONE ===')
