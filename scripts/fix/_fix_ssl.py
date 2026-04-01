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

# Check which cert old.namaa is actually using
run("old.namaa SSL vhost config",
    "grep -E 'SSLCert|ServerName' /etc/apache2/sites-available/old.namaa.aqssat.co-le-ssl.conf 2>/dev/null")

# Check cert details
run("old.namaa cert CN",
    "openssl x509 -in /etc/letsencrypt/live/old.namaa.aqssat.co/cert.pem -noout -subject -text 2>/dev/null | grep -E 'Subject:|DNS:' | head -5")

# Check old.jadal cert
run("old.jadal cert CN",
    "openssl x509 -in /etc/letsencrypt/live/old.jadal.aqssat.co/cert.pem -noout -subject -text 2>/dev/null | grep -E 'Subject:|DNS:' | head -5")

# List all certbot certs
run("All certbot certs",
    "certbot certificates 2>/dev/null")

ssh.close()
