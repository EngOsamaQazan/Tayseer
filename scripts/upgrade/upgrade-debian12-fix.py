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
        for line in err.strip().split('\n')[:30]:
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
    # FIX BROKEN PACKAGES
    # ========================================
    print("=" * 60)
    print("  FIX BROKEN PACKAGES")
    print("=" * 60, flush=True)

    # Fix broken installs
    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt --fix-broken install -y "
        "-o Dpkg::Options::='--force-confold' "
        "-o Dpkg::Options::='--force-confdef' "
        "2>&1 | tail -20",
        timeout=300)

    # dpkg configure any half-configured packages
    run_cmd(ssh, "dpkg --configure -a 2>&1 | tail -10", timeout=120)

    # Try to fix phpMyAdmin specifically - remove and reinstall
    print("\n--- Fixing phpMyAdmin ---", flush=True)
    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt-get install -y --fix-broken phpmyadmin "
        "-o Dpkg::Options::='--force-confold' "
        "-o Dpkg::Options::='--force-confdef' "
        "2>&1 | tail -20",
        timeout=300)

    # If still broken, remove phpMyAdmin (it's not critical)
    ec, out, _ = run_cmd(ssh, "dpkg -l phpmyadmin 2>&1 | grep -E '^(ii|iF|iU)'")
    if 'ii' not in out:
        print("\n--- phpMyAdmin still broken, removing it ---", flush=True)
        run_cmd(ssh, "DEBIAN_FRONTEND=noninteractive apt-get remove -y phpmyadmin 2>&1 | tail -10", timeout=120)
        run_cmd(ssh, "dpkg --configure -a 2>&1 | tail -5", timeout=120)

    # Final autoremove
    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt-get autoremove -y "
        "-o Dpkg::Options::='--force-confold' "
        "2>&1 | tail -10",
        timeout=300)

    # Check for remaining issues
    print("\n--- Checking for remaining issues ---", flush=True)
    run_cmd(ssh, "dpkg --audit 2>&1")

    # ========================================
    # VERIFY SERVICES BEFORE REBOOT
    # ========================================
    print("\n" + "=" * 60)
    print("  VERIFY SERVICES BEFORE REBOOT")
    print("=" * 60, flush=True)

    run_cmd(ssh, "cat /etc/os-release | grep PRETTY")
    run_cmd(ssh, "php -v | head -1")
    run_cmd(ssh, "apache2ctl -M 2>&1 | grep php")
    run_cmd(ssh, "systemctl is-active apache2")
    run_cmd(ssh, "systemctl is-active mariadb")
    run_cmd(ssh, "systemctl is-active fail2ban")
    run_cmd(ssh, "ufw status | head -3")

    for site in PROTECTED_SITES:
        run_cmd(ssh, f"curl -sL -o /dev/null -w '%{{http_code}}' https://{site}")

    # ========================================
    # REBOOT
    # ========================================
    print("\n" + "=" * 60)
    print("  REBOOTING SERVER...")
    print("=" * 60, flush=True)

    try:
        run_cmd(ssh, "reboot", timeout=10)
    except Exception:
        pass

    ssh.close()
    print("\n  Server is rebooting... waiting 30 seconds...", flush=True)
    time.sleep(30)

    # ========================================
    # RECONNECT AND VERIFY
    # ========================================
    print("\n" + "=" * 60)
    print("  RECONNECTING AFTER REBOOT...")
    print("=" * 60, flush=True)

    for attempt in range(6):
        try:
            time.sleep(10)
            print(f"\n  Reconnect attempt {attempt + 1}...", flush=True)
            ssh2 = paramiko.SSHClient()
            ssh2.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            ssh2.connect(HOST, username=USER, password=PASS, timeout=30)
            ssh2.get_transport().set_keepalive(15)
            print("  Connected!", flush=True)
            break
        except Exception as e:
            print(f"  Not ready yet: {e}", flush=True)
    else:
        print("  ERROR: Could not reconnect after reboot!", flush=True)
        return

    # ========================================
    # POST-REBOOT VERIFICATION
    # ========================================
    print("\n" + "=" * 60)
    print("  POST-REBOOT VERIFICATION")
    print("=" * 60, flush=True)

    run_cmd(ssh2, "uptime")
    run_cmd(ssh2, "cat /etc/os-release | grep PRETTY")
    run_cmd(ssh2, "uname -r")
    run_cmd(ssh2, "php -v | head -1")
    run_cmd(ssh2, "apache2 -v 2>&1 | head -1")
    run_cmd(ssh2, "mysql --version 2>&1")
    run_cmd(ssh2, "systemctl is-active apache2")
    run_cmd(ssh2, "systemctl is-active mariadb")
    run_cmd(ssh2, "systemctl is-active fail2ban")
    run_cmd(ssh2, "ufw status | head -3")
    run_cmd(ssh2, "free -h | head -3")
    run_cmd(ssh2, "df -h / | tail -1")
    run_cmd(ssh2, "swapon --show")

    print("\n--- Testing all sites ---", flush=True)
    for site in PROTECTED_SITES:
        run_cmd(ssh2, f"curl -sL -o /dev/null -w '%{{http_code}}' https://{site}")

    # Check MariaDB databases are intact
    print("\n--- Checking databases ---", flush=True)
    run_cmd(ssh2, "mysql -e 'SHOW DATABASES;' 2>&1")

    print("\n" + "=" * 60)
    print("  UPGRADE COMPLETE!")
    print("=" * 60, flush=True)

    ssh2.close()

if __name__ == '__main__':
    main()
