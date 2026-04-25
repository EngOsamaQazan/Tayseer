#!/usr/bin/env python3
"""
Step 3: Setup ALL Apache VirtualHosts on new server.
SSL will be added after DNS is updated.
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
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS
ADMIN_EMAIL = 'zaxx44a7@gmail.com'

SITES = [
    {'domain': 'jadal.aqssat.co', 'docroot': '/var/www/jadal.aqssat.co/backend/web'},
    {'domain': 'namaa.aqssat.co', 'docroot': '/var/www/namaa.aqssat.co/backend/web'},
    {'domain': 'old.jadal.aqssat.co', 'docroot': '/var/www/old.jadal.aqssat.co/backend/web'},
    {'domain': 'old.namaa.aqssat.co', 'docroot': '/var/www/old.namaa.aqssat.co/backend/web'},
    {'domain': 'fahras.aqssat.co', 'docroot': '/var/www/fahras.aqssat.co'},
    {'domain': 'vite.jadal.aqssat.co', 'docroot': '/var/www/vite.jadal.aqssat.co'},
    {'domain': 'vite.namaa.aqssat.co', 'docroot': '/var/www/vite.namaa.aqssat.co'},
]


def run(ssh, cmd, timeout=300, show=True):
    if show:
        print(f"  $ {cmd[:150]}{'...' if len(cmd) > 150 else ''}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if show and out:
        for line in out.split('\n')[:20]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if code != 0 and err:
        for line in err.split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out, err


def main():
    print(f"Connecting to {NEW_HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    # Phase 1: VirtualHosts
    print("=" * 60)
    print("  PHASE 1: Apache VirtualHost Configuration")
    print("=" * 60, flush=True)

    for site in SITES:
        domain = site['domain']
        docroot = site['docroot']

        vhost = f"""<VirtualHost *:80>
    ServerName {domain}
    DocumentRoot {docroot}

    <Directory {docroot}>
        Require all granted
        AllowOverride All
        Options FollowSymLinks
    </Directory>

    ErrorLog ${{APACHE_LOG_DIR}}/{domain}-error.log
    CustomLog ${{APACHE_LOG_DIR}}/{domain}-access.log combined
</VirtualHost>"""

        conf_path = f"/etc/apache2/sites-available/{domain}.conf"
        print(f"\n  Creating VHost: {domain}", flush=True)

        run(ssh, f"cat > {conf_path} << 'VHOSTEOF'\n{vhost}\nVHOSTEOF")
        run(ssh, f"a2ensite {domain}.conf 2>/dev/null; true")

    run(ssh, "a2dissite 000-default.conf 2>/dev/null; true")

    # Phase 2: Security headers
    print("\n" + "=" * 60)
    print("  PHASE 2: Security Headers")
    print("=" * 60, flush=True)

    run(ssh, """cat > /etc/apache2/conf-available/security-headers.conf << 'EOF'
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
EOF
""")
    run(ssh, "a2enconf security-headers 2>/dev/null; true")

    # Test and restart
    print("\n  Testing Apache config...", flush=True)
    code, _, _ = run(ssh, "apache2ctl configtest")
    if code == 0:
        run(ssh, "systemctl reload apache2")
        print("\n  VHosts created and Apache reloaded!", flush=True)
    else:
        print("\n  WARNING: Apache config test failed! Check errors above.", flush=True)

    # Phase 3: Verify
    print("\n" + "=" * 60)
    print("  VERIFICATION")
    print("=" * 60, flush=True)
    run(ssh, "apache2ctl -S 2>&1 | head -25")

    for site in SITES:
        domain = site['domain']
        run(ssh, f"curl -sI -o /dev/null -w '{domain} -> HTTP %{{http_code}}\\n' http://localhost -H 'Host: {domain}' 2>/dev/null; true")

    print("\n" + "=" * 60)
    print("  VHOSTS SETUP COMPLETE!")
    print("  ")
    print("  NEXT STEPS:")
    print(f"  1. Update DNS A records to point to {NEW_HOST}")
    print("  2. Wait for DNS propagation (5-30 min)")
    print("  3. Run SSL setup: certbot --apache -d DOMAIN")
    print("  ")
    print("  DNS Records to update:")
    for site in SITES:
        print(f"    A  {site['domain']}  ->  {NEW_HOST}")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
