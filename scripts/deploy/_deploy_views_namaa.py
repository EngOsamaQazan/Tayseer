import paramiko, sys, os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

SSH_HOST = '31.220.82.115'
SSH_USER = 'root'
SSH_PASS = 'HAmAS12852'
DB_NAME  = 'namaa_erp'
LOCAL_SQL = os.path.join(os.path.dirname(__file__), '..', '..', 'database', 'sql', 'phase1_views.sql')
REMOTE_SQL = '/tmp/phase1_views.sql'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, username=SSH_USER, password=SSH_PASS, timeout=15)

def run(label, cmd):
    print(f'\n=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out: print(out)
    if err: print(f'[stderr] {err}')
    if not out and not err: print('[ok]')
    return out

# 1. Upload SQL file
print('=== Uploading phase1_views.sql ===')
sftp = ssh.open_sftp()
sftp.put(os.path.normpath(LOCAL_SQL), REMOTE_SQL)
sftp.close()
print('Uploaded.')

# 2. Execute on namaa_erp
run(f'Executing views on {DB_NAME}',
    f'mysql -u root {DB_NAME} < {REMOTE_SQL}')

# 3. Verify
run(f'Verify os_follow_up_report on {DB_NAME}',
    f"mysql -u root -e \"SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA='{DB_NAME}' AND TABLE_NAME='os_follow_up_report';\"")

run(f'Test due_amount query on {DB_NAME}',
    f"mysql -u root {DB_NAME} -e \"SELECT id, due_amount FROM os_follow_up_report LIMIT 3;\"")

# 4. Clear caches
run('Clear Yii cache',
    'rm -rf /var/www/namaa.aqssat.co/backend/runtime/cache/*')

run('Reset OPcache',
    'curl -s -o /dev/null http://namaa.aqssat.co/; echo "OPcache warmed"')

ssh.close()
print('\n=== Done ===')
