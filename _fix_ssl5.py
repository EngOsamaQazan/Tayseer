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

# Remove the stale copy and create proper symlink
run("Remove stale enabled file",
    "rm -f /etc/apache2/sites-enabled/old.namaa.aqssat.co-le-ssl.conf && echo REMOVED")

run("Create proper symlink",
    "ln -s /etc/apache2/sites-available/old.namaa.aqssat.co-le-ssl.conf "
    "/etc/apache2/sites-enabled/old.namaa.aqssat.co-le-ssl.conf && echo LINKED")

run("Verify symlink",
    "ls -la /etc/apache2/sites-enabled/old.namaa.aqssat.co-le-ssl.conf")

run("Verify content",
    "grep SSLCert /etc/apache2/sites-enabled/old.namaa.aqssat.co-le-ssl.conf")

run("Test config", "apachectl configtest 2>&1")
run("Restart Apache", "systemctl restart apache2 2>&1 && echo RESTARTED")

import time
time.sleep(2)

run("Verify SSL now correct",
    "echo | openssl s_client -servername old.namaa.aqssat.co -connect old.namaa.aqssat.co:443 2>/dev/null | openssl x509 -noout -subject 2>/dev/null")

run("Check warnings gone",
    "tail -5 /var/log/apache2/error.log 2>/dev/null | grep -i 'ssl.*warn' || echo NO_SSL_WARNINGS")

ssh.close()
