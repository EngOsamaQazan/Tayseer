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

PROTECTED_SITES = [
    '/var/www/jadal.aqssat.co',
    '/var/www/namaa.aqssat.co',
    '/var/www/old.jadal.aqssat.co',
    '/var/www/old.namaa.aqssat.co',
]

def run_cmd(ssh, cmd, timeout=120):
    print(f"\n  $ {cmd}", flush=True)
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
    if out.strip():
        for line in out.strip().split('\n')[:30]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if err.strip():
        for line in err.strip().split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    print(f"  -> exit: {exit_code}", flush=True)
    chan.close()
    return exit_code, out, err

def main():
    print(f"Connecting to {HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    # ========================================
    # PHASE 0: Verify protected sites
    # ========================================
    print("=" * 60)
    print("  PHASE 0: Verify protected sites are UP")
    print("=" * 60, flush=True)
    for site in PROTECTED_SITES:
        run_cmd(ssh, f"test -d {site} && echo 'EXISTS' || echo 'MISSING'")
    run_cmd(ssh, "df -h / | tail -1")
    print("  BEFORE cleanup disk usage recorded.", flush=True)

    # ========================================
    # PHASE 1: CLEANUP
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 1: DISK CLEANUP")
    print("=" * 60, flush=True)

    # 1a. Remove staging sites (no recent activity)
    print("\n  --- Remove unused staging sites ---", flush=True)
    run_cmd(ssh, "rm -rf /var/www/staging.aqssat.co", timeout=120)
    run_cmd(ssh, "rm -rf /var/www/new_staging.aqssat.co", timeout=120)

    # 1b. Remove old duplicate copies (jadal/ and namaa/ without domain)
    print("\n  --- Remove old duplicate copies ---", flush=True)
    run_cmd(ssh, "rm -rf /var/www/jadal", timeout=60)
    run_cmd(ssh, "rm -rf /var/www/namaa", timeout=60)

    # 1c. Clean journal logs (keep last 100MB)
    print("\n  --- Clean journal logs ---", flush=True)
    run_cmd(ssh, "journalctl --vacuum-size=100M 2>&1")

    # 1d. Clean old system logs
    print("\n  --- Clean old system logs ---", flush=True)
    run_cmd(ssh, "truncate -s 0 /var/log/btmp")
    run_cmd(ssh, "find /var/log -name '*.gz' -delete 2>/dev/null; true")
    run_cmd(ssh, "find /var/log -name '*.1' -delete 2>/dev/null; true")
    run_cmd(ssh, "find /var/log -name '*.old' -delete 2>/dev/null; true")
    run_cmd(ssh, "find /var/log/apache2 -name '*.log' -mtime +7 -delete 2>/dev/null; true")

    # 1e. Clean apt cache
    print("\n  --- Clean apt cache ---", flush=True)
    run_cmd(ssh, "apt-get clean 2>&1")
    run_cmd(ssh, "apt-get autoremove -y 2>&1", timeout=120)

    # 1f. Remove old PHP versions (7.4 and 8.3)
    print("\n  --- Remove old PHP 7.4 ---", flush=True)
    run_cmd(ssh, "DEBIAN_FRONTEND=noninteractive apt-get purge -y 'php7.4*' 2>&1", timeout=120)

    print("\n  --- Remove old PHP 8.3 ---", flush=True)
    run_cmd(ssh, "DEBIAN_FRONTEND=noninteractive apt-get purge -y 'php8.3*' 'libapache2-mod-php8.3' 2>&1", timeout=120)

    run_cmd(ssh, "apt-get autoremove -y 2>&1", timeout=120)

    # 1g. Clean /root temp files
    print("\n  --- Clean /root temp files ---", flush=True)
    run_cmd(ssh, "rm -rf /root/.cache/pip 2>/dev/null; true")
    run_cmd(ssh, "rm -rf /root/.npm/_cacache 2>/dev/null; true")
    run_cmd(ssh, "rm -f /composer-setup.php 2>/dev/null; true")
    run_cmd(ssh, "rm -f /tmp/debsuryorg-archive-keyring.deb 2>/dev/null; true")

    # 1h. Clean /tmp
    print("\n  --- Clean /tmp ---", flush=True)
    run_cmd(ssh, "find /tmp -type f -mtime +1 -delete 2>/dev/null; true")

    # 1i. Disable Apache configs for removed sites
    print("\n  --- Disable removed site configs ---", flush=True)
    run_cmd(ssh, "a2dissite staging.aqssat.co.conf 2>/dev/null; true")
    run_cmd(ssh, "a2dissite staging.aqssat.co-le-ssl.conf 2>/dev/null; true")
    run_cmd(ssh, "systemctl reload apache2 2>&1")

    # Check disk after cleanup
    print("\n  --- Disk after cleanup ---", flush=True)
    run_cmd(ssh, "df -h / | tail -1")

    # ========================================
    # PHASE 2: ADD SWAP
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 2: ADD 2GB SWAP")
    print("=" * 60, flush=True)

    run_cmd(ssh, "swapon --show 2>&1 | head -3")
    run_cmd(ssh, "fallocate -l 2G /swapfile 2>&1")
    run_cmd(ssh, "chmod 600 /swapfile")
    run_cmd(ssh, "mkswap /swapfile 2>&1")
    run_cmd(ssh, "swapon /swapfile 2>&1")
    run_cmd(ssh, "grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab")
    run_cmd(ssh, "sysctl vm.swappiness=10 2>&1")
    run_cmd(ssh, "grep -q 'vm.swappiness' /etc/sysctl.conf || echo 'vm.swappiness=10' >> /etc/sysctl.conf")
    run_cmd(ssh, "free -h")

    # ========================================
    # PHASE 3: FIREWALL (UFW)
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 3: ENABLE UFW FIREWALL")
    print("=" * 60, flush=True)

    run_cmd(ssh, "apt-get install -y ufw 2>&1", timeout=120)
    run_cmd(ssh, "ufw default deny incoming 2>&1")
    run_cmd(ssh, "ufw default allow outgoing 2>&1")
    run_cmd(ssh, "ufw allow 22/tcp comment 'SSH' 2>&1")
    run_cmd(ssh, "ufw allow 80/tcp comment 'HTTP' 2>&1")
    run_cmd(ssh, "ufw allow 443/tcp comment 'HTTPS' 2>&1")
    run_cmd(ssh, "echo 'y' | ufw enable 2>&1")
    run_cmd(ssh, "ufw status verbose 2>&1")

    # ========================================
    # PHASE 4: FAIL2BAN
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 4: ENABLE FAIL2BAN")
    print("=" * 60, flush=True)

    run_cmd(ssh, "apt-get install -y fail2ban 2>&1", timeout=120)

    jail_config = """[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
backend = systemd

[sshd]
enabled = true
port = 22
filter = sshd
maxretry = 3
bantime = 86400
"""
    run_cmd(ssh, f"cat > /etc/fail2ban/jail.local << 'EOF'\n{jail_config}EOF")
    run_cmd(ssh, "systemctl enable fail2ban 2>&1")
    run_cmd(ssh, "systemctl restart fail2ban 2>&1")
    run_cmd(ssh, "systemctl status fail2ban 2>&1 | head -5")
    run_cmd(ssh, "fail2ban-client status 2>&1")

    # ========================================
    # PHASE 5: VERIFY EVERYTHING WORKS
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 5: FINAL VERIFICATION")
    print("=" * 60, flush=True)

    print("\n  --- Protected sites check ---", flush=True)
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://old.jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://old.namaa.aqssat.co")

    print("\n  --- Services ---", flush=True)
    run_cmd(ssh, "systemctl status apache2 2>&1 | head -3")
    run_cmd(ssh, "php -v | head -1")
    run_cmd(ssh, "mysql --version 2>&1")

    print("\n  --- Security ---", flush=True)
    run_cmd(ssh, "ufw status 2>&1 | head -10")
    run_cmd(ssh, "fail2ban-client status sshd 2>&1")

    print("\n  --- Disk ---", flush=True)
    run_cmd(ssh, "df -h / | tail -1")
    run_cmd(ssh, "free -h | head -3")

    print("\n  --- Protected site files intact ---", flush=True)
    for site in PROTECTED_SITES:
        name = site.split('/')[-1]
        _, out, _ = run_cmd(ssh, f"ls {site}/backend/web/index.php 2>&1 && echo 'OK' || echo 'MISSING'")

    print("\n" + "=" * 60)
    print("  ALL DONE!")
    print("=" * 60, flush=True)
    ssh.close()

if __name__ == '__main__':
    main()
