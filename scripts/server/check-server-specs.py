# --- Credentials (loaded from scripts/credentials.py, git-ignored) ---
# Copy scripts/credentials.example.py to scripts/credentials.py and
# fill in the real values before running this script.
import os as _os, sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..'))
_sys.path.insert(0, _os.path.dirname(_os.path.abspath(__file__)))
try:
    from credentials import *  # noqa: F401,F403
except ImportError as _e:
    raise SystemExit(
        'Missing scripts/credentials.py — copy credentials.example.py and fill in real values.\n'
        f'Original error: {_e}'
    )
# ---------------------------------------------------------------------

import paramiko
import os
import time

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS

def run_cmd(ssh, cmd, timeout=30):
    chan = ssh.get_transport().open_session()
    chan.settimeout(timeout)
    chan.exec_command(cmd)
    out_data = b''
    err_data = b''
    while True:
        if chan.recv_ready():
            out_data += chan.recv(65536)
            continue
        if chan.recv_stderr_ready():
            err_data += chan.recv_stderr(65536)
            continue
        if chan.exit_status_ready():
            while chan.recv_ready():
                out_data += chan.recv(65536)
            while chan.recv_stderr_ready():
                err_data += chan.recv_stderr(65536)
            break
        time.sleep(0.1)
    exit_code = chan.recv_exit_status()
    out = out_data.decode('utf-8', errors='replace')
    err = err_data.decode('utf-8', errors='replace')
    chan.close()
    return out.strip(), err.strip()

def show(ssh, label, cmd):
    out, err = run_cmd(ssh, cmd)
    result = out if out else err
    print(f"  {label}: {result}", flush=True)
    return result

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)

    print("=" * 60)
    print("  SERVER FULL SPECS")
    print("=" * 60, flush=True)

    print("\n--- OS ---", flush=True)
    show(ssh, "OS", "cat /etc/os-release | grep PRETTY")
    show(ssh, "Kernel", "uname -r")
    show(ssh, "Arch", "uname -m")

    print("\n--- CPU ---", flush=True)
    show(ssh, "Model", "grep 'model name' /proc/cpuinfo | head -1 | cut -d: -f2")
    show(ssh, "Cores", "nproc")
    show(ssh, "Threads", "grep -c processor /proc/cpuinfo")

    print("\n--- RAM ---", flush=True)
    show(ssh, "Total", "free -h | awk '/Mem:/{print $2}'")
    show(ssh, "Used", "free -h | awk '/Mem:/{print $3}'")
    show(ssh, "Available", "free -h | awk '/Mem:/{print $7}'")
    show(ssh, "Swap Total", "free -h | awk '/Swap:/{print $2}'")
    show(ssh, "Swap Used", "free -h | awk '/Swap:/{print $3}'")

    print("\n--- Disk ---", flush=True)
    show(ssh, "Disks", "df -h / | tail -1")
    show(ssh, "Total www", "du -sh /var/www/ 2>/dev/null | cut -f1")

    print("\n--- Network ---", flush=True)
    show(ssh, "Hostname", "hostname")
    show(ssh, "IP", "hostname -I | awk '{print $1}'")
    show(ssh, "Provider", "curl -s ipinfo.io/org 2>/dev/null || echo 'N/A'")
    show(ssh, "Location", "curl -s ipinfo.io/city 2>/dev/null || echo 'N/A'")
    show(ssh, "Country", "curl -s ipinfo.io/country 2>/dev/null || echo 'N/A'")

    print("\n--- Services ---", flush=True)
    show(ssh, "Apache", "apache2 -v 2>&1 | head -1")
    show(ssh, "PHP", "php -v | head -1")
    show(ssh, "MySQL/MariaDB", "mysql --version 2>/dev/null || mariadb --version 2>/dev/null || echo 'Not found'")
    show(ssh, "Node.js", "node -v 2>/dev/null || echo 'Not installed'")
    show(ssh, "Composer", "composer --version 2>/dev/null | head -1")
    show(ssh, "Git", "git --version")
    show(ssh, "Certbot/SSL", "certbot --version 2>/dev/null || echo 'Not installed'")

    print("\n--- Database ---", flush=True)
    show(ssh, "DB Engine", "mysql -e 'SELECT VERSION();' 2>/dev/null | tail -1 || echo 'Cannot connect'")
    show(ssh, "Databases", "mysql -e 'SHOW DATABASES;' 2>/dev/null | grep -v 'Database\\|information_schema\\|performance_schema\\|mysql\\|sys' || echo 'Cannot list'")
    show(ssh, "DB Sizes", "mysql -e \"SELECT table_schema, ROUND(SUM(data_length+index_length)/1024/1024,1) AS 'Size_MB' FROM information_schema.TABLES GROUP BY table_schema HAVING Size_MB > 1 ORDER BY Size_MB DESC;\" 2>/dev/null || echo 'N/A'")

    print("\n--- Security ---", flush=True)
    show(ssh, "Firewall", "ufw status 2>/dev/null || iptables -L -n 2>/dev/null | head -3 || echo 'No firewall'")
    show(ssh, "Fail2ban", "systemctl is-active fail2ban 2>/dev/null || echo 'Not installed'")
    show(ssh, "SSH port", "grep '^Port' /etc/ssh/sshd_config 2>/dev/null || echo 'Default (22)'")
    show(ssh, "Root login", "grep '^PermitRootLogin' /etc/ssh/sshd_config 2>/dev/null || echo 'Default'")

    print("\n--- Uptime & Load ---", flush=True)
    show(ssh, "Uptime", "uptime -p")
    show(ssh, "Load", "cat /proc/loadavg")
    show(ssh, "Running procs", "ps aux | wc -l")

    print("\n--- Hosting ---", flush=True)
    show(ssh, "Virtualization", "systemd-detect-virt 2>/dev/null || echo 'Unknown'")
    show(ssh, "Sites", "ls /var/www/ 2>/dev/null")
    show(ssh, "Apache vhosts", "ls /etc/apache2/sites-enabled/ 2>/dev/null")

    print("\n" + "=" * 60)
    print("  DONE")
    print("=" * 60, flush=True)
    ssh.close()

if __name__ == '__main__':
    main()
