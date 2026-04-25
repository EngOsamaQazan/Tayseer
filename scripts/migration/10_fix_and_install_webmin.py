#!/usr/bin/env python3
"""
Emergency fix: Restore MariaDB + Apache, cleanup HestiaCP mess,
install Webmin (supports Debian 13 natively).

Webmin provides: File Manager, Database Management, Apache config,
SSL management, Firewall, System monitoring, and more.
"""
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
import time
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS
WEBMIN_PORT = 10000


def run(ssh, cmd, timeout=120, show=True):
    if show:
        print(f"  $ {cmd[:200]}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if show and out:
        for line in out.split('\n')[:30]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if show and code != 0 and err:
        for line in err.split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out, err


def main():
    print(f"\nConnecting to {NEW_HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    ssh.get_transport().set_keepalive(30)
    print("Connected!\n", flush=True)

    # ══════════════════════════════════════════════════════════
    # PHASE 1: TOTAL CLEANUP - Remove ALL HestiaCP mess
    # ══════════════════════════════════════════════════════════
    print("=" * 60)
    print("  PHASE 1: Remove ALL HestiaCP Artifacts")
    print("=" * 60, flush=True)

    run(ssh, "pkill -9 -f 'hst-install' 2>/dev/null; true")
    run(ssh, "sleep 1")

    # Remove HestiaCP
    run(ssh, "rm -rf /usr/local/hestia 2>/dev/null; true")
    run(ssh, "rm -f /root/hst-install-debian.sh /root/run_hestia_install.sh 2>/dev/null; true")
    run(ssh, "rm -f /root/hestia_install.log /root/hestia_nohup.log 2>/dev/null; true")

    # Remove HestiaCP user/group
    run(ssh, "userdel -r admin 2>/dev/null; true")
    run(ssh, "groupdel admin 2>/dev/null; true")
    run(ssh, "userdel -r hestiaweb 2>/dev/null; true")

    # Remove ALL broken repo files from HestiaCP
    run(ssh, "rm -f /etc/apt/sources.list.d/hestia.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/nginx.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/mariadb.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/apache2.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/sury.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/php.list 2>/dev/null; true")

    # Restore OS identity to Debian 13
    run(ssh, "test -f /etc/debian_version.bak.trixie && cp /etc/debian_version.bak.trixie /etc/debian_version; true")
    run(ssh, "test -f /etc/os-release.bak.trixie && cp /etc/os-release.bak.trixie /etc/os-release; true")
    run(ssh, "cat /etc/debian_version")

    # Restore original Sury PHP repo (for trixie)
    run(ssh, (
        'test -f /etc/apt/sources.list.d/sury-php.list || '
        'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] '
        'https://packages.sury.org/php/ trixie main" > /etc/apt/sources.list.d/sury-php.list'
    ))

    # Show what repos remain
    run(ssh, "ls /etc/apt/sources.list.d/")

    # ══════════════════════════════════════════════════════════
    # PHASE 2: Fix broken packages
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 2: Fix Broken Packages")
    print("=" * 60, flush=True)

    run(ssh, "dpkg --configure -a 2>&1 | tail -10", timeout=120)
    run(ssh, "apt-get update -qq 2>&1 | tail -5", timeout=60)
    run(ssh, "apt-get install -y --fix-broken 2>&1 | tail -10", timeout=120)

    # ══════════════════════════════════════════════════════════
    # PHASE 3: Restore MariaDB
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 3: Restore MariaDB")
    print("=" * 60, flush=True)

    run(ssh, "systemctl start mariadb 2>&1")
    code, status, _ = run(ssh, "systemctl is-active mariadb")

    if status.strip() != 'active':
        print("  MariaDB failed, diagnosing...", flush=True)
        run(ssh, "journalctl -u mariadb --no-pager -n 20 2>&1")
        # Try reinstalling
        run(ssh, "apt-get install -y mariadb-server 2>&1 | tail -10", timeout=180)
        run(ssh, "systemctl start mariadb 2>&1")
        code, status, _ = run(ssh, "systemctl is-active mariadb")

    if status.strip() != 'active':
        # Maybe data dir issue
        print("  Trying mysql_install_db...", flush=True)
        run(ssh, "mysql_install_db --user=mysql 2>&1 | tail -5; true")
        run(ssh, "chown -R mysql:mysql /var/lib/mysql 2>&1; true")
        run(ssh, "systemctl start mariadb 2>&1")
        code, status, _ = run(ssh, "systemctl is-active mariadb")

    print(f"\n  MariaDB: {status.strip()}", flush=True)

    # Verify databases
    if status.strip() == 'active':
        run(ssh, "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null | head -20")

    # ══════════════════════════════════════════════════════════
    # PHASE 4: Restore Apache
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 4: Restore Apache")
    print("=" * 60, flush=True)

    # Remove any broken apache configs from HestiaCP
    run(ssh, "rm -f /etc/apache2/sites-enabled/hestia* 2>/dev/null; true")
    run(ssh, "a2enmod rewrite headers ssl php8.3 2>/dev/null; true")
    run(ssh, "a2dismod mpm_event 2>/dev/null; a2enmod mpm_prefork 2>/dev/null; true")
    run(ssh, "apache2ctl configtest 2>&1")
    run(ssh, "systemctl start apache2 2>&1")
    _, status, _ = run(ssh, "systemctl is-active apache2")
    print(f"\n  Apache: {status.strip()}", flush=True)

    if status.strip() != 'active':
        print("  Apache failed, diagnosing...", flush=True)
        run(ssh, "journalctl -u apache2 --no-pager -n 15 2>&1")
        run(ssh, "apt-get install -y apache2 libapache2-mod-php8.3 2>&1 | tail -10", timeout=120)
        run(ssh, "systemctl start apache2 2>&1")
        _, status, _ = run(ssh, "systemctl is-active apache2")
        print(f"  Apache (retry): {status.strip()}", flush=True)

    # ══════════════════════════════════════════════════════════
    # PHASE 5: Re-enable UFW
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 5: Re-enable UFW")
    print("=" * 60, flush=True)
    run(ssh, "ufw allow 22 && ufw allow 80 && ufw allow 443 2>/dev/null; true")
    run(ssh, f"ufw allow {WEBMIN_PORT} 2>/dev/null; true")
    run(ssh, "ufw --force enable 2>/dev/null; true")
    run(ssh, "ufw status | head -10")

    # ══════════════════════════════════════════════════════════
    # PHASE 6: Install Webmin
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 6: Install Webmin")
    print("=" * 60, flush=True)

    # Add Webmin repo
    run(ssh, (
        "curl -fsSL https://raw.githubusercontent.com/webmin/webmin/master/setup-repos.sh "
        "| sh 2>&1 | tail -10"
    ), timeout=60)

    # Install Webmin
    run(ssh, "apt-get install -y webmin 2>&1 | tail -15", timeout=300)

    # Check if Webmin is running
    run(ssh, "systemctl is-active webmin 2>/dev/null")
    run(ssh, f"ss -tlnp | grep :{WEBMIN_PORT} || echo 'Port {WEBMIN_PORT} NOT listening'")

    # Set Webmin password (root login uses system password)
    print("\n  Webmin uses root system password for login.", flush=True)

    # ══════════════════════════════════════════════════════════
    # PHASE 7: Install Authentic Theme (modern UI) if not present
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 7: Verification")
    print("=" * 60, flush=True)

    for svc in ['mariadb', 'apache2', 'webmin']:
        _, status, _ = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        result = "RUNNING" if status.strip() == 'active' else "NOT RUNNING"
        print(f"    {svc}: {result}", flush=True)

    # Check databases
    run(ssh, "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null | head -10")
    # Check web
    run(ssh, "curl -sI http://localhost/ 2>/dev/null | head -3; true")

    # Final summary
    print("\n" + "=" * 60)
    print("  INSTALLATION COMPLETE!")
    print("=" * 60)
    print(f"")
    print(f"  Webmin Panel:  https://{NEW_HOST}:{WEBMIN_PORT}")
    print(f"  Username:      root")
    print(f"  Password:      {NEW_PASS}")
    print(f"")
    print(f"  File Manager:  https://{NEW_HOST}:{WEBMIN_PORT}/filemin/")
    print(f"  Databases:     https://{NEW_HOST}:{WEBMIN_PORT}/mysql/")
    print(f"  Apache:        https://{NEW_HOST}:{WEBMIN_PORT}/apache/")
    print(f"  phpMyAdmin:    http://{NEW_HOST}/phpmyadmin")
    print(f"")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
