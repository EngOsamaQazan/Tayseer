#!/usr/bin/env bash
# Verify the integration end-to-end by hitting Apache (mod_php), since SetEnv
# values are only visible to PHP inside Apache request context — not CLI.
set -u

TOKEN=$(< /root/.fahras_tayseer_token)
TS=$(date +%s)

for tenant in jadal majd namaa watar; do
    DROP="/var/www/${tenant}.aqssat.co/backend/web/__fahras_probe_${TS}.php"
    cat > "$DROP" <<'PHP'
<?php
header('Content-Type: text/plain; charset=UTF-8');

echo "== env reach test ==\n";
$tok = getenv('FAHRAS_TOKEN_TAYSEER');
echo "FAHRAS_TOKEN_TAYSEER (getenv) length: " . ($tok === false ? 'FALSE' : strlen($tok)) . "\n";
echo "FAHRAS_TOKEN_TAYSEER (server)  length: "
   . (isset($_SERVER['FAHRAS_TOKEN_TAYSEER']) ? strlen($_SERVER['FAHRAS_TOKEN_TAYSEER']) : 'UNSET') . "\n";

echo "\n== params-local snapshot ==\n";
$projectRoot = dirname(__DIR__, 2);  // .../backend/web → project root
$p = require $projectRoot . '/common/config/params-local.php';
$f = $p['fahras'] ?? [];
echo "  enabled  = " . var_export($f['enabled'] ?? null, true) . "\n";
echo "  baseUrl  = " . ($f['baseUrl'] ?? '?') . "\n";
echo "  token    = " . (empty($f['token']) ? '(EMPTY!)' : substr($f['token'],0,20) . '…' . substr($f['token'],-6)) . "\n";
echo "  policy   = " . ($f['failurePolicy'] ?? '?') . "\n";

echo "\n== live Fahras call (cURL inside Apache request) ==\n";
$ch = curl_init('https://fahras.aqssat.co/admin/api/check.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'token'     => $f['token'] ?? '',
        'client'    => 'tayseer',
        'id_number' => '9999999999',
        'name'      => 'اختبار',
    ]),
    CURLOPT_TIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$body = curl_exec($ch);
echo "  http_status = " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "  response    = " . substr((string)$body, 0, 300) . "\n";
PHP

    chown www-data:www-data "$DROP"
    URL="https://${tenant}.aqssat.co/__fahras_probe_${TS}.php"
    echo "── ${tenant} ($URL) ──"
    curl -k -sS "$URL"
    rm -f "$DROP"
    echo
done
