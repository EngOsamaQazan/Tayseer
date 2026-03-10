import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

# Reset OPcache
print('=== Resetting OPcache ===')
ssh.exec_command('php -r "opcache_reset();"')
stdin, stdout, stderr = ssh.exec_command('service apache2 reload')
print(stdout.read().decode())
print(stderr.read().decode())

# Test API directly from server using PHP CLI
print('=== Testing API via PHP CLI ===')
cmd = '''cd /var/www/jadal.aqssat.co/backend/web && php -r "
\\$_REQUEST = ['db'=>'jadal','token'=>'b83ba7a49b72','action'=>'search','search'=>'9348'];
ob_start();
include 'fahras/api.php';
\\$out = ob_get_clean();
echo substr(\\$out, 0, 3000);
"'''
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode()
err = stderr.read().decode()
print('STDOUT:', out[:3000])
if err:
    print('STDERR:', err[:1000])

ssh.close()
print('\nDone!')
