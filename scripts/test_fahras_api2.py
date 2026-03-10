import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

# Reload Apache to clear OPcache
print('=== Reloading Apache ===')
stdin, stdout, stderr = ssh.exec_command('service apache2 reload 2>&1')
print(stdout.read().decode())

# Test via curl from server
print('=== Testing api.php via curl ===')
stdin, stdout, stderr = ssh.exec_command(
    'curl -s "http://localhost/fahras/api.php?db=jadal&token=b83ba7a49b72&action=search&search=9348"'
)
out = stdout.read().decode()
print(out[:3000])

print('\n=== Testing relations.php via curl ===')
stdin, stdout, stderr = ssh.exec_command(
    'curl -s "http://localhost/fahras/relations.php?db=jadal&token=b83ba7a49b72&client=9348&format=json"'
)
out = stdout.read().decode()
print(out[:3000])

ssh.close()
print('\nDone!')
