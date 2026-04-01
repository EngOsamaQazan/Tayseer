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

# Yii2 runtime logs for jadal - today's errors
run("JADAL app.log (today errors)",
    "grep -A5 '2026-03-28' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -iE 'error|exception|fatal|500|followUp' | head -40")

# Full recent app.log entries
run("JADAL app.log (last 100 lines)",
    "tail -100 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null")

# Check followUp controller location
run("FollowUp controller path",
    "find /var/www/jadal.aqssat.co -name 'FollowUpController.php' -not -path '*/vendor/*' 2>/dev/null")

# Check followUpReport controller
run("FollowUpReport controller path",
    "find /var/www/jadal.aqssat.co -name 'FollowUpReportController.php' -not -path '*/vendor/*' 2>/dev/null")

# Today's Apache errors for jadal specifically
run("JADAL Apache error (today only)",
    "grep '28/Mar/2026' /var/log/apache2/jadal.aqssat.co-error.log 2>/dev/null | head -20")

# Check PHP error log if exists
run("PHP error log",
    "find /var/www/jadal.aqssat.co -name 'php_errors.log' -o -name 'error.log' 2>/dev/null | head -5")

ssh.close()
