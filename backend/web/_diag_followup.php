<?php
/**
 * Temporary diagnostic script for followUpReport 500 error.
 * Access: https://namaa.aqssat.co/_diag_followup.php
 * DELETE THIS FILE AFTER DIAGNOSIS.
 */
header('Content-Type: text/plain; charset=utf-8');

$token = $_GET['t'] ?? '';
if ($token !== 'tayseer2026diag') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

try {
    $app = new yii\web\Application($config);
} catch (\Throwable $e) {
    echo "BOOT ERROR: " . $e->getMessage() . "\n";
    exit;
}

$db = Yii::$app->db;
echo "=== Tayseer followUpReport Diagnostic ===\n";
echo "Site: " . Yii::$app->request->hostInfo . "\n";
echo "DB DSN: " . $db->dsn . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$checks = [
    'os_contracts' => 'TABLE',
    'os_follow_up' => 'TABLE',
    'os_loan_scheduling' => 'TABLE',
    'os_income' => 'TABLE',
    'os_judiciary' => 'TABLE',
    'os_expenses' => 'TABLE',
    'os_contract_adjustments' => 'TABLE',
    'os_contracts_customers' => 'TABLE',
    'os_customers' => 'TABLE',
    'os_jobs' => 'TABLE',
    'os_follow_up_report' => 'VIEW',
    'os_follow_up_no_contact' => 'VIEW',
    'os_vw_contract_customers_names' => 'VIEW',
    'os_vw_contract_balance' => 'VIEW',
];

echo "=== Database Objects Check ===\n";
foreach ($checks as $name => $expectedType) {
    try {
        $row = $db->createCommand("SHOW FULL TABLES LIKE :name", [':name' => $name])->queryOne();
        if ($row) {
            $type = array_values($row)[1] ?? '?';
            $status = ($type === 'BASE TABLE' ? 'TABLE' : $type) === $expectedType ? 'OK' : "WRONG TYPE ($type)";
            echo "  [$status] $name ($type)\n";
        } else {
            echo "  [MISSING] $name (expected: $expectedType)\n";
        }
    } catch (\Throwable $e) {
        echo "  [ERROR] $name: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Column Check: os_contracts ===\n";
$requiredCols = ['id', 'status', 'is_can_not_contact', 'followed_by', 'seller_id',
    'total_value', 'monthly_installment_value', 'first_installment_date', 'company_id', 'is_deleted'];
try {
    $cols = $db->createCommand("SHOW COLUMNS FROM os_contracts")->queryAll();
    $colNames = array_column($cols, 'Field');
    foreach ($requiredCols as $c) {
        echo "  " . (in_array($c, $colNames) ? '[OK]' : '[MISSING]') . " $c\n";
    }
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Recent Migrations (last 10) ===\n";
try {
    $rows = $db->createCommand("SELECT version, apply_time FROM os_migration ORDER BY apply_time DESC LIMIT 10")->queryAll();
    foreach ($rows as $r) {
        echo "  " . date('Y-m-d H:i', $r['apply_time']) . " | " . $r['version'] . "\n";
    }
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Key Migration Check ===\n";
$keyMigrations = [
    'm260227_100000_create_contract_adjustments_table',
    'm260326_100001_create_vw_contract_balance',
    'm260326_100002_create_vw_contract_customers_names',
    'm260326_100003_stabilize_follow_up_report_views',
    'm260326_200001_create_vw_contracts_overview',
    'm260327_000001_add_performance_indexes',
];
try {
    foreach ($keyMigrations as $mig) {
        $row = $db->createCommand("SELECT apply_time FROM os_migration WHERE version = :v", [':v' => $mig])->queryOne();
        if ($row) {
            echo "  [APPLIED] $mig (" . date('Y-m-d H:i', $row['apply_time']) . ")\n";
        } else {
            echo "  [NOT APPLIED] $mig\n";
        }
    }
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test: followUpReport query ===\n";
try {
    $count = $db->createCommand("SELECT COUNT(*) FROM os_follow_up_report")->queryScalar();
    echo "  [OK] os_follow_up_report has $count rows\n";
} catch (\Throwable $e) {
    echo "  [FAIL] " . $e->getMessage() . "\n";
}

echo "\n=== Test: vw_contract_customers_names query ===\n";
try {
    $count = $db->createCommand("SELECT COUNT(*) FROM os_vw_contract_customers_names")->queryScalar();
    echo "  [OK] os_vw_contract_customers_names has $count rows\n";
} catch (\Throwable $e) {
    echo "  [FAIL] " . $e->getMessage() . "\n";
}

echo "\n=== Test: card stats query (from actionIndex) ===\n";
try {
    $row = $db->createCommand("
        SELECT
            SUM(CASE WHEN is_can_not_contact = 0 AND (reminder IS NULL OR reminder <= CURDATE() OR never_followed = 1) THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN is_can_not_contact = 1 THEN 1 ELSE 0 END) AS no_contact_count
        FROM os_follow_up_report
    ")->queryOne();
    echo "  [OK] active=" . ($row['active_count'] ?? 0) . " no_contact=" . ($row['no_contact_count'] ?? 0) . "\n";
} catch (\Throwable $e) {
    echo "  [FAIL] " . $e->getMessage() . "\n";
}

echo "\n=== Test: Full actionIndex simulation ===\n";
try {
    $searchModel = new \backend\modules\followUpReport\models\FollowUpReportSearch();
    $params = ['FollowUpReportSearch' => ['is_can_not_contact' => '0', 'reminder' => date('Y-m-d')]];
    $dataProvider = $searchModel->search($params);
    $count = $dataProvider->getTotalCount();
    echo "  [OK] DataProvider totalCount = $count\n";

    $models = $dataProvider->getModels();
    echo "  [OK] Models loaded: " . count($models) . "\n";

    if (!empty($models)) {
        $contractIds = \yii\helpers\ArrayHelper::getColumn($models, 'id');
        $idList = implode(',', array_map('intval', $contractIds));
        $namesMap = $db->createCommand("SELECT contract_id, client_names FROM os_vw_contract_customers_names WHERE contract_id IN ($idList)")->queryAll();
        echo "  [OK] NamesMap loaded: " . count($namesMap) . " entries\n";
    }
} catch (\Throwable $e) {
    echo "  [FAIL] " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test: Jobs model ===\n";
try {
    $count = $db->createCommand("SELECT COUNT(*) FROM os_jobs WHERE is_deleted = 0 OR is_deleted IS NULL")->queryScalar();
    echo "  [OK] os_jobs has $count active rows\n";
} catch (\Throwable $e) {
    echo "  [FAIL] " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";
