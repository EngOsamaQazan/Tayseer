#!/usr/bin/env python3
"""Check install log, restart services, diagnose HestiaCP failure."""
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

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS


def run(ssh, cmd, timeout=60):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err


def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)

    # Restart services
    print("=== RESTARTING CRITICAL SERVICES ===")
    for svc in ['mariadb', 'apache2']:
        run(ssh, f'systemctl start {svc}')
        out, _ = run(ssh, f'systemctl is-active {svc}')
        print(f"  {svc}: {out}")

    # Read full install log
    print("\n=== FULL HESTIA INSTALL LOG ===")
    out, _ = run(ssh, 'cat /root/hestia_install.log 2>/dev/null')
    if out:
        for line in out.split('\n'):
            try:
                print(line)
            except UnicodeEncodeError:
                pass
    else:
        print("  (empty)")

    # Read HestiaCP's own log
    print("\n=== HESTIACP INSTALLER LOG (latest) ===")
    out, _ = run(ssh, 'ls -t /root/hst_install_backups/hst_install-*.log 2>/dev/null | head -1')
    if out:
        log_content, _ = run(ssh, f'cat {out}')
        for line in log_content.split('\n')[:100]:
            try:
                print(line)
            except UnicodeEncodeError:
                pass

    # Check apt sources
    print("\n=== APT SOURCES ===")
    out, _ = run(ssh, 'cat /etc/apt/sources.list.d/*.list 2>/dev/null')
    print(out if out else "  (no .list files)")

    # Check hestia package availability
    print("\n=== HESTIA PACKAGE CHECK ===")
    out, err = run(ssh, 'apt-cache policy hestia 2>/dev/null')
    print(out if out else "  Not available")
    if err:
        print(f"  Error: {err}")

    # Check gpg keys
    print("\n=== GPG KEYRING ===")
    out, _ = run(ssh, 'ls -la /usr/share/keyrings/hestia* 2>/dev/null || echo "No hestia keyring"')
    print(out)

    ssh.close()


if __name__ == '__main__':
    main()
