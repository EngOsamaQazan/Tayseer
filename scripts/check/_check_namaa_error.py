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
    if out:
        print(out)
    if err and 'Warning' not in err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[empty]')

run('Yii app.log - last errors (namaa)',
    'tail -300 /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -iE "error|exception|fatal|followUp" | tail -30')

run('Check os_follow_up_report view exists on namaa_erp',
    "mysql -u root -e \"SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA='namaa_erp' AND TABLE_NAME='os_follow_up_report';\" 2>/dev/null")

run('Check os_follow_up_report columns on namaa_erp',
    "mysql -u root -e \"SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='namaa_erp' AND TABLE_NAME='os_follow_up_report' ORDER BY ORDINAL_POSITION;\" 2>/dev/null")

run('Test query on namaa_erp',
    "mysql -u root namaa_erp -e \"SELECT id, due_amount FROM os_follow_up_report LIMIT 3;\" 2>/dev/null")

run('PHP syntax check controller',
    'php -l /var/www/namaa.aqssat.co/backend/modules/followUpReport/controllers/FollowUpReportController.php 2>&1')

run('PHP syntax check search model',
    'php -l /var/www/namaa.aqssat.co/backend/modules/followUpReport/models/FollowUpReportSearch.php 2>&1')

run('Recent Apache errors for namaa',
    'tail -50 /var/log/apache2/error.log 2>/dev/null | grep -i namaa | tail -15')

run('OPcache reset',
    'php -r "opcache_reset();" 2>&1; echo "OPcache CLI done"')

run('Clear Yii runtime cache namaa',
    'rm -rf /var/www/namaa.aqssat.co/backend/runtime/cache/* 2>&1; echo "Cache cleared"')

ssh.close()
print('\n=== Done ===')
