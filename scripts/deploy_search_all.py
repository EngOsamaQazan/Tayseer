import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

LOCAL_ROOT = r'c:\Users\PC\Desktop\Tayseer'
SITE = 'jadal'
REMOTE_ROOT = f'/var/www/{SITE}.aqssat.co'

FILES = [
    'backend/modules/contracts/controllers/ContractsController.php',
    'backend/modules/contracts/models/ContractsSearch.php',
    'backend/modules/followUpReport/controllers/FollowUpReportController.php',
    'backend/modules/followUpReport/models/FollowUpReportSearch.php',
    'backend/modules/companies/controllers/CompaniesController.php',
    'backend/modules/companies/models/CompaniesSearch.php',
    'backend/modules/shareholders/controllers/ShareholdersController.php',
    'backend/modules/shareholders/models/ShareholdersSearch.php',
    'backend/modules/customers/models/CustomersSearch.php',
    'backend/modules/customers/controllers/CustomersController.php',
    'backend/web/css/unified-search.css',
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

print('Flushing cache...')
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

ssh.close()
print('\nDeploy complete!')
