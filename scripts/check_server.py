import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

for site in ['namaa', 'jadal']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')

    # Check git HEAD
    stdin, stdout, stderr = ssh.exec_command(f'cd {path} && git log --oneline -1')
    print(f'HEAD: {stdout.read().decode().strip()}')

    # Check redirect in JudiciaryController
    stdin, stdout, stderr = ssh.exec_command(f"grep -n \"redirect(\" {path}/backend/modules/judiciary/controllers/JudiciaryController.php | head -20")
    lines = stdout.read().decode().strip()
    print(f'Redirects in JudiciaryController:')
    for line in lines.split('\n'):
        if line.strip():
            print(f'  {line}')

    # Check for bad redirect (string literal)
    stdin, stdout, stderr = ssh.exec_command(f"grep -n \"redirect('index')\" {path}/backend/modules/judiciary/controllers/JudiciaryController.php")
    bad = stdout.read().decode().strip()
    if bad:
        print(f'  BAD redirect found: {bad}')
    else:
        print(f'  No bad redirects found (good)')

ssh.close()
print('\nDone!')
