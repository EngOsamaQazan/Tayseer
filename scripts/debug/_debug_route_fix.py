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
        print('[OK]')
    print()

proj = '/var/www/jadal.aqssat.co'

# 1. Verify the fix is in the files
run('Check RouteAccessBehavior has full route check',
    f'grep -n "getRequiredPermissionsForRoute.*resolvedRoute" {proj}/backend/components/RouteAccessBehavior.php')

run('Check Permissions has site/system-settings',
    f'grep -n "site/system-settings" {proj}/common/helper/Permissions.php')

run('Check Permissions has site/image-manager',
    f'grep -n "site/image-manager" {proj}/common/helper/Permissions.php')

# 2. Clear OPcache
run('Clear OPcache',
    f'''php -r "
if (function_exists('opcache_reset')) {{
    opcache_reset();
    echo 'OPcache cleared';
}} else {{
    echo 'OPcache not available in CLI';
}}"''')

run('Clear OPcache via web',
    f'''echo '<?php if(function_exists("opcache_reset")){{opcache_reset();echo "OPcache cleared";}}else{{echo "no opcache";}}'  > {proj}/backend/web/_opcache_clear.php && curl -sk https://jadal.aqssat.co/_opcache_clear.php 2>&1 && rm -f {proj}/backend/web/_opcache_clear.php''')

# 3. Clear all caches
run('Clear file cache',
    f'rm -rf {proj}/backend/runtime/cache/*')

run('Clear Yii cache',
    f'cd {proj} && php yii cache/flush-all 2>&1')

# 4. Check if there are other permission checks - check the layout or beforeAction
run('Check for beforeAction in SiteController',
    f'grep -n "beforeAction\\|checkAccess\\|ForbiddenHttpException" {proj}/backend/controllers/SiteController.php')

# 5. Check if there is middleware or application-level check
run('Check routeAccess config',
    f'grep -n "routeAccess\\|RouteAccessBehavior" {proj}/backend/config/main.php')

# 6. Check the actual behavior file content around the fix
run('RouteAccessBehavior lines 128-145',
    f'sed -n "128,145p" {proj}/backend/components/RouteAccessBehavior.php')

# 7. Check if yara has settings permissions in DB
run('Check yara user and permissions',
    f'''cd {proj} && php -r "
require 'vendor/autoload.php';
require 'common/config/bootstrap.php';
\\\$app = new yii\\\\web\\\\Application(yii\\\\helpers\\\\ArrayHelper::merge(
    require 'common/config/main.php',
    require 'common/config/main-local.php',
    require 'backend/config/main.php',
    require 'backend/config/main-local.php'
));
\\\$db = Yii::\\\$app->db;
\\\$user = \\\$db->createCommand('SELECT id, username FROM os_user WHERE username = :u', [':u' => 'yara'])->queryOne();
echo 'User: ' . json_encode(\\\$user) . PHP_EOL;
if (\\\$user) {{
    \\\$perms = \\\$db->createCommand('SELECT item_name FROM os_auth_assignment WHERE user_id = :uid AND item_name NOT LIKE \\'/%\\'', [':uid' => \\\$user['id']])->queryColumn();
    echo 'Permissions (' . count(\\\$perms) . '): ' . implode(', ', \\\$perms) . PHP_EOL;
    \\\$settingsPerms = ['الحالات','حالات الوثائق','الاقارب','الجنسيه','البنوك','كيف سمعت عنا','المدن','طرق الدفع','الانفعالات','طريقة الاتصال','رد العميل','انواع الوثائق','الرسائل','الوظائف','الوظائف: مشاهدة','الوظائف: إضافة','الوظائف: تعديل','الوظائف: حذف'];
    \\\$hasSettings = array_intersect(\\\$settingsPerms, \\\$perms);
    echo 'Has settings perms: ' . count(\\\$hasSettings) . '/' . count(\\\$settingsPerms) . PHP_EOL;
    \\\$authItems = \\\$db->createCommand('SELECT name FROM os_auth_item WHERE type = 2 AND name IN (\\'الحالات\\',\\'البنوك\\',\\'المدن\\')')->queryColumn();
    echo 'Auth items exist: ' . implode(', ', \\\$authItems) . PHP_EOL;
}}" 2>&1''')

ssh.close()
print('Done!')
