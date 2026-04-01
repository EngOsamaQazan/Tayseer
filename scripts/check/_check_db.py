import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[NONE]')
    print()

# Get DB name from Yii2 config
run("JADAL DB config",
    "grep -n 'dsn\\|dbname' /var/www/jadal.aqssat.co/common/config/main-local.php 2>/dev/null")

# Get DB name from .env if exists
run("JADAL .env",
    "cat /var/www/jadal.aqssat.co/.env 2>/dev/null | grep -i 'db\\|database' | head -5")

# List databases
run("All databases",
    "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null")

ssh.close()
