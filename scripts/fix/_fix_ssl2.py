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
        print('OK')
    print()

# Fix: Replace wrong cert path in old.namaa vhost
run("Fix old.namaa SSL cert path",
    "sed -i 's|/etc/letsencrypt/live/old.jadal.aqssat.co/|/etc/letsencrypt/live/old.namaa.aqssat.co/|g' "
    "/etc/apache2/sites-available/old.namaa.aqssat.co-le-ssl.conf")

run("Verify fix",
    "grep -E 'SSLCert|ServerName' /etc/apache2/sites-available/old.namaa.aqssat.co-le-ssl.conf")

run("Test Apache config", "apachectl configtest 2>&1")
run("Reload Apache", "systemctl reload apache2 2>&1 && echo RELOADED")

# Verify SSL works
run("Verify old.namaa SSL",
    "echo | openssl s_client -servername old.namaa.aqssat.co -connect old.namaa.aqssat.co:443 2>/dev/null | openssl x509 -noout -subject 2>/dev/null")

# Final check - no more warnings
run("Final Apache error check",
    "apachectl configtest 2>&1")

ssh.close()
