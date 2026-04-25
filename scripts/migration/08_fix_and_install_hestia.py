#!/usr/bin/env python3
"""
Fix HestiaCP installation issues:
1. Fix MariaDB
2. Fix GPG key for HestiaCP repo (Debian 13 sqv compatibility)
3. Install HestiaCP packages
4. Re-run installer
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

    # ── Phase 1: Fix MariaDB ──
    print("=" * 60)
    print("  PHASE 1: Fix MariaDB")
    print("=" * 60, flush=True)

    run(ssh, "pkill -9 -f hst-install 2>/dev/null; true")
    run(ssh, "sleep 2")

    # Remove the broken mariadb.list that HestiaCP added (uses bookworm)
    run(ssh, "rm -f /etc/apt/sources.list.d/mariadb.list")
    # Remove nginx list too (bookworm)
    run(ssh, "rm -f /etc/apt/sources.list.d/nginx.list")
    # Remove apache2 sury list (bookworm)
    run(ssh, "rm -f /etc/apt/sources.list.d/apache2.list")
    # Keep only the original PHP sury list (trixie)
    run(ssh, "rm -f /etc/apt/sources.list.d/php.list")
    # Remove hestia list for now
    run(ssh, "rm -f /etc/apt/sources.list.d/hestia.list")

    # Restore OS identity to trixie first
    run(ssh, "test -f /etc/debian_version.bak.trixie && cp /etc/debian_version.bak.trixie /etc/debian_version; true")
    run(ssh, "test -f /etc/os-release.bak.trixie && cp /etc/os-release.bak.trixie /etc/os-release; true")

    run(ssh, "apt-get update -qq 2>&1 | tail -3")

    # Try starting MariaDB
    code, out, _ = run(ssh, "systemctl start mariadb 2>&1")
    if code != 0:
        run(ssh, "journalctl -u mariadb --no-pager -n 20")
        # Try reinstalling mariadb if needed
        run(ssh, "apt-get install -y --fix-broken 2>&1 | tail -5")
        run(ssh, "dpkg --configure -a 2>&1 | tail -5")
        run(ssh, "systemctl start mariadb 2>&1")

    code, out, _ = run(ssh, "systemctl is-active mariadb")
    print(f"\n  MariaDB: {out}", flush=True)

    # Fix Apache
    run(ssh, "systemctl start apache2 2>&1; true")
    code, out, _ = run(ssh, "systemctl is-active apache2")
    print(f"  Apache:  {out}", flush=True)

    # ── Phase 2: Clean up HestiaCP artifacts ──
    print("\n" + "=" * 60)
    print("  PHASE 2: Clean up previous HestiaCP attempts")
    print("=" * 60, flush=True)
    run(ssh, "rm -rf /usr/local/hestia 2>/dev/null; true")
    run(ssh, "rm -f /root/hst-install-debian.sh 2>/dev/null; true")
    run(ssh, "userdel -r admin 2>/dev/null; true")
    run(ssh, "groupdel admin 2>/dev/null; true")

    # ── Phase 3: Patch OS to bookworm ──
    print("\n" + "=" * 60)
    print("  PHASE 3: Patch OS -> Debian 12 (Bookworm)")
    print("=" * 60, flush=True)
    run(ssh, "echo '12.0' > /etc/debian_version")
    run(ssh, '''sed -i 's/VERSION_ID="13"/VERSION_ID="12"/' /etc/os-release''')
    run(ssh, '''sed -i 's/VERSION="13 (trixie)"/VERSION="12 (bookworm)"/' /etc/os-release''')
    run(ssh, '''sed -i 's/VERSION_CODENAME=trixie/VERSION_CODENAME=bookworm/' /etc/os-release''')
    run(ssh, '''sed -i 's/trixie/bookworm/g' /etc/os-release''')
    run(ssh, "cat /etc/debian_version && grep VERSION_CODENAME /etc/os-release")

    # ── Phase 4: Fix HestiaCP GPG key (Debian 13 sqv compatibility) ──
    print("\n" + "=" * 60)
    print("  PHASE 4: Fix HestiaCP GPG Key")
    print("=" * 60, flush=True)

    # Method: Download key from Ubuntu keyserver and convert with gpg --dearmor
    run(ssh, "rm -f /usr/share/keyrings/hestia-keyring.gpg 2>/dev/null")
    run(ssh, (
        "curl -fsSL 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xA189E93654F0B0E5' "
        "| gpg --dearmor -o /usr/share/keyrings/hestia-keyring.gpg 2>&1"
    ))
    run(ssh, "ls -la /usr/share/keyrings/hestia-keyring.gpg")
    run(ssh, "file /usr/share/keyrings/hestia-keyring.gpg")

    # Set up HestiaCP repo
    run(ssh, (
        'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/hestia-keyring.gpg] '
        'https://apt.hestiacp.com/ bookworm main" > /etc/apt/sources.list.d/hestia.list'
    ))

    # Also fix other repo keys using dearmor method
    for name, url_key, repo_line in [
        ('nginx', 'https://nginx.org/keys/nginx_signing.key',
         'deb [arch=amd64 signed-by=/usr/share/keyrings/nginx-keyring.gpg] https://nginx.org/packages/mainline/debian/ bookworm nginx'),
        ('sury-php', 'https://packages.sury.org/php/apt.gpg',
         'deb [arch=amd64 signed-by=/usr/share/keyrings/sury-keyring.gpg] https://packages.sury.org/php/ bookworm main'),
        ('sury-apache2', 'https://packages.sury.org/apache2/apt.gpg',
         'deb [arch=amd64 signed-by=/usr/share/keyrings/apache2-keyring.gpg] https://packages.sury.org/apache2/ bookworm main'),
        ('mariadb', 'https://mariadb.org/mariadb_release_signing_key.asc',
         'deb [arch=amd64 signed-by=/usr/share/keyrings/mariadb-keyring.gpg] https://dlm.mariadb.com/repo/mariadb-server/11.4/repo/debian bookworm main'),
    ]:
        keyring = f"/usr/share/keyrings/{name.replace('-', '')}-keyring.gpg"
        if 'sury' in name:
            keyring = f"/usr/share/keyrings/{name.split('-')[1]}-keyring.gpg"
            if name == 'sury-php':
                keyring = "/usr/share/keyrings/sury-keyring.gpg"
            else:
                keyring = "/usr/share/keyrings/apache2-keyring.gpg"
        elif name == 'nginx':
            keyring = "/usr/share/keyrings/nginx-keyring.gpg"
        elif name == 'mariadb':
            keyring = "/usr/share/keyrings/mariadb-keyring.gpg"

        print(f"\n  Setting up {name} repo...", flush=True)
        run(ssh, f"curl -fsSL '{url_key}' | gpg --dearmor -o {keyring} 2>&1")
        list_file = f"/etc/apt/sources.list.d/{name.split('-')[0]}.list"
        run(ssh, f'echo "{repo_line}" > {list_file}')

    # Remove duplicate PHP list (the old trixie one)
    run(ssh, "rm -f /etc/apt/sources.list.d/sury-php.list 2>/dev/null; true")

    # Update apt
    print("\n  Updating apt...", flush=True)
    run(ssh, "apt-get update 2>&1 | tail -15", timeout=120)

    # Check if hestia package is now available
    run(ssh, "apt-cache policy hestia 2>/dev/null | head -5")

    # ── Phase 5: Download and run patched installer ──
    print("\n" + "=" * 60)
    print("  PHASE 5: Download and Run HestiaCP Installer")
    print("=" * 60, flush=True)

    run(ssh, (
        "wget -q https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install-debian.sh "
        "-O /root/hst-install-debian.sh"
    ))
    run(ssh, "chmod +x /root/hst-install-debian.sh")

    # Patch: skip conflict check + set force
    run(ssh, r"""sed -i '2i force="yes"' /root/hst-install-debian.sh""")
    run(ssh, r"""sed -i 's/if \[ -n "\$conflicts" \] && \[ -z "\$force" \]; then/if false; then/' /root/hst-install-debian.sh""")

    # Run installer
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
    print(f"  This takes 10-25 minutes...\n", flush=True)
    code, out, err = run(ssh, install_cmd, timeout=2400)
    elapsed = int(time.time() - start)
    print(f"\n  Done in {elapsed//60}m {elapsed%60}s (exit: {code})", flush=True)

    # ── Phase 6: Restore OS + Verify ──
    print("\n" + "=" * 60)
    print("  PHASE 6: Restore OS & Verify")
    print("=" * 60, flush=True)
    run(ssh, "test -f /etc/debian_version.bak.trixie && cp /etc/debian_version.bak.trixie /etc/debian_version; true")
    run(ssh, "test -f /etc/os-release.bak.trixie && cp /etc/os-release.bak.trixie /etc/os-release; true")

    for svc in ['hestia', 'apache2', 'mariadb']:
        _, status, _ = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        print(f"    {svc}: {'RUNNING' if status.strip() == 'active' else 'NOT RUNNING'}", flush=True)

    run(ssh, f"ss -tlnp | grep :8083 || echo 'Port 8083 NOT listening'")

    # Show last part of log
    run(ssh, "tail -20 /root/hst_install_backups/hst_install-*.log 2>/dev/null | tail -20")

    print("\n" + "=" * 60)
    print(f"  Panel:    https://{NEW_HOST}:8083")
    print(f"  User:     admin")
    print(f"  Password: {HESTIA_PASS}")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
