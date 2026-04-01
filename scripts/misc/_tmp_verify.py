import paramiko, sys, time
sys.stdout.reconfigure(encoding='utf-8')

time.sleep(5)

OLD = {'host': '54.38.236.112', 'user': 'root', 'pass': 'Hussain@1986'}
NEW = {'host': '31.220.82.115', 'user': 'root', 'pass': 'HAmAS12852'}

def connect(info):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(info['host'], username=info['user'], password=info['pass'], timeout=15)
    return ssh

for label, info in [('NEW', NEW), ('OLD', OLD)]:
    ssh = connect(info)
    stdin, stdout, _ = ssh.exec_command("mysql -u root -e 'SHOW SLAVE STATUS\\G'", timeout=15)
    status = stdout.read().decode('utf-8', errors='replace')
    print(f'{label}:')
    for line in status.split('\n'):
        line = line.strip()
        if any(k in line for k in ['Slave_IO_Running:', 'Slave_SQL_Running:', 'Seconds_Behind', 'Last_IO_Error', 'Last_SQL_Error']):
            if 'State' not in line:
                print(f'  {line}')
    ssh.close()

print('\n=== DONE ===')
