"""
Deploy the updated backend/web/.htaccess to all 3 production sites and verify
that the new Permissions-Policy header is being served.

WHY this is its own script:
  The wizard's smart ID scan was failing on real phones because the upstream
  Apache config was sending:
      Permissions-Policy: camera=(), microphone=(), geolocation=()
  which BLOCKS getUserMedia for first-party use. Our local .htaccess change
  overrides it with:
      Permissions-Policy: camera=(self), microphone=(self), geolocation=(self)
  but a code-only commit doesn't help until the file is on the server.

What the script does:
  1. SFTP-upload backend/web/.htaccess to each site.
  2. systemctl reload apache2 (no service interruption).
  3. curl -I each site and confirm `camera=(self)` is present.
"""
import paramiko
import sys
import os
import time

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

SERVER  = '31.220.82.115'
USER    = 'root'
PASSWD  = 'HAmAS12852'
LOCAL   = r'c:\Users\Administrator\Desktop\Tayseer\backend\web\.htaccess'
SITES   = ['jadal.aqssat.co', 'namaa.aqssat.co', 'watar.aqssat.co']

def run(ssh, cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    rc  = stdout.channel.recv_exit_status()
    return out, err, rc

print('Connecting…')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SERVER, username=USER, password=PASSWD, timeout=15)
sftp = ssh.open_sftp()

# ── 1. Backup + upload ──
print('\n=== Uploading .htaccess ===')
for site in SITES:
    remote = f'/var/www/{site}/backend/web/.htaccess'
    backup = f'{remote}.bak.{int(time.time())}'
    out, err, rc = run(ssh, f'cp {remote} {backup} 2>/dev/null && echo OK || echo NO_PREV')
    print(f'  {site}: backup -> {out}')
    try:
        sftp.put(LOCAL, remote)
        print(f'  {site}: uploaded')
    except Exception as e:
        print(f'  {site}: FAIL - {e}')

# ── 2. Reload Apache ──
print('\n=== Reloading Apache ===')
out, err, rc = run(ssh, 'apache2ctl configtest 2>&1')
print(f'  configtest: {out}')
if 'Syntax OK' not in out:
    print('  ABORT — config has errors, NOT reloading')
    sys.exit(1)
out, err, rc = run(ssh, 'systemctl reload apache2 && systemctl is-active apache2')
print(f'  reload: {out}')

# ── 3. Verify the header is now correct ──
print('\n=== Verifying Permissions-Policy header ===')
all_good = True
for site in SITES:
    out, _, _ = run(ssh, f'curl -sIk https://{site}/user/login | grep -i "permissions-policy"')
    if 'camera=(self)' in out.lower():
        print(f'  OK   {site}: {out}')
    else:
        print(f'  FAIL {site}: {out or "(no Permissions-Policy header)"}')
        all_good = False

sftp.close()
ssh.close()

if all_good:
    print('\nAll sites updated successfully.')
else:
    print('\nSome sites missing the new header.')
    sys.exit(1)
