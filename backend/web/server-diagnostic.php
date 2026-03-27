<?php
/**
 * Server Diagnostic Tool — Tayseer ERP
 * يفحص إعدادات السيرفر ويقدم توصيات لتحسين الأداء
 * 
 * الاستخدام: https://your-domain.com/server-diagnostic.php?key=tayseer2026
 * احذف هذا الملف بعد الانتهاء من التشخيص!
 */

if (($_GET['key'] ?? '') !== 'tayseer2026') {
    http_response_code(403);
    die('Access denied');
}

header('Content-Type: text/html; charset=utf-8');

$checks = [];

// ═══ PHP VERSION & SETTINGS ═══
$checks['PHP'] = [
    'Version' => PHP_VERSION,
    'SAPI' => php_sapi_name(),
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time') . 's',
    'Max Input Vars' => ini_get('max_input_vars'),
    'Upload Max Filesize' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size'),
    'Realpath Cache Size' => ini_get('realpath_cache_size'),
    'Realpath Cache TTL' => ini_get('realpath_cache_ttl') . 's',
];

// ═══ OPCACHE ═══
$opcache = [];
if (function_exists('opcache_get_status')) {
    $status = @opcache_get_status(false);
    $config = @opcache_get_configuration();
    if ($status && $config) {
        $opcache = [
            'Enabled' => $status['opcache_enabled'] ? 'YES ✓' : 'NO ✗ — CRITICAL!',
            'Memory Used' => round($status['memory_usage']['used_memory'] / 1048576, 1) . ' MB',
            'Memory Free' => round($status['memory_usage']['free_memory'] / 1048576, 1) . ' MB',
            'Memory Total' => $config['directives']['opcache.memory_consumption'] . ' MB',
            'Hit Rate' => round($status['opcache_statistics']['opcache_hit_rate'], 1) . '%',
            'Cached Scripts' => $status['opcache_statistics']['num_cached_scripts'],
            'Max Cached Keys' => $config['directives']['opcache.max_accelerated_files'],
            'Revalidate Freq' => $config['directives']['opcache.revalidate_freq'] . 's',
            'Save Comments' => $config['directives']['opcache.save_comments'] ? 'YES' : 'NO',
            'Validate Timestamps' => $config['directives']['opcache.validate_timestamps'] ? 'YES' : 'NO',
            'JIT' => isset($config['directives']['opcache.jit']) ? ($config['directives']['opcache.jit'] ?: 'disabled') : 'N/A',
        ];
    } else {
        $opcache = ['Status' => 'DISABLED — CRITICAL! Enable OPcache for massive speed improvement'];
    }
} else {
    $opcache = ['Status' => 'NOT INSTALLED — CRITICAL!'];
}
$checks['OPcache'] = $opcache;

