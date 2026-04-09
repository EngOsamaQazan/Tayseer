<?php

namespace backend\modules\companyManagement\models;

use Yii;

class ProvisionService
{
    private Company $company;
    private array $adminData;
    private string $serverIp;
    private string $sshUser;
    private string $sshPass;
    private string $dbUser;
    private string $dbPass;

    public function __construct(Company $company, array $adminData = [])
    {
        $this->company   = $company;
        $this->adminData = $adminData;

        $provision = Yii::$app->params['provision'] ?? [];
        $this->serverIp = $company->server_ip ?: ($provision['serverIp'] ?? '31.220.82.115');
        $this->sshUser   = $provision['sshUser'] ?? 'root';
        $this->sshPass   = $provision['sshPass'] ?? '';
        $this->dbUser    = $provision['dbUser'] ?? 'osama';
        $this->dbPass    = $provision['dbPass'] ?? 'OsamaDB123';
    }

    public function runStep(string $step): array
    {
        return match ($step) {
            'dns'      => $this->stepDns(),
            'database' => $this->stepDatabase(),
            'server'   => $this->stepServer(),
            'ssl'      => $this->stepSsl(),
            'migrate'  => $this->stepMigrate(),
            'deploy'   => $this->stepDeploy(),
            default    => ['success' => false, 'message' => "خطوة غير معروفة: {$step}"],
        };
    }

    // ─── Step 1: DNS ─────────────────────────────────────────
    private function stepDns(): array
    {
        $dns = new GodaddyDnsService();
        $subdomain = $this->company->slug;

        $this->company->appendLog("DNS: إنشاء سجل A للنطاق {$subdomain}.aqssat.co -> {$this->serverIp}");

        $result = $dns->createARecord($subdomain, $this->serverIp);
        if (!$result['success']) {
            return $result;
        }

        $this->company->status = 'dns_ready';
        $this->company->appendLog("DNS: تم إنشاء السجل بنجاح");
        return ['success' => true, 'message' => "تم إنشاء سجل DNS: {$subdomain}.aqssat.co -> {$this->serverIp}"];
    }

    // ─── Step 2: Database ────────────────────────────────────
    private function stepDatabase(): array
    {
        $dbName = $this->company->db_name;
        $this->company->appendLog("DB: إنشاء قاعدة البيانات {$dbName}");

        $commands = [
            "mysql -u root -e \"CREATE DATABASE IF NOT EXISTS \`{$dbName}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\"",
            "mysql -u root -e \"GRANT ALL PRIVILEGES ON \`{$dbName}\`.* TO '{$this->dbUser}'@'localhost'; FLUSH PRIVILEGES;\"",
        ];

        foreach ($commands as $cmd) {
            $out = $this->ssh($cmd);
            if ($out === false) {
                return ['success' => false, 'message' => "فشل تنفيذ أمر قاعدة البيانات"];
            }
        }

        $this->company->appendLog("DB: تم إنشاء القاعدة وتعيين الصلاحيات");
        return ['success' => true, 'message' => "تم إنشاء {$dbName} بنجاح"];
    }

