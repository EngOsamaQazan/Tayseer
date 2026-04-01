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

# 1. Check user 94 permissions
run("User 94 info",
    f"""mysql -u root {DB} -e "SELECT id, username FROM os_user WHERE id = 94;" """)

run("User 94 auth assignments",
    f"""mysql -u root {DB} -e "SELECT item_name FROM os_auth_assignment WHERE user_id = 94;" """)

# 2. Check user 94 categories  
run("User 94 categories",
    f"""mysql -u root {DB} -e "
SELECT uc.slug, uc.name_ar FROM os_user_category_map ucm 
JOIN os_user_categories uc ON uc.id = ucm.category_id 
WHERE ucm.user_id = 94;" """)

# 3. Check how RouteAccessBehavior works
run("RouteAccessBehavior",
    "grep -n 'inventoryInvoices\\|update\\|INVINV' /var/www/jadal.aqssat.co/backend/components/RouteAccessBehavior.php 2>/dev/null | head -20")

# 4. Check the Permissions::can method
run("Permissions::can method",
    "grep -A10 'function can' /var/www/jadal.aqssat.co/common/helper/Permissions.php | head -15")

# 5. Check User.hasCategory() uses the right table name
run("User model getCategories relation",
    "grep -A5 'function getCategories' /var/www/jadal.aqssat.co/common/models/User.php")

# 6. Check what UserCategory model uses on the server
run("UserCategory tableName on server",
    "grep 'tableName' /var/www/jadal.aqssat.co/backend/models/UserCategory.php")

# 7. Check if os_user_categories has the right data on server
run("os_user_categories on server",
    f"""mysql -u root {DB} -e "SELECT id, slug FROM os_user_categories WHERE is_active = 1;" """)

# 8. Check user 1 hasCategory test
run("Test hasCategory for user 1",
    r"""cd /var/www/jadal.aqssat.co && php -r '
require "vendor/autoload.php";
require "common/config/bootstrap.php";
$app = new yii\web\Application(yii\helpers\ArrayHelper::merge(
    require "common/config/main.php",
    require "common/config/main-local.php",
    require "backend/config/main.php",
    require "backend/config/main-local.php"
));
$user = common\models\User::findOne(1);
echo "User: " . $user->username . PHP_EOL;
echo "Categories: ";
foreach ($user->categories as $c) {
    echo $c->slug . ", ";
}
echo PHP_EOL;
echo "hasCategory(manager): " . ($user->hasCategory("manager") ? "YES" : "NO") . PHP_EOL;
echo "hasCategory(sales_employee): " . ($user->hasCategory("sales_employee") ? "YES" : "NO") . PHP_EOL;
' 2>&1""")

ssh.close()
