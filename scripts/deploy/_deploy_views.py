import paramiko, sys, os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd, timeout=60):
    print(f'\n=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        for line in out.split('\n')[-20:]:
            print(f'  {line}')
    if err and 'Warning' not in err:
        print(f'  [stderr] {err[:500]}')
    if not out and not err:
        print('  [OK]')

local_sql = os.path.join(os.path.dirname(__file__), '..', '..', 'database', 'sql', 'phase1_views.sql')
local_sql = os.path.abspath(local_sql)
remote_sql = '/tmp/phase1_views.sql'

print(f'Uploading {local_sql} to server...')
sftp.put(local_sql, remote_sql)
print('  Uploaded.')

databases = [
    ('namaa_erp', 'osama', 'OsamaDB123', 'namaa.aqssat.co'),
    ('namaa_jadal', 'osama', 'OsamaDB123', 'jadal.aqssat.co'),
    ('tayseer_watar', 'osama', 'OsamaDB123', 'watar.aqssat.co'),
]

for dbname, dbuser, dbpass, site in databases:
    run(f'Run views SQL on {dbname} ({site})',
        f"mysql -u {dbuser} -p'{dbpass}' {dbname} < {remote_sql} 2>&1")

run('Cleanup temp SQL', f'rm -f {remote_sql}')

for site in ['namaa.aqssat.co', 'jadal.aqssat.co', 'watar.aqssat.co']:
    proj = f'/var/www/{site}'
    run(f'Clear cache {site}',
        f"rm -rf {proj}/backend/runtime/cache/* 2>/dev/null; "
        f"rm -rf {proj}/backend/runtime/debug/* 2>/dev/null; "
        f"echo 'cache cleared'")

    path = f'{proj}/backend/web/_opcache_reset.php'
    try:
        with sftp.file(path, 'w') as f:
            f.write('<?php opcache_reset(); echo "OK";')
        result_stdin, result_stdout, result_stderr = ssh.exec_command(
            f"curl -sk https://{site}/_opcache_reset.php 2>/dev/null; rm -f {path}", timeout=15)
        opcache_result = result_stdout.read().decode('utf-8', errors='replace').strip()
        print(f'  OPcache {site}: {opcache_result}')
    except Exception as e:
        print(f'  OPcache {site}: {e}')

for site in ['namaa.aqssat.co', 'jadal.aqssat.co', 'watar.aqssat.co']:
    run(f'Verify view on {site}',
        f"mysql -u osama -p'OsamaDB123' {databases[['namaa.aqssat.co','jadal.aqssat.co','watar.aqssat.co'].index(site)][0]} "
        f"-e \"SELECT COUNT(*) AS cnt FROM os_follow_up_report LIMIT 1;\" 2>&1")

sftp.close()
ssh.close()
print('\n=== All done ===')
