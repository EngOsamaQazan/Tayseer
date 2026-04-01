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

# Get DB password from config
out = run("grep -A2 'password' /var/www/jadal.aqssat.co/common/config/main-local.php 2>/dev/null | head -5")
print("=== DB CREDENTIALS ===")
print(out)

# Query the database directly using mysql CLI
query = """
SELECT u.id, u.username, u.email, u.status 
FROM os_user u 
WHERE u.email = 'osamaqazan89@gmail.com';
"""
out = run(f"mysql -u root namaa_jadal -e \"{query}\" 2>&1")
print("=== USER INFO ===")
print(out)

# Get roles for this user
out2 = run("""mysql -u root namaa_jadal -e "SELECT aa.user_id, aa.item_name FROM os_auth_assignment aa JOIN os_user u ON aa.user_id = u.id WHERE u.email = 'osamaqazan89@gmail.com';" 2>&1""")
print("=== USER ROLES ===")
print(out2)

# Get user categories
out3 = run("""mysql -u root namaa_jadal -e "SELECT ucm.user_id, uc.name, uc.slug FROM os_user_category_map ucm JOIN os_user_category uc ON ucm.category_id = uc.id JOIN os_user u ON ucm.user_id = u.id WHERE u.email = 'osamaqazan89@gmail.com';" 2>&1""")
print("=== USER CATEGORIES ===")
print(out3)

# Count notifications for this user
out4 = run("""mysql -u root namaa_jadal -e "SELECT COUNT(*) as total FROM os_notification n JOIN os_user u ON n.recipient_id = u.id WHERE u.email = 'osamaqazan89@gmail.com';" 2>&1""")
print("=== NOTIFICATION COUNT ===")
print(out4)

# Recent notifications
out5 = run("""mysql -u root namaa_jadal -e "SELECT n.id, n.type_of_notification, n.title_html, FROM_UNIXTIME(n.created_time) as dt, n.is_unread FROM os_notification n JOIN os_user u ON n.recipient_id = u.id WHERE u.email = 'osamaqazan89@gmail.com' ORDER BY n.created_time DESC LIMIT 15;" 2>&1""")
print("=== RECENT NOTIFICATIONS ===")
print(out5)

# Notification type distribution
out6 = run("""mysql -u root namaa_jadal -e "SELECT n.type_of_notification, COUNT(*) as cnt FROM os_notification n JOIN os_user u ON n.recipient_id = u.id WHERE u.email = 'osamaqazan89@gmail.com' GROUP BY n.type_of_notification;" 2>&1""")
print("=== NOTIFICATION TYPES DISTRIBUTION ===")
print(out6)

ssh.close()
