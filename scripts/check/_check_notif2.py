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

DB = 'namaa_jadal'

# 1. Check notifications table
run("Last 10 notifications",
    f"""mysql -u root -e "SELECT id, sender_id, recipient_id, type_of_notification, SUBSTRING(title_html,1,60) as title, FROM_UNIXTIME(created_time) as created_at FROM os_notification ORDER BY id DESC LIMIT 10;" {DB}""")

run("Total notifications",
    f"""mysql -u root -e "SELECT COUNT(*) as total FROM os_notification;" {DB}""")

# 2. Check user_category table
run("UserCategory table",
    f"""mysql -u root -e "SELECT * FROM os_user_category;" {DB} 2>/dev/null""")

# Check if table exists
run("Tables like user_category",
    f"""mysql -u root -e "SHOW TABLES LIKE '%%user_category%%';" {DB}""")

# 3. Check auth_assignment for admin
run("Auth assignments (admin)",
    f"""mysql -u root -e "SELECT user_id, item_name FROM os_auth_assignment WHERE item_name = 'admin' LIMIT 5;" {DB}""")

# All auth assignments
run("All auth assignments",
    f"""mysql -u root -e "SELECT DISTINCT item_name, COUNT(*) as cnt FROM os_auth_assignment GROUP BY item_name;" {DB}""")

# 4. systemManagerUserId in params
run("Params config",
    "grep -rn 'systemManagerUserId\\|params' /var/www/jadal.aqssat.co/common/config/params*.php /var/www/jadal.aqssat.co/backend/config/params*.php 2>/dev/null | head -20")

# 5. Latest invoices
run("Latest inventory invoices",
    f"""mysql -u root -e "SELECT id, status, branch_id, created_by, FROM_UNIXTIME(created_at) as created_at FROM os_inventory_invoices ORDER BY id DESC LIMIT 5;" {DB}""")

# 6. Check tables like notification
run("Tables like notification",
    f"""mysql -u root -e "SHOW TABLES LIKE '%%notification%%';" {DB}""")

# 7. Check user with id=1 (the current user / admin)
run("User id=1",
    f"""mysql -u root -e "SELECT id, username, email, location FROM os_user WHERE id = 1;" {DB}""")

ssh.close()
