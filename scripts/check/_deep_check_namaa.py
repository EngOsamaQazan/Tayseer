import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd):
    print(f'\n=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out: print(out)
    if err and 'Warning' not in err: print(f'[stderr] {err}')
    if not out and not err: print('[empty]')

# 1. Get the EXACT latest error with full stack trace
run('Last 5 errors with full trace',
    'grep -B1 -A20 "UnknownPropertyException\\|effective_installment" /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | tail -50')

# 2. Check the FollowUpReport BASE model on server - what attributes does it declare?
run('FollowUpReport.php on server (full rules + attributes)',
    'cat /var/www/namaa.aqssat.co/backend/modules/followUpReport/models/FollowUpReport.php')

# 3. Check the FollowUpReportSearch on server
run('FollowUpReportSearch.php on server (full)',
    'cat /var/www/namaa.aqssat.co/backend/modules/followUpReport/models/FollowUpReportSearch.php')

# 4. Check DB schema cache config
run('DB schema cache config',
    "grep -n 'enableSchemaCache\\|schemaCacheDuration\\|schemaCache' /var/www/namaa.aqssat.co/common/config/main-local.php 2>&1")

# 5. Check if Yii cache has schema cached
run('Yii cache files',
    "find /var/www/namaa.aqssat.co/backend/runtime/cache -type f 2>/dev/null | head -20")

# 6. Check the actual DB columns RIGHT NOW
run('DB columns of os_follow_up_report NOW',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SHOW COLUMNS FROM os_follow_up_report WHERE Field='effective_installment';\" 2>/dev/null")

# 7. Try a Yii console command to flush schema
run('Yii console cache flush',
    "cd /var/www/namaa.aqssat.co && php yii cache/flush-schema 2>&1; php yii cache/flush-all 2>&1")

ssh.close()
print('\n=== Done ===')
