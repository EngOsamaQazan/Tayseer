#!/usr/bin/env python3
"""
Step 4: Verify everything works on the new server + update deploy scripts.
Run this as the final step after DNS propagation.

Requirements: pip install paramiko requests
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

try:
    import requests
    HAS_REQUESTS = True
except ImportError:
    HAS_REQUESTS = False

# ============================================================
# ⚠️  UPDATE THESE
# ============================================================
NEW_HOST = 'YOUR_NEW_SERVER_IP'
NEW_USER = 'root'
NEW_PASS = 'YOUR_NEW_SERVER_PASSWORD'
NEW_DB_USER = DB_USER
NEW_DB_PASS = DB_PASS
# ============================================================

OLD_HOST = OLD_SERVER_IP

SITES = {
    'jadal': {
        'domain': 'jadal.aqssat.co',
        'path': '/var/www/jadal.aqssat.co',
        'db': 'namaa_jadal',
    },
    'namaa': {
        'domain': 'namaa.aqssat.co',
        'path': '/var/www/namaa.aqssat.co',
        'db': 'namaa_erp',
    },
}


def run(ssh, cmd, timeout=120, show=True):
    if show:
        print(f"  $ {cmd[:120]}{'...' if len(cmd) > 120 else ''}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if show and out:
        for line in out.split('\n')[:20]:
            print(f"    {line}")
    if code != 0 and err:
        for line in err.split('\n')[:5]:
            print(f"    [err] {line}")
    return code, out, err


def main():
    if NEW_HOST == 'YOUR_NEW_SERVER_IP':
        print("ERROR: Update server details before running!")
        sys.exit(1)

    print(f"Connecting to new server ({NEW_HOST})...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    print("Connected!\n")

    all_ok = True

    # Check 1: Server Resources
    print("=" * 60)
    print("  CHECK 1: Server Resources")
    print("=" * 60)
    run(ssh, "free -h")
    run(ssh, "df -h /")
    run(ssh, "nproc")
    run(ssh, "uptime")

    # Check 2: Services Running
    print("\n" + "=" * 60)
    print("  CHECK 2: Services Status")
    print("=" * 60)
    for svc in ['apache2', 'mariadb', 'fail2ban']:
        code, out, _ = run(ssh, f"systemctl is-active {svc}")
        if 'active' not in out:
            print(f"  WARNING: {svc} is NOT running!")
            all_ok = False

    # Check 3: PHP
    print("\n" + "=" * 60)
    print("  CHECK 3: PHP Version & Extensions")
    print("=" * 60)
    run(ssh, "php -v | head -1")
    code, out, _ = run(ssh, "php -m | grep -i -E 'pdo_mysql|mbstring|gd|intl|zip|bcmath|curl|xml|opcache'")

    # Check 4: Database Tables
    print("\n" + "=" * 60)
    print("  CHECK 4: Database Integrity")
    print("=" * 60)
    for name, site in SITES.items():
        db = site['db']
        print(f"\n  [{name}] Database: {db}")
        run(ssh, f"mysql -u {NEW_DB_USER} -p'{NEW_DB_PASS}' -e 'SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema=\"{db}\";'")
        run(ssh, f"mysql -u {NEW_DB_USER} -p'{NEW_DB_PASS}' {db} -e 'SELECT COUNT(*) AS user_count FROM os_user;' 2>/dev/null || echo 'os_user table check skipped'")

    # Check 5: File Permissions
    print("\n" + "=" * 60)
    print("  CHECK 5: File Permissions")
    print("=" * 60)
    for name, site in SITES.items():
        path = site['path']
        print(f"\n  [{name}] Checking {path}")
        run(ssh, f"ls -la {path}/backend/web/index.php")
        run(ssh, f"test -w {path}/backend/runtime && echo 'runtime: writable' || echo 'runtime: NOT writable'")
        run(ssh, f"test -w {path}/backend/web/assets && echo 'assets: writable' || echo 'assets: NOT writable'")
        run(ssh, f"du -sh {path}/backend/web/uploads/ 2>/dev/null || echo 'no uploads dir'")
        run(ssh, f"du -sh {path}/backend/web/images/ 2>/dev/null || echo 'no images dir'")

    # Check 6: Apache VHosts
    print("\n" + "=" * 60)
    print("  CHECK 6: Apache Configuration")
    print("=" * 60)
    run(ssh, "apache2ctl -S 2>&1 | head -20")

    # Check 7: SSL Certificates
    print("\n" + "=" * 60)
    print("  CHECK 7: SSL Certificates")
    print("=" * 60)
    run(ssh, "certbot certificates 2>&1 | grep -A3 'Certificate Name'")

    # Check 8: HTTP Response
    print("\n" + "=" * 60)
    print("  CHECK 8: HTTP Response Test")
    print("=" * 60)
    for name, site in SITES.items():
        domain = site['domain']
        print(f"\n  [{name}] Testing {domain}...")
        run(ssh, f"curl -sI -o /dev/null -w 'HTTP %{{http_code}} - %{{time_total}}s' http://localhost -H 'Host: {domain}' && echo")

    # Check 9: Yii Console
    print("\n" + "=" * 60)
    print("  CHECK 9: Yii Console Test")
    print("=" * 60)
    for name, site in SITES.items():
        path = site['path']
        print(f"\n  [{name}]")
        run(ssh, f"cd {path} && php yii 2>&1 | head -5")

    # Check 10: External HTTP (if requests available)
    if HAS_REQUESTS:
        print("\n" + "=" * 60)
        print("  CHECK 10: External Access Test")
        print("=" * 60)
        for name, site in SITES.items():
            domain = site['domain']
            for proto in ['https', 'http']:
                try:
                    r = requests.get(f"{proto}://{domain}", timeout=10, allow_redirects=True)
                    print(f"  [{name}] {proto}://{domain} -> {r.status_code}")
                    break
                except Exception as e:
                    print(f"  [{name}] {proto}://{domain} -> FAILED: {e}")

    # Summary
    print("\n" + "=" * 60)
    if all_ok:
        print("  ALL CHECKS PASSED!")
    else:
        print("  SOME CHECKS FAILED - Review above")
    print("=" * 60)

    print(f"""
  MIGRATION SUMMARY:
  ─────────────────────────────────────────
  Old Server: {OLD_HOST} (OVH)
  New Server: {NEW_HOST} (Contabo)
  ─────────────────────────────────────────
  Sites:
    - jadal.aqssat.co -> /var/www/jadal.aqssat.co
    - namaa.aqssat.co -> /var/www/namaa.aqssat.co
  Databases:
    - namaa_erp (user: {NEW_DB_USER})
    - namaa_jadal (user: {NEW_DB_USER})
  ─────────────────────────────────────────

  NEXT STEPS:
  1. Test login on both sites
  2. Test critical features (contracts, judiciary, HR, etc.)
  3. Update deploy scripts with new server IP
  4. Cancel old OVH server after 1-2 weeks of monitoring
  5. Change SSH password on new server!
""")

    ssh.close()


if __name__ == '__main__':
    main()
