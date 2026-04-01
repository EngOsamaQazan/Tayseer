import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    return out

# First find the database name from config
out = run("grep -r 'dbname' /var/www/jadal.aqssat.co/common/config/main-local.php 2>/dev/null")
print("=== DB CONFIG ===")
print(out)

# Also check environments
out = run("grep -r 'dbname' /var/www/jadal.aqssat.co/environments/prod/common/config/main-local.php 2>/dev/null")
print(out)

ssh.close()
