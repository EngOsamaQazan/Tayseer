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
    if not out and not err: print('[ok]')

# 1. Delete ALL cache files on all 3 sites
for site in ['namaa.aqssat.co', 'jadal.aqssat.co', 'watar.aqssat.co']:
    run(f'Nuke cache {site}',
        f"rm -rf /var/www/{site}/backend/runtime/cache && "
        f"mkdir -p /var/www/{site}/backend/runtime/cache && "
        f"chown www-data:www-data /var/www/{site}/backend/runtime/cache && "
        f"echo 'done'")

# 2. Restart Apache to fully clear OPcache
run('Restart Apache (clears OPcache)',
    'systemctl restart apache2 && echo "Apache restarted"')

# 3. Verify the VIEW column
run('Verify effective_installment exists',
    "mysql -u osama -p'OsamaDB123' namaa_erp -e \"SELECT id, effective_installment FROM os_follow_up_report LIMIT 1;\" 2>/dev/null")

# 4. Warm up the schema cache by hitting the page
run('Warm up namaa (expect redirect to login)',
    "curl -sk -o /dev/null -w '%{http_code}' https://namaa.aqssat.co/followUpReport/index 2>/dev/null")

ssh.close()
print('\n=== Done ===')
