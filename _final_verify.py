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

projects = ['jadal.aqssat.co', 'namaa.aqssat.co', 'watar.aqssat.co']
for proj in projects:
    modals_path = f'/var/www/{proj}/backend/modules/followUp/views/follow-up/modals.php'
    run(f"{proj} - verify _stl vars (lines 395-402)",
        f"sed -n '395,402p' {modals_path}")

# PHP syntax check on the fixed files
for proj in projects:
    modals_path = f'/var/www/{proj}/backend/modules/followUp/views/follow-up/modals.php'
    run(f"{proj} - PHP syntax check",
        f"php -l {modals_path} 2>&1")

# Test the page via PHP CLI to check for fatal errors
run("JADAL - PHP test render (check syntax/requires)",
    """cd /var/www/jadal.aqssat.co && php -r "
require 'vendor/autoload.php';
echo 'autoload OK' . PHP_EOL;
" 2>&1""")

# Check if any new errors appeared after our fix (timestamp-based)
run("JADAL new errors after fix",
    "tail -3 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null")

ssh.close()
