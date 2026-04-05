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

run('Yii app.log - last errors',
    'tail -200 /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -iE "error|exception|fatal|500|followUpReport" | tail -30')

run('Apache error log',
    'tail -50 /var/log/apache2/error.log 2>/dev/null | grep -i "namaa" | tail -20')

run('PHP error log',
    'tail -100 /var/log/php*.log 2>/dev/null | tail -20; tail -100 /var/log/apache2/error.log 2>/dev/null | grep -iE "PHP|Fatal|Class.*not found" | tail -20')

run('Check if ExportTrait exists',
    'ls -la /var/www/namaa.aqssat.co/backend/helpers/ExportTrait.php 2>&1')

run('Check if FlatpickrWidget exists',
    'ls -la /var/www/namaa.aqssat.co/backend/helpers/FlatpickrWidget.php 2>&1')

run('Check if ExportButtons exists',
    'ls -la /var/www/namaa.aqssat.co/backend/widgets/ExportButtons.php 2>&1')

run('Check if NameHelper exists',
    'ls -la /var/www/namaa.aqssat.co/backend/helpers/NameHelper.php 2>&1')

run('Check if contracts-v2 CSS/JS exist',
    'ls -la /var/www/namaa.aqssat.co/backend/web/css/contracts-v2.css /var/www/namaa.aqssat.co/backend/web/js/contracts-v2.js 2>&1')

run('Check if unified-search CSS/JS exist',
    'ls -la /var/www/namaa.aqssat.co/backend/web/css/unified-search.css /var/www/namaa.aqssat.co/backend/web/js/unified-search.js 2>&1')

run('Check if pin-system CSS/JS exist',
    'ls -la /var/www/namaa.aqssat.co/backend/web/css/pin-system.css /var/www/namaa.aqssat.co/backend/web/js/pin-system.js 2>&1')

run('Check DB view vw_contract_customers_names',
    "mysql -u root -e \"SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA='namaa_jadal' AND TABLE_NAME='vw_contract_customers_names';\" 2>&1")

run('Check followUpReport controller',
    'ls -la /var/www/namaa.aqssat.co/backend/modules/followUpReport/controllers/FollowUpReportController.php 2>&1')

run('Check followUpReport index view',
    'ls -la /var/www/namaa.aqssat.co/backend/modules/followUpReport/views/follow-up-report/index.php 2>&1')

run('Check followUpReport search model',
    'ls -la /var/www/namaa.aqssat.co/backend/modules/followUpReport/models/FollowUpReportSearch.php 2>&1')

run('Check FollowUpReport model',
    'ls -la /var/www/namaa.aqssat.co/backend/modules/followUpReport/models/FollowUpReport.php 2>&1')

run('PHP version on server',
    'php -v 2>&1 | head -1')

ssh.close()
print('\n=== Done ===')
