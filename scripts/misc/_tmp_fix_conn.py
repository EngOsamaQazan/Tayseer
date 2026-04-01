import paramiko, sys, time
sys.stdout.reconfigure(encoding='utf-8')

NEW = {'host': '31.220.82.115', 'user': 'root', 'pass': 'HAmAS12852'}
OLD = {'host': '54.38.236.112', 'user': 'root', 'pass': 'Hussain@1986'}

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(NEW['host'], username=NEW['user'], password=NEW['pass'], timeout=15)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=15)
    return stdout.read().decode('utf-8', errors='replace').strip()

# Check
print('bind_address:', run("mysql -u root -N -e \"SHOW VARIABLES LIKE 'bind_address';\""))
print('port 3306:', run('ss -tlnp | grep 3306'))
print('ufw status:', run('ufw status | grep 3306'))

# Make sure bind-address is correct
print('\nChecking 50-server.cnf:')
print(run("grep bind-address /etc/mysql/mariadb.conf.d/50-server.cnf"))

# Fix if needed
current = run("grep bind-address /etc/mysql/mariadb.conf.d/50-server.cnf")
if '127.0.0.1' in current:
    print('  Fixing bind-address...')
    run("sed -i 's/bind-address.*=.*127.0.0.1/bind-address = 0.0.0.0/' /etc/mysql/mariadb.conf.d/50-server.cnf")
    run('systemctl restart mariadb')
    time.sleep(3)
    print('  MariaDB:', run('systemctl is-active mariadb'))
    print('  bind_address:', run("mysql -u root -N -e \"SHOW VARIABLES LIKE 'bind_address';\""))

# Make sure UFW rule exists
print('\nUFW 3306 rule:', run('ufw status | grep 3306'))
if '3306' not in run('ufw status'):
    run('ufw allow from 54.38.236.112 to any port 3306 proto tcp')
    print('  Added UFW rule')

ssh.close()

# Test from OLD
time.sleep(3)
ssh2 = paramiko.SSHClient()
ssh2.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh2.connect(OLD['host'], username=OLD['user'], password=OLD['pass'], timeout=15)

stdin, stdout, _ = ssh2.exec_command("mysql -h 31.220.82.115 -u repl_user -p'ReplSync2026!' -e 'SELECT 1;' 2>&1", timeout=15)
print('\nOLD -> NEW test:', stdout.read().decode().strip())

# Check slave status
time.sleep(5)
stdin, stdout, _ = ssh2.exec_command("mysql -u root -e 'SHOW SLAVE STATUS\\G'", timeout=15)
status = stdout.read().decode('utf-8', errors='replace')
for line in status.split('\n'):
    line = line.strip()
    if any(k in line for k in ['Slave_IO_Running:', 'Slave_SQL_Running:', 'Seconds_Behind', 'Last_IO_Error']):
        if 'State' not in line:
            print(f'OLD: {line}')

ssh2.close()
print('\n=== DONE ===')
