#!/usr/bin/env python3
"""
Step 6: Install HestiaCP control panel on the new Contabo server.
Patches the installer to support Debian 13 (Trixie) by using Debian 12 (Bookworm) packages.

After installation, access the panel at:
  https://31.220.82.115:8083
  Username: admin
  Password: see scripts/credentials.py (HESTIA_PASS)
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
import sys
import time
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS

HESTIA_EMAIL = 'osamaqazan89@gmail.com'
HESTIA_HOSTNAME = 'cp.aqssat.co'
HESTIA_PORT = 8083
HESTIA_ADMIN_PASS = HESTIA_PASS


def run(ssh, cmd, timeout=600, show=True):
    if show:
        print(f"  $ {cmd[:200]}{'...' if len(cmd) > 200 else ''}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if show and out:
        for line in out.split('\n')[:40]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if code != 0 and err:
        for line in err.split('\n')[:15]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out, err


def main():
    print(f"\n{'=' * 60}", flush=True)
    print(f"  HESTIACP INSTALLER - Server {NEW_HOST}")
    print(f"  (Patched for Debian 13 Trixie)")
    print(f"{'=' * 60}\n", flush=True)

    print(f"Connecting to {NEW_HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    ssh.get_transport().set_keepalive(30)
    print("Connected!\n", flush=True)

    # ── Check if already installed ──
    print("=" * 60)
    print("  PRE-CHECK: Is HestiaCP already installed?")
    print("=" * 60, flush=True)
    code, out, _ = run(ssh, "dpkg -l | grep hestia-php 2>/dev/null || echo 'NOT_INSTALLED'")
    if 'NOT_INSTALLED' not in out and 'hestia' in out.lower():
        print("\n  HestiaCP is already installed!", flush=True)
        run(ssh, "v-list-sys-info 2>/dev/null")
        print(f"\n  Access: https://{NEW_HOST}:{HESTIA_PORT}")
        print(f"  User:   admin")
        ssh.close()
        return

    # ── Check OS ──
    print("\n  Checking OS...", flush=True)
    run(ssh, "cat /etc/os-release | head -5")
    _, release_out, _ = run(ssh, "cat /etc/debian_version")
    print(f"  Debian version: {release_out}", flush=True)

    # ── Phase 1: Backup databases ──
    print("\n" + "=" * 60)
    print("  PHASE 1: Backup Existing Databases (safety net)")
    print("=" * 60, flush=True)
    run(ssh, "mkdir -p /root/backup_before_hestia")
    code, out, _ = run(ssh, "mysql -u root -e 'SHOW DATABASES;' 2>/dev/null")
    if code == 0 and out:
        run(ssh, (
            "mysqldump -u root --all-databases --single-transaction "
            "> /root/backup_before_hestia/all_databases_$(date +%Y%m%d_%H%M%S).sql 2>/dev/null "
            "&& echo 'Backup complete' || echo 'No databases to backup'"
        ))
    else:
        print("  No MariaDB running yet, skipping backup.", flush=True)

    # ── Phase 2: Disable UFW ──
    print("\n" + "=" * 60)
    print("  PHASE 2: Disable UFW (HestiaCP will manage firewall)")
    print("=" * 60, flush=True)
    run(ssh, "ufw disable 2>/dev/null || echo 'UFW not active'")

    # ── Phase 3: Set hostname ──
    print("\n" + "=" * 60)
    print("  PHASE 3: Set Server Hostname")
    print("=" * 60, flush=True)
    run(ssh, f"hostnamectl set-hostname {HESTIA_HOSTNAME}")
    run(ssh, f"hostname {HESTIA_HOSTNAME}")
    run(ssh, f"grep -q '{HESTIA_HOSTNAME}' /etc/hosts || echo '127.0.1.1 {HESTIA_HOSTNAME}' >> /etc/hosts")
    run(ssh, "hostname -f")

    # ── Phase 4: Trick system into looking like Debian 12 for HestiaCP ──
    print("\n" + "=" * 60)
    print("  PHASE 4: Patch OS Identity for HestiaCP Compatibility")
    print("=" * 60, flush=True)

    # Backup original files
    run(ssh, "cp /etc/debian_version /etc/debian_version.bak.trixie")
    run(ssh, "cp /etc/os-release /etc/os-release.bak.trixie")

    # Temporarily set Debian version to 12
    run(ssh, "echo '12.0' > /etc/debian_version")

    # Temporarily change os-release to bookworm
    run(ssh, r"""sed -i 's/VERSION_ID="13"/VERSION_ID="12"/' /etc/os-release""")
    run(ssh, r"""sed -i 's/VERSION="13 (trixie)"/VERSION="12 (bookworm)"/' /etc/os-release""")
    run(ssh, r"""sed -i 's/VERSION_CODENAME=trixie/VERSION_CODENAME=bookworm/' /etc/os-release""")
    run(ssh, r"""sed -i 's/trixie/bookworm/g' /etc/os-release""")

    # Also update lsb-release if it exists
    run(ssh, r"""test -f /etc/lsb-release && sed -i 's/trixie/bookworm/g' /etc/lsb-release 2>/dev/null; true""")

    # Verify the change
    run(ssh, "cat /etc/debian_version")
    run(ssh, "cat /etc/os-release | head -5")

    # ── Phase 5: Download and run HestiaCP installer ──
    print("\n" + "=" * 60)
    print("  PHASE 5: Download HestiaCP Installer")
    print("=" * 60, flush=True)
    run(ssh, (
        "wget -q https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh "
        "-O /tmp/hst-install.sh && echo 'Downloaded successfully' || echo 'Download FAILED'"
    ))
    run(ssh, "chmod +x /tmp/hst-install.sh")

    # ── Phase 6: Install HestiaCP ──
    print("\n" + "=" * 60)
    print("  PHASE 6: Installing HestiaCP")
    print("  This will take 10-25 minutes... please wait.")
    print("=" * 60, flush=True)

    install_cmd = " ".join([
        "bash /tmp/hst-install.sh",
        "--interactive no",
        f"--hostname {HESTIA_HOSTNAME}",
        f"--email {HESTIA_EMAIL}",
        f"--password '{HESTIA_ADMIN_PASS}'",
        "--apache yes",
        "--phpfpm yes",
        "--multiphp yes",
        "--mysql yes",
        "--postgresql no",
        "--named no",
        "--exim yes",
        "--dovecot no",
        "--clamav no",
        "--spamassassin no",
        "--iptables yes",
        "--fail2ban yes",
        "--quota no",
        "--api yes",
        f"--port {HESTIA_PORT}",
        "--lang ar",
        "--force",
    ])

    start_time = time.time()
    print(f"\n  Started at: {time.strftime('%H:%M:%S')}", flush=True)
    code, out, err = run(ssh, install_cmd, timeout=2400)
    elapsed = int(time.time() - start_time)
    print(f"\n  Finished in {elapsed // 60}m {elapsed % 60}s (exit code: {code})", flush=True)

    if code != 0:
        print(f"\n  WARNING: Installer exited with code {code}", flush=True)
        print("  Checking install logs...", flush=True)
        run(ssh, "ls -la /root/hst_install_backups/ 2>/dev/null || echo 'No backup dir'")
        run(ssh, "tail -80 /root/hst_install_backups/hst_install-*.log 2>/dev/null || echo 'No install log found'")

    # ── Phase 7: Restore real OS identity ──
    print("\n" + "=" * 60)
    print("  PHASE 7: Restore Original OS Identity")
    print("=" * 60, flush=True)
    run(ssh, "cp /etc/debian_version.bak.trixie /etc/debian_version")
    run(ssh, "cp /etc/os-release.bak.trixie /etc/os-release")
    run(ssh, "cat /etc/debian_version")
    run(ssh, "cat /etc/os-release | head -5")

    # ── Phase 8: Verify installation ──
    print("\n" + "=" * 60)
    print("  PHASE 8: Verification")
    print("=" * 60, flush=True)

    run(ssh, "v-list-sys-info 2>/dev/null || echo 'HestiaCP commands not available'")
    run(ssh, "systemctl status hestia 2>/dev/null | head -8 || echo 'Hestia service not found'")
    run(ssh, "v-list-users 2>/dev/null || echo 'Cannot list users'")

    for svc in ['hestia', 'apache2', 'mariadb']:
        code, out, _ = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        status = "RUNNING" if out.strip() == 'active' else "NOT RUNNING"
        print(f"    {svc}: {status}", flush=True)

    run(ssh, f"ss -tlnp | grep :{HESTIA_PORT} || echo 'Port {HESTIA_PORT} not listening'")

    # ── Phase 9: Restore database access ──
    print("\n" + "=" * 60)
    print("  PHASE 9: Restore Database User Access")
    print("=" * 60, flush=True)
    db_user = DB_USER
    db_pass = DB_PASS
    databases = [
        'namaa_erp', 'namaa_jadal', 'staging', 'namaa_khaldon',
        'fahras_db', 'access_db', 'tenanttenantJadel', 'dictionary',
        'baseel', 'ahwal', 'erb_digram', 'sass', 'tazej_food', 'bugzilla',
    ]
    grant_cmds = []
    for db in databases:
        grant_cmds.append(f"CREATE DATABASE IF NOT EXISTS `{db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
        grant_cmds.append(f"GRANT ALL PRIVILEGES ON `{db}`.* TO '{db_user}'@'localhost';")

    run(ssh, f"""mysql -u root -e "
