import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

# Reload Apache
print('=== Reloading Apache ===')
stdin, stdout, stderr = ssh.exec_command('service apache2 reload 2>&1')
print(stdout.read().decode())

# Test via curl with HTTPS and -L to follow redirects
print('=== Testing api.php ===')
stdin, stdout, stderr = ssh.exec_command(
    'curl -sL "https://jadal.aqssat.co/fahras/api.php?db=jadal&token=b83ba7a49b72&action=search&search=9348"'
)
out = stdout.read().decode()
print('Length:', len(out))
# show only relevant parts
if '"images"' in out:
    print('>>> images field FOUND!')
    idx = out.find('"images"')
    print(out[max(0,idx-50):idx+500])
else:
    print('>>> images field NOT found')
    print(out[:2000])

ssh.close()
print('\nDone!')
