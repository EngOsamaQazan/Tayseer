import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

# Test the bad URL from the server itself
stdin, stdout, stderr = ssh.exec_command("curl -s -o /dev/null -w '%{http_code}' 'http://localhost/judiciary/update/index' -H 'Host: namaa.aqssat.co'")
print(f'judiciary/update/index => HTTP {stdout.read().decode().strip()}')

# Test the correct URL
stdin, stdout, stderr = ssh.exec_command("curl -s -o /dev/null -w '%{http_code}' 'http://localhost/judiciary/judiciary/index' -H 'Host: namaa.aqssat.co'")
print(f'judiciary/judiciary/index => HTTP {stdout.read().decode().strip()}')

# Check RouteAccessBehavior or access control for judiciary/view
stdin, stdout, stderr = ssh.exec_command("grep -n 'JUD_VIEW\\|judiciary/view\\|actionView' /var/www/namaa.aqssat.co/backend/modules/judiciary/controllers/JudiciaryController.php | head -10")
print(f'\nView action check:')
print(stdout.read().decode().strip())

# Check access control behaviors
stdin, stdout, stderr = ssh.exec_command("grep -A30 'function behaviors' /var/www/namaa.aqssat.co/backend/modules/judiciary/controllers/JudiciaryController.php | head -40")
print(f'\nBehaviors:')
print(stdout.read().decode().strip())

ssh.close()
