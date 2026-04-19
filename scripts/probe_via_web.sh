#!/usr/bin/env bash
# Final web-context verification: bootstrap Yii inside Apache request, call
# Fahras through the live service, render a clean JSON for each tenant.
set -u
TS=$(date +%s)

for tenant in jadal majd namaa watar; do
    DROP="/var/www/${tenant}.aqssat.co/backend/web/__fahras_probe_${TS}.php"
    cat > "$DROP" <<'PHP'
<?php
header('Content-Type: text/plain; charset=UTF-8');
defined('YII_DEBUG')   or define('YII_DEBUG', false);
defined('YII_ENV')     or define('YII_ENV', 'prod');

$root = dirname(__DIR__, 2);  // backend/web → project root

require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
require $root . '/backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require $root . '/common/config/main.php',
    require $root . '/common/config/main-local.php',
    require $root . '/backend/config/main.php',
    require $root . '/backend/config/main-local.php'
);

// Avoid web Application bootstrap re-routing — just instantiate config.
new yii\web\Application($config);

$f = Yii::$app->params['fahras'] ?? [];
echo "== params['fahras'] ==\n";
echo "  enabled  = " . var_export($f['enabled'] ?? null, true) . "\n";
echo "  baseUrl  = " . ($f['baseUrl'] ?? '?') . "\n";
echo "  token    = " . (empty($f['token']) ? '(EMPTY!)' : substr($f['token'],0,20).'…'.substr($f['token'],-6)) . "\n";
echo "  policy   = " . ($f['failurePolicy'] ?? '?') . "\n";

echo "\n== Yii::\$app->fahras->check('9999999999', 'اختبار', '0790000000') ==\n";
$svc = Yii::$app->get('fahras', false);
if (!$svc) { echo "  ✗ component not registered\n"; exit; }
$v = $svc->check('9999999999', 'اختبار', '0790000000');
echo "  verdict     = " . $v->verdict . "\n";
echo "  http_status = " . ($v->httpStatus ?? '-') . "\n";
echo "  reason_ar   = " . ($v->reasonAr ?? '-') . "\n";
echo "  duration_ms = " . ($v->durationMs ?? 0) . "\n";
echo "  blocks      = " . ($v->blocks($f['failurePolicy'] ?? 'closed') ? 'YES' : 'no') . "\n";
PHP

    chown www-data:www-data "$DROP"
    URL="https://${tenant}.aqssat.co/__fahras_probe_${TS}.php"
    echo "── ${tenant} ──"
    curl -k -sS "$URL"
    rm -f "$DROP"
    echo
done
