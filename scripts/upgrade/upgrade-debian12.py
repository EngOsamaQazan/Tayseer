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
        for line in out.strip().split('\n')[:50]:
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
    # PHASE 1: BACKUP CONFIGS
    # ========================================
    print("=" * 60)
    print("  PHASE 1: BACKUP CONFIGS")
    print("=" * 60, flush=True)

    run_cmd(ssh, "mkdir -p /root/backup-before-upgrade")
    run_cmd(ssh, "cp -a /etc/apache2 /root/backup-before-upgrade/apache2")
    run_cmd(ssh, "cp -a /etc/php /root/backup-before-upgrade/php")
    run_cmd(ssh, "cp /etc/mysql/mariadb.conf.d/50-server.cnf /root/backup-before-upgrade/ 2>/dev/null; true")
    run_cmd(ssh, "cp /etc/ssh/sshd_config /root/backup-before-upgrade/")
    run_cmd(ssh, "cp /etc/fstab /root/backup-before-upgrade/")
    run_cmd(ssh, "cp -a /etc/fail2ban /root/backup-before-upgrade/fail2ban")
    run_cmd(ssh, "dpkg --get-selections > /root/backup-before-upgrade/packages.list")
    run_cmd(ssh, "cat /etc/apt/sources.list > /root/backup-before-upgrade/sources.list.backup")
    run_cmd(ssh, "cp -a /etc/apt/sources.list.d /root/backup-before-upgrade/sources.list.d.backup")
    run_cmd(ssh, "ls -la /root/backup-before-upgrade/")

    # ========================================
    # PHASE 2: FULLY UPDATE DEBIAN 11 FIRST
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 2: FULLY UPDATE DEBIAN 11")
    print("=" * 60, flush=True)

    run_cmd(ssh, "DEBIAN_FRONTEND=noninteractive apt-get update 2>&1", timeout=120)
    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt-get upgrade -y "
        "-o Dpkg::Options::='--force-confold' 2>&1 | tail -10",
        timeout=600)
    run_cmd(ssh, "cat /etc/os-release | grep PRETTY")

    # ========================================
    # PHASE 3: CHANGE SOURCES TO BOOKWORM
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 3: SWITCH SOURCES TO DEBIAN 12 (bookworm)")
    print("=" * 60, flush=True)

    # Show current sources
    run_cmd(ssh, "cat /etc/apt/sources.list")

    # Replace bullseye with bookworm in main sources
    run_cmd(ssh, "sed -i 's/bullseye/bookworm/g' /etc/apt/sources.list")

    # Also need to add non-free-firmware for Debian 12
    run_cmd(ssh, "sed -i 's/non-free$/non-free non-free-firmware/' /etc/apt/sources.list 2>/dev/null; true")

    # Update Sury PHP repo to bookworm
    run_cmd(ssh, "cat /etc/apt/sources.list.d/php.list 2>/dev/null")
    run_cmd(ssh, "sed -i 's/bullseye/bookworm/g' /etc/apt/sources.list.d/php.list 2>/dev/null; true")

    # Show new sources
    run_cmd(ssh, "echo '--- New sources.list ---'")
    run_cmd(ssh, "cat /etc/apt/sources.list")
    run_cmd(ssh, "cat /etc/apt/sources.list.d/php.list 2>/dev/null")

    # Update package index
    run_cmd(ssh, "apt-get update 2>&1 | tail -10", timeout=120)

    # ========================================
    # PHASE 4: MINIMAL UPGRADE
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 4: MINIMAL UPGRADE (apt upgrade)")
    print("=" * 60, flush=True)

    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt-get upgrade -y "
        "-o Dpkg::Options::='--force-confold' "
        "-o Dpkg::Options::='--force-confdef' "
        "2>&1 | tail -20",
        timeout=900)

    # ========================================
    # PHASE 5: FULL UPGRADE
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 5: FULL UPGRADE (apt full-upgrade)")
    print("=" * 60, flush=True)

    run_cmd(ssh,
        "DEBIAN_FRONTEND=noninteractive apt-get full-upgrade -y "
        "-o Dpkg::Options::='--force-confold' "
        "-o Dpkg::Options::='--force-confdef' "
        "2>&1 | tail -30",
        timeout=900)

    # Clean up
    run_cmd(ssh, "DEBIAN_FRONTEND=noninteractive apt-get autoremove -y 2>&1 | tail -5", timeout=120)
    run_cmd(ssh, "apt-get clean 2>&1")

    # ========================================
    # PHASE 6: PRE-REBOOT CHECK
    # ========================================
    print("\n" + "=" * 60)
    print("  PHASE 6: PRE-REBOOT CHECK")
    print("=" * 60, flush=True)

    run_cmd(ssh, "cat /etc/os-release | grep PRETTY")
    run_cmd(ssh, "php -v | head -1")
    run_cmd(ssh, "apache2 -v 2>&1 | head -1")
    run_cmd(ssh, "mysql --version 2>&1")
    run_cmd(ssh, "df -h / | tail -1")

    # Test sites before reboot
    for site in PROTECTED_SITES:
        run_cmd(ssh, f"curl -sL -o /dev/null -w '%{{http_code}}' https://{site}")

    print("\n" + "=" * 60)
    print("  PHASE 6 DONE - Ready for reboot")
    print("  Sites tested, configs backed up")
    print("=" * 60, flush=True)

    ssh.close()

if __name__ == '__main__':
    main()
