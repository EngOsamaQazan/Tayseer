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

# Remove broken symlinks from sites-enabled
broken = [
    'jadal2.aqssat.co-le-ssl.conf',
    'khaldon.aqssat.co-le-ssl.conf',
    'namaa2.aqssat.co-le-ssl.conf',
]

for conf in broken:
    run(f"Remove enabled symlink: {conf}",
        f"rm -f /etc/apache2/sites-enabled/{conf} && echo REMOVED")

# Also remove remaining config files from sites-available
run("Remove khaldon available configs",
    "rm -f /etc/apache2/sites-available/khaldon.aqssat.co* && echo REMOVED")

# Remove orphan SSL certs for deleted domains
run("Remove jadal2 cert", "certbot delete --cert-name jadal2.aqssat.co --non-interactive 2>&1 || echo NO_CERT")
run("Remove namaa2 cert", "certbot delete --cert-name namaa2.aqssat.co --non-interactive 2>&1 || echo NO_CERT")
run("Remove vite.jadal cert", "certbot delete --cert-name vite.jadal.aqssat.co --non-interactive 2>&1 || echo NO_CERT")
run("Remove vite.namaa cert", "certbot delete --cert-name vite.namaa.aqssat.co --non-interactive 2>&1 || echo NO_CERT")

# Test Apache config
run("Test Apache config", "apachectl configtest 2>&1")
run("Reload Apache", "systemctl reload apache2 2>&1 && echo RELOADED")

# Final verification
run("Sites enabled (final)", "ls /etc/apache2/sites-enabled/")
run("Sites available (final)", "ls /etc/apache2/sites-available/")
run("/var/www contents (final)", "ls -la /var/www/")
run("/var/www/html contents (final)", "ls -la /var/www/html/")
run("Disk usage (final)", "df -h / | tail -1")

ssh.close()
