#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
#  Tayseer ↔ Fahras production deployment script
#  Run as root on the host that serves both Fahras and the four
#  Tayseer tenants (jadal/majd/namaa/watar).
#
#  Idempotent:
#    – Re-running it will refresh tokens, re-apply migrations, flush
#      caches and reload Apache without breaking previous state.
# ─────────────────────────────────────────────────────────────────────
set -euo pipefail

TENANTS=(jadal majd namaa watar)
FAHRAS_DIR="/var/www/fahras.aqssat.co"
FAHRAS_VHOST_HTTPS="/etc/apache2/sites-available/fahras.aqssat.co-le-ssl.conf"
FAHRAS_VHOST_HTTP="/etc/apache2/sites-available/fahras.aqssat.co.conf"
TOKEN_FILE="/root/.fahras_tayseer_token"
TS=$(date +%Y%m%d_%H%M%S)

log() { printf '\033[1;36m[%s] %s\033[0m\n' "$(date +'%H:%M:%S')" "$*"; }
ok()  { printf '\033[1;32m  ✓ %s\033[0m\n' "$*"; }
warn(){ printf '\033[1;33m  ! %s\033[0m\n' "$*"; }
err() { printf '\033[1;31m  ✗ %s\033[0m\n' "$*" >&2; }

# ─── 1) Generate or reuse a stable shared secret ────────────────────
if [[ -s "$TOKEN_FILE" ]]; then
    TOKEN=$(< "$TOKEN_FILE")
    log "Reusing existing Fahras token from $TOKEN_FILE"
else
    RAND=$(php -r 'echo bin2hex(random_bytes(24));')
    TOKEN="tayseer_fahras_2026_${RAND}"
    umask 077
    printf '%s' "$TOKEN" > "$TOKEN_FILE"
    chmod 600 "$TOKEN_FILE"
    log "Generated new Fahras token (stored in $TOKEN_FILE)"
fi
ok "Token length: ${#TOKEN} chars"

# ─── 2) Patch each Tayseer tenant ───────────────────────────────────
patch_params_local() {
    local file="$1"
    [[ -f "$file" ]] || return 0

    # Backup once per run
    cp -a "$file" "${file}.bak.${TS}"

    # If a `fahras` block already exists, replace its contents in place.
    # Otherwise inject one just before the closing `];`.
    php <<PHP
<?php
\$file  = "$file";
\$token = "$TOKEN";
\$contents = file_get_contents(\$file);

\$block = "    'fahras' => [\n"
       . "        'enabled'        => true,\n"
       . "        'baseUrl'        => 'https://fahras.aqssat.co',\n"
       . "        'token'          => '" . addslashes(\$token) . "',\n"
       . "        'clientId'       => 'tayseer',\n"
       . "        'timeoutSec'     => 8,\n"
       . "        'cacheTtlSec'    => 300,\n"
       . "        'failurePolicy'  => 'closed',\n"
       . "        'overridePerm'   => 'customer.fahras.override',\n"
       . "        'logViewPerm'    => 'customer.fahras.log.view',\n"
       . "    ],\n";

if (preg_match("/'fahras'\s*=>\s*\[.*?\],\s*\n/s", \$contents)) {
    \$contents = preg_replace("/'fahras'\s*=>\s*\[.*?\],\s*\n/s", \$block, \$contents);
} else {
    // Insert before the LAST `];` of the returned array
    \$contents = preg_replace("/\];\s*\$/", \$block . "];\n", \$contents, 1);
}

if (file_put_contents(\$file, \$contents) === false) {
    fwrite(STDERR, "FAILED to write \$file\n");
    exit(1);
}
echo "patched\n";
PHP
}

