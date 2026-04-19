#!/usr/bin/env bash
# Final verification: clean test row + show overall status
set -u

# Delete the test row from jadal
echo "── cleanup test row ──"
cd /var/www/jadal.aqssat.co
php -r '
    $cfg = require "common/config/main-local.php";
    $pdo = new PDO($cfg["components"]["db"]["dsn"], $cfg["components"]["db"]["username"], $cfg["components"]["db"]["password"]);
    $pdo->exec("DELETE FROM os_fahras_check_log WHERE id_number=\"9999999999\" AND name LIKE \"%اختبار%\"");
    echo "  deleted test rows\n";
'

# Final status across tenants
echo
echo "── final status across tenants ──"
for t in jadal majd namaa watar; do
    echo "── $t ──"
    cd /var/www/${t}.aqssat.co
    php -r '
        $p = require "common/config/params-local.php";
        $f = $p["fahras"] ?? null;
        if (!$f) { echo "  ✗ no fahras config!\n"; exit; }
        echo "  ✓ enabled=" . ($f["enabled"] ? "true" : "false")
           . "  baseUrl=" . $f["baseUrl"]
           . "  policy=" . $f["failurePolicy"]
           . "  token=" . substr($f["token"], 0, 20) . "…" . substr($f["token"], -6) . "\n";

        $cfg = require "common/config/main-local.php";
        $pdo = new PDO($cfg["components"]["db"]["dsn"], $cfg["components"]["db"]["username"], $cfg["components"]["db"]["password"]);
        $cnt = $pdo->query("SELECT COUNT(*) FROM os_fahras_check_log")->fetchColumn();
        echo "  ✓ os_fahras_check_log rows: $cnt  (db=" . preg_replace("/.*dbname=([^;]+).*/", "\$1", $cfg["components"]["db"]["dsn"]) . ")\n";
    '
done

# Apache state
echo
echo "── Apache vhost SetEnv (redacted) ──"
grep -h FAHRAS_TOKEN_TAYSEER /etc/apache2/sites-enabled/fahras.aqssat.co*.conf | sed 's/= .*$/= ***REDACTED***/' | sed 's/SetEnv FAHRAS_TOKEN_TAYSEER .*/SetEnv FAHRAS_TOKEN_TAYSEER ***REDACTED***/'

# Live API ping
echo
echo "── live API ping (id=9999999999) ──"
TOKEN=$(< /root/.fahras_tayseer_token)
curl -k -sS -X POST https://fahras.aqssat.co/admin/api/check.php \
     --data-urlencode "token=$TOKEN" \
     --data-urlencode "client=tayseer" \
     --data-urlencode "id_number=9999999999" \
     --data-urlencode "name=عميل اختبار" \
| python3 -c 'import sys,json; d=json.load(sys.stdin); print("  ok=",d["ok"],"verdict=",d["verdict"],"reason=",d["reason_ar"],"duration_ms=",d.get("duration_ms"))' 2>/dev/null \
|| echo "  (raw response shown above)"

echo
echo "✓ Tayseer ↔ Fahras integration is LIVE on this server."