// ═══ MYSQL / MariaDB ═══
try {
    require __DIR__ . '/../../common/config/main-local.php';
    $dbConfig = require __DIR__ . '/../../common/config/main-local.php';
    $dsn = $dbConfig['components']['db']['dsn'];
    $user = $dbConfig['components']['db']['username'];
    $pass = $dbConfig['components']['db']['password'];
    
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    
    $vars = [];
    $importantVars = [
        'innodb_buffer_pool_size', 'innodb_log_file_size', 'innodb_flush_log_at_trx_commit',
        'innodb_flush_method', 'innodb_io_capacity', 'innodb_io_capacity_max',
        'innodb_buffer_pool_instances', 'innodb_read_io_threads', 'innodb_write_io_threads',
        'key_buffer_size', 'max_connections', 'max_allowed_packet',
        'tmp_table_size', 'max_heap_table_size', 'table_open_cache',
        'query_cache_type', 'query_cache_size', 'query_cache_limit',
        'sort_buffer_size', 'read_buffer_size', 'read_rnd_buffer_size',
        'join_buffer_size', 'thread_cache_size', 'wait_timeout',
        'interactive_timeout', 'slow_query_log', 'long_query_time',
        'performance_schema', 'sql_mode',
    ];
    
    foreach ($importantVars as $v) {
        $row = $pdo->query("SHOW VARIABLES LIKE '$v'")->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $val = $row['Value'];
            if (in_array($v, ['innodb_buffer_pool_size', 'key_buffer_size', 'tmp_table_size',
                'max_heap_table_size', 'sort_buffer_size', 'read_buffer_size',
                'read_rnd_buffer_size', 'join_buffer_size', 'query_cache_size',
                'query_cache_limit', 'max_allowed_packet', 'innodb_log_file_size'])) {
                $val = round($val / 1048576) . ' MB';
            }
            $vars[$v] = $val;
        }
    }

    $globalStatus = [];
    $statusVars = ['Threads_connected', 'Threads_running', 'Slow_queries',
        'Questions', 'Uptime', 'Created_tmp_disk_tables', 'Created_tmp_tables',
        'Innodb_buffer_pool_read_requests', 'Innodb_buffer_pool_reads',
        'Qcache_hits', 'Qcache_inserts', 'Qcache_not_cached'];
    foreach ($statusVars as $sv) {
        $row = $pdo->query("SHOW GLOBAL STATUS LIKE '$sv'")->fetch(PDO::FETCH_ASSOC);
        if ($row) $globalStatus[$sv] = $row['Value'];
    }
    
    $bufferPoolHitRate = 'N/A';
    if (isset($globalStatus['Innodb_buffer_pool_read_requests'], $globalStatus['Innodb_buffer_pool_reads'])) {
        $req = (int)$globalStatus['Innodb_buffer_pool_read_requests'];
        $reads = (int)$globalStatus['Innodb_buffer_pool_reads'];
        if ($req > 0) {
            $bufferPoolHitRate = round(100 * (1 - $reads / $req), 2) . '%';
        }
    }

    $totalRAM = 'N/A';
    $ramRow = $pdo->query("SELECT @@global.innodb_buffer_pool_size as bp")->fetch(PDO::FETCH_ASSOC);
    
    $checks['MySQL'] = array_merge(
        ['Version' => $version, 'Buffer Pool Hit Rate' => $bufferPoolHitRate],
        $vars
    );
    $checks['MySQL Status'] = $globalStatus;
    
} catch (Exception $e) {
    $checks['MySQL'] = ['Error' => $e->getMessage()];
}

// ═══ APACHE MODULES ═══
$apacheModules = [];
if (function_exists('apache_get_modules')) {
    $mods = apache_get_modules();
    $important = ['mod_deflate', 'mod_expires', 'mod_headers', 'mod_rewrite',
        'mod_ssl', 'mod_http2', 'mod_brotli', 'mod_cache', 'mod_proxy_fcgi'];
    foreach ($important as $mod) {
        $apacheModules[$mod] = in_array($mod, $mods) ? 'YES ✓' : 'NO ✗';
    }
} else {
    $apacheModules['Note'] = 'Cannot detect (PHP-FPM mode)';
    foreach (['deflate', 'expires', 'headers', 'http2', 'brotli'] as $mod) {
        $test = shell_exec("apachectl -M 2>/dev/null | grep -i $mod");
        $apacheModules["mod_$mod"] = trim($test) ? 'YES ✓' : 'Unknown';
    }
}
$checks['Apache Modules'] = $apacheModules;

// ═══ SYSTEM INFO ═══
$sysInfo = [
    'Hostname' => gethostname(),
    'OS' => php_uname('s') . ' ' . php_uname('r'),
];
if (is_readable('/proc/cpuinfo')) {
    $cpuinfo = file_get_contents('/proc/cpuinfo');
    preg_match('/model name\s*:\s*(.+)/i', $cpuinfo, $m);
    $sysInfo['CPU'] = $m[1] ?? 'Unknown';
    $sysInfo['CPU Cores'] = substr_count($cpuinfo, 'processor');
}
if (is_readable('/proc/meminfo')) {
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s*(\d+)/', $meminfo, $m);
    $sysInfo['Total RAM'] = isset($m[1]) ? round($m[1] / 1024) . ' MB' : 'Unknown';
    preg_match('/MemAvailable:\s*(\d+)/', $meminfo, $m);
    $sysInfo['Available RAM'] = isset($m[1]) ? round($m[1] / 1024) . ' MB' : 'Unknown';
}
$sysInfo['Disk Free'] = round(disk_free_space('/') / 1073741824, 1) . ' GB';
$sysInfo['Disk Total'] = round(disk_total_space('/') / 1073741824, 1) . ' GB';

