import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    return out

def mysql(db, sql, timeout=30):
    run(f"cat > /tmp/verify.sql << 'SQLEOF'\n{sql}\nSQLEOF")
    return run(f"mysql -u root {db} < /tmp/verify.sql 2>&1", timeout=timeout)

DATABASES = {
    'jadal': 'namaa_jadal',
    'namaa': 'namaa_erp',
    'watar': 'tayseer_watar',
}

for site, db in DATABASES.items():
    print(f"\n{'='*60}")
    print(f"  {site.upper()} ({db})")
    print(f"{'='*60}")
    
    # 1. Verify categories table exists and has data
    out = mysql(db, "SELECT COUNT(*) AS cat_count FROM os_user_categories WHERE is_active = 1;")
    print(f"\n  1. Active categories: {out.strip().split(chr(10))[-1]}")
    
    # 2. Verify category_map has data
    out = mysql(db, "SELECT COUNT(*) AS map_count FROM os_user_category_map;")
    print(f"  2. Category mappings: {out.strip().split(chr(10))[-1]}")
    
    # 3. Verify manager category has users
    out = mysql(db, """
SELECT uc.slug, uc.name_ar, COUNT(ucm.user_id) AS users
FROM os_user_categories uc
LEFT JOIN os_user_category_map ucm ON uc.id = ucm.category_id
WHERE uc.slug IN ('manager', 'sales_employee', 'employee')
GROUP BY uc.id, uc.slug, uc.name_ar;
    """)
    print(f"  3. Key category users:\n{out}")
    
    # 4. Verify notification component file is correct
    path = f"/var/www/{site}.aqssat.co"
    out = run(f"grep 'setReadedAll' {path}/common/components/notificationComponent.php 2>&1")
    has_fix = 'updateAll' in out
    print(f"  4. notificationComponent fixed: {'YES' if has_fix else 'NO'}")
    
    # 5. Verify NotificationsHandler doesn't have early return
    out = run(f"head -40 {path}/api/helpers/NotificationsHandler.php 2>&1")
    lines = out.strip().split('\n')
    has_early_return = False
    for i, line in enumerate(lines):
        if 'return true;' in line and i < 30:
            next_line = lines[i+1] if i+1 < len(lines) else ''
            if 'if' in next_line and 'to_user_id' in next_line:
                has_early_return = True
    print(f"  5. NotificationsHandler early return removed: {'YES' if not has_early_return else 'NO - STILL BROKEN'}")
    
    # 6. Verify kartik bs5dropdown package
    out = run(f"ls {path}/vendor/kartik-v/yii2-bootstrap5-dropdown/composer.json 2>/dev/null")
    print(f"  6. kartik bs5dropdown installed: {'YES' if 'composer.json' in out else 'NO'}")
    
    # 7. Test PHP syntax for key files
    files_to_check = [
        f'{path}/common/components/notificationComponent.php',
        f'{path}/api/helpers/NotificationsHandler.php',
        f'{path}/backend/modules/notification/controllers/NotificationController.php',
    ]
    all_ok = True
    for f in files_to_check:
        out = run(f"php -l {f} 2>&1")
        if 'No syntax errors' not in out:
            print(f"  7. SYNTAX ERROR in {f}: {out.strip()}")
            all_ok = False
    if all_ok:
        print(f"  7. All PHP files syntax valid: YES")

    # 8. Test notification/index page
    out = run(f"curl -s -o /dev/null -w '%{{http_code}}' https://{site}.aqssat.co/notification/index -k 2>&1")
    status = out.strip().replace("'", "")
    print(f"  8. notification/index HTTP status: {status} ({'OK' if status == '302' else 'CHECK'})")

ssh.close()
print(f"\n{'='*60}")
print("  VERIFICATION COMPLETE")
print(f"{'='*60}")
