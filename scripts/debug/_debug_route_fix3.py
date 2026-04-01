import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[OK]')
    print()

proj = '/var/www/jadal.aqssat.co'

# 1. Restart Apache/PHP to clear OPcache
run('Restart Apache', 'systemctl restart apache2 2>&1 || service apache2 restart 2>&1 || service httpd restart 2>&1')
run('Restart PHP-FPM', 'systemctl restart php*-fpm 2>&1 || service php*-fpm restart 2>&1')

# 2. Create a proper OPcache clear file and test it
opcache_php = '''<?php
header('Content-Type: text/plain; charset=utf-8');
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset OK\\n";
    echo "opcache.enable: " . ini_get('opcache.enable') . "\\n";
} else {
    echo "OPcache not available\\n";
}
'''
with sftp.open(f'{proj}/backend/web/opclear.php', 'w') as f:
    f.write(opcache_php)

run('Clear OPcache via web', f'curl -sk https://jadal.aqssat.co/opclear.php 2>&1')

# 3. Create a debug script that traces the EXACT RouteAccessBehavior flow
debug_php = r'''<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate what RouteAccessBehavior does for site/system-settings
$rawPathInfo = 'site/system-settings';
echo "=== Simulating RouteAccessBehavior for: {$rawPathInfo} ===\n\n";

// Step 1: getControllerUniqueIdFromPath
$parts = explode('/', $rawPathInfo);
$action = array_pop($parts);
$controllerId = implode('/', $parts);
echo "controllerId: {$controllerId}\n";
echo "action: {$action}\n\n";

// Step 2: Load Yii and check Permissions
define('YII_DEBUG', false);
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../../backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../backend/config/main.php',
    require __DIR__ . '/../../backend/config/main-local.php'
);

// Don't actually run the app, just init components
$app = new yii\web\Application($config);

$map = \common\helper\Permissions::getRoutePermissionMap();

echo "--- Route permission map check ---\n";
echo "Has 'site/system-settings' key: " . (isset($map['site/system-settings']) ? 'YES' : 'NO') . "\n";
echo "Has 'site/image-manager' key: " . (isset($map['site/image-manager']) ? 'YES' : 'NO') . "\n";
echo "Has 'site' key: " . (isset($map['site']) ? 'YES' : 'NO') . "\n\n";

// Step 3: Check getRequiredPermissionsForRoute
$fullRoutePerms = \common\helper\Permissions::getRequiredPermissionsForRoute('site/system-settings');
echo "getRequiredPermissionsForRoute('site/system-settings'): ";
if ($fullRoutePerms === null) {
    echo "NULL\n";
} else {
    echo count($fullRoutePerms) . " permissions\n";
    echo "  First 3: " . implode(', ', array_slice($fullRoutePerms, 0, 3)) . "\n";
}

$controllerPerms = \common\helper\Permissions::getRequiredPermissionsForRoute('site');
echo "\ngetRequiredPermissionsForRoute('site'): ";
if ($controllerPerms === null) {
    echo "NULL\n";
} else {
    echo count($controllerPerms) . " permissions: " . implode(', ', $controllerPerms) . "\n";
}

// Step 4: Check yara's can() for settings permissions
echo "\n--- Checking yara (id=94) can() ---\n";
$settingsPerms = \common\helper\Permissions::getSettingsPermissions();
$auth = Yii::$app->authManager;

// Manually check
foreach ($settingsPerms as $sp) {
    $canResult = $auth->checkAccess(94, $sp);
    if ($canResult) {
        echo "  can('{$sp}'): TRUE\n";
        break; // Just need one
    }
}

// Check if any works
$anyWorks = false;
foreach ($settingsPerms as $sp) {
    if ($auth->checkAccess(94, $sp)) {
        $anyWorks = true;
        break;
    }
}
echo "\nhasAnyPermission result: " . ($anyWorks ? 'TRUE (should allow)' : 'FALSE (will block)') . "\n";

echo "\n=== Done ===\n";
'''
with sftp.open(f'{proj}/backend/web/debug_route.php', 'w') as f:
    f.write(debug_php)

run('Test route debug via web', f'curl -sk https://jadal.aqssat.co/debug_route.php 2>&1')

# Cleanup
run('Cleanup', f'rm -f {proj}/backend/web/opclear.php {proj}/backend/web/debug_route.php')

sftp.close()
ssh.close()
print('Done!')
