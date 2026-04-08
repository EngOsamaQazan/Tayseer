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

run('Latest errors in app.log',
    'tail -100 /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -B2 -A8 "followUpReport\\|effective_installment\\|UnknownProperty" | tail -40')

run('Verify VIEW exists on namaa_erp',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SHOW TABLES LIKE 'os_follow_up_report';\" 2>/dev/null")

run('Check effective_installment column exists',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SELECT effective_installment FROM os_follow_up_report LIMIT 1;\" 2>/dev/null")

run('Check FollowUpReport model rules/attributes on server',
    "grep -n 'effective_installment\\|rules\\|tableName\\|attributes' /var/www/namaa.aqssat.co/backend/modules/followUpReport/models/FollowUpReport.php 2>&1")

run('Check FollowUpReportSearch safe attributes',
    "grep -n 'effective_installment\\|safe\\|rules' /var/www/namaa.aqssat.co/backend/modules/followUpReport/models/FollowUpReportSearch.php 2>&1")

run('Schema cache - check if cached',
    "ls -la /var/www/namaa.aqssat.co/backend/runtime/cache/ 2>&1 | head -10")

run('Clear ALL caches',
    "rm -rf /var/www/namaa.aqssat.co/backend/runtime/cache/* 2>&1; echo 'cache cleared'")

run('OPcache reset via web endpoint',
    "echo '<?php opcache_reset(); echo \"RESET_OK\";' > /var/www/namaa.aqssat.co/backend/web/_opc.php; curl -sk https://namaa.aqssat.co/_opc.php 2>/dev/null; rm -f /var/www/namaa.aqssat.co/backend/web/_opc.php")

ssh.close()
print('\n=== Done ===')
