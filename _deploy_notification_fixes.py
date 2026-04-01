import paramiko
import sys
import os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(cmd, timeout=60):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

LOCAL_BASE = r'c:\Users\PC\Desktop\Tayseer'

FILES_TO_DEPLOY = [
    'common/components/notificationComponent.php',
    'api/helpers/NotificationsHandler.php',
]

SITES = ['jadal', 'namaa', 'watar']

for f in FILES_TO_DEPLOY:
    local_path = os.path.join(LOCAL_BASE, f.replace('/', '\\'))
    print(f"\n{'='*60}")
    print(f"  Deploying: {f}")
    print(f"{'='*60}")
    
    for site in SITES:
        remote_path = f'/var/www/{site}.aqssat.co/{f}'
        try:
            # Backup first
            run(f"cp {remote_path} {remote_path}.bak.$(date +%Y%m%d%H%M%S) 2>/dev/null")
            
            # Upload
            sftp.put(local_path, remote_path)
            
            # Verify
            out, _ = run(f"php -l {remote_path} 2>&1")
            if 'No syntax errors' in out:
                print(f"  [{site}] OK - deployed and syntax valid")
            else:
                print(f"  [{site}] WARNING - {out.strip()}")
        except Exception as e:
            print(f"  [{site}] ERROR - {e}")

# Restart Apache
print(f"\n{'='*60}")
print("  Restarting Apache")
print(f"{'='*60}")
out, _ = run("service apache2 graceful 2>&1")
print(f"  {out.strip()}")

# Quick verification: check if notification page returns non-500
print(f"\n{'='*60}")
print("  Verification")
print(f"{'='*60}")
for site in SITES:
    out, _ = run(f"curl -s -o /dev/null -w '%{{http_code}}' https://{site}.aqssat.co/notification/index -k 2>&1")
    status = out.strip().replace("'", "")
    expected = "302"
    result = "OK" if status == expected else f"CHECK (got {status})"
    print(f"  [{site}] HTTP {status} - {result}")

sftp.close()
ssh.close()
print("\nAll deployments complete!")
