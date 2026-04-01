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

run("Directory sizes", "du -sh /var/www/*/ 2>/dev/null")
run("Symlinks in /var/www", "find /var/www -maxdepth 2 -type l -ls 2>/dev/null")
run("Contents of /var/www/html", "ls -la /var/www/html/")
run("Apache sites-available", "ls /etc/apache2/sites-available/ 2>/dev/null")
run("Apache sites-enabled", "ls /etc/apache2/sites-enabled/ 2>/dev/null")

ssh.close()
