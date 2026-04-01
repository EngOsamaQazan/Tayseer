import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=120):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('OK')
    print()

# ============================================================
# STEP 2: DISABLE APACHE VHOSTS for deleted/orphan projects
# ============================================================
vhosts_to_disable = [
    'jadal2.aqssat.co',
    'jadal2.aqssat.co-le-ssl',
    'namaa2.aqssat.co',
    'namaa2.aqssat.co-le-ssl',
    'staging.aqssat.co',
    'staging.aqssat.co-le-ssl',
    'sass.aqssat.co',
    'sass.aqssat.co-le-ssl',
    'api-sass.aqssat.co',
    'api-sass.aqssat.co-le-ssl',
    'khaldon.aqssat.co',
    'khaldon.aqssat.co-le-ssl',
    'vite.jadal.aqssat.co',
    'vite.jadal.aqssat.co-le-ssl',
    'vite.namaa.aqssat.co',
    'vite.namaa.aqssat.co-le-ssl',
]

for vhost in vhosts_to_disable:
    run(f"Disable vhost: {vhost}",
        f"a2dissite {vhost} 2>/dev/null && echo DISABLED || echo ALREADY_DISABLED_OR_NOT_FOUND")

# ============================================================
# STEP 3: DELETE PROJECT DIRECTORIES
# ============================================================
dirs_to_delete = [
    '/var/www/jadal2.aqssat.co',
    '/var/www/namaa2.aqssat.co',
    '/var/www/old-jadal',
    '/var/www/old-namaa',
    '/var/www/micro_services',
    '/var/www/document_errors',
    '/var/www/a.py',
    '/var/www/html/jadal.aqssat.co',
    '/var/www/html/namaa.aqssat.co',
    '/var/www/html/jadal2.aqssat.co',
    '/var/www/html/namaa2.aqssat.co',
    '/var/www/html/tes.php',
]

for d in dirs_to_delete:
    run(f"Delete: {d}",
        f"rm -rf {d} && echo DELETED || echo FAILED")

# ============================================================
# STEP 4: REMOVE ORPHAN APACHE CONFIG FILES
# ============================================================
configs_to_remove = [
    'jadal2.aqssat.co.conf',
    'jadal2.aqssat.co-le-ssl.conf',
    'namaa2.aqssat.co.conf',
    'namaa2.aqssat.co-le-ssl.conf',
    'staging.aqssat.co.conf',
    'staging.aqssat.co-le-ssl.conf',
    'sass.aqssat.co.conf',
    'sass.aqssat.co-le-ssl.conf',
    'api-sass.aqssat.co.conf',
    'api-sass.aqssat.co-le-ssl.conf',
    'khaldon.aqssat.co.conf',
    'khaldon.aqssat.co-le-ssl.conf',
    'vite.jadal.aqssat.co.conf',
    'vite.jadal.aqssat.co-le-ssl.conf',
    'vite.namaa.aqssat.co.conf',
    'vite.namaa.aqssat.co-le-ssl.conf',
    'api-jadal.aqssat.co.conf.bak',
    'api-jadal.aqssat.co-le-ssl.conf.bak',
    'api-namaa.aqssat.co.conf.bak',
    '000-default.conf.dpkg-dist',
]

for conf in configs_to_remove:
    run(f"Remove config: {conf}",
        f"rm -f /etc/apache2/sites-available/{conf} && echo REMOVED || echo FAILED")

# ============================================================
# STEP 5: TEST APACHE CONFIG & RELOAD
# ============================================================
run("Test Apache config", "apachectl configtest 2>&1")
run("Reload Apache", "systemctl reload apache2 2>&1 && echo RELOADED")

ssh.close()
print("=== DELETION COMPLETE ===")
