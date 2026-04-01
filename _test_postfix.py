import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=30):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('OK (no output)')
    print()

run("Postfix version", "postconf mail_version 2>/dev/null")
run("Postfix main config", "postconf inet_interfaces myhostname mydomain myorigin mydestination 2>/dev/null")
run("PHP sendmail path", "php -r \"echo ini_get('sendmail_path');\" 2>/dev/null")
run("Test local mail", "echo 'Test from Postfix' | mail -s 'Postfix Test' root 2>&1 && echo MAIL_SENT || echo MAIL_FAILED")
run("Check mail queue", "postqueue -p 2>/dev/null")
run("Exim4 fully removed", "dpkg -l | grep exim 2>/dev/null || echo EXIM_COMPLETELY_REMOVED")
run("Postfix enabled on boot", "systemctl is-enabled postfix 2>/dev/null")

ssh.close()
