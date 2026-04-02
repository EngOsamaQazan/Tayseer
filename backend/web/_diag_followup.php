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

// ═══ AUTO-FIX: Re-create views if requested ═══
if (isset($_GET['fix']) && $_GET['fix'] === '1') {
    echo "\n=== APPLYING FIX: Re-creating views ===\n";

    $viewSql = [
        'os_follow_up_report' => "
CREATE OR REPLACE VIEW os_follow_up_report AS
SELECT
    c.*,
    f.date_time      AS last_follow_up,
    f.promise_to_pay_at,
    f.reminder,
    IFNULL(payments.total_paid, 0) AS total_paid,
    COALESCE(ls.monthly_installment, c.monthly_installment_value) AS effective_installment,
    LEAST(
        GREATEST(0,
            PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
            + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                   THEN 1 ELSE 0 END
        ),
        CEIL(
            GREATEST(0,
                c.total_value
                + IFNULL(exp_sum.total_expenses, 0)
                + IFNULL(jud.total_lawyer, 0)
                - IFNULL(adj.total_adjustments, 0)
                - IFNULL(payments.total_paid, 0)
            ) / GREATEST(COALESCE(ls.monthly_installment, c.monthly_installment_value), 1)
        )
    ) AS due_installments,
    LEAST(
        CASE
            WHEN jud.jud_id IS NOT NULL AND ls.id IS NULL THEN
                GREATEST(0,
                    c.total_value
                    + IFNULL(exp_sum.total_expenses, 0)
                    + IFNULL(jud.total_lawyer, 0)
                    - IFNULL(adj.total_adjustments, 0)
                    - IFNULL(payments.total_paid, 0)
                )
            ELSE
                GREATEST(0,
                    (GREATEST(0,
                        PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                            DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                        + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                               THEN 1 ELSE 0 END
                    ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                    - IFNULL(payments.total_paid, 0)
                )
        END,
        GREATEST(0,
            c.total_value
            + IFNULL(exp_sum.total_expenses, 0)
            + IFNULL(jud.total_lawyer, 0)
            - IFNULL(adj.total_adjustments, 0)
            - IFNULL(payments.total_paid, 0)
        )
    ) AS due_amount,
    CASE WHEN f.id IS NULL THEN 1 ELSE 0 END AS never_followed
FROM os_contracts c
LEFT JOIN os_follow_up f ON f.contract_id = c.id
    AND f.id = (SELECT MAX(id) FROM os_follow_up WHERE contract_id = c.id)
LEFT JOIN os_loan_scheduling ls ON ls.contract_id = c.id
    AND ls.is_deleted = 0
    AND ls.id = (SELECT MAX(id) FROM os_loan_scheduling WHERE contract_id = c.id AND is_deleted = 0)
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_paid
    FROM os_income GROUP BY contract_id
) payments ON c.id = payments.contract_id
LEFT JOIN (
    SELECT contract_id, MAX(id) AS jud_id, SUM(lawyer_cost) AS total_lawyer
    FROM os_judiciary WHERE is_deleted = 0
    GROUP BY contract_id
) jud ON jud.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_expenses
    FROM os_expenses
    WHERE (is_deleted = 0 OR is_deleted IS NULL)
    GROUP BY contract_id
) exp_sum ON exp_sum.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_adjustments
    FROM os_contract_adjustments WHERE is_deleted = 0
    GROUP BY contract_id
) adj ON adj.contract_id = c.id
WHERE
    c.status NOT IN ('finished','canceled')
    AND NOT (
        c.status = 'judiciary'
        AND (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
             - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) <= 0.01
    )
    AND (
        (c.is_can_not_contact = 0 AND (
            (jud.jud_id IS NOT NULL AND ls.id IS NULL AND
                (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
                 - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) > 5
            )
            OR
            ((jud.jud_id IS NULL OR ls.id IS NOT NULL) AND
                ((GREATEST(0,
                    PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                        DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                    + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                           THEN 1 ELSE 0 END
                ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                - IFNULL(payments.total_paid, 0)) > 5
            )
        ))
        OR
        c.is_can_not_contact = 1
    )",

        'os_follow_up_no_contact' => "
CREATE OR REPLACE VIEW os_follow_up_no_contact AS
SELECT
    c.*,
    f.date_time,
    f.promise_to_pay_at,
    f.reminder,
    IFNULL(payments.total_paid, 0) AS total_paid,
    COALESCE(ls.monthly_installment, c.monthly_installment_value) AS effective_installment,
    LEAST(
        GREATEST(0,
            PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
            + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                   THEN 1 ELSE 0 END
        ),
        CEIL(
            GREATEST(0,
                c.total_value
                + IFNULL(exp_sum.total_expenses, 0)
                + IFNULL(jud.total_lawyer, 0)
                - IFNULL(adj.total_adjustments, 0)
                - IFNULL(payments.total_paid, 0)
            ) / GREATEST(COALESCE(ls.monthly_installment, c.monthly_installment_value), 1)
        )
    ) AS due_installments,
    LEAST(
        CASE
            WHEN jud.jud_id IS NOT NULL AND ls.id IS NULL THEN
                GREATEST(0,
                    c.total_value
                    + IFNULL(exp_sum.total_expenses, 0)
                    + IFNULL(jud.total_lawyer, 0)
                    - IFNULL(adj.total_adjustments, 0)
                    - IFNULL(payments.total_paid, 0)
                )
            ELSE
                GREATEST(0,
                    (GREATEST(0,
                        PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                            DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                        + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                               THEN 1 ELSE 0 END
                    ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                    - IFNULL(payments.total_paid, 0)
                )
        END,
        GREATEST(0,
            c.total_value
            + IFNULL(exp_sum.total_expenses, 0)
            + IFNULL(jud.total_lawyer, 0)
            - IFNULL(adj.total_adjustments, 0)
            - IFNULL(payments.total_paid, 0)
        )
    ) AS due_amount
FROM os_contracts c
LEFT JOIN os_follow_up f ON f.contract_id = c.id
    AND f.id = (SELECT MAX(id) FROM os_follow_up WHERE contract_id = c.id)
LEFT JOIN os_loan_scheduling ls ON ls.contract_id = c.id
    AND ls.is_deleted = 0
    AND ls.id = (SELECT MAX(id) FROM os_loan_scheduling WHERE contract_id = c.id AND is_deleted = 0)
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_paid
    FROM os_income GROUP BY contract_id
) payments ON c.id = payments.contract_id
LEFT JOIN (
    SELECT contract_id, MAX(id) AS jud_id, SUM(lawyer_cost) AS total_lawyer
    FROM os_judiciary WHERE is_deleted = 0
    GROUP BY contract_id
) jud ON jud.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_expenses
    FROM os_expenses
    WHERE (is_deleted = 0 OR is_deleted IS NULL)
    GROUP BY contract_id
) exp_sum ON exp_sum.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_adjustments
    FROM os_contract_adjustments WHERE is_deleted = 0
    GROUP BY contract_id
) adj ON adj.contract_id = c.id
WHERE c.is_can_not_contact = 1
    AND NOT (
        c.status = 'judiciary'
        AND (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
             - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) <= 0.01
    )",

        'os_vw_contract_customers_names' => "
CREATE OR REPLACE VIEW os_vw_contract_customers_names AS
SELECT
    cc.contract_id,
    GROUP_CONCAT(CASE WHEN cc.customer_type = 'client' THEN c.name END ORDER BY c.name SEPARATOR '، ') AS client_names,
    GROUP_CONCAT(CASE WHEN cc.customer_type = 'guarantor' THEN c.name END ORDER BY c.name SEPARATOR '، ') AS guarantor_names,
    GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR '، ') AS all_party_names,
    MIN(CASE WHEN cc.customer_type = 'client' THEN c.primary_phone_number END) AS client_phone
FROM os_contracts_customers cc
INNER JOIN os_customers c ON c.id = cc.customer_id
GROUP BY cc.contract_id",
    ];

    foreach ($viewSql as $viewName => $sql) {
        try {
            $db->createCommand($sql)->execute();
            echo "  [FIXED] $viewName re-created successfully\n";
        } catch (\Throwable $e) {
            echo "  [FAIL] $viewName: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== Clearing schema cache ===\n";
    try {
        $db->getSchema()->refresh();
        Yii::$app->cache->flush();
        echo "  [OK] Schema cache cleared\n";
    } catch (\Throwable $e) {
        echo "  [WARN] " . $e->getMessage() . "\n";
    }

    echo "\n=== Verify fix ===\n";
    try {
        $count = $db->createCommand("SELECT COUNT(*) FROM os_follow_up_report WHERE never_followed = 1")->queryScalar();
        echo "  [OK] never_followed column works, $count rows with never_followed=1\n";
    } catch (\Throwable $e) {
        echo "  [FAIL] " . $e->getMessage() . "\n";
    }
    try {
        $row = $db->createCommand("SELECT effective_installment, due_amount, due_installments FROM os_follow_up_report LIMIT 1")->queryOne();
        echo "  [OK] effective_installment, due_amount, due_installments columns work\n";
    } catch (\Throwable $e) {
        echo "  [FAIL] " . $e->getMessage() . "\n";
    }
}

echo "\n=== Done ===\n";
