import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[OK]')
    print()

local_base = r'c:\Users\PC\Desktop\Tayseer'

files_to_upload = [
    'backend/components/RouteAccessBehavior.php',
    'common/helper/Permissions.php',
]

projects = [
    '/var/www/jadal.aqssat.co',
    '/var/www/namaa.aqssat.co',
    '/var/www/watar.aqssat.co',
]

print('=== Uploading files ===')
for rel_path in files_to_upload:
    local_path = f'{local_base}\\{rel_path}'.replace('/', '\\')
    for proj in projects:
        remote_path = f'{proj}/{rel_path}'
        proj_name = proj.split('/')[3]
        try:
            sftp.put(local_path, remote_path)
            print(f'  OK: {proj_name}/{rel_path}')
        except Exception as e:
            print(f'  FAIL: {proj_name}/{rel_path} - {e}')
print()

print('=== Syntax check ===')
for proj in projects:
    proj_name = proj.split('/')[3]
    for rel_path in files_to_upload:
        run(f'{proj_name}/{rel_path.split("/")[-1]}',
            f'php -l {proj}/{rel_path} 2>&1')

print('=== Clear RBAC cache ===')
for proj in projects:
    proj_name = proj.split('/')[3]
    run(f'{proj_name} - clear cache',
        f'cd {proj} && php yii cache/flush-all 2>&1 || rm -rf {proj}/backend/runtime/cache/* 2>&1')

sftp.close()
ssh.close()
print('Done!')
