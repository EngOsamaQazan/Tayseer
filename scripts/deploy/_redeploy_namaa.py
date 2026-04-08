import paramiko, sys, os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd):
    print(f'\n=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out: print(out)
    if err: print(f'[stderr] {err}')
    if not out and not err: print('[ok]')

local_sql = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', 'database', 'sql', 'phase1_views.sql'))
remote_sql = '/tmp/phase1_views_namaa.sql'

print(f'Uploading {local_sql}...')
sftp.put(local_sql, remote_sql)
print('Uploaded.')

# Show first few lines to confirm it's the right file
run('Verify SQL file on server',
    f'head -10 {remote_sql}')

# Execute with FULL error output
run('Execute SQL on namaa_erp (with errors)',
    f"mysql -u osama -p'OsamaDB123' namaa_erp < {remote_sql} 2>&1")

# Verify columns after
run('Columns after deploy',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SHOW COLUMNS FROM os_follow_up_report;\" 2>/dev/null")

run('Test effective_installment',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SELECT id, effective_installment, due_amount FROM os_follow_up_report LIMIT 3;\" 2>/dev/null")

# Clear cache + schema
run('Clear runtime cache',
    "rm -rf /var/www/namaa.aqssat.co/backend/runtime/cache/* 2>&1; echo 'cleared'")

path = '/var/www/namaa.aqssat.co/backend/web/_flush2.php'
with sftp.file(path, 'w') as f:
    f.write('<?php\n'
            'opcache_reset();\n'
            'Yii::$app->cache->flush();\n'
            'Yii::$app->db->getSchema()->refresh();\n'
            'echo "FLUSHED";\n')
run('Flush OPcache + schema via web',
    f"curl -sk https://namaa.aqssat.co/_flush2.php 2>/dev/null; rm -f {path}")

run('Cleanup',
    f'rm -f {remote_sql}')

sftp.close()
ssh.close()
print('\n=== Done ===')
