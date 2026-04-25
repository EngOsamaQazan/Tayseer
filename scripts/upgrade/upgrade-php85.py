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

def run_cmd(ssh, cmd, timeout=300, show_all=False):
    print(f"\n  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    limit = 200 if show_all else 50
    if out.strip():
        for line in out.strip().split('\n')[:limit]:
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
    print("Connected!\n")

    # Step 1: Current state
    print("=" * 60)
    print("  STEP 1: Current PHP and OS info")
    print("=" * 60)
    run_cmd(ssh, "php -v | head -1")
    run_cmd(ssh, "cat /etc/os-release | head -2")
    run_cmd(ssh, "uname -m")

    # Step 2: Fix the GPG key properly
    print("\n" + "=" * 60)
    print("  STEP 2: Fix Sury GPG key")
    print("=" * 60)
    
    # Remove all old repo configs
    run_cmd(ssh, "rm -f /etc/apt/sources.list.d/sury-php.list 2>/dev/null; true")
    run_cmd(ssh, "rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-*.list 2>/dev/null; true")
    
    # Download fresh GPG key using wget (not gpg --dearmor which needs tty)
    run_cmd(ssh, "wget -qO /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg 2>&1")
    run_cmd(ssh, "ls -la /etc/apt/trusted.gpg.d/php.gpg")
    run_cmd(ssh, "file /etc/apt/trusted.gpg.d/php.gpg")
    
    # Make sure the repo source is correct
    run_cmd(ssh, "echo 'deb https://packages.sury.org/php/ bullseye main' > /etc/apt/sources.list.d/php.list")
    run_cmd(ssh, "cat /etc/apt/sources.list.d/php.list")

    # Step 3: Update package lists
    print("\n" + "=" * 60)
    print("  STEP 3: apt-get update")
    print("=" * 60)
    run_cmd(ssh, "apt-get update 2>&1", timeout=120, show_all=True)

    # Step 4: Check PHP 8.5 availability
    print("\n" + "=" * 60)
    print("  STEP 4: Check PHP 8.5 packages")
    print("=" * 60)
    run_cmd(ssh, "apt-cache policy php8.5")
    run_cmd(ssh, "apt-cache madison php8.5 2>/dev/null | head -5")
    
    # List all available php8.5 packages
    run_cmd(ssh, "apt-cache search php8.5 | sort")

    # Step 5: Install PHP 8.5
    print("\n" + "=" * 60)
    print("  STEP 5: Install PHP 8.5 + extensions")
    print("=" * 60)
    
    # Get list of currently installed php8.3 extensions to match
    run_cmd(ssh, "dpkg -l 'php8.3-*' 2>/dev/null | grep '^ii' | awk '{print $2}'")
    
    pkgs = (
        "php8.5 php8.5-cli php8.5-common php8.5-mysql php8.5-xml "
        "php8.5-mbstring php8.5-curl php8.5-zip php8.5-gd php8.5-intl "
        "php8.5-bcmath php8.5-soap php8.5-readline php8.5-bz2 "
        "libapache2-mod-php8.5"
    )
    
    code, out, _ = run_cmd(ssh, f"DEBIAN_FRONTEND=noninteractive apt-get install -y {pkgs} 2>&1", timeout=600, show_all=True)
    
    if code != 0:
        print("\n  Installation failed. Trying with --fix-missing...")
        run_cmd(ssh, f"DEBIAN_FRONTEND=noninteractive apt-get install -y --fix-missing {pkgs} 2>&1", timeout=600, show_all=True)

    # Step 6: Verify
    print("\n" + "=" * 60)
    print("  STEP 6: Verify PHP 8.5")
    print("=" * 60)
    code, out, _ = run_cmd(ssh, "php8.5 -v 2>&1")
    
    if 'not found' in out.lower() or code != 0:
        print("\n  PHP 8.5 installation FAILED.")
        print("  Keeping sites on PHP 8.3.")
        run_cmd(ssh, "php -v | head -1")
        run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
        ssh.close()
        return
    
    run_cmd(ssh, "php8.5 -m | sort")

    # Step 7: Switch Apache
    print("\n" + "=" * 60)
    print("  STEP 7: Switch Apache from 8.3 to 8.5")
    print("=" * 60)
    run_cmd(ssh, "a2dismod php8.3 2>&1")
    run_cmd(ssh, "a2enmod php8.5 2>&1")
    run_cmd(ssh, "systemctl restart apache2 2>&1")
    run_cmd(ssh, "systemctl status apache2 2>&1 | head -5")

    # Step 8: Set CLI default
    print("\n" + "=" * 60)
    print("  STEP 8: Set PHP 8.5 as CLI default")
    print("=" * 60)
    run_cmd(ssh, "update-alternatives --set php /usr/bin/php8.5 2>&1")
    run_cmd(ssh, "php -v | head -1")

    # Step 9: Test sites
    print("\n" + "=" * 60)
    print("  STEP 9: Test sites")
    print("=" * 60)
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")
    # Check it's not showing raw PHP
    run_cmd(ssh, "curl -sL https://jadal.aqssat.co 2>/dev/null | head -3")
    
    # If we see <?php, revert to 8.3
    code, out, _ = run_cmd(ssh, "curl -sL https://jadal.aqssat.co 2>/dev/null | grep -c '<?php'")
    if out.strip() != '0':
        print("\n  WARNING: Site showing raw PHP! Reverting to 8.3...")
        run_cmd(ssh, "a2dismod php8.5 2>&1; true")
        run_cmd(ssh, "a2enmod php8.3 2>&1")
        run_cmd(ssh, "systemctl restart apache2 2>&1")
        run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")

    print("\n" + "=" * 60)
    print("  COMPLETE!")
    print("=" * 60)
    run_cmd(ssh, "php -v | head -1")
    
    ssh.close()

if __name__ == '__main__':
    main()