$loadAvg = sys_getloadavg();
if ($loadAvg) {
    $sysInfo['Load Average'] = implode(', ', array_map(fn($v) => round($v, 2), $loadAvg));
}
$checks['System'] = $sysInfo;

// ═══ RECOMMENDATIONS ═══
$recommendations = [];

if (isset($opcache['Enabled']) && strpos($opcache['Enabled'], 'NO') !== false) {
    $recommendations[] = '🔴 CRITICAL: OPcache is DISABLED! Enable it in php.ini: opcache.enable=1';
}
if (isset($opcache['Hit Rate']) && (float)$opcache['Hit Rate'] < 90) {
    $recommendations[] = '🟡 OPcache hit rate is low. Increase opcache.memory_consumption and opcache.max_accelerated_files';
}

if (isset($vars['innodb_buffer_pool_size'])) {
    $bpMB = (int)$vars['innodb_buffer_pool_size'];
    $totalMB = isset($sysInfo['Total RAM']) ? (int)$sysInfo['Total RAM'] : 0;
    if ($totalMB > 0 && $bpMB < $totalMB * 0.5) {
        $recommended = round($totalMB * 0.6);
        $recommendations[] = "🔴 innodb_buffer_pool_size is {$bpMB}MB but server has {$totalMB}MB RAM. Recommend: {$recommended}MB (60% of RAM)";
    }
}

if (isset($vars['tmp_table_size']) && (int)$vars['tmp_table_size'] < 64) {
    $recommendations[] = '🟡 tmp_table_size is low (' . $vars['tmp_table_size'] . '). Recommend: 256MB to reduce disk temp tables';
}
if (isset($vars['max_heap_table_size']) && (int)$vars['max_heap_table_size'] < 64) {
    $recommendations[] = '🟡 max_heap_table_size is low (' . $vars['max_heap_table_size'] . '). Recommend: 256MB';
}

if ($bufferPoolHitRate !== 'N/A' && (float)$bufferPoolHitRate < 99) {
    $recommendations[] = '🔴 InnoDB Buffer Pool hit rate is ' . $bufferPoolHitRate . '. Should be >99%. Increase innodb_buffer_pool_size';
}

if (isset($vars['slow_query_log']) && $vars['slow_query_log'] === 'OFF') {
    $recommendations[] = '🟡 Slow query log is OFF. Enable to identify slow queries: slow_query_log=1, long_query_time=1';
}

if (isset($vars['query_cache_type']) && $vars['query_cache_type'] === 'OFF') {
    if (strpos($version ?? '', 'MariaDB') !== false) {
        $recommendations[] = '🟡 Query cache is OFF on MariaDB. Consider enabling: query_cache_type=1, query_cache_size=128M';
    }
}

$phpMem = ini_get('memory_limit');
$phpMemMB = (int)$phpMem;
if (stripos($phpMem, 'G') !== false) $phpMemMB = (int)$phpMem * 1024;
if ($phpMemMB < 256) {
    $recommendations[] = '🟡 PHP memory_limit is ' . $phpMem . '. Recommend: 512M for complex reports';
}

$realpathCacheSize = ini_get('realpath_cache_size');
if ($realpathCacheSize && (int)$realpathCacheSize < 4096) {
    $recommendations[] = '🟡 realpath_cache_size is low (' . $realpathCacheSize . '). Recommend: 4096K for faster file resolution';
}

$checks['Recommendations'] = $recommendations ?: ['All checks passed!'];

