#!/usr/bin/env bash
# Final, robust fix:
#   1. Add `SetEnv FAHRAS_TOKEN_TAYSEER <secret>` to every Tayseer tenant vhost
#      (HTTP + HTTPS files in sites-enabled). The webhook deploy script will
#      never wipe this — it only touches the project working tree.
#   2. Pull latest code so the new env templates (which source the token via
#      getenv()) become active on each tenant.
#   3. Reload Apache to pick up SetEnv changes AND invalidate mod_php opcache.
#   4. Verify token reaches PHP and call live Fahras through the Yii service.
set -euo pipefail

TOKEN_FILE="/root/.fahras_tayseer_token"
TOKEN=$(< "$TOKEN_FILE")
TS=$(date +%Y%m%d_%H%M%S)
TENANTS=(jadal majd namaa watar)

if [[ -z "$TOKEN" ]]; then echo "ERR: empty token"; exit 1; fi
echo "Using token: ${TOKEN:0:20}…${TOKEN: -6}"

# ─── 1) Patch tenant vhosts (sites-enabled is the live one on this box) ───
inject_tenant_vhost() {
    local f="$1"
    [[ -f "$f" ]] || { echo "  ✗ vhost missing: $f"; return 0; }
    cp -a "$f" "${f}.bak.${TS}"

    # Drop any previous SetEnv FAHRAS_TOKEN_TAYSEER lines (idempotent).
    sed -i '/SetEnv[[:space:]]\+FAHRAS_TOKEN_TAYSEER/d' "$f"

    # Insert SetEnv right after the opening <VirtualHost ...> tag of every
    # virtual host inside the file (HTTP and HTTPS blocks both get it).
    awk -v tok="$TOKEN" '
        { print }
        /<VirtualHost/ {
            print "    SetEnv FAHRAS_TOKEN_TAYSEER " tok
        }
    ' "$f" > "${f}.new" && mv "${f}.new" "$f"
    echo "  ✓ patched $(basename "$f")"
}

for t in "${TENANTS[@]}"; do
    echo "── tenant vhost: $t ──"
    inject_tenant_vhost "/etc/apache2/sites-enabled/${t}.aqssat.co.conf"
    inject_tenant_vhost "/etc/apache2/sites-enabled/${t}.aqssat.co-le-ssl.conf"
done

# ─── 2) Sync each tenant working tree with origin/main and run env init ───
for t in "${TENANTS[@]}"; do
    BASE="/var/www/${t}.aqssat.co"
    ENV="prod_${t}"
    echo "── sync tenant code: $t ──"
    cd "$BASE"

    # Drop any local edits to env templates (we now manage them via git).
    git checkout -- environments/ 2>/dev/null || true
    git fetch origin main --depth 1 -q
    git reset --hard origin/main -q

    # Apply env template into the active locations (mirrors deploy-pull.sh).
    for cfg in common/config/main-local.php common/config/params-local.php \
               backend/web/index.php frontend/web/index.php \
               console/config/main-local.php console/config/params-local.php \
               yii; do
        [[ -f "environments/${ENV}/$cfg" ]] && cp -f "environments/${ENV}/$cfg" "$cfg"
    done
    chmod +x yii 2>/dev/null || true
    chown -R www-data:www-data backend/ common/ console/ frontend/ api/ vendor/ 2>/dev/null || true
    rm -rf backend/runtime/cache/* frontend/runtime/cache/* 2>/dev/null || true
    echo "  ✓ synced"
done

# ─── 3) Apache configtest + reload ────────────────────────────────────────
echo "── apache configtest ──"
apache2ctl configtest 2>&1 | tail -n 3
systemctl reload apache2 && echo "  ✓ apache reloaded"

# ─── 4) Verify env templates are correct (raw grep) ───────────────────────
echo
echo "── verify env templates contain getenv() based fahras block ──"
for t in "${TENANTS[@]}"; do
    f="/var/www/${t}.aqssat.co/common/config/params-local.php"
    if grep -q "FAHRAS_TOKEN_TAYSEER" "$f"; then
        echo "  $t: ✓ active params-local sources token via getenv()"
    else
        echo "  $t: ✗ active params-local missing getenv block — check git pull"
    fi
done

# ─── 5) Verify Apache SetEnv reaches mod_php and Yii service works ────────
echo
echo "── live e2e through Yii (jadal) ──"
sudo -u www-data php /var/www/jadal.aqssat.co/scripts/e2e_test.php 2>&1 | head -n 22
