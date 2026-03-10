import sys, io, paramiko
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

f = '/var/www/jadal.aqssat.co/backend/modules/followUpReport/controllers/FollowUpReportController.php'

stdin, stdout, stderr = ssh.exec_command(f'grep -c "LEAST" {f}')
print('LEAST count:', stdout.read().decode().strip())

stdin, stdout, stderr = ssh.exec_command(f'grep -n "due_installments" {f}')
print('due_installments lines:')
print(stdout.read().decode())

# Reset OPcache properly
sftp = ssh.open_sftp()
with sftp.file('/var/www/jadal.aqssat.co/backend/web/_opcache_reset.php', 'w') as fh:
    fh.write('<?php opcache_reset(); echo "OPcache reset OK";')
sftp.close()

stdin, stdout, stderr = ssh.exec_command('curl -sLk https://jadal.aqssat.co/_opcache_reset.php 2>&1', timeout=15)
out = stdout.read().decode('utf-8', errors='replace')
print('OPcache:', out)

ssh.exec_command('rm -f /var/www/jadal.aqssat.co/backend/web/_opcache_reset.php')

# Flush cache
stdin, stdout, stderr = ssh.exec_command('cd /var/www/jadal.aqssat.co && php yii cache/flush-all', timeout=30)
print(stdout.read().decode())

ssh.close()
print('Done')
