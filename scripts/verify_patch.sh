#!/usr/bin/env bash
set -u
echo "── verify patch on disk ──"
for t in jadal majd namaa watar; do
    f="/var/www/${t}.aqssat.co/common/services/FahrasService.php"
    cnt=$(grep -c 'do NOT call curl_close' "$f" 2>/dev/null || echo 0)
    closes=$(grep -c 'curl_close' "$f" 2>/dev/null || echo 0)
    echo "  ${t}: patch_marker=$cnt   curl_close_calls_remaining=$closes"
done

echo
echo "── reload Apache (and reset opcache) ──"
systemctl reload apache2 && echo "  Apache reloaded"

# Force-reset opcache via tiny ad-hoc script run inside web context
DROP="/var/www/jadal.aqssat.co/admin/__opc_reset_$$.php"
echo '<?php if (function_exists("opcache_reset")) { var_dump(opcache_reset()); } else { echo "no_opcache\n"; }' > "$DROP"
chown www-data:www-data "$DROP"
curl -k -sS "https://jadal.aqssat.co/admin/__opc_reset_$$.php"
echo
rm -f "$DROP"

echo
echo "── e2e re-test (jadal) ──"
sudo -u www-data php /var/www/jadal.aqssat.co/scripts/e2e_test.php 2>&1 | head -n 25
