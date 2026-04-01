import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out: print(out)
    if err and not out: print(f'[stderr] {err}')
    if not out and not err: print('[OK]')
    print()

sites = ['jadal.aqssat.co', 'namaa.aqssat.co', 'watar.aqssat.co']

# 1. Clear OPcache via web (affects the Apache PHP process)
opcache_php = '<?php opcache_reset(); echo "OPcache reset OK";'
for site in sites:
    path = f'/var/www/{site}/backend/web/_opcache_reset.php'
    sftp = ssh.open_sftp()
    with sftp.file(path, 'w') as f:
        f.write(opcache_php)
    sftp.close()
    run(f'OPcache {site}', f'curl -sk https://{site}/_opcache_reset.php 2>/dev/null; rm -f {path}')

# 2. Clear Yii2 runtime cache
for site in sites:
    run(f'Cache {site}', f'rm -rf /var/www/{site}/backend/runtime/cache/* && echo "cache cleared"')

# 3. Restart Apache
run('Restart Apache', 'systemctl restart apache2 && echo "Apache restarted successfully"')
run('Apache Status', 'systemctl is-active apache2')

ssh.close()
print('Done!')