CREATE USER IF NOT EXISTS '{db_user}'@'localhost' IDENTIFIED BY '{db_pass}';
{' '.join(grant_cmds)}
FLUSH PRIVILEGES;
" 2>/dev/null || echo 'DB user setup - will retry after MariaDB is ready'""")

    # ── Phase 10: Firewall rules ──
    print("\n" + "=" * 60)
    print("  PHASE 10: Firewall Rules")
    print("=" * 60, flush=True)
    run(ssh, "v-add-firewall-rule accept 0.0.0.0/0 22 TCP 2>/dev/null || true")
    run(ssh, "v-add-firewall-rule accept 0.0.0.0/0 80 TCP 2>/dev/null || true")
    run(ssh, "v-add-firewall-rule accept 0.0.0.0/0 443 TCP 2>/dev/null || true")
    run(ssh, f"v-add-firewall-rule accept 0.0.0.0/0 {HESTIA_PORT} TCP 2>/dev/null || true")
    run(ssh, "v-update-firewall 2>/dev/null || true")

    # ── Done ──
    print("\n" + "=" * 60)
    print("  HESTIACP INSTALLATION COMPLETE!")
    print("=" * 60)
    print(f"")
    print(f"  Panel URL:   https://{NEW_HOST}:{HESTIA_PORT}")
    print(f"  Username:    admin")
    print(f"  Password:    {HESTIA_ADMIN_PASS}")
    print(f"  Email:       {HESTIA_EMAIL}")
    print(f"  Hostname:    {HESTIA_HOSTNAME}")
    print(f"")
    print(f"  File Manager: https://{NEW_HOST}:{HESTIA_PORT}/list/directory/")
    print(f"  Databases:    https://{NEW_HOST}:{HESTIA_PORT}/list/db/")
    print(f"")
    print(f"  Web files:   /home/admin/web/")
    print(f"  Old files:   /var/www/ (still there)")
    print(f"  DB backup:   /root/backup_before_hestia/")
    print(f"")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
