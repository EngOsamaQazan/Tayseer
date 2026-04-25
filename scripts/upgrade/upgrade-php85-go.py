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
        for line in out.strip().split('\n')[:100]:
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

    # Kill stuck dpkg
    print("=" * 60)
    print("  Kill stuck dpkg process")
    print("=" * 60, flush=True)
    run_cmd(ssh, "kill -9 2760735 2>/dev/null; true")
    run_cmd(ssh, "rm -f /var/lib/dpkg/lock-frontend /var/lib/dpkg/lock /var/cache/apt/archives/lock 2>/dev/null; true")
    run_cmd(ssh, "dpkg --configure -a 2>&1", timeout=120)

    # Install PHP 8.5
    print("\n" + "=" * 60)
    print("  Install PHP 8.5.3")
    print("=" * 60, flush=True)
    pkgs = (
        "php8.5 php8.5-cli php8.5-common php8.5-mysql php8.5-xml "
        "php8.5-mbstring php8.5-curl php8.5-zip php8.5-gd php8.5-intl "
        "php8.5-bcmath php8.5-soap php8.5-readline php8.5-bz2 "
        "libapache2-mod-php8.5"
    )
    code, out, _ = run_cmd(ssh,
        f"DEBIAN_FRONTEND=noninteractive apt-get install -y {pkgs} 2>&1",
        timeout=600)

    # Verify
    print("\n" + "=" * 60)
    print("  Verify PHP 8.5")
    print("=" * 60, flush=True)
    code, out, _ = run_cmd(ssh, "php8.5 -v 2>&1")

    if 'not found' in out.lower() or code != 0:
        print("\n  FAILED!", flush=True)
        run_cmd(ssh, "php -v | head -1")
        ssh.close()
        return

    # Switch Apache
    print("\n" + "=" * 60)
    print("  Switch Apache to PHP 8.5")
    print("=" * 60, flush=True)
    run_cmd(ssh, "a2dismod php8.3 2>&1")
    run_cmd(ssh, "a2enmod php8.5 2>&1")
    run_cmd(ssh, "systemctl restart apache2 2>&1")
    run_cmd(ssh, "update-alternatives --set php /usr/bin/php8.5 2>&1")
    run_cmd(ssh, "php -v | head -1")

    # Test
    print("\n" + "=" * 60)
    print("  Test sites")
    print("=" * 60, flush=True)
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")

    _, check, _ = run_cmd(ssh, "curl -sL https://jadal.aqssat.co 2>/dev/null | head -1")
    if '<?php' in check:
        print("\n  Raw PHP! Reverting...", flush=True)
        run_cmd(ssh, "a2dismod php8.5 2>&1; a2enmod php8.3 2>&1; systemctl restart apache2 2>&1")
    else:
        print("\n  SUCCESS!", flush=True)

    print("\n  DONE!", flush=True)
    ssh.close()

if __name__ == '__main__':
    main()
