#!/usr/bin/env bash
# Fix Fahras vhosts: SetEnv must be added to the *enabled* files
# (which on this server are real files, not symlinks to sites-available).
set -euo pipefail

TOKEN_FILE="/root/.fahras_tayseer_token"
TOKEN=$(< "$TOKEN_FILE")
TS=$(date +%Y%m%d_%H%M%S)

# Restore sites-available from backups (we mistakenly patched them earlier)
for f in /etc/apache2/sites-available/fahras.aqssat.co-le-ssl.conf \
         /etc/apache2/sites-available/fahras.aqssat.co.conf; do
    last_bak=$(ls -1t "${f}.bak."* 2>/dev/null | head -n1 || true)
    if [[ -n "$last_bak" && -f "$last_bak" ]]; then
        cp -a "$last_bak" "$f"
        echo "restored $f from $last_bak"
    fi
done

# Now patch the actually-enabled files
for f in /etc/apache2/sites-enabled/fahras.aqssat.co-le-ssl.conf \
         /etc/apache2/sites-enabled/fahras.aqssat.co.conf; do
    [[ -f "$f" ]] || continue
    cp -a "$f" "${f}.bak.${TS}"

    # Remove any previous FAHRAS_TOKEN_TAYSEER line
    sed -i '/SetEnv[[:space:]]\+FAHRAS_TOKEN_TAYSEER/d' "$f"

    # Insert SetEnv right after each FAHRAS_DB_PASS line (preserving indentation)
    awk -v tok="$TOKEN" '
        { print }
        /SetEnv[[:space:]]+FAHRAS_DB_PASS/ {
            match($0, /^[[:space:]]*/);
            ind = substr($0, RSTART, RLENGTH);
            print ind "SetEnv FAHRAS_TOKEN_TAYSEER " tok
        }
    ' "$f" > "${f}.new" && mv "${f}.new" "$f"
    echo "patched $f"
done

apache2ctl configtest 2>&1 | tail -n 3
systemctl reload apache2
echo "Apache reloaded"

echo
echo "── Verify SetEnv is present ──"
grep -n FAHRAS_TOKEN_TAYSEER /etc/apache2/sites-enabled/fahras.aqssat.co*.conf | sed 's/=.*/=***/'

echo
echo "── Smoke test (correct token) ──"
HTTP=$(curl -k -sS -o /tmp/fahras_resp.json -w '%{http_code}' \
    -X POST "https://fahras.aqssat.co/admin/api/check.php" \
    --data-urlencode "token=$TOKEN" \
    --data-urlencode "client=tayseer" \
    --data-urlencode "id_number=9999999999" \
    --data-urlencode "name=اختبار النشر")
echo "HTTP $HTTP"
head -c 800 /tmp/fahras_resp.json
echo