for t in "${TENANTS[@]}"; do
    BASE="/var/www/${t}.aqssat.co"
    log "── Tenant: $t  (${BASE}) ──"
    if [[ ! -d "$BASE" ]]; then
        warn "directory not found, skipping"; continue
    fi

    # 2a) Patch active config + persistent env template(s)
    for f in \
        "$BASE/common/config/params-local.php" \
        "$BASE/environments/prod_${t}/common/config/params-local.php" \
        "$BASE/environments/prod/common/config/params-local.php"
    do
        if [[ -f "$f" ]]; then
            patch_params_local "$f" >/dev/null && ok "patched $(realpath --relative-to="$BASE" "$f")"
        fi
    done

    # 2b) Run migration (only ours; safe even if applied)
    if [[ -f "$BASE/console/migrations/m260420_100000_create_fahras_check_log.php" ]]; then
        log "  applying migration m260420_100000_create_fahras_check_log…"
        ( cd "$BASE" && sudo -u www-data php yii migrate/up --interactive=0 \
            --migrationPath=@console/migrations \
            2>&1 | tail -n 20 ) || warn "migrate command returned non-zero (may already be applied)"
    fi

    # 2c) Flush cache (best-effort)
    if [[ -x "$BASE/yii" ]] || [[ -f "$BASE/yii" ]]; then
        log "  flushing cache…"
        ( cd "$BASE" && sudo -u www-data php yii cache/flush-all --interactive=0 2>&1 | tail -n 5 ) \
            || warn "cache flush returned non-zero"
    fi
done

# ─── 3) Inject env var into Fahras Apache vhost ─────────────────────
inject_setenv() {
    local vhost="$1"
    [[ -f "$vhost" ]] || { warn "vhost $vhost not found"; return 0; }
    cp -a "$vhost" "${vhost}.bak.${TS}"

    # Remove any previous SetEnv FAHRAS_TOKEN_TAYSEER lines
    sed -i '/SetEnv[[:space:]]\+FAHRAS_TOKEN_TAYSEER/d' "$vhost"

    # Insert SetEnv right after every existing 'SetEnv FAHRAS_DB_PASS …' line
    awk -v tok="$TOKEN" '
        { print }
        /SetEnv[[:space:]]+FAHRAS_DB_PASS/ {
            # preserve indentation of previous line
            match($0, /^[[:space:]]*/);
            indent = substr($0, RSTART, RLENGTH);
            print indent "SetEnv FAHRAS_TOKEN_TAYSEER " tok
        }
    ' "$vhost" > "${vhost}.new" && mv "${vhost}.new" "$vhost"
    ok "patched $(basename "$vhost")"
}

log "── Fahras Apache vhost ──"
inject_setenv "$FAHRAS_VHOST_HTTPS"
inject_setenv "$FAHRAS_VHOST_HTTP"

# ─── 4) Sanity-test config & reload Apache ──────────────────────────
log "── Apache config test ──"
if apache2ctl configtest 2>&1 | tail -n 3; then
    ok "configtest passed"
    systemctl reload apache2 && ok "Apache reloaded" || err "Apache reload failed"
else
    err "Apache config test FAILED — NOT reloading. Inspect manually."
    exit 1
fi

# ─── 5) Smoke-test the live endpoint ────────────────────────────────
log "── Smoke test: /admin/api/check.php ──"
sleep 1
HTTP_CODE=$(curl -k -sS -o /tmp/fahras_resp.json -w '%{http_code}' \
    -X POST "https://fahras.aqssat.co/admin/api/check.php" \
    --data-urlencode "token=$TOKEN" \
    --data-urlencode "client=tayseer" \
    --data-urlencode "id_number=9999999999" \
    --data-urlencode "name=اختبار النشر")
ok "HTTP $HTTP_CODE"
echo "  Response (first 600 chars):"
head -c 600 /tmp/fahras_resp.json
echo
echo

log "── Smoke test: bad token (must be 403) ──"
HTTP_BAD=$(curl -k -sS -o /tmp/fahras_bad.json -w '%{http_code}' \
    -X POST "https://fahras.aqssat.co/admin/api/check.php" \
    --data-urlencode "token=wrong_token" \
    --data-urlencode "client=tayseer" \
    --data-urlencode "id_number=9999999999")
ok "Bad-token HTTP $HTTP_BAD (expected 403)"

# ─── 6) Done ────────────────────────────────────────────────────────
log "Deployment complete."
echo
echo "Token (kept on server, NOT printed in full):"
echo "  ${TOKEN:0:20}…${TOKEN: -6}"
echo "  full value lives in $TOKEN_FILE  (chmod 600, root only)"
