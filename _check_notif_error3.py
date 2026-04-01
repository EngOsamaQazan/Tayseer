import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=15):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

# Get recent notification-related errors from app.log
out, _ = run("grep -A5 'notification' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log | tail -100")
print("=== NOTIFICATION ERRORS ===")
print(out[-5000:] if len(out) > 5000 else out)

print("\n" + "="*80)

# Get the most recent errors (last 100 lines)
out, _ = run("tail -100 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log")
print("=== LAST 100 LINES ===")
print(out[-5000:] if len(out) > 5000 else out)

print("\n" + "="*80)

# Check index.php view on server
out, _ = run("cat /var/www/jadal.aqssat.co/backend/modules/notification/views/notification/index.php")
print("=== INDEX VIEW ON SERVER ===")
print(out)

ssh.close()
