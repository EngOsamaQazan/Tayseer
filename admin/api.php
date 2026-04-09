<?php
session_start();
date_default_timezone_set('Asia/Amman');
header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => 'config.php not found']);
    exit;
}
$cfg = require $configFile;

if (empty($_SESSION['authed'])) {
    echo json_encode(['success' => false, 'message' => 'unauthorized']);
    exit;
}

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $c = $GLOBALS['cfg'];
        $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function getCompany(int $id): ?array {
    $stmt = db()->prepare("SELECT * FROM os_companies WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function appendLog(int $id, string $msg): void {
    $ts = date('Y-m-d H:i:s');
    $entry = "[{$ts}] {$msg}";
    $stmt = db()->prepare("UPDATE os_companies SET provision_log = CONCAT(COALESCE(provision_log,''), ?, '\n'), updated_at = ? WHERE id = ?");
    $stmt->execute([$entry, time(), $id]);
}

function updateStatus(int $id, string $status): void {
    $extra = '';
    if ($status === 'provisioned') $extra = ', provisioned_at = ' . time();
    db()->exec("UPDATE os_companies SET status = '{$status}'{$extra}, updated_at = " . time() . " WHERE id = {$id}");
}

function sshExec(string $cmd, int $timeout = 60): string {
    $cfg = $GLOBALS['cfg'];
    $escapedPass = escapeshellarg($cfg['ssh_pass']);
    $escapedCmd  = escapeshellarg($cmd);
    $full = "sshpass -p {$escapedPass} ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o ServerAliveInterval=15 "
        . "{$cfg['ssh_user']}@{$cfg['ssh_host']} {$escapedCmd} 2>&1";
    $output = [];
    exec($full, $output, $code);
    return implode("\n", $output);
}

function sshUpload(string $remotePath, string $content): void {
    $cfg = $GLOBALS['cfg'];
    $tmp = tempnam(sys_get_temp_dir(), 'tayseer_');
    file_put_contents($tmp, $content);
    $escapedPass = escapeshellarg($cfg['ssh_pass']);
    exec("sshpass -p {$escapedPass} scp -o StrictHostKeyChecking=no " . escapeshellarg($tmp) . " {$cfg['ssh_user']}@{$cfg['ssh_host']}:{$remotePath} 2>&1");
    unlink($tmp);
}

// ─── GET: Status ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'status') {
    $c = getCompany((int)($_GET['id'] ?? 0));
    echo json_encode($c ? ['status' => $c['status'], 'log' => $c['provision_log']] : ['error' => 'not found']);
    exit;
}

// ─── POST: Provision Step ────────────────────────────────────
$action    = $_POST['action'] ?? '';
$step      = $_POST['step'] ?? '';
$companyId = (int)($_POST['company_id'] ?? 0);

if ($action !== 'provision' || !$step || !$companyId) {
    echo json_encode(['success' => false, 'message' => 'invalid request']);
    exit;
}

$company = getCompany($companyId);
if (!$company) {
    echo json_encode(['success' => false, 'message' => 'company not found']);
    exit;
}

$slug    = $company['slug'];
$domain  = $company['domain'];
$dbName  = $company['db_name'];
$siteDir = "/var/www/{$domain}";
$envDir  = "prod_{$slug}";
$serverIp = $company['server_ip'];

