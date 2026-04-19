<?php
/**
 * Local-only probe: verify Fahras integration on the developer machine.
 * Run from project root:   php scripts/probe_local.php
 */
chdir(__DIR__ . '/..');

// Stub `Yii::t()` so we can require params-local.php standalone (the file
// uses Yii::t for some labels but not for fahras keys).
if (!class_exists('Yii')) {
    class Yii { public static function t($cat, $msg) { return $msg; } }
}

$p = require __DIR__ . '/../common/config/params-local.php';
$f = $p['fahras'] ?? [];

echo "── params['fahras'] (local) ──\n";
echo "  enabled  = " . var_export($f['enabled'] ?? null, true) . "\n";
echo "  baseUrl  = " . ($f['baseUrl'] ?? '?') . "\n";
echo "  token    = " . (empty($f['token'])
    ? '(EMPTY!)'
    : substr($f['token'], 0, 20) . '…' . substr($f['token'], -6))
    . "  (len=" . strlen($f['token'] ?? '') . ")\n";
echo "  policy   = " . ($f['failurePolicy'] ?? '?') . "\n";

if (empty($f['token'])) {
    echo "\n✗ token is empty — please re-check params-local.php\n";
    exit(1);
}

echo "\n── live cURL POST to {$f['baseUrl']}/admin/api/check.php ──\n";
$ch = curl_init($f['baseUrl'] . '/admin/api/check.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'token'     => $f['token'],
        'client'    => $f['clientId'] ?? 'tayseer',
        'id_number' => '9999999999',
        'name'      => 'اختبار محلي',
        'phone'     => '0790000000',
    ]),
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$body   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err    = curl_error($ch);

echo "  http_status = {$status}\n";
echo "  curl_error  = " . ($err ?: '(none)') . "\n";
echo "  body        = " . substr((string)$body, 0, 500) . "\n";

if ($status === 200) {
    $j = json_decode((string)$body, true);
    if ($j && ($j['ok'] ?? false)) {
        echo "\n✓ LOCAL → FAHRAS PRODUCTION OK\n";
        echo "  verdict   = " . ($j['verdict'] ?? '?') . "\n";
        echo "  reason_ar = " . ($j['reason_ar'] ?? '?') . "\n";
        exit(0);
    }
}
echo "\n✗ Fahras call failed (status={$status})\n";
exit(2);