    // ─── Step 3: Server setup ────────────────────────────────
    private function stepServer(): array
    {
        $slug    = $this->company->slug;
        $domain  = $this->company->domain;
        $siteDir = "/var/www/{$domain}";
        $envDir  = "prod_{$slug}";

        $this->company->appendLog("Server: إعداد المجلد {$siteDir}");

        $script = <<<BASH
set -e
mkdir -p {$siteDir}
if [ ! -d "{$siteDir}/.git" ]; then
    cd /tmp
    rm -rf tayseer_provision
    git clone --depth 1 --branch main https://github.com/EngOsamaQazan/Tayseer.git tayseer_provision
    rsync -a --exclude='.env' --exclude='runtime/' --exclude='web/images/' --exclude='web/uploads/' tayseer_provision/ {$siteDir}/
    rm -rf /tmp/tayseer_provision
    cd {$siteDir}
    git init
    git remote add origin https://github.com/EngOsamaQazan/Tayseer.git
    git fetch origin main --depth 1
    git reset --hard origin/main
fi

cd {$siteDir}

# Apply environment configs
for cfg in common/config/main-local.php common/config/params-local.php \
           backend/web/index.php frontend/web/index.php \
           console/config/main-local.php console/config/params-local.php \
           yii; do
    if [ -f "environments/{$envDir}/\$cfg" ]; then
        mkdir -p "\$(dirname "\$cfg")"
        cp -f "environments/{$envDir}/\$cfg" "\$cfg"
    fi
done
chmod +x yii 2>/dev/null || true

# Cookie-key configs
for cfg in backend/config/main-local.php backend/config/params-local.php \
           frontend/config/main-local.php frontend/config/params-local.php; do
    if [ ! -f "\$cfg" ] && [ -f "environments/{$envDir}/\$cfg" ]; then
        mkdir -p "\$(dirname "\$cfg")"
        cp "environments/{$envDir}/\$cfg" "\$cfg"
        KEY=\$(openssl rand -hex 32)
        sed -i "s/=> ''/=> '\$KEY'/" "\$cfg"
    fi
done

# Composer
composer install --no-dev --no-interaction --optimize-autoloader 2>&1 || true

# Copy extensions.php from jadal (has dektrium registration)
cp /var/www/jadal.aqssat.co/vendor/yiisoft/extensions.php {$siteDir}/vendor/yiisoft/extensions.php 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data {$siteDir}/
chmod -R 775 {$siteDir}/backend/runtime/ {$siteDir}/frontend/runtime/ 2>/dev/null || true
chmod -R 775 {$siteDir}/backend/web/assets/ {$siteDir}/frontend/web/assets/ 2>/dev/null || true
mkdir -p {$siteDir}/backend/web/images/
cp /var/www/jadal.aqssat.co/backend/web/images/favicon.png {$siteDir}/backend/web/images/favicon.png 2>/dev/null || true

echo "SERVER_SETUP_DONE"
BASH;

        $out = $this->ssh($script, 300);
        if ($out === false || strpos($out, 'SERVER_SETUP_DONE') === false) {
            return ['success' => false, 'message' => "فشل إعداد السيرفر: " . substr($out ?: '', -500)];
        }

        $this->company->appendLog("Server: تم الاستنساخ والإعداد بنجاح");
        return ['success' => true, 'message' => "تم إعداد السيرفر في {$siteDir}"];
    }

    // ─── Step 4: Apache VHost + SSL ──────────────────────────
    private function stepSsl(): array
    {
        $domain  = $this->company->domain;
        $siteDir = "/var/www/{$domain}";

        $this->company->appendLog("SSL: إعداد Apache VHost و SSL لـ {$domain}");

        $vhostConf = <<<VHOST
<VirtualHost *:80>
    ServerName {$domain}
    DocumentRoot {$siteDir}/backend/web
    <Directory {$siteDir}/backend/web>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/{$domain}-error.log
    CustomLog \${APACHE_LOG_DIR}/{$domain}-access.log combined
</VirtualHost>
VHOST;

        $script = <<<BASH
set -e
# Write VHost
cat > /etc/apache2/sites-available/{$domain}.conf << 'VHOSTEOF'
{$vhostConf}
VHOSTEOF

a2ensite {$domain}.conf 2>/dev/null || true
systemctl reload apache2

# SSL via certbot
certbot --apache -d {$domain} --non-interactive --agree-tos --redirect --register-unsafely-without-email 2>&1 || true

systemctl reload apache2
echo "SSL_DONE"
BASH;

        $out = $this->ssh($script, 120);
        if ($out === false || strpos($out, 'SSL_DONE') === false) {
            return ['success' => false, 'message' => "فشل إعداد SSL: " . substr($out ?: '', -500)];
        }

        $this->company->appendLog("SSL: تم إعداد VHost و SSL بنجاح");
        return ['success' => true, 'message' => "تم تأمين {$domain} بـ HTTPS"];
    }

