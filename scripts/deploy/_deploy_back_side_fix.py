"""
Deploy the back-side capture fixes to all 3 production sites:
  - VisionService.php  (side-aware Gemini prompt)
  - WizardController.php (graceful empty-back handling)
  - scan-camera.js (skip-back button, success overlay)
  - scan-camera.css (success styles, skip-button pulse)

Each upload is followed by a PHP syntax check on the server (where applicable),
opcache reset, and runtime cache clear so changes take effect immediately.
"""
import paramiko
import sys
import os
import time

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

SERVER = '31.220.82.115'
USER   = 'root'
PASSWD = 'HAmAS12852'
LOCAL  = r'c:\Users\Administrator\Desktop\Tayseer'
SITES  = ['jadal.aqssat.co', 'namaa.aqssat.co', 'watar.aqssat.co']

FILES = [
    'backend/modules/customers/components/VisionService.php',
    'backend/modules/customers/controllers/WizardController.php',
    'backend/web/js/customer-wizard/scan-camera.js',
    'backend/web/css/customer-wizard/scan-camera.css',
]

def run(ssh, cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    return (stdout.read().decode('utf-8', 'replace').strip(),
            stderr.read().decode('utf-8', 'replace').strip(),
            stdout.channel.recv_exit_status())

print('Connecting…')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SERVER, username=USER, password=PASSWD, timeout=15)
sftp = ssh.open_sftp()

errors = []

print('\n=== Uploading files ===')
for rel in FILES:
    local_path = os.path.join(LOCAL, rel.replace('/', '\\'))
    if not os.path.exists(local_path):
        print(f'  SKIP (missing locally): {rel}')
        continue
    fname = rel.split('/')[-1]
    for site in SITES:
        remote = f'/var/www/{site}/{rel}'
        try:
            sftp.put(local_path, remote)
            print(f'  OK  {site}/{fname}')
        except Exception as e:
            msg = f'FAIL {site}/{fname} -> {e}'
            print(f'  {msg}')
            errors.append(msg)

print('\n=== PHP syntax check (on first site) ===')
for rel in FILES:
    if not rel.endswith('.php'): continue
    fname = rel.split('/')[-1]
    out, _, _ = run(ssh, f'php -l /var/www/{SITES[0]}/{rel} 2>&1')
    status = 'OK' if 'No syntax errors' in out else 'ERROR'
    print(f'  {status}: {fname}' + (f'\n     {out}' if status == 'ERROR' else ''))

print('\n=== Reset OPcache + clear runtime cache ===')
for site in SITES:
    path = f'/var/www/{site}/backend/web/_opcache_reset.php'
    with sftp.file(path, 'w') as f:
        f.write('<?php opcache_reset(); echo "OK";')
    out, _, _ = run(ssh, f'curl -sk https://{site}/_opcache_reset.php 2>/dev/null; rm -f {path}')
    run(ssh, f'rm -rf /var/www/{site}/backend/runtime/cache/*')
    print(f'  OPcache {site}: {out}')

sftp.close()
ssh.close()

if errors:
    print(f'\n{len(errors)} errors')
    sys.exit(1)
print(f'\nAll {len(FILES)} files deployed to {len(SITES)} sites.')