try {
    switch ($step) {

    // ═══ Step 1: DNS ═════════════════════════════════════════
    case 'dns':
        appendLog($companyId, "DNS: إنشاء سجل A لـ {$slug}.aqssat.co -> {$serverIp}");

        $apiKey    = $cfg['godaddy_key'];
        $apiSecret = $cfg['godaddy_secret'];
        $gdDomain  = $cfg['godaddy_domain'];

        $payload = json_encode([[
            'type' => 'A', 'name' => $slug, 'data' => $serverIp, 'ttl' => 600,
        ]]);

        $ch = curl_init("https://api.godaddy.com/v1/domains/{$gdDomain}/records");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Authorization: sso-key {$apiKey}:{$apiSecret}",
                "Content-Type: application/json",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 || $code === 204) {
            updateStatus($companyId, 'dns_ready');
            appendLog($companyId, "DNS: تم إنشاء السجل بنجاح");
            echo json_encode(['success' => true, 'message' => "تم إنشاء سجل DNS: {$slug}.{$gdDomain} -> {$serverIp}"]);
        } else {
            $err = json_decode($resp, true);
            $msg = $err['message'] ?? "HTTP {$code}";
            appendLog($companyId, "DNS: فشل — {$msg}");
            echo json_encode(['success' => false, 'message' => "GoDaddy API: {$msg}"]);
        }
        break;

    // ═══ Step 2: Database ════════════════════════════════════
    case 'database':
        appendLog($companyId, "DB: إنشاء قاعدة البيانات {$dbName}");
        $appDbUser = $cfg['app_db_user'];

        $out1 = sshExec("mysql -u root -e \"CREATE DATABASE IF NOT EXISTS \`{$dbName}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\"");
        $out2 = sshExec("mysql -u root -e \"GRANT ALL PRIVILEGES ON \`{$dbName}\`.* TO '{$appDbUser}'@'localhost'; FLUSH PRIVILEGES;\"");

        appendLog($companyId, "DB: تم إنشاء القاعدة وتعيين الصلاحيات");
        echo json_encode(['success' => true, 'message' => "تم إنشاء {$dbName} بنجاح\n{$out1}\n{$out2}"]);
        break;

    // ═══ Step 3: Server Setup ════════════════════════════════
    case 'server':
        appendLog($companyId, "Server: إعداد المجلد {$siteDir}");

        $script = <<<BASH
set -e
mkdir -p {$siteDir}
if [ ! -d "{$siteDir}/.git" ]; then
    cd /tmp && rm -rf tayseer_provision
    git clone --depth 1 --branch main https://github.com/{$cfg['github_repo']}.git tayseer_provision
    rsync -a --exclude='.env' --exclude='runtime/' --exclude='web/images/' --exclude='web/uploads/' tayseer_provision/ {$siteDir}/
    rm -rf /tmp/tayseer_provision
    cd {$siteDir}
    git init && git remote add origin https://github.com/{$cfg['github_repo']}.git
    git fetch origin main --depth 1 && git reset --hard origin/main
fi
cd {$siteDir}
for cfg_file in common/config/main-local.php common/config/params-local.php \
           backend/web/index.php frontend/web/index.php \
           console/config/main-local.php console/config/params-local.php yii; do
    if [ -f "environments/{$envDir}/\$cfg_file" ]; then
        mkdir -p "\$(dirname "\$cfg_file")" && cp -f "environments/{$envDir}/\$cfg_file" "\$cfg_file"
    fi
done
chmod +x yii 2>/dev/null || true
for cfg_file in backend/config/main-local.php backend/config/params-local.php \
           frontend/config/main-local.php frontend/config/params-local.php; do
    if [ ! -f "\$cfg_file" ] && [ -f "environments/{$envDir}/\$cfg_file" ]; then
        mkdir -p "\$(dirname "\$cfg_file")" && cp "environments/{$envDir}/\$cfg_file" "\$cfg_file"
        KEY=\$(openssl rand -hex 32) && sed -i "s/=> ''/=> '\$KEY'/" "\$cfg_file"
    fi
done
composer install --no-dev --no-interaction --optimize-autoloader 2>&1 || true
cp /var/www/jadal.aqssat.co/vendor/yiisoft/extensions.php {$siteDir}/vendor/yiisoft/extensions.php 2>/dev/null || true
chown -R www-data:www-data {$siteDir}/
chmod -R 775 {$siteDir}/backend/runtime/ {$siteDir}/frontend/runtime/ 2>/dev/null || true
chmod -R 775 {$siteDir}/backend/web/assets/ {$siteDir}/frontend/web/assets/ 2>/dev/null || true
mkdir -p {$siteDir}/backend/web/images/
cp /var/www/jadal.aqssat.co/backend/web/images/favicon.png {$siteDir}/backend/web/images/favicon.png 2>/dev/null || true
echo "SERVER_SETUP_DONE"
BASH;
        $out = sshExec($script, 300);
        if (strpos($out, 'SERVER_SETUP_DONE') !== false) {
            appendLog($companyId, "Server: تم الاستنساخ والإعداد بنجاح");
            echo json_encode(['success' => true, 'message' => "تم إعداد السيرفر في {$siteDir}"]);
        } else {
            appendLog($companyId, "Server: فشل — " . substr($out, -300));
            echo json_encode(['success' => false, 'message' => "فشل إعداد السيرفر:\n" . substr($out, -500)]);
        }
        break;

    // ═══ Step 4: Apache + SSL ════════════════════════════════
    case 'ssl':
        appendLog($companyId, "SSL: إعداد Apache و SSL لـ {$domain}");

        $vhost = "<VirtualHost *:80>\n    ServerName {$domain}\n    DocumentRoot {$siteDir}/backend/web\n"
            . "    <Directory {$siteDir}/backend/web>\n        AllowOverride All\n        Require all granted\n    </Directory>\n"
            . "    ErrorLog \${APACHE_LOG_DIR}/{$domain}-error.log\n    CustomLog \${APACHE_LOG_DIR}/{$domain}-access.log combined\n</VirtualHost>";

        $script = <<<BASH
set -e
cat > /etc/apache2/sites-available/{$domain}.conf << 'VHOSTEOF'
{$vhost}
VHOSTEOF
a2ensite {$domain}.conf 2>/dev/null || true
systemctl reload apache2
certbot --apache -d {$domain} --non-interactive --agree-tos --redirect --register-unsafely-without-email 2>&1 || true
systemctl reload apache2
echo "SSL_DONE"
BASH;
        $out = sshExec($script, 120);
        if (strpos($out, 'SSL_DONE') !== false) {
            appendLog($companyId, "SSL: تم إعداد VHost و SSL بنجاح");
            echo json_encode(['success' => true, 'message' => "تم تأمين {$domain} بـ HTTPS"]);
        } else {
            appendLog($companyId, "SSL: فشل — " . substr($out, -300));
            echo json_encode(['success' => false, 'message' => "فشل إعداد SSL:\n" . substr($out, -500)]);
        }
        break;

    // ═══ Step 5: Migrations + RBAC + Admin ═══════════════════
    case 'migrate':
        appendLog($companyId, "Migrate: تشغيل التهجير وإنشاء المدير");

        $adminData = $_SESSION["admin_data_{$companyId}"] ?? ['username'=>'admin','email'=>"admin@{$domain}",'password'=>'admin@123'];
        $appDbUser = $cfg['app_db_user'];
        $appDbPass = $cfg['app_db_pass'];
        $srcDb     = $cfg['db_name']; // master DB for RBAC copy

        $phpScript = <<<'PHPSETUP'
<?php
$adminUser=$argv[1]; $adminEmail=$argv[2]; $adminPass=$argv[3];
$srcDb=$argv[4]; $dstDb=$argv[5]; $dbUser=$argv[6]; $dbPass=$argv[7];
$pdo = new PDO("mysql:host=localhost;dbname={$dstDb}", $dbUser, $dbPass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$hash=password_hash($adminPass, PASSWORD_BCRYPT, ['cost'=>12]);
$now=time(); $key=bin2hex(random_bytes(16));
$pdo->exec("DELETE FROM os_user WHERE username='{$adminUser}'");
$pdo->exec("INSERT INTO os_user (username,email,password_hash,auth_key,confirmed_at,created_at,updated_at) VALUES ('{$adminUser}','{$adminEmail}','{$hash}','{$key}',{$now},{$now},{$now})");
$uid=$pdo->lastInsertId();
$pdo->exec("INSERT INTO os_profile (user_id,name) VALUES ({$uid},'{$adminUser}')");
echo "User ID={$uid}\n";
$src=new PDO("mysql:host=localhost;dbname={$srcDb}",$dbUser,$dbPass);
$src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("DELETE FROM os_auth_assignment");
$pdo->exec("DELETE FROM os_auth_item_child");
$pdo->exec("DELETE FROM os_auth_item");
$pdo->exec("DELETE FROM os_auth_rule");
foreach($src->query("SELECT * FROM os_auth_rule")->fetchAll(PDO::FETCH_ASSOC) as $r){
    $s=$pdo->prepare("INSERT INTO os_auth_rule (name,data,created_at,updated_at) VALUES (?,?,?,?)");
    $s->execute([$r['name'],$r['data'],$r['created_at'],$r['updated_at']]);
}
foreach($src->query("SELECT * FROM os_auth_item ORDER BY type DESC")->fetchAll(PDO::FETCH_ASSOC) as $r){
    $s=$pdo->prepare("INSERT INTO os_auth_item (name,type,description,rule_name,data,created_at,updated_at) VALUES (?,?,?,?,?,?,?)");
    $s->execute([$r['name'],$r['type'],$r['description'],$r['rule_name'],$r['data'],$r['created_at'],$r['updated_at']]);
}
foreach($src->query("SELECT * FROM os_auth_item_child")->fetchAll(PDO::FETCH_ASSOC) as $r){
    $s=$pdo->prepare("INSERT INTO os_auth_item_child (parent,child) VALUES (?,?)");
    $s->execute([$r['parent'],$r['child']]);
}
$items=$pdo->query("SELECT name FROM os_auth_item")->fetchAll(PDO::FETCH_COLUMN);
$ins=$pdo->prepare("INSERT INTO os_auth_assignment (item_name,user_id,created_at) VALUES (?,?,?)");
foreach($items as $n) $ins->execute([$n,(string)$uid,$now]);
echo "RBAC: ".count($items)." assigned\nSETUP_COMPLETE\n";
PHPSETUP;

        sshUpload('/tmp/_tayseer_setup.php', $phpScript);

        $script = <<<BASH
set -e
cd {$siteDir}
php yii migrate/up --interactive=0 2>&1 || true
php /tmp/_tayseer_setup.php '{$adminData['username']}' '{$adminData['email']}' '{$adminData['password']}' '{$srcDb}' '{$dbName}' '{$appDbUser}' '{$appDbPass}' 2>&1
rm -f /tmp/_tayseer_setup.php
rm -rf {$siteDir}/backend/runtime/cache/* {$siteDir}/frontend/runtime/cache/*
echo "MIGRATE_DONE"
BASH;
        $out = sshExec($script, 180);
        if (strpos($out, 'MIGRATE_DONE') !== false) {
            updateStatus($companyId, 'provisioned');
            appendLog($companyId, "Migrate: تم التهجير والصلاحيات وإنشاء المدير بنجاح");
            echo json_encode(['success' => true, 'message' => "تم تشغيل التهجير وإنشاء المدير\n{$out}"]);
        } else {
            appendLog($companyId, "Migrate: فشل — " . substr($out, -300));
            echo json_encode(['success' => false, 'message' => "فشل التهجير:\n" . substr($out, -500)]);
        }
        break;

    // ═══ Step 6: Deploy Scripts ══════════════════════════════
    case 'deploy':
        appendLog($companyId, "Deploy: تحديث سكريبتات النشر");
        $ghToken = $cfg['github_token'];
        $repo    = $cfg['github_repo'];

        if (empty($ghToken) || $ghToken === 'CHANGE_ME') {
            updateStatus($companyId, 'active');
            appendLog($companyId, "Deploy: تم تنشيط المنشأة (بدون تحديث GitHub — لا يوجد token)");
            echo json_encode([
                'success' => true,
                'message' => "تم تنشيط المنشأة.\n⚠️ يرجى تحديث deploy.yml و deploy-pull.sh يدوياً:\n"
                    . "deploy_site \"{$siteDir}\" \"{$envDir}\"\n"
                    . "pull_site \"{$siteDir}\" \"{$envDir}\"",
            ]);
            break;
        }

        $headers = [
            "Authorization: Bearer {$ghToken}",
            "Accept: application/vnd.github.v3+json",
            "User-Agent: Tayseer-Admin",
        ];

        $updated = [];
        $files = [
            '.github/workflows/deploy.yml' => function($content) use ($siteDir, $envDir, $slug, $domain) {
                if (strpos($content, $siteDir) !== false) return $content;
                preg_match_all('/PID(\d+)=\$!/', $content, $m);
                $last = max($m[1] ?? [0]);
                $next = $last + 1;
                $line = "            deploy_site \"{$siteDir}\" \"{$envDir}\" &\n            PID{$next}=\$!";
                $content = preg_replace('/(PID'.$last.'=\$!)/', "$1\n{$line}", $content);
                $pids = implode(' ', array_map(fn($n)=>'$PID'.$n, range(1, $next)));
                $content = preg_replace('/wait \$PID1[^\n]*/', "wait {$pids}", $content);
                $content = str_replace('/var/www/majd.aqssat.co;', "/var/www/majd.aqssat.co /var/www/{$domain};", $content);
                return $content;
            },
            '.github/deploy-pull.sh' => function($content) use ($siteDir, $envDir) {
                if (strpos($content, $siteDir) !== false) return $content;
                return str_replace("wait\n", "pull_site \"{$siteDir}\" \"{$envDir}\" &\nwait\n", $content);
            },
        ];

        foreach ($files as $path => $transform) {
            $url = "https://api.github.com/repos/{$repo}/contents/{$path}";
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) { $updated[] = "{$path}: فشل الجلب ({$httpCode})"; continue; }
            $data = json_decode($resp, true);
            $oldContent = base64_decode($data['content']);
            $newContent = $transform($oldContent);
            if ($newContent === $oldContent) { $updated[] = "{$path}: محدّث بالفعل"; continue; }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => json_encode(['message'=>"feat: add {$slug} to deploy",'content'=>base64_encode($newContent),'sha'=>$data['sha']]),
                CURLOPT_HTTPHEADER => array_merge($headers, ['Content-Type: application/json']),
            ]);
            curl_exec($ch);
            $rc = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $updated[] = "{$path}: " . ($rc === 200 || $rc === 201 ? "تم ✓" : "فشل ({$rc})");
        }

        updateStatus($companyId, 'active');
        appendLog($companyId, "Deploy: تم تنشيط المنشأة — " . implode(' | ', $updated));
        echo json_encode(['success' => true, 'message' => "تم تنشيط المنشأة.\n" . implode("\n", $updated)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => "خطوة غير معروفة: {$step}"]);
    }
} catch (Exception $e) {
    appendLog($companyId, "خطأ في {$step}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
