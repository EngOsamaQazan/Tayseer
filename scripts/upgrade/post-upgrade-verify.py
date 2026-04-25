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
    'jadal.aqssat.co',
    'namaa.aqssat.co',
    'old.jadal.aqssat.co',
    'old.namaa.aqssat.co',
]

def run_cmd(ssh, cmd, timeout=300):
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
        for line in out.strip().split('\n')[:60]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if err.strip():
        for line in err.strip().split('\n')[:20]:
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
    # SYSTEM STATUS
    # ========================================
    print("=" * 60)
    print("  SYSTEM STATUS")
    print("=" * 60, flush=True)

    run_cmd(ssh, "cat /etc/os-release | grep PRETTY")
    run_cmd(ssh, "uname -r")
    run_cmd(ssh, "uptime")
    run_cmd(ssh, "php -v | head -1")
    run_cmd(ssh, "apache2 -v 2>&1 | head -1")
    run_cmd(ssh, "mysql --version 2>&1")
    run_cmd(ssh, "free -h | head -3")
    run_cmd(ssh, "df -h / | tail -1")
    run_cmd(ssh, "swapon --show")

    # ========================================
    # SERVICES STATUS
    # ========================================
    print("\n" + "=" * 60)
    print("  SERVICES STATUS")
    print("=" * 60, flush=True)

    run_cmd(ssh, "systemctl is-active apache2")
    run_cmd(ssh, "systemctl is-active mariadb")
    run_cmd(ssh, "systemctl is-active ssh")
    run_cmd(ssh, "systemctl is-active fail2ban")
    run_cmd(ssh, "ufw status | head -5")

    # ========================================
    # FIX BROKEN PACKAGES
    # ========================================
    print("\n" + "=" * 60)
    print("  FIX BROKEN PACKAGES")
    print("=" * 60, flush=True)

    run_cmd(ssh, "dpkg --audit 2>&1 | head -20")
    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt --fix-broken install -y "
        "-o Dpkg::Options::='--force-confold' "
        "-o Dpkg::Options::='--force-confdef' "
        "2>&1 | tail -20",
        timeout=300)
    run_cmd(ssh, "dpkg --configure -a 2>&1 | tail -10", timeout=120)
    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt-get autoremove -y 2>&1 | tail -10",
        timeout=300)

    # ========================================
    # TEST DATABASES
    # ========================================
    print("\n" + "=" * 60)
    print("  DATABASE CHECK")
    print("=" * 60, flush=True)

    run_cmd(ssh, "mysql -e 'SHOW DATABASES;' 2>&1")
    run_cmd(ssh, "mysql -e 'SELECT COUNT(*) as tables_jadal FROM information_schema.tables WHERE table_schema=\"jadal\";' 2>&1")
    run_cmd(ssh, "mysql -e 'SELECT COUNT(*) as tables_namaa FROM information_schema.tables WHERE table_schema=\"namaa\";' 2>&1")

    # ========================================
    # TEST WEBSITES
    # ========================================
    print("\n" + "=" * 60)
    print("  WEBSITE TESTS")
    print("=" * 60, flush=True)

    for site in PROTECTED_SITES:
        run_cmd(ssh, f"curl -sL -o /dev/null -w '%{{http_code}}' https://{site}")

    # Check for PHP errors in sites
    for site in ['jadal.aqssat.co', 'namaa.aqssat.co']:
        run_cmd(ssh, f"curl -sL https://{site} 2>&1 | head -5")

    # ========================================
    # CHECK SITE FILES INTACT
    # ========================================
    print("\n" + "=" * 60)
    print("  SITE FILES CHECK")
    print("=" * 60, flush=True)

    for site in PROTECTED_SITES:
        run_cmd(ssh, f"ls -la /var/www/{site}/backend/web/index.php 2>&1")

    # Check images directories
    run_cmd(ssh, "du -sh /var/www/jadal.aqssat.co/backend/web/uploads/ 2>/dev/null; echo 'no uploads dir' 2>/dev/null")
    run_cmd(ssh, "du -sh /var/www/namaa.aqssat.co/backend/web/uploads/ 2>/dev/null; echo 'no uploads dir' 2>/dev/null")

    # ========================================
    # PRODUCTION SETTINGS CHECK
    # ========================================
    print("\n" + "=" * 60)
    print("  PRODUCTION SETTINGS")
    print("=" * 60, flush=True)

    run_cmd(ssh, "grep 'YII_DEBUG' /var/www/jadal.aqssat.co/backend/web/index.php")
    run_cmd(ssh, "grep 'YII_ENV' /var/www/jadal.aqssat.co/backend/web/index.php")
    run_cmd(ssh, "php -i 2>/dev/null | grep 'display_errors' | head -1")
    run_cmd(ssh, "php -i 2>/dev/null | grep 'opcache.enable ' | head -1")

    print("\n" + "=" * 60)
    print("  VERIFICATION COMPLETE!")
    print("=" * 60, flush=True)

    ssh.close()

if __name__ == '__main__':
    main()
