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
        print('OK (no output)')
    print()

run("Current kernel", "uname -r")
run("All kernels before", "dpkg -l 'linux-image*' 2>/dev/null | grep '^ii'")
run("Purge old kernel", "apt-get purge -y linux-image-6.12.38+deb13-cloud-amd64 2>&1 | tail -10")
run("Autoremove", "apt-get autoremove -y 2>&1 | tail -10")
run("All kernels after", "dpkg -l 'linux-image*' 2>/dev/null | grep '^ii'")
run("Disk after", "df -h / | tail -1")

ssh.close()
