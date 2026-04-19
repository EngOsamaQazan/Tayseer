<?php
/**
 * End-to-end test: load Yii app for one tenant, call Fahras, inspect log.
 *
 * Usage:  php /var/www/<tenant>.aqssat.co/scripts/e2e_test.php
 */
declare(strict_types=1);

$tenantRoot = dirname(__DIR__);

// Bootstrap as the backend application (where the `fahras` component is registered)
defined('YII_DEBUG')   or define('YII_DEBUG', false);
defined('YII_ENV')     or define('YII_ENV', 'prod');

require $tenantRoot . '/vendor/autoload.php';
require $tenantRoot . '/vendor/yiisoft/yii2/Yii.php';
require $tenantRoot . '/common/config/bootstrap.php';
require $tenantRoot . '/backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require $tenantRoot . '/common/config/main.php',
    require $tenantRoot . '/common/config/main-local.php',
    require $tenantRoot . '/backend/config/main.php',
    require $tenantRoot . '/backend/config/main-local.php'
);
new yii\web\Application($config);

$tenant = basename($tenantRoot, '.aqssat.co');
echo "── tenant: {$tenant} ──\n";

$fahras = Yii::$app->get('fahras', false);
if (!$fahras) {
    fwrite(STDERR, "FAIL: 'fahras' component not registered.\n");
    exit(1);
}

$cfg = Yii::$app->params['fahras'] ?? [];
echo "config:\n";
echo "  enabled       = " . var_export($cfg['enabled'] ?? null, true) . "\n";
echo "  baseUrl       = " . ($cfg['baseUrl'] ?? '?') . "\n";
echo "  token         = " . (empty($cfg['token']) ? '(empty!)' : substr((string)$cfg['token'], 0, 20) . '…' . substr((string)$cfg['token'], -6)) . "\n";
echo "  failurePolicy = " . ($cfg['failurePolicy'] ?? '?') . "\n";

// ── 1) Call check() ───────────────────────────────────────────────
echo "\n[1] check(idNumber=9999999999, name='عميل اختبار النشر')\n";
$verdict = $fahras->check('9999999999', 'عميل اختبار النشر', '0790000000');
print_r([
    'verdict'      => $verdict->verdict,
    'reasonCode'   => $verdict->reasonCode,
    'reasonAr'     => $verdict->reasonAr,
    'httpStatus'   => $verdict->httpStatus,
    'fromCache'    => $verdict->fromCache,
    'durationMs'   => $verdict->durationMs,
    'requestId'    => $verdict->requestId,
    'matchesCount' => count($verdict->matches),
    'remoteErrors' => $verdict->remoteErrors,
    'blocks'       => $verdict->blocks($cfg['failurePolicy'] ?? 'closed'),
    'warns'        => $verdict->warns(),
]);

// ── 2) Persist to audit log ───────────────────────────────────────
echo "\n[2] FahrasCheckLog::record() — persisting verdict\n";
// Direct save (so we can see validation/db errors)
$row = new common\models\FahrasCheckLog();
$row->user_id     = null;
$row->customer_id = null;
$row->id_number   = '9999999999';
$row->name        = 'عميل اختبار النشر';
$row->phone       = '0790000000';
$row->verdict     = $verdict->verdict;
$row->reason_code = $verdict->reasonCode;
$row->reason_ar   = $verdict->reasonAr;
$row->matches_json= $verdict->matches ? json_encode($verdict->matches, JSON_UNESCAPED_UNICODE) : null;
$row->http_status = $verdict->httpStatus;
$row->request_id  = $verdict->requestId;
$row->duration_ms = $verdict->durationMs;
$row->from_cache  = $verdict->fromCache ? 1 : 0;
$row->source      = common\models\FahrasCheckLog::SOURCE_MANUAL;
$row->created_at  = time();
if (!$row->save()) {
    echo "  validate? " . var_export($row->validate(), true) . "\n";
    echo "  errors:\n";
    print_r($row->errors);
    echo "  attributes:\n";
    print_r($row->attributes);
    exit(1);
}
$logId = $row->id;
echo "  inserted id = {$logId}\n";

// ── 3) Read it back ───────────────────────────────────────────────
$row = common\models\FahrasCheckLog::findOne($logId);
echo "\n[3] read-back row:\n";
print_r([
    'id'         => $row->id,
    'verdict'    => $row->verdict,
    'reason_ar'  => $row->reason_ar,
    'source'     => $row->source,
    'http_status'=> $row->http_status,
    'duration_ms'=> $row->duration_ms,
    'created_at' => $row->created_at,
]);

echo "\n✓ End-to-end test completed successfully.\n";
