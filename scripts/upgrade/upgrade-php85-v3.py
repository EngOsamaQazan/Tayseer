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

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS

def run_cmd(ssh, cmd, timeout=600):
    print(f"\n  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        for line in out.strip().split('\n')[:100]:
            try:
                print(f"    {line}")
            except UnicodeEncodeError:
                print(f"    {line.encode('ascii', 'replace').decode()}")
    if err.strip():
        for line in err.strip().split('\n')[:30]:
            try:
                print(f"    [err] {line}")
            except UnicodeEncodeError:
                print(f"    [err] {line.encode('ascii', 'replace').decode()}")
    print(f"  -> exit: {exit_code}")
    return exit_code, out, err

def main():
    print(f"Connecting to {HOST}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(30)
    print("Connected!\n")

    # Step 1: Install keyring package with longer timeout
    print("=" * 60)
    print("  STEP 1: Install debsuryorg-archive-keyring")
    print("=" * 60)
    run_cmd(ssh, "dpkg -i /tmp/debsuryorg-archive-keyring.deb 2>&1", timeout=600)

    # Step 2: Clean and update
    print("\n" + "=" * 60)
    print("  STEP 2: Clean and update apt")
    print("=" * 60)
    run_cmd(ssh, "apt-get clean 2>&1")
    run_cmd(ssh, "rm -rf /var/lib/apt/lists/* 2>&1")
    run_cmd(ssh, "apt-get update 2>&1", timeout=600)

    # Step 3: Check
    print("\n" + "=" * 60)
    print("  STEP 3: Check PHP 8.5")
    print("=" * 60)
    run_cmd(ssh, "apt-cache policy php8.5 | head -8")

    # Step 4: Install
    print("\n" + "=" * 60)
    print("  STEP 4: Install PHP 8.5")
    print("=" * 60)
    pkgs = (
        "php8.5 php8.5-cli php8.5-common php8.5-mysql php8.5-xml "
        "php8.5-mbstring php8.5-curl php8.5-zip php8.5-gd php8.5-intl "
        "php8.5-bcmath php8.5-soap php8.5-readline php8.5-bz2 "
        "libapache2-mod-php8.5"
    )
    code, out, _ = run_cmd(ssh, f"DEBIAN_FRONTEND=noninteractive apt-get install -y {pkgs} 2>&1", timeout=600)

    # Step 5: Verify
    print("\n" + "=" * 60)
    print("  STEP 5: Verify")
    print("=" * 60)
    code, out, _ = run_cmd(ssh, "php8.5 -v 2>&1")

    if 'not found' in out.lower() or code != 0:
        print("\n  PHP 8.5 FAILED. Sites stay on PHP 8.3.")
        run_cmd(ssh, "php -v | head -1")
        ssh.close()
        return

    # Step 6: Switch
    print("\n" + "=" * 60)
    print("  STEP 6: Switch Apache to PHP 8.5")
    print("=" * 60)
    run_cmd(ssh, "a2dismod php8.3 2>&1")
    run_cmd(ssh, "a2enmod php8.5 2>&1")
    run_cmd(ssh, "systemctl restart apache2 2>&1")
    run_cmd(ssh, "update-alternatives --set php /usr/bin/php8.5 2>&1")
    run_cmd(ssh, "php -v | head -1")

    # Step 7: Test
    print("\n" + "=" * 60)
    print("  STEP 7: Test sites")
    print("=" * 60)
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")

    _, check, _ = run_cmd(ssh, "curl -sL https://jadal.aqssat.co 2>/dev/null | head -1")
    if '<?php' in check:
        print("\n  Raw PHP detected! Reverting to 8.3...")
        run_cmd(ssh, "a2dismod php8.5 2>&1; a2enmod php8.3 2>&1; systemctl restart apache2 2>&1")
    else:
        print("\n  SUCCESS! Sites running on PHP 8.5!")

    print("\n" + "=" * 60)
    print("  DONE!")
    print("=" * 60)
    ssh.close()

if __name__ == '__main__':
    main()
