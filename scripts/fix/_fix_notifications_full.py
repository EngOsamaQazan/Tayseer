import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=60):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

# Step 1: Find all database names
print("=" * 60)
print("STEP 1: Finding database configs for all 3 systems")
print("=" * 60)

for site in ['jadal', 'namaa', 'watar']:
    out, _ = run(f"grep 'dbname' /var/www/{site}.aqssat.co/common/config/main-local.php 2>/dev/null")
    print(f"\n{site}: {out.strip()}")

ssh.close()
