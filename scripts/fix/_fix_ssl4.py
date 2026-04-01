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

# Full vhost file
run("Full old.namaa SSL config",
    "cat /etc/apache2/sites-available/old.namaa.aqssat.co-le-ssl.conf")

# Check enabled symlink target
run("Enabled symlink",
    "ls -la /etc/apache2/sites-enabled/old.namaa.aqssat.co-le-ssl.conf")

# Check actual file content of enabled link
run("Enabled file content",
    "cat /etc/apache2/sites-enabled/old.namaa.aqssat.co-le-ssl.conf | grep -E 'SSL|Server'")

# Check default-ssl
run("default-ssl config",
    "cat /etc/apache2/sites-available/default-ssl.conf | grep -E 'SSL|Server|Document' | head -10")

ssh.close()
