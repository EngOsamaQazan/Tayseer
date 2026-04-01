import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=120):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('OK')
    print()

# Check if cert files are symlinks
run("Check cert symlinks",
    "ls -la /etc/letsencrypt/live/old.namaa.aqssat.co/ 2>/dev/null")

# Check actual cert CN
run("Actual cert in old.namaa path",
    "openssl x509 -in /etc/letsencrypt/live/old.namaa.aqssat.co/fullchain.pem -noout -subject 2>/dev/null")

# Full restart Apache (not reload) for SSL changes
run("Full restart Apache",
    "systemctl restart apache2 2>&1 && echo RESTARTED")

# Wait and verify
import time
time.sleep(2)

run("Verify old.namaa SSL after restart",
    "echo | openssl s_client -servername old.namaa.aqssat.co -connect old.namaa.aqssat.co:443 2>/dev/null | openssl x509 -noout -subject 2>/dev/null")

# Check Apache error log for SSL warnings
run("Check for SSL warnings",
    "tail -5 /var/log/apache2/error.log 2>/dev/null | grep -i ssl || echo NO_SSL_WARNINGS")

ssh.close()
