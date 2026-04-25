#!/usr/bin/env python3
"""
Install Webmin via .deb file (bypasses broken GPG repo on Debian 13).
Also fix DB user grants.
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


def run(ssh, cmd, timeout=180, show=True):
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

    # ── Fix DB user grants ──
    print("=" * 60)
    print("  FIX: Database User Grants")
    print("=" * 60, flush=True)

    db_user = DB_USER
    db_pass = DB_PASS
    databases = [
        'namaa_erp', 'namaa_jadal', 'staging', 'namaa_khaldon',
        'fahras_db', 'access_db', 'tenanttenantJadel', 'dictionary',
        'baseel', 'ahwal', 'erb_digram', 'sass', 'tazej_food', 'bugzilla',
    ]

    sql_cmds = f"CREATE USER IF NOT EXISTS '{db_user}'@'localhost' IDENTIFIED BY '{db_pass}';\n"
    for db in databases:
        sql_cmds += f"GRANT ALL PRIVILEGES ON \\`{db}\\`.* TO '{db_user}'@'localhost';\n"
    sql_cmds += "FLUSH PRIVILEGES;\n"

    run(ssh, f'mysql -u root -e "{sql_cmds}"')
    print("  DB user grants fixed!", flush=True)

    # ── Remove broken Webmin repo ──
    print("\n" + "=" * 60)
    print("  CLEANUP: Remove broken Webmin repo")
    print("=" * 60, flush=True)
    run(ssh, "rm -f /etc/apt/sources.list.d/webmin.list 2>/dev/null; true")
    run(ssh, "apt-get update -qq 2>&1 | tail -3", timeout=60)

    # ── Install Webmin from .deb ──
    print("\n" + "=" * 60)
    print("  INSTALL: Webmin via .deb package")
    print("=" * 60, flush=True)

    # Install dependencies first
    print("  Installing dependencies...", flush=True)
    run(ssh, (
        "apt-get install -y perl libnet-ssleay-perl openssl "
        "libauthen-pam-perl libpam-runtime libio-pty-perl "
        "apt-show-versions python3 unzip shared-mime-info "
        "2>&1 | tail -5"
    ), timeout=300)

    # Download latest Webmin .deb
    print("\n  Downloading Webmin .deb...", flush=True)
    run(ssh, (
        "cd /tmp && "
        "wget -q 'https://github.com/webmin/webmin/releases/download/2.302/webmin_2.302_all.deb' "
        "-O webmin.deb 2>&1 && echo 'Downloaded' || "
        "wget -q 'https://download.webmin.com/download/repository/pool/contrib/w/webmin/webmin_2.302_all.deb' "
        "-O webmin.deb 2>&1 && echo 'Downloaded (mirror)' || "
        "echo 'TRYING LATEST...' && "
        "curl -fsSL 'https://www.webmin.com/download/webmin-current.tar.gz' -o /tmp/webmin.tar.gz && echo 'Got tarball'"
    ), timeout=120)

    # Check what we got
    run(ssh, "ls -la /tmp/webmin.deb 2>/dev/null || ls -la /tmp/webmin.tar.gz 2>/dev/null || echo 'No download'")

    # Try deb install first
    code, out, _ = run(ssh, "test -f /tmp/webmin.deb && echo 'DEB_EXISTS' || echo 'NO_DEB'")
    if 'DEB_EXISTS' in out:
        print("\n  Installing from .deb...", flush=True)
        run(ssh, "dpkg -i /tmp/webmin.deb 2>&1 | tail -10", timeout=120)
        run(ssh, "apt-get install -f -y 2>&1 | tail -10", timeout=120)
    else:
        # Fallback: try Cockpit (in official Debian repos)
        print("\n  .deb not available. Installing Cockpit instead...", flush=True)
        run(ssh, "apt-get install -y cockpit 2>&1 | tail -15", timeout=300)
        run(ssh, "systemctl enable --now cockpit.socket 2>/dev/null; true")

    # ── Check what got installed ──
    print("\n" + "=" * 60)
    print("  VERIFY: Service Status")
    print("=" * 60, flush=True)

    # Check Webmin
    _, webmin_status, _ = run(ssh, "systemctl is-active webmin 2>/dev/null")
    webmin_running = webmin_status.strip() == 'active'

    if not webmin_running:
        # Try starting
        run(ssh, "systemctl start webmin 2>/dev/null; true")
        run(ssh, "/etc/webmin/start 2>/dev/null; true")
        time.sleep(3)
        _, webmin_status, _ = run(ssh, "systemctl is-active webmin 2>/dev/null")
        webmin_running = webmin_status.strip() == 'active'

    # Check Cockpit
    _, cockpit_status, _ = run(ssh, "systemctl is-active cockpit.socket 2>/dev/null")
    cockpit_running = cockpit_status.strip() == 'active'

    for svc in ['mariadb', 'apache2']:
        _, status, _ = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        print(f"    {svc}: {'RUNNING' if status.strip() == 'active' else 'NOT RUNNING'}", flush=True)

    if webmin_running:
        print(f"    webmin: RUNNING", flush=True)
        run(ssh, "ss -tlnp | grep :10000")
    elif cockpit_running:
        print(f"    cockpit: RUNNING", flush=True)
        run(ssh, "ss -tlnp | grep :9090")
    else:
        print(f"    webmin: NOT RUNNING", flush=True)
        print(f"    cockpit: NOT RUNNING", flush=True)

    # UFW
    if webmin_running:
        run(ssh, "ufw allow 10000 2>/dev/null; true")
    if cockpit_running:
        run(ssh, "ufw allow 9090 2>/dev/null; true")

    # Databases
    run(ssh, "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null")

    # ── Summary ──
    print("\n" + "=" * 60)
    print("  DONE!")
    print("=" * 60)

    if webmin_running:
        print(f"")
        print(f"  Webmin:     https://{NEW_HOST}:10000")
        print(f"  User:       root")
        print(f"  Password:   {NEW_PASS}")
    elif cockpit_running:
        print(f"")
        print(f"  Cockpit:    https://{NEW_HOST}:9090")
        print(f"  User:       root")
        print(f"  Password:   {NEW_PASS}")
    else:
        print(f"  No control panel installed successfully.")
        print(f"  phpMyAdmin is still available for DB management.")

    print(f"")
    print(f"  phpMyAdmin: http://{NEW_HOST}/phpmyadmin")
    print(f"  DB User:    {DB_USER} / {DB_PASS}")
    print(f"")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
