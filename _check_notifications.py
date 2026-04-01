import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[NONE]')
    print()

# 1. Check the notifications table - last 10 records
run("Last 10 notifications in jadal",
    """mysql -u root -e "SELECT id, sender_id, recipient_id, type_of_notification, title_html, FROM_UNIXTIME(created_time) as created_at FROM os_notification ORDER BY id DESC LIMIT 10;" jadal_db 2>/dev/null""")

# 2. Count total notifications
run("Total notifications",
    """mysql -u root -e "SELECT COUNT(*) as total FROM os_notification;" jadal_db 2>/dev/null""")

# 3. Check if there are sales_employee users
run("Users with sales_employee category",
    """mysql -u root -e "
    SELECT u.id, u.username, u.location, uc.name as category_name 
    FROM os_user u 
    JOIN os_user_category_map ucm ON ucm.user_id = u.id 
    JOIN os_user_category uc ON uc.id = ucm.category_id 
    WHERE uc.slug = 'sales_employee' AND uc.is_active = 1
    LIMIT 10;" jadal_db 2>/dev/null""")

# 4. Check if user_category table exists and has sales_employee
run("UserCategory sales_employee",
    """mysql -u root -e "SELECT * FROM os_user_category WHERE slug = 'sales_employee';" jadal_db 2>/dev/null""")

# 5. Check systemManagerUserId param
run("systemManagerUserId in params",
    "grep -rn 'systemManagerUserId' /var/www/jadal.aqssat.co/backend/config/ /var/www/jadal.aqssat.co/common/config/ 2>/dev/null")

# 6. Check admin role assignment
run("Admin role assignment",
    """mysql -u root -e "SELECT user_id, item_name FROM os_auth_assignment WHERE item_name = 'admin' LIMIT 5;" jadal_db 2>/dev/null""")

# 7. Check latest invoice status
run("Latest inventory invoices",
    """mysql -u root -e "SELECT id, status, branch_id, created_by, FROM_UNIXTIME(created_at) as created_at FROM os_inventory_invoices ORDER BY id DESC LIMIT 5;" jadal_db 2>/dev/null""")

# 8. Check if notifications component is registered
run("notifications component in config",
    "grep -n 'notifications' /var/www/jadal.aqssat.co/backend/config/main.php 2>/dev/null")

# 9. Check the layout for notification bell/display
run("Notification display in layout",
    "grep -rn 'notification\\|os_notification\\|is_unread' /var/www/jadal.aqssat.co/backend/views/layouts/ 2>/dev/null | head -10")

ssh.close()
