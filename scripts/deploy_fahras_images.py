import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

LOCAL_ROOT = r'c:\Users\PC\Desktop\Tayseer'
SITE = 'jadal'
REMOTE_ROOT = f'/var/www/{SITE}.aqssat.co'

FILES = [
    'backend/web/fahras/client-attachments.php',
    'backend/web/fahras/api.php',
    'backend/web/fahras/relations.php',
]

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
print('Connecting...')
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)
sftp = ssh.open_sftp()

for f in FILES:
    local = os.path.join(LOCAL_ROOT, f.replace('/', os.sep))
    remote = f'{REMOTE_ROOT}/{f}'
    print(f'  {f} ... ', end='')
    sftp.put(local, remote)
    print('OK')

print('\nFlushing cache...')
stdin, stdout, stderr = ssh.exec_command(f'cd {REMOTE_ROOT} && php yii cache/flush-all')
print(stdout.read().decode())

sftp.close()
ssh.close()
print('Done!')
