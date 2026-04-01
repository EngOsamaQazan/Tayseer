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

# Check what's still in sites-enabled
run("Sites still enabled", "ls /etc/apache2/sites-enabled/")

# Check which enabled sites have missing DocumentRoots
run("Check for broken DocumentRoots",
    "for f in /etc/apache2/sites-enabled/*.conf; do "
    "  root=$(grep -m1 DocumentRoot $f 2>/dev/null | awk '{print $2}'); "
    "  if [ ! -z \"$root\" ] && [ ! -d \"$root\" ]; then "
    "    echo \"BROKEN: $f -> $root\"; "
    "  fi; "
    "done")

ssh.close()
