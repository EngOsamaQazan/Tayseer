import paramiko
import sys
import time
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[OK]')
    print()

proj = '/var/www/jadal.aqssat.co'

# Upload debug version
local_path = r'c:\Users\PC\Desktop\Tayseer\backend\components\RouteAccessBehavior.php'
remote_path = f'{proj}/backend/components/RouteAccessBehavior.php'
sftp.put(local_path, remote_path)
print('Uploaded RouteAccessBehavior.php with debug logging')
print()

# Syntax check
run('Syntax check', f'php -l {remote_path} 2>&1')

# Clear debug log
run('Clear old debug log', f'rm -f {proj}/backend/runtime/logs/route_debug.log')

# Restart Apache to clear OPcache
run('Restart Apache', 'systemctl restart apache2 2>&1')

print('=== Now ask yara to access /site/system-settings ===')
print('=== Then run the read_debug script ===')

# Also try curl to trigger it (won't be authenticated but will show the flow)
time.sleep(2)
run('Trigger via curl (unauthenticated)', f'curl -sk -o /dev/null -w "HTTP %{{http_code}}" https://jadal.aqssat.co/site/system-settings')

time.sleep(1)
run('Check debug log', f'cat {proj}/backend/runtime/logs/route_debug.log 2>&1')

sftp.close()
ssh.close()
print('Done!')
