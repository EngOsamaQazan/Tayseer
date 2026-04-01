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
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('EMPTY')
    print()

run("html contents", "ls -la /var/www/html/")
run("index.html preview", "head -30 /var/www/html/index.html 2>/dev/null")
run("000-default.conf", "cat /etc/apache2/sites-available/000-default.conf 2>/dev/null")
run("aqssat.co.conf", "cat /etc/apache2/sites-available/aqssat.co.conf 2>/dev/null")
run("aqssat.co-le-ssl.conf", "cat /etc/apache2/sites-available/aqssat.co-le-ssl.conf 2>/dev/null")
run("What uses /var/www/html", "grep -rl '/var/www/html' /etc/apache2/sites-enabled/ 2>/dev/null")
run("Symlink targets", "ls -la /var/www/html/jadal /var/www/html/namaa 2>/dev/null")

ssh.close()
