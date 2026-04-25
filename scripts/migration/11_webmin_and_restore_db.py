#!/usr/bin/env python3
"""
Fix remaining issues:
1. Restore databases from backup
2. Install Webmin properly (auto-accept repo setup)
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
    # PHASE 1: Restore Databases from Backup
    # ══════════════════════════════════════════════════════════
    print("=" * 60)
    print("  PHASE 1: Restore Databases from Backup")
    print("=" * 60, flush=True)

    # Check current state
    run(ssh, "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null")

    # Find backup file
    run(ssh, "ls -la /root/backup_before_hestia/ 2>/dev/null")
    code, backup_file, _ = run(ssh, "ls -t /root/backup_before_hestia/all_databases_*.sql 2>/dev/null | head -1")

    if backup_file:
        print(f"\n  Found backup: {backup_file}", flush=True)
        run(ssh, f"wc -l {backup_file}")

        # Restore
        print("\n  Restoring databases...", flush=True)
        code, out, err = run(ssh, f"mysql -u root < {backup_file} 2>&1", timeout=600)
        if code == 0:
            print("  Restore completed!", flush=True)
        else:
            print(f"  Restore had issues (exit: {code})", flush=True)

        # Verify
        print("\n  Databases after restore:", flush=True)
        run(ssh, "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null")

        # Recreate DB user
        db_user = DB_USER
        db_pass = DB_PASS
        databases = [
            'namaa_erp', 'namaa_jadal', 'staging', 'namaa_khaldon',
            'fahras_db', 'access_db', 'tenanttenantJadel', 'dictionary',
            'baseel', 'ahwal', 'erb_digram', 'sass', 'tazej_food', 'bugzilla',
        ]
        grant_cmds = []
        for db in databases:
            grant_cmds.append(f"GRANT ALL PRIVILEGES ON `{db}`.* TO '{db_user}'@'localhost';")

        run(ssh, f"""mysql -u root -e "
CREATE USER IF NOT EXISTS '{db_user}'@'localhost' IDENTIFIED BY '{db_pass}';
{' '.join(grant_cmds)}
FLUSH PRIVILEGES;
" 2>/dev/null""")
        print("  DB user restored!", flush=True)
    else:
        print("  WARNING: No backup file found!", flush=True)

    # ══════════════════════════════════════════════════════════
    # PHASE 2: Install Webmin
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 2: Install Webmin")
    print("=" * 60, flush=True)

    # Add Webmin repo manually (bypass interactive setup script)
    print("  Adding Webmin repo...", flush=True)
    run(ssh, (
        "curl -fsSL https://download.webmin.com/jcameron-key.asc "
        "| gpg --batch --yes --dearmor -o /usr/share/keyrings/webmin-keyring.gpg 2>/dev/null"
    ))
    run(ssh, "file /usr/share/keyrings/webmin-keyring.gpg")
    run(ssh, (
        'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/webmin-keyring.gpg] '
        'https://download.webmin.com/download/repository sarge contrib" '
        '> /etc/apt/sources.list.d/webmin.list'
    ))

    run(ssh, "apt-get update -qq 2>&1 | tail -5", timeout=60)

    # Check if webmin is available
    run(ssh, "apt-cache policy webmin 2>/dev/null | head -5")

    # Install Webmin
    print("\n  Installing Webmin (may take 2-5 minutes)...", flush=True)
    run(ssh, "apt-get install -y webmin 2>&1 | tail -20", timeout=600)

    # Start Webmin
    run(ssh, "systemctl enable webmin 2>/dev/null; true")
    run(ssh, "systemctl start webmin 2>/dev/null")
    _, status, _ = run(ssh, "systemctl is-active webmin")
    print(f"\n  Webmin: {status.strip()}", flush=True)

    if status.strip() != 'active':
        print("  Webmin not active, checking...", flush=True)
        run(ssh, "journalctl -u webmin --no-pager -n 10 2>&1")
        # Try starting directly
        run(ssh, "/etc/init.d/webmin start 2>&1; true")
        time.sleep(3)
        run(ssh, "ss -tlnp | grep :10000")

    # Check port
    run(ssh, "ss -tlnp | grep :10000 || echo 'Port 10000 NOT listening'")

    # ══════════════════════════════════════════════════════════
    # PHASE 3: Final Verification
    # ══════════════════════════════════════════════════════════
    print("\n" + "=" * 60)
    print("  PHASE 3: Final Verification")
    print("=" * 60, flush=True)

    for svc in ['mariadb', 'apache2', 'webmin']:
        _, status, _ = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        result = "RUNNING" if status.strip() == 'active' else "NOT RUNNING"
        print(f"    {svc}: {result}", flush=True)

    # UFW
    run(ssh, "ufw status | head -10")

    # Databases
    run(ssh, "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null")

    # Web check
    run(ssh, "curl -sI http://localhost/ 2>/dev/null | head -3; true")

    # Summary
    print("\n" + "=" * 60)
    print("  ALL DONE!")
    print("=" * 60)
    print(f"")
    print(f"  Webmin:     https://{NEW_HOST}:10000")
    print(f"  User:       root")
    print(f"  Password:   {NEW_PASS}")
    print(f"")
    print(f"  phpMyAdmin: http://{NEW_HOST}/phpmyadmin")
    print(f"  DB User:    {DB_USER} / {DB_PASS}")
    print(f"")
    print(f"  Webmin Features:")
    print(f"    - File Manager: browse/edit all server files")
    print(f"    - MySQL/MariaDB: manage databases")
    print(f"    - Apache: manage virtual hosts")
    print(f"    - Firewall: manage UFW rules")
    print(f"    - System: monitor CPU, RAM, disk")
    print(f"    - Terminal: web-based SSH terminal")
    print(f"")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