    // ─── Step 5: Migrations + RBAC + Admin ───────────────────
    private function stepMigrate(): array
    {
        $domain  = $this->company->domain;
        $siteDir = "/var/www/{$domain}";
        $dbName  = $this->company->db_name;

        $adminUser  = $this->adminData['username'] ?? 'admin';
        $adminEmail = $this->adminData['email'] ?? "admin@{$domain}";
        $adminPass  = $this->adminData['password'] ?? 'admin@123';

        $this->company->appendLog("Migrate: تشغيل التهجير وإنشاء المدير");

        $phpSetup = <<<'PHP'
<?php
$adminUser  = $argv[1];
$adminEmail = $argv[2];
$adminPass  = $argv[3];
$srcDb      = $argv[4];
$dstDb      = $argv[5];
$dbUser     = $argv[6];
$dbPass     = $argv[7];

$pdo = new PDO("mysql:host=localhost;dbname={$dstDb}", $dbUser, $dbPass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create admin user
$hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
$now  = time();

$pdo->exec("DELETE FROM os_user WHERE username='{$adminUser}'");
$pdo->exec("INSERT INTO os_user (username, email, password_hash, auth_key, confirmed_at, created_at, updated_at)
VALUES ('{$adminUser}', '{$adminEmail}', '{$hash}', '" . bin2hex(random_bytes(16)) . "', {$now}, {$now}, {$now})");
$userId = $pdo->lastInsertId();
$pdo->exec("INSERT INTO os_profile (user_id, name) VALUES ({$userId}, '{$adminUser}')");
echo "User created: ID={$userId}\n";

// Copy RBAC from source
$src = new PDO("mysql:host=localhost;dbname={$srcDb}", $dbUser, $dbPass);
$src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// auth_rule
$pdo->exec("DELETE FROM os_auth_assignment");
$pdo->exec("DELETE FROM os_auth_item_child");
$pdo->exec("DELETE FROM os_auth_item");
$pdo->exec("DELETE FROM os_auth_rule");

foreach ($src->query("SELECT * FROM os_auth_rule")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $stmt = $pdo->prepare("INSERT INTO os_auth_rule (name, data, created_at, updated_at) VALUES (?,?,?,?)");
    $stmt->execute([$r['name'], $r['data'], $r['created_at'], $r['updated_at']]);
}

// auth_item
foreach ($src->query("SELECT * FROM os_auth_item ORDER BY type DESC")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $stmt = $pdo->prepare("INSERT INTO os_auth_item (name, type, description, rule_name, data, created_at, updated_at) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$r['name'], $r['type'], $r['description'], $r['rule_name'], $r['data'], $r['created_at'], $r['updated_at']]);
}

// auth_item_child
foreach ($src->query("SELECT * FROM os_auth_item_child")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $stmt = $pdo->prepare("INSERT INTO os_auth_item_child (parent, child) VALUES (?,?)");
    $stmt->execute([$r['parent'], $r['child']]);
}

// Assign all to admin
$items = $pdo->query("SELECT name FROM os_auth_item")->fetchAll(PDO::FETCH_COLUMN);
$ins = $pdo->prepare("INSERT INTO os_auth_assignment (item_name, user_id, created_at) VALUES (?,?,?)");
foreach ($items as $name) {
    $ins->execute([$name, (string)$userId, $now]);
}

echo "RBAC: " . count($items) . " permissions assigned\n";
echo "SETUP_COMPLETE\n";
PHP;

        // Upload the setup script
        $this->sshUpload('/tmp/_tayseer_setup.php', $phpSetup);

        $script = <<<BASH
set -e
cd {$siteDir}

# Run migrations
php yii migrate/up --interactive=0 2>&1 || true

# Run admin + RBAC setup
php /tmp/_tayseer_setup.php '{$adminUser}' '{$adminEmail}' '{$adminPass}' 'namaa_jadal' '{$dbName}' '{$this->dbUser}' '{$this->dbPass}' 2>&1

rm -f /tmp/_tayseer_setup.php

# Clear caches
rm -rf {$siteDir}/backend/runtime/cache/*
rm -rf {$siteDir}/frontend/runtime/cache/*

echo "MIGRATE_DONE"
BASH;

        $out = $this->ssh($script, 180);
        if ($out === false || strpos($out, 'MIGRATE_DONE') === false) {
            return ['success' => false, 'message' => "فشل التهجير: " . substr($out ?: '', -500)];
        }

        $this->company->status = 'provisioned';
        $this->company->provisioned_at = time();
        $this->company->appendLog("Migrate: تم التهجير والصلاحيات وإنشاء المدير");
        return ['success' => true, 'message' => "تم تشغيل التهجير وإنشاء المدير بنجاح"];
    }

    // ─── Step 6: Update deploy scripts via GitHub API ────────
    private function stepDeploy(): array
    {
        $slug    = $this->company->slug;
        $domain  = $this->company->domain;
        $siteDir = "/var/www/{$domain}";
        $envDir  = "prod_{$slug}";

        $this->company->appendLog("Deploy: تحديث سكريبتات النشر");

        $ghToken = Yii::$app->params['provision']['githubToken'] ?? '';
        if (empty($ghToken)) {
            $this->company->appendLog("Deploy: تحذير — لا يوجد GitHub token. يجب تحديث deploy.yml و deploy-pull.sh يدوياً");
            $this->company->status = 'active';
            return [
                'success' => true,
                'message' => "تحذير: لا يوجد GitHub token. يرجى تحديث deploy.yml و deploy-pull.sh يدوياً لإضافة:\n" .
                    "deploy_site \"{$siteDir}\" \"{$envDir}\"\n" .
                    "pull_site \"{$siteDir}\" \"{$envDir}\"",
            ];
        }

        $repo = 'EngOsamaQazan/Tayseer';

        // Update deploy.yml
        $deployResult = $this->updateGitHubFile(
            $ghToken, $repo,
            '.github/workflows/deploy.yml',
            function ($content) use ($siteDir, $envDir, $slug) {
                // Add deploy_site line before wait
                if (strpos($content, $siteDir) !== false) {
                    return $content; // already present
                }

                // Find last PID line and add new one
                preg_match_all('/PID(\d+)=\$!/', $content, $m);
                $lastPidNum = max($m[1] ?? [0]);
                $newPidNum  = $lastPidNum + 1;

                $newLine = "            deploy_site \"{$siteDir}\" \"{$envDir}\" &\n            PID{$newPidNum}=\$!";
                $content = preg_replace(
                    '/(PID' . $lastPidNum . '=\$!)/',
                    "$1\n{$newLine}",
                    $content
                );

                // Update wait command
                $content = preg_replace(
                    '/wait \$PID1[^\\n]*/',
                    'wait $PID1 $PID2 $PID3 $PID4 $PID' . $newPidNum,
                    $content
                );

                // Add to cache flush loop
                $content = str_replace(
                    '/var/www/majd.aqssat.co;',
                    "/var/www/majd.aqssat.co /var/www/{$this->company->domain};",
                    $content
                );

                return $content;
            },
            "feat: add {$slug} to deploy pipeline"
        );

        // Update deploy-pull.sh
        $pullResult = $this->updateGitHubFile(
            $ghToken, $repo,
            '.github/deploy-pull.sh',
            function ($content) use ($siteDir, $envDir) {
                if (strpos($content, $siteDir) !== false) {
                    return $content;
                }
                $newLine = "pull_site \"{$siteDir}\" \"{$envDir}\" &";
                $content = str_replace("wait\n", "{$newLine}\nwait\n", $content);
                return $content;
            },
            "feat: add {$slug} to fast deploy"
        );

        $this->company->status = 'active';
        $this->company->appendLog("Deploy: تم تحديث سكريبتات النشر");
        return ['success' => true, 'message' => "تم تحديث deploy.yml و deploy-pull.sh"];
    }

