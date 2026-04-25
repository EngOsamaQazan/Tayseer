#!/usr/bin/env python3
"""Configure SSL for Webmin using existing Let's Encrypt certs."""
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
    print(f"  $ {cmd[:180]}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if out:
        for line in out.split('\n')[:15]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if code != 0 and err:
        for line in err.split('\n')[:5]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out


def main():
    print(f"\nConnecting to {NEW_HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    ssh.get_transport().set_keepalive(30)
    print("Connected!\n", flush=True)

    # Check existing certs
    print("=" * 60)
    print("  CHECK: Existing Let's Encrypt Certificates")
    print("=" * 60, flush=True)

    domains_with_certs = []
    code, out = run(ssh, "ls /etc/letsencrypt/live/ 2>/dev/null")
    if out:
        for d in out.split('\n'):
            d = d.strip()
            if d and d != 'README':
                code2, expiry = run(ssh, f"openssl x509 -enddate -noout -in /etc/letsencrypt/live/{d}/cert.pem 2>/dev/null")
                if code2 == 0:
                    domains_with_certs.append((d, expiry))

    # Check current Webmin config
    print("\n" + "=" * 60)
    print("  CHECK: Webmin SSL Config")
    print("=" * 60, flush=True)
    run(ssh, "grep -E 'ssl|certfile|keyfile|extracas' /etc/webmin/miniserv.conf 2>/dev/null")

    # Try to get a cert for cp.aqssat.co first
    print("\n" + "=" * 60)
    print("  ATTEMPT: Get Let's Encrypt cert for cp.aqssat.co")
    print("=" * 60, flush=True)

    # Check if DNS resolves
    code, dns = run(ssh, "dig +short cp.aqssat.co A 2>/dev/null")
    has_dns = dns.strip() == NEW_HOST if dns else False

    if has_dns:
        print(f"  DNS for cp.aqssat.co -> {dns.strip()}", flush=True)
        # Get cert using standalone (stop apache temporarily)
        run(ssh, "systemctl stop apache2 2>/dev/null; true")
        code, out = run(ssh,
            f"certbot certonly --standalone --non-interactive --agree-tos "
            f"--email osamaqazan89@gmail.com -d cp.aqssat.co 2>&1",
            timeout=120)
        run(ssh, "systemctl start apache2 2>/dev/null; true")

        if code == 0:
            cert_domain = 'cp.aqssat.co'
        else:
            cert_domain = None
    else:
        print("  DNS for cp.aqssat.co NOT pointing to this server.", flush=True)
        cert_domain = None

    # If no cp.aqssat.co cert, use an existing valid cert
    if not cert_domain and domains_with_certs:
        cert_domain = domains_with_certs[0][0]
        print(f"\n  Using existing cert: {cert_domain}", flush=True)

    if cert_domain:
        # Configure Webmin to use the Let's Encrypt cert
        print(f"\n" + "=" * 60)
        print(f"  CONFIGURE: Webmin SSL with {cert_domain}")
        print("=" * 60, flush=True)

        cert_path = f"/etc/letsencrypt/live/{cert_domain}"

        # Update miniserv.conf
        run(ssh, f"cp /etc/webmin/miniserv.conf /etc/webmin/miniserv.conf.bak")

        # Set certificate paths
        run(ssh, f"sed -i 's|^certfile=.*|certfile={cert_path}/fullchain.pem|' /etc/webmin/miniserv.conf")
        run(ssh, f"sed -i 's|^keyfile=.*|keyfile={cert_path}/privkey.pem|' /etc/webmin/miniserv.conf")

        # If certfile/keyfile not in config, add them
        code, check = run(ssh, "grep '^certfile=' /etc/webmin/miniserv.conf")
        if not check:
            run(ssh, f"echo 'certfile={cert_path}/fullchain.pem' >> /etc/webmin/miniserv.conf")
        code, check = run(ssh, "grep '^keyfile=' /etc/webmin/miniserv.conf")
        if not check:
            run(ssh, f"echo 'keyfile={cert_path}/privkey.pem' >> /etc/webmin/miniserv.conf")

        # Make sure ssl=1
        code, check = run(ssh, "grep '^ssl=' /etc/webmin/miniserv.conf")
        if not check or 'ssl=0' in check:
            run(ssh, "sed -i 's|^ssl=.*|ssl=1|' /etc/webmin/miniserv.conf")
            code, check2 = run(ssh, "grep '^ssl=' /etc/webmin/miniserv.conf")
            if not check2:
                run(ssh, "echo 'ssl=1' >> /etc/webmin/miniserv.conf")

        # Verify config
        run(ssh, "grep -E 'ssl|certfile|keyfile' /etc/webmin/miniserv.conf")

        # Restart Webmin
        print("\n  Restarting Webmin...", flush=True)
        run(ssh, "systemctl restart webmin 2>/dev/null; true")
        import time
        time.sleep(3)
        _, status = run(ssh, "systemctl is-active webmin 2>/dev/null")
        print(f"  Webmin: {status.strip()}", flush=True)

        run(ssh, "ss -tlnp | grep :10000")

        # Summary
        print("\n" + "=" * 60)
        print("  SSL CONFIGURED!")
        print("=" * 60)
        print(f"")
        if cert_domain == 'cp.aqssat.co':
            print(f"  Access via: https://cp.aqssat.co:10000")
            print(f"  (no more warnings!)")
        else:
            print(f"  Certificate from: {cert_domain}")
            print(f"  Access via: https://{cert_domain}:10000")
            print(f"  (no warnings when using this domain)")
            print(f"")
            print(f"  NOTE: If you access via IP (https://{NEW_HOST}:10000)")
            print(f"  you'll still see a warning because the cert is for {cert_domain}")
            print(f"")
            print(f"  To fix permanently:")
            print(f"  1. Add DNS A record: cp.aqssat.co -> {NEW_HOST}")
            print(f"  2. Run: certbot certonly --standalone -d cp.aqssat.co")
            print(f"  3. Update /etc/webmin/miniserv.conf")
        print(f"")
        print(f"  User: root / {NEW_PASS}")
        print("=" * 60, flush=True)
    else:
        print("\n" + "=" * 60)
        print("  NO VALID CERT AVAILABLE")
        print("=" * 60)
        print(f"")
        print(f"  To get SSL without warnings:")
        print(f"  1. Go to your DNS provider (Cloudflare/Namecheap/etc)")
        print(f"  2. Add A record: cp.aqssat.co -> {NEW_HOST}")
        print(f"  3. Wait 5 min for DNS propagation")
        print(f"  4. Run this script again")
        print(f"")
        print(f"  Or access Webmin via one of your existing domains")
        print(f"  (after pointing DNS to {NEW_HOST})")
        print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
