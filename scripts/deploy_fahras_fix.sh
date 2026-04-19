#!/usr/bin/env bash
# Deploy the curl_close() PHP-8.5 deprecation fix across all four
# Tayseer tenants and verify the live endpoint still works.
set -euo pipefail

SRC="/tmp/FahrasService.php"
TS=$(date +%Y%m%d_%H%M%S)
TENANTS=(jadal majd namaa watar)

if ! grep -q 'do NOT call curl_close' "$SRC"; then
    echo "ERR: $SRC is missing the patch marker — aborting."
    exit 1
fi

for t in "${TENANTS[@]}"; do
    BASE="/var/www/${t}.aqssat.co"
    DEST="$BASE/common/services/FahrasService.php"
    echo "── tenant: $t ──"
    if [[ ! -f "$DEST" ]]; then
        echo "  ✗ $DEST not found, skipping"
        continue
    fi
    cp -a "$DEST" "${DEST}.bak.${TS}"
    cp "$SRC" "$DEST"
    chown www-data:www-data "$DEST"
    chmod 644 "$DEST"
    echo "  ✓ patched (backup: ${DEST}.bak.${TS})"

    # Lint
    if php -l "$DEST" >/dev/null 2>&1; then
        echo "  ✓ php -l OK"
    else
        echo "  ✗ syntax error after patch — restoring backup"
        cp -a "${DEST}.bak.${TS}" "$DEST"
        continue
    fi

    # Flush cache
    cd "$BASE" && sudo -u www-data php yii cache/flush-all --interactive=0 >/dev/null 2>&1 \
        && echo "  ✓ cache flushed" \
        || echo "  ! cache flush failed (non-fatal)"
done

echo
echo "── verifying patch is live (jadal e2e) ──"
sudo -u www-data php /var/www/jadal.aqssat.co/scripts/e2e_test.php 2>&1 | head -n 25
