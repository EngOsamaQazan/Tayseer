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
        print('EMPTY')
    print()

# Check questionable vhost configs
for name in ['jadal2', 'namaa2', 'sass', 'staging', 'khaldon', 'vite.jadal', 'vite.namaa', 'api-sass', 'api-jadal', 'api-namaa']:
    run(f"vhost: {name}",
        f"grep -E 'ServerName|DocumentRoot|ServerAlias' /etc/apache2/sites-available/{name}.aqssat.co*.conf 2>/dev/null | head -10")

# Check micro_services
run("micro_services contents", "find /var/www/micro_services -type f | head -20 2>/dev/null")

# Check document_errors
run("document_errors contents", "ls -la /var/www/document_errors/ 2>/dev/null")

# Check /var/www/html sub-directories (not symlinks)
run("html/jadal.aqssat.co", "ls -la /var/www/html/jadal.aqssat.co/ 2>/dev/null")
run("html/namaa.aqssat.co", "ls -la /var/www/html/namaa.aqssat.co/ 2>/dev/null")
run("html/jadal2.aqssat.co", "ls -la /var/www/html/jadal2.aqssat.co/ 2>/dev/null")
run("html/namaa2.aqssat.co", "ls -la /var/www/html/namaa2.aqssat.co/ 2>/dev/null")

# Check SSL certs for domains to delete
run("SSL certs", "ls /etc/letsencrypt/live/ 2>/dev/null")

# a.py in /var/www
run("a.py content", "cat /var/www/a.py 2>/dev/null | head -10")

ssh.close()
