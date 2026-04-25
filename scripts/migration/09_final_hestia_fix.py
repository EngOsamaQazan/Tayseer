#!/usr/bin/env python3
"""
Final HestiaCP fix: proper GPG keys, clean repos, proper install.
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

    # ── Step 1: TOTAL CLEANUP ──
    print("=" * 60)
    print("  STEP 1: Total Cleanup")
    print("=" * 60, flush=True)
    run(ssh, "pkill -9 -f 'hst-install' 2>/dev/null; true")
    run(ssh, "sleep 2")

    # Remove ALL third-party repo files
    run(ssh, "rm -f /etc/apt/sources.list.d/hestia.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/nginx.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/mariadb.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/apache2.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/sury.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/php.list 2>/dev/null; true")
    # Keep the original sury-php list
    run(ssh, "ls /etc/apt/sources.list.d/", timeout=10)

    # Clean hestia artifacts
    run(ssh, "rm -rf /usr/local/hestia 2>/dev/null; true")
    run(ssh, "rm -f /root/hst-install-debian.sh 2>/dev/null; true")
    run(ssh, "userdel -r admin 2>/dev/null; true")
    run(ssh, "groupdel admin 2>/dev/null; true")

    # Restore OS identity
    run(ssh, "test -f /etc/debian_version.bak.trixie && cp /etc/debian_version.bak.trixie /etc/debian_version; true")
    run(ssh, "test -f /etc/os-release.bak.trixie && cp /etc/os-release.bak.trixie /etc/os-release; true")

    # ── Step 2: Fix broken packages ──
    print("\n" + "=" * 60)
    print("  STEP 2: Fix Broken Packages")
    print("=" * 60, flush=True)
    run(ssh, "dpkg --configure -a 2>&1 | tail -10", timeout=120)
    run(ssh, "apt-get update -qq 2>&1 | tail -5", timeout=60)
    run(ssh, "apt-get install -y --fix-broken 2>&1 | tail -10", timeout=120)

    # ── Step 3: Fix MariaDB ──
    print("\n" + "=" * 60)
    print("  STEP 3: Fix MariaDB")
    print("=" * 60, flush=True)
    run(ssh, "systemctl start mariadb 2>&1")
    _, status, _ = run(ssh, "systemctl is-active mariadb")
    if status.strip() != 'active':
        print("  MariaDB not starting, checking...", flush=True)
        run(ssh, "journalctl -u mariadb --no-pager -n 15 2>&1")
        run(ssh, "apt-get install -y mariadb-server 2>&1 | tail -10", timeout=120)
        run(ssh, "systemctl start mariadb 2>&1")
        _, status, _ = run(ssh, "systemctl is-active mariadb")
    print(f"\n  MariaDB: {status.strip()}", flush=True)

    # Fix Apache
    run(ssh, "systemctl start apache2 2>&1; true")
    _, status, _ = run(ssh, "systemctl is-active apache2")
    print(f"  Apache:  {status.strip()}", flush=True)

    # ── Step 4: Set OS to bookworm ──
    print("\n" + "=" * 60)
    print("  STEP 4: Patch OS -> Debian 12 (Bookworm)")
    print("=" * 60, flush=True)
    run(ssh, "cp /etc/debian_version /etc/debian_version.bak.trixie 2>/dev/null; true")
    run(ssh, "cp /etc/os-release /etc/os-release.bak.trixie 2>/dev/null; true")
    run(ssh, "echo '12.0' > /etc/debian_version")
    run(ssh, '''sed -i 's/VERSION_ID="13"/VERSION_ID="12"/' /etc/os-release''')
    run(ssh, '''sed -i 's/VERSION="13 (trixie)"/VERSION="12 (bookworm)"/' /etc/os-release''')
    run(ssh, '''sed -i 's/VERSION_CODENAME=trixie/VERSION_CODENAME=bookworm/' /etc/os-release''')
    run(ssh, '''sed -i 's/trixie/bookworm/g' /etc/os-release''')

    # ── Step 5: Setup repos with PROPER GPG keys ──
    print("\n" + "=" * 60)
    print("  STEP 5: Setup Repos with Proper GPG Keys")
    print("=" * 60, flush=True)

    # HestiaCP key - use curl + gpg --batch --dearmor
    print("\n  [HestiaCP]", flush=True)
    run(ssh, "rm -f /usr/share/keyrings/hestia-keyring.gpg 2>/dev/null; true")
    run(ssh, (
        "curl -fsSL 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xA189E93654F0B0E5' "
        "2>/dev/null | gpg --batch --yes --dearmor -o /usr/share/keyrings/hestia-keyring.gpg"
    ))
    run(ssh, "file /usr/share/keyrings/hestia-keyring.gpg")
    run(ssh, (
        'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/hestia-keyring.gpg] '
        'https://apt.hestiacp.com/ bookworm main" > /etc/apt/sources.list.d/hestia.list'
    ))

    # NGINX key
    print("\n  [NGINX]", flush=True)
    run(ssh, (
        "curl -fsSL 'https://nginx.org/keys/nginx_signing.key' "
        "2>/dev/null | gpg --batch --yes --dearmor -o /usr/share/keyrings/nginx-keyring.gpg"
    ))
    run(ssh, (
        'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/nginx-keyring.gpg] '
        'https://nginx.org/packages/mainline/debian/ bookworm nginx" > /etc/apt/sources.list.d/nginx.list'
    ))

    # PHP (Sury) key
    print("\n  [PHP Sury]", flush=True)
    run(ssh, (
        "curl -fsSL 'https://packages.sury.org/php/apt.gpg' "
        "2>/dev/null | gpg --batch --yes --dearmor -o /usr/share/keyrings/sury-keyring.gpg"
    ))
    run(ssh, (
        'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/sury-keyring.gpg] '
        'https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/php.list'
    ))

    # Apache2 (Sury) key
    print("\n  [Apache2 Sury]", flush=True)
    run(ssh, (
        "curl -fsSL 'https://packages.sury.org/apache2/apt.gpg' "
        "2>/dev/null | gpg --batch --yes --dearmor -o /usr/share/keyrings/apache2-keyring.gpg"
    ))
    run(ssh, (
        'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/apache2-keyring.gpg] '
        'https://packages.sury.org/apache2/ bookworm main" > /etc/apt/sources.list.d/apache2.list'
    ))

    # MariaDB key
    print("\n  [MariaDB]", flush=True)
    run(ssh, (
        "curl -fsSL 'https://mariadb.org/mariadb_release_signing_key.asc' "
        "2>/dev/null | gpg --batch --yes --dearmor -o /usr/share/keyrings/mariadb-keyring.gpg"
    ))
    run(ssh, (
        'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/mariadb-keyring.gpg] '
        'https://dlm.mariadb.com/repo/mariadb-server/11.4/repo/debian bookworm main" > /etc/apt/sources.list.d/mariadb.list'
    ))

    # Remove any old trixie php list
    run(ssh, "rm -f /etc/apt/sources.list.d/sury-php.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/sury.list 2>/dev/null; true")

    # Check all keyrings
    print("\n  Keyring files:", flush=True)
    run(ssh, "ls -la /usr/share/keyrings/*-keyring.gpg 2>/dev/null")

    # Update apt
    print("\n  Updating apt...", flush=True)
    run(ssh, "apt-get update 2>&1 | tail -10", timeout=60)

    # Verify hestia is available
    run(ssh, "apt-cache policy hestia 2>/dev/null | head -5")

    # Try installing hestia package directly first
    print("\n  Testing hestia package install...", flush=True)
    code, out, err = run(ssh, "apt-get install -y --dry-run hestia hestia-nginx hestia-php 2>&1 | tail -20", timeout=60)

    # ── Step 6: Run installer ──
    print("\n" + "=" * 60)
    print("  STEP 6: Run HestiaCP Installer")
    print("=" * 60, flush=True)

    run(ssh, (
        "wget -q https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install-debian.sh "
        "-O /root/hst-install-debian.sh"
    ))
    run(ssh, "chmod +x /root/hst-install-debian.sh")

    # Patch installer
    run(ssh, r"""sed -i '2i force="yes"' /root/hst-install-debian.sh""")
    run(ssh, r"""sed -i 's/if \[ -n "\$conflicts" \] && \[ -z "\$force" \]; then/if false; then/' /root/hst-install-debian.sh""")
    # Also skip the netplan check
    run(ssh, r"""sed -i 's/if \[ -d \/etc\/netplan \] && \[ -z "\$force" \]; then/if false; then/' /root/hst-install-debian.sh""")

    install_cmd = " ".join([
        "bash /root/hst-install-debian.sh",
        "--interactive no",
        "--hostname cp.aqssat.co",
        "--email osamaqazan89@gmail.com",
        "--username admin",
        "--password HESTIA_PASS",
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
        "--port 8083",
        "--lang ar",
        "--force",
        "</dev/null",
    ])

    start = time.time()
    print(f"\n  Started: {time.strftime('%H:%M:%S')}", flush=True)
    print("  This takes 10-25 minutes...", flush=True)
    code, out, err = run(ssh, install_cmd, timeout=2400)
    elapsed = int(time.time() - start)
    print(f"\n  Done in {elapsed//60}m {elapsed%60}s (exit: {code})", flush=True)

    if code != 0:
        print("\n  Checking latest install log...", flush=True)
        run(ssh, "ls -t /root/hst_install_backups/hst_install-*.log 2>/dev/null | head -1")
        run(ssh, "tail -30 $(ls -t /root/hst_install_backups/hst_install-*.log 2>/dev/null | head -1) 2>/dev/null")

    # ── Step 7: Restore & Verify ──
    print("\n" + "=" * 60)
    print("  STEP 7: Restore OS & Verify")
    print("=" * 60, flush=True)
    run(ssh, "test -f /etc/debian_version.bak.trixie && cp /etc/debian_version.bak.trixie /etc/debian_version; true")
    run(ssh, "test -f /etc/os-release.bak.trixie && cp /etc/os-release.bak.trixie /etc/os-release; true")

    for svc in ['hestia', 'apache2', 'mariadb']:
        _, status, _ = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        result = "RUNNING" if status.strip() == 'active' else "NOT RUNNING"
        print(f"    {svc}: {result}", flush=True)

    run(ssh, "ss -tlnp | grep :8083 || echo 'Port 8083 NOT listening'")

    print("\n" + "=" * 60)
    print(f"  Panel:    https://{NEW_HOST}:8083")
    print(f"  User:     admin")
    print(f"  Password: {HESTIA_PASS}")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
