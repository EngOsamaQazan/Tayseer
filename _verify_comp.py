import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    return stdout.read().decode('utf-8', errors='replace')

for site in ['jadal', 'namaa', 'watar']:
    path = f"/var/www/{site}.aqssat.co/common/components/notificationComponent.php"
    print(f"\n=== {site} ===")
    out = run(f"cat {path}")
    print(out[:2000])

ssh.close()
