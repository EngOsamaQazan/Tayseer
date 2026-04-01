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
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[empty]')
    print()

proj = '/var/www/jadal.aqssat.co'

# Read the debug log, filter for user 94 (yara) or system-settings
run('All log entries with system-settings or user=94',
    f'grep -E "system-settings|image-manager|user=94" {proj}/backend/runtime/logs/route_debug.log 2>&1')

run('Last 30 lines of debug log',
    f'tail -30 {proj}/backend/runtime/logs/route_debug.log 2>&1')

ssh.close()
