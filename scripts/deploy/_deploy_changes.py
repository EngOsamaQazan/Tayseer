import paramiko, sys, os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

local_base = r'c:\Users\PC\Desktop\Tayseer'

files_to_upload = [
    'backend/modules/followUp/views/follow-up/panel/_side_panels.php',
    'backend/modules/followUp/views/follow-up/panel.php',
    'backend/modules/followUp/views/follow-up/partial/tabs/phone_numbers.php',
    'backend/modules/followUp/views/follow-up/_form.php',
    'backend/modules/followUp/views/follow-up/view.php',
    'backend/modules/followUp/views/follow-up/_search.php',
    'backend/modules/followUp/controllers/FollowUpController.php',
    'backend/modules/followUp/models/FollowUp.php',
    'backend/modules/followUp/models/FollowUpSearch.php',
    'backend/modules/phoneNumbers/controllers/PhoneNumbersController.php',
    'backend/modules/contracts/controllers/ContractsController.php',
    'backend/modules/reports/views/follow-up-reports/_columns.php',
    'backend/modules/reports/views/follow-up-reports/view.php',
    'backend/modules/reports/views/customers-judiciary-actions-report/view.php',
    'backend/models/FollowUpSearch.php',
]

projects = [
    '/var/www/jadal.aqssat.co',
    '/var/www/namaa.aqssat.co',
    '/var/www/watar.aqssat.co',
]

print(f'Deploying {len(files_to_upload)} files to {len(projects)} sites...\n')

errors = []
for rel_path in files_to_upload:
    local_path = os.path.join(local_base, rel_path.replace('/', '\\'))
    fname = rel_path.split('/')[-1]
    for proj in projects:
        remote_path = f'{proj}/{rel_path}'
        proj_name = proj.split('/')[3]
        try:
            sftp.put(local_path, remote_path)
            print(f'  OK  {proj_name}/{fname}')
        except Exception as e:
            msg = f'FAIL {proj_name}/{fname} - {e}'
            print(f'  {msg}')
            errors.append(msg)

print(f'\n=== Syntax Check ===')
def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    return out

for rel_path in files_to_upload:
    if rel_path.endswith('.php'):
        fname = rel_path.split('/')[-1]
        result = run(f'php -l {projects[0]}/{rel_path} 2>&1')
        status = 'OK' if 'No syntax errors' in result else 'ERROR'
        if status == 'ERROR':
            print(f'  {status}: {fname} -> {result}')
        else:
            print(f'  {status}: {fname}')

print(f'\n=== Clear OPcache & Restart Apache ===')
for site in ['jadal.aqssat.co', 'namaa.aqssat.co', 'watar.aqssat.co']:
    path = f'/var/www/{site}/backend/web/_opcache_reset.php'
    with sftp.file(path, 'w') as f:
        f.write('<?php opcache_reset(); echo "OK";')
    result = run(f'curl -sk https://{site}/_opcache_reset.php 2>/dev/null; rm -f {path}')
    print(f'  OPcache {site}: {result}')
    run(f'rm -rf /var/www/{site}/backend/runtime/cache/*')

result = run('systemctl restart apache2 && systemctl is-active apache2')
print(f'  Apache: {result}')

sftp.close()
ssh.close()

if errors:
    print(f'\n{len(errors)} errors occurred!')
else:
    print(f'\nAll {len(files_to_upload)} files deployed successfully to {len(projects)} sites!')