// ═══ OUTPUT ═══
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head><meta charset="utf-8"><title>Server Diagnostic — Tayseer</title>
<style>
body{font-family:system-ui,-apple-system,sans-serif;max-width:900px;margin:20px auto;padding:0 20px;background:#f5f5f5;color:#333}
h1{text-align:center;color:#800020}
.section{background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.section h2{margin:0 0 12px;padding-bottom:8px;border-bottom:2px solid #800020;color:#800020;font-size:18px}
table{width:100%;border-collapse:collapse}
td{padding:6px 12px;border-bottom:1px solid #eee;font-size:14px}
td:first-child{font-weight:600;width:40%;color:#555}
.warn{color:#E65100;font-weight:600}
.ok{color:#2E7D32}
.critical{color:#C62828;font-weight:700}
.rec{padding:8px 12px;margin:4px 0;border-radius:6px;font-size:14px}
.rec:nth-child(odd){background:#FFF3E0}
.rec:nth-child(even){background:#E3F2FD}
.footer{text-align:center;color:#999;font-size:12px;margin-top:20px}
</style></head>
<body>
<h1>🔍 Server Diagnostic — Tayseer ERP</h1>
<p style="text-align:center;color:#666">Generated: <?= date('Y-m-d H:i:s') ?></p>

<?php foreach ($checks as $section => $items): ?>
<div class="section">
    <h2><?= htmlspecialchars($section) ?></h2>
    <?php if ($section === 'Recommendations'): ?>
        <?php foreach ($items as $r): ?>
            <div class="rec"><?= $r ?></div>
        <?php endforeach ?>
    <?php else: ?>
        <table>
        <?php foreach ($items as $key => $val): ?>
            <tr>
                <td><?= htmlspecialchars($key) ?></td>
                <td><?= htmlspecialchars($val) ?></td>
            </tr>
        <?php endforeach ?>
        </table>
    <?php endif ?>
</div>
<?php endforeach ?>

<div class="section">
    <h2>📋 MySQL Optimization Script</h2>
    <p style="color:#666;font-size:13px">انسخ هذا السكريبت وشغله على السيرفر إذا كانت الإعدادات تحتاج تحسين:</p>
    <pre style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:8px;overflow-x:auto;font-size:12px;direction:ltr;text-align:left">
# Add to /etc/mysql/mysql.conf.d/mysqld.cnf or /etc/mysql/mariadb.conf.d/50-server.cnf

[mysqld]
# ─── InnoDB Engine (most critical) ───
innodb_buffer_pool_size = <?= isset($sysInfo['Total RAM']) ? round((int)$sysInfo['Total RAM'] * 0.6) : 2048 ?>M
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_io_capacity = 2000
innodb_io_capacity_max = 4000
innodb_buffer_pool_instances = 4
innodb_read_io_threads = 8
innodb_write_io_threads = 8

# ─── Temp tables ───
tmp_table_size = 256M
max_heap_table_size = 256M

# ─── Connections & buffers ───
max_connections = 200
thread_cache_size = 32
table_open_cache = 4000
sort_buffer_size = 4M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
join_buffer_size = 4M

# ─── Slow query log ───
slow_query_log = 1
long_query_time = 1
slow_query_log_file = /var/log/mysql/slow.log

# ─── Performance ───
skip_name_resolve = 1
max_allowed_packet = 64M
</pre>
</div>

<div class="section">
    <h2>📋 PHP-FPM Optimization</h2>
    <pre style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:8px;overflow-x:auto;font-size:12px;direction:ltr;text-align:left">
# Edit: /etc/php/<?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>/fpm/pool.d/www.conf

pm = dynamic
pm.max_children = 30
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
pm.process_idle_timeout = 10s

# Edit: /etc/php/<?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>/fpm/conf.d/10-opcache.ini

opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.validate_timestamps=1
opcache.interned_strings_buffer=32
opcache.fast_shutdown=1
opcache.save_comments=1

# Edit: /etc/php/<?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>/fpm/php.ini

memory_limit = 512M
realpath_cache_size = 4096K
realpath_cache_ttl = 600
</pre>
</div>

<div class="section">
    <h2>📋 Apache Optimization</h2>
    <pre style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:8px;overflow-x:auto;font-size:12px;direction:ltr;text-align:left">
# Enable required modules:
sudo a2enmod deflate expires headers http2 rewrite

# Edit: /etc/apache2/apache2.conf
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# Enable HTTP/2:
# In your VirtualHost: Protocols h2 h2c http/1.1

sudo systemctl restart apache2
</pre>
</div>

<div class="footer">⚠️ احذف هذا الملف بعد الانتهاء من التشخيص!</div>
</body></html>