    // ─── SSH Helper ──────────────────────────────────────────
    private function ssh(string $command, int $timeout = 60)
    {
        $connection = @ssh2_connect($this->serverIp, 22);
        if (!$connection) {
            // Fallback: use curl to a provisioning endpoint or exec
            return $this->sshViaExec($command, $timeout);
        }

        if (!@ssh2_auth_password($connection, $this->sshUser, $this->sshPass)) {
            return false;
        }

        $stream = ssh2_exec($connection, $command);
        if (!$stream) return false;

        stream_set_blocking($stream, true);
        stream_set_timeout($stream, $timeout);
        $output = stream_get_contents($stream);
        fclose($stream);

        return $output;
    }

    private function sshViaExec(string $command, int $timeout = 60)
    {
        $escapedPass = escapeshellarg($this->sshPass);
        $escapedCmd  = escapeshellarg($command);

        $fullCmd = "sshpass -p {$escapedPass} ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 "
            . "{$this->sshUser}@{$this->serverIp} {$escapedCmd} 2>&1";

        $output = [];
        $code   = 0;
        exec($fullCmd, $output, $code);

        return implode("\n", $output);
    }

    private function sshUpload(string $remotePath, string $content): bool
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'tayseer_');
        file_put_contents($tmpFile, $content);

        $escapedPass = escapeshellarg($this->sshPass);
        $cmd = "sshpass -p {$escapedPass} scp -o StrictHostKeyChecking=no "
            . escapeshellarg($tmpFile) . " {$this->sshUser}@{$this->serverIp}:{$remotePath} 2>&1";
        exec($cmd);

        unlink($tmpFile);
        return true;
    }

    // ─── GitHub API Helper ───────────────────────────────────
    private function updateGitHubFile(string $token, string $repo, string $path, callable $transform, string $message): array
    {
        $apiBase = "https://api.github.com/repos/{$repo}/contents/{$path}";
        $headers = [
            "Authorization: Bearer {$token}",
            "Accept: application/vnd.github.v3+json",
            "User-Agent: Tayseer-ERP",
        ];

        // GET current file
        $ch = curl_init($apiBase);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => "GitHub API: فشل جلب الملف ({$httpCode})"];
        }

        $data = json_decode($response, true);
        $currentContent = base64_decode($data['content']);
        $sha = $data['sha'];

        // Transform
        $newContent = $transform($currentContent);
        if ($newContent === $currentContent) {
            return ['success' => true, 'message' => "الملف محدّث بالفعل"];
        }

        // PUT updated file
        $putData = json_encode([
            'message' => $message,
            'content' => base64_encode($newContent),
            'sha'     => $sha,
        ]);

        $ch = curl_init($apiBase);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $putData,
            CURLOPT_HTTPHEADER => array_merge($headers, ['Content-Type: application/json']),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            return ['success' => true, 'message' => "تم تحديث {$path}"];
        }

        return ['success' => false, 'message' => "GitHub API: فشل التحديث ({$httpCode})"];
    }
}
