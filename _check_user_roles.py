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

# Find user by email
out = run("""cd /var/www/jadal.aqssat.co && php -r "
require 'vendor/autoload.php';
require 'common/config/main.php';
\$db = new PDO('mysql:host=localhost;dbname=jadal_aqssat', 'root', 'HAmAS12852');
\$db->exec('SET NAMES utf8');

// Find user
\$stmt = \$db->query(\\\"SELECT id, username, email, status FROM os_user WHERE email = 'osamaqazan89@gmail.com'\\\");
\$user = \$stmt->fetch(PDO::FETCH_ASSOC);
echo '=== USER INFO ===' . PHP_EOL;
if (\$user) {
    echo 'ID: ' . \$user['id'] . PHP_EOL;
    echo 'Username: ' . \$user['username'] . PHP_EOL;
    echo 'Email: ' . \$user['email'] . PHP_EOL;
    echo 'Status: ' . \$user['status'] . PHP_EOL;
    
    // Get roles
    \$stmt2 = \$db->query('SELECT item_name FROM os_auth_assignment WHERE user_id = ' . \$user['id']);
    echo PHP_EOL . '=== ROLES ===' . PHP_EOL;
    while (\$row = \$stmt2->fetch(PDO::FETCH_ASSOC)) {
        echo '- ' . \$row['item_name'] . PHP_EOL;
    }
    
    // Get user categories
    \$stmt3 = \$db->query('SELECT uc.name FROM os_user_category_map ucm JOIN os_user_category uc ON ucm.category_id = uc.id WHERE ucm.user_id = ' . \$user['id']);
    echo PHP_EOL . '=== USER CATEGORIES ===' . PHP_EOL;
    while (\$row = \$stmt3->fetch(PDO::FETCH_ASSOC)) {
        echo '- ' . \$row['name'] . PHP_EOL;
    }
    
    // Count notifications
    \$stmt4 = \$db->query('SELECT COUNT(*) as cnt FROM os_notification WHERE recipient_id = ' . \$user['id']);
    \$cnt = \$stmt4->fetch(PDO::FETCH_ASSOC);
    echo PHP_EOL . '=== NOTIFICATION COUNT ===' . PHP_EOL;
    echo 'Total: ' . \$cnt['cnt'] . PHP_EOL;
    
    // Recent notifications
    \$stmt5 = \$db->query('SELECT id, type_of_notification, title_html, body_html, FROM_UNIXTIME(created_time) as dt FROM os_notification WHERE recipient_id = ' . \$user['id'] . ' ORDER BY created_time DESC LIMIT 10');
    echo PHP_EOL . '=== RECENT 10 NOTIFICATIONS ===' . PHP_EOL;
    while (\$row = \$stmt5->fetch(PDO::FETCH_ASSOC)) {
        echo \$row['dt'] . ' | Type: ' . \$row['type_of_notification'] . ' | ' . \$row['title_html'] . PHP_EOL;
    }
} else {
    echo 'User not found!' . PHP_EOL;
    // List all users
    \$stmt = \$db->query('SELECT id, username, email FROM os_user LIMIT 20');
    echo PHP_EOL . '=== ALL USERS ===' . PHP_EOL;
    while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo \$row['id'] . ' | ' . \$row['username'] . ' | ' . \$row['email'] . PHP_EOL;
    }
}
" 2>&1""")
print(out)

ssh.close()
