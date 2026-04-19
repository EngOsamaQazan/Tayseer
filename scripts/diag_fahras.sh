#!/usr/bin/env bash
# Diagnose why FahrasService.check() returns 'transport_error' from inside Tayseer.
set -u

BASE="/var/www/jadal.aqssat.co"
TOKEN=$(< /root/.fahras_tayseer_token)

echo "── 1) curl from www-data to fahras.aqssat.co ──"
sudo -u www-data curl -sS -o /tmp/diag_curl.json -w "HTTP %{http_code}  time=%{time_total}s  ssl=%{ssl_verify_result}\n" \
    -X POST "https://fahras.aqssat.co/admin/api/check.php" \
    --data-urlencode "token=$TOKEN" \
    --data-urlencode "client=tayseer" \
    --data-urlencode "id_number=9999999999" 2>&1
head -c 300 /tmp/diag_curl.json; echo

echo
echo "── 2) PHP curl_exec from www-data ──"
sudo -u www-data php -r '
    $ch = curl_init("https://fahras.aqssat.co/admin/api/check.php");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            "token"     => trim(file_get_contents("/root/.fahras_tayseer_token")),
            "client"    => "tayseer",
            "id_number" => "9999999999",
        ]),
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    echo "errno   = " . curl_errno($ch) . "\n";
    echo "errmsg  = " . curl_error($ch) . "\n";
    echo "status  = " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
    echo "body    = " . substr((string)$body, 0, 300) . "\n";
'

echo
echo "── 3) Tayseer recent log entries (fahras) ──"
ls -lt "$BASE/backend/runtime/logs/" 2>/dev/null | head -5
echo
for f in "$BASE/backend/runtime/logs/app.log" "$BASE/backend/runtime/logs/error.log"; do
    if [[ -f "$f" ]]; then
        echo "── $(basename $f) (last fahras lines) ──"
        grep -i 'fahras\|curl\|FahrasService' "$f" 2>/dev/null | tail -n 30
    fi
done

echo
echo "── 4) Apache error log (last fahras entries) ──"
grep -i 'fahras\|FahrasService\|curl' /var/log/apache2/jadal-error.log 2>/dev/null | tail -n 30
grep -i 'fahras\|FahrasService\|curl' /var/log/apache2/error.log 2>/dev/null | tail -n 20

echo
echo "── 5) PHP info: openssl + curl + cafile ──"
sudo -u www-data php -r '
    echo "OpenSSL version: " . OPENSSL_VERSION_TEXT . "\n";
    echo "curl version:    " . curl_version()["version"] . "\n";
    echo "curl ssl version:" . curl_version()["ssl_version"] . "\n";
    echo "openssl.cafile = " . (ini_get("openssl.cafile") ?: "(empty)") . "\n";
    echo "curl.cainfo    = " . (ini_get("curl.cainfo") ?: "(empty)") . "\n";
'
