import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

local_file = r'c:\Users\PC\Desktop\Tayseer\backend\modules\jobs\controllers\JobsController.php'
remote_file = '/var/www/watar.aqssat.co/backend/modules/jobs/controllers/JobsController.php'

sftp = ssh.open_sftp()
sftp.put(local_file, remote_file)
print('Uploaded JobsController.php (with curl_close fix)')

stdin, stdout, stderr = ssh.exec_command(f'grep -n "curl_close\\|unset.*ch" {remote_file}')
out = stdout.read().decode().strip()
print(f'Verification: {out}')

sftp.close()
ssh.close()
