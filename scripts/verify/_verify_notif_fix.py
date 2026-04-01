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

# Test the notification page with curl (check HTTP status)
out = run("curl -s -o /dev/null -w '%{http_code}' -b 'advanced-backend=test' https://jadal.aqssat.co/notification/index -k 2>&1")
print("=== HTTP STATUS CODE ===")
print(out)

# Check for any new errors in the log
out = run("tail -5 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log | head -3")
print("\n=== LATEST LOG ENTRIES ===")
print(out)

# Check if the kartik bs5dropdown class is autoloadable
out = run("cd /var/www/jadal.aqssat.co && php -r \"require 'vendor/autoload.php'; echo class_exists('kartik\\\\bs5dropdown\\\\ButtonDropdown') ? 'CLASS EXISTS' : 'CLASS NOT FOUND';\" 2>&1")
print("\n=== CLASS CHECK ===")
print(out)

ssh.close()
print("\nDone!")
