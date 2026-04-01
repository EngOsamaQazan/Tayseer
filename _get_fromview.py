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
        print('NONE')
    print()

# fromView method
run("fromView static method",
    "grep -A30 'function fromView' /var/www/jadal.aqssat.co/backend/modules/followUp/helper/ContractCalculations.php")

# Also check totalDebt method
run("totalDebt method",
    "grep -A8 'function totalDebt' /var/www/jadal.aqssat.co/backend/modules/followUp/helper/ContractCalculations.php")

# Check the same error on NAMAA (same codebase structure)
run("NAMAA same file exists?",
    "grep -n '_stlTotalDebt' /var/www/namaa.aqssat.co/backend/modules/followUp/views/follow-up/modals.php 2>/dev/null")

run("WATAR same file exists?",
    "grep -n '_stlTotalDebt' /var/www/watar.aqssat.co/backend/modules/followUp/views/follow-up/modals.php 2>/dev/null")

ssh.close()
