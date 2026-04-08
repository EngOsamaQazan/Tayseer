import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd):
    print(f'\n=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out: print(out)
    if err and 'Warning' not in err: print(f'[stderr] {err}')
    if not out and not err: print('[empty]')

# 1. Check if effective_installment column exists in the VIEW
run('Check columns of os_follow_up_report on namaa_erp',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SHOW COLUMNS FROM os_follow_up_report LIKE 'effective%';\" 2>/dev/null")

run('All VIEW columns',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SHOW COLUMNS FROM os_follow_up_report;\" 2>/dev/null | head -40")

# 2. Nuke ALL Yii runtime cache
run('Delete ALL runtime cache',
    "rm -rf /var/www/namaa.aqssat.co/backend/runtime/cache 2>&1; echo 'deleted'")

run('Recreate cache dir',
    "mkdir -p /var/www/namaa.aqssat.co/backend/runtime/cache && chown www-data:www-data /var/www/namaa.aqssat.co/backend/runtime/cache && echo 'created'")

# 3. Reset OPcache via web
path = '/var/www/namaa.aqssat.co/backend/web/_flush.php'
try:
    with sftp.file(path, 'w') as f:
        f.write('<?php\n'
                'if (function_exists("opcache_reset")) opcache_reset();\n'
                'Yii::$app->cache->flush();\n'
                'Yii::$app->db->getSchema()->refresh();\n'
                'echo "ALL_FLUSHED";\n')
    run('Flush via web (OPcache + Yii cache + schema)',
        f"curl -sk https://namaa.aqssat.co/_flush.php 2>/dev/null; rm -f {path}")
except Exception as e:
    print(f'Error: {e}')

# 4. Verify after flush
run('Verify effective_installment accessible',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SELECT id, effective_installment FROM os_follow_up_report LIMIT 2;\" 2>/dev/null")

sftp.close()
ssh.close()
print('\n=== Done ===')
