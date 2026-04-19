#!/usr/bin/env bash
# Provision `SetEnv FAHRAS_COMPANY_NAME <canonical>` on every Tayseer tenant
# vhost so the wizard's «إضافة عقد جديد» same-company CTA fires when Fahras
# blocks because the customer is already ours.
#
# Mirrors fix_fahras_tenant_vhosts.sh — idempotent, vhost-scoped, never
# touched by the webhook deploy (vhost files live outside the project tree).
#
# Run as root on the production box (where /etc/apache2/sites-enabled/ holds
# the live tenant vhosts):
#
#   sudo bash scripts/set_fahras_company_name.sh
#
# After this runs, syncs each tenant working tree with origin/main so the
# tracked env templates (which now read getenv('FAHRAS_COMPANY_NAME'))
# become active, then reloads Apache.
set -euo pipefail

TS=$(date +%Y%m%d_%H%M%S)

# tenant slug → canonical Fahras account name (must match
# violation_engine.php::canonicalAccountName() on the Fahras side
# AND FahrasService::normaliseCompany() on the Tayseer side).
declare -A NAMES=(
    [jadal]="جدل"
    [majd]="عالم المجد"
    [namaa]="نماء"
    [watar]="وتر"
)

inject_vhost() {
    local f="$1" name="$2"
    [[ -f "$f" ]] || { echo "  ✗ vhost missing: $f"; return 0; }
    cp -a "$f" "${f}.bak.${TS}"

    # Drop any previous SetEnv FAHRAS_COMPANY_NAME line (idempotent).
    sed -i '/SetEnv[[:space:]]\+FAHRAS_COMPANY_NAME/d' "$f"

    # Insert SetEnv right after every <VirtualHost ...> opener so HTTP +
    # HTTPS blocks both inherit it.
    awk -v val="$name" '
        { print }
        /<VirtualHost/ {
            print "    SetEnv FAHRAS_COMPANY_NAME " val
        }
    ' "$f" > "${f}.new" && mv "${f}.new" "$f"
    echo "  ✓ patched $(basename "$f") → $name"
}

for t in "${!NAMES[@]}"; do
    echo "── tenant: $t (${NAMES[$t]}) ──"
    inject_vhost "/etc/apache2/sites-enabled/${t}.aqssat.co.conf"        "${NAMES[$t]}"
    inject_vhost "/etc/apache2/sites-enabled/${t}.aqssat.co-le-ssl.conf" "${NAMES[$t]}"
done

# Sync each tenant working tree so the updated env templates land.
for t in "${!NAMES[@]}"; do
    BASE="/var/www/${t}.aqssat.co"
    ENV="prod_${t}"
    [[ -d "$BASE" ]] || { echo "  ⚠ $BASE missing — skipping sync"; continue; }
    echo "── sync code: $t ──"
    cd "$BASE"
    git checkout -- environments/ 2>/dev/null || true
    git fetch origin main --depth 1 -q
    git reset --hard origin/main -q
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

# Reload Apache and verify.
echo "── apache configtest ──"
apache2ctl configtest 2>&1 | tail -n 3
systemctl reload apache2 && echo "  ✓ apache reloaded"

echo
echo "── verify SetEnv landed in vhosts ──"
for t in "${!NAMES[@]}"; do
    f="/etc/apache2/sites-enabled/${t}.aqssat.co-le-ssl.conf"
    if [[ -f "$f" ]] && grep -q "FAHRAS_COMPANY_NAME[[:space:]]\+${NAMES[$t]}" "$f"; then
        echo "  $t: ✓ SetEnv FAHRAS_COMPANY_NAME ${NAMES[$t]}"
    else
        echo "  $t: ✗ SetEnv missing in $(basename "$f")"
    fi
done
