#!/usr/bin/env bash
# Simulate the browser's POST /customers/wizard/fahras-check inside Apache
# request context. Calls runAction() directly so the full controller path
# (auth bypassed via fake user) is exercised exactly like a logged-in rep.
set -u
TS=$(date +%s)

for tenant in jadal majd namaa watar; do
    DROP="/var/www/${tenant}.aqssat.co/backend/web/__fahras_action_${TS}.php"
    cat > "$DROP" <<'PHP'
<?php
header('Content-Type: text/plain; charset=UTF-8');
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV')   or define('YII_ENV', 'prod');

$root = dirname(__DIR__, 2);
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

new yii\web\Application($config);

// Force-fake POST so request->post() inside the action returns our values.
$_POST['id_number'] = '9999999999';
$_POST['name']      = 'عميل اختبار من المتصفح';
$_POST['phone']     = '0790000000';
$_POST['_csrf-backend'] = Yii::$app->request->csrfToken;
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    $svc = Yii::$app->fahras ?? null;
    if (!$svc) { echo "ERR: fahras component not registered\n"; exit; }

    $req      = Yii::$app->request;
    $idNumber = trim((string)$req->post('id_number', ''));
    $name     = trim((string)$req->post('name', ''));
    $phone    = trim((string)$req->post('phone', ''));

    $verdict = $svc->check($idNumber, $name ?: null, $phone ?: null);

    $resp = [
        'ok'           => true,
        'enabled'      => true,
        'verdict'      => $verdict->verdict,
        'reason_code'  => $verdict->reasonCode,
        'reason_ar'    => $verdict->reasonAr,
        'matches'      => $verdict->matches,
        'remote_errors'=> $verdict->remoteErrors,
        'request_id'   => $verdict->requestId,
        'blocks'       => $verdict->blocks($svc->failurePolicy),
        'warns'        => $verdict->warns(),
        'from_cache'   => $verdict->fromCache,
        'failure_policy' => $svc->failurePolicy,
    ];
    echo json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Throwable $e) {
    echo "EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ':' . $e->getLine() . "\n";
}
PHP

    chown www-data:www-data "$DROP"
    URL="https://${tenant}.aqssat.co/__fahras_action_${TS}.php"
    echo "── ${tenant} ──"
    curl -k -sS "$URL"
    rm -f "$DROP"
    echo
done
