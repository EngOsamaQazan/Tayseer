import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

LOCAL_ROOT = r'c:\Users\PC\Desktop\Tayseer'
SITE = 'jadal'
REMOTE_ROOT = f'/var/www/{SITE}.aqssat.co'

FILES = [
    'backend/modules/customers/controllers/CustomersController.php',
    'backend/modules/customers/models/CustomersSearch.php',
]

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
print('Connecting...')
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)
sftp = ssh.open_sftp()

for f in FILES:
    local = os.path.join(LOCAL_ROOT, f.replace('/', os.sep))
    remote = f'{REMOTE_ROOT}/{f}'
    print(f'  Uploading {f}...')
    sftp.put(local, remote)
    print(f'    OK')

sftp.close()

print('\nFlushing cache...')
stdin, stdout, stderr = ssh.exec_command(f'cd {REMOTE_ROOT} && php yii cache/flush-all', timeout=30)
print(stdout.read().decode('utf-8', errors='replace'))

print('Resetting OPcache...')
opcache_php = '<?php opcache_reset(); echo "OPcache reset OK";'
sftp2 = ssh.open_sftp()
with sftp2.file(f'{REMOTE_ROOT}/backend/web/_opcache_reset.php', 'w') as fh:
    fh.write(opcache_php)
sftp2.close()

stdin, stdout, stderr = ssh.exec_command(f'curl -sLk https://{SITE}.aqssat.co/_opcache_reset.php 2>&1', timeout=15)
out = stdout.read().decode('utf-8', errors='replace')
if 'OPcache reset OK' in out:
    print('  OPcache reset OK')
else:
    print(f'  OPcache result: {out[:200]}')

ssh.exec_command(f'rm -f {REMOTE_ROOT}/backend/web/_opcache_reset.php')

print('Restarting Apache...')
stdin, stdout, stderr = ssh.exec_command('systemctl restart apache2', timeout=30)
print(stdout.read().decode('utf-8', errors='replace'))
err = stderr.read().decode('utf-8', errors='replace')
if err:
    print(f'  Apache stderr: {err}')
else:
    print('  Apache restarted OK')

ssh.close()
print('\nDeploy complete!')
