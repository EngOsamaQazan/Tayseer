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

# Get the full error for notification/index
out = run("grep -B20 'notification/views/notification/index.php' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log | tail -50")
print("=== FULL ERROR CONTEXT ===")
print(out)

print("\n" + "="*80)

# Also get the actual error message (usually starts with a timestamp)
out = run("grep -B30 'notification/index' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log | grep -E '(Exception|Error|error|exception|Class|class not found|Undefined|undefined|Column|column)' | tail -20")
print("=== ERROR MESSAGES ===")
print(out)

print("\n" + "="*80)

# Get the last occurrence of notification/index error with full context
out = run("tac /var/www/jadal.aqssat.co/backend/runtime/logs/app.log | grep -m1 -B50 'notification/index' | tac")
print("=== LATEST NOTIFICATION ERROR (FULL) ===")
print(out[-5000:] if len(out) > 5000 else out)

ssh.close()
