#!/usr/bin/env bash
# v2: properly inject `fahras` block into BOTH the active params-local.php
#     AND the environments/prod_<tenant>/common/config/params-local.php
#     template (which the webhook deploy script copies over the active one
#     on every push). Idempotent.
set -euo pipefail

TOKEN=$(< /root/.fahras_tayseer_token)
TS=$(date +%Y%m%d_%H%M%S)
TENANTS=(jadal majd namaa watar)

if [[ -z "$TOKEN" ]]; then
    echo "ERR: /root/.fahras_tayseer_token is empty"; exit 1
fi
echo "Using token: ${TOKEN:0:20}…${TOKEN: -6}"

# Robust PHP patcher: unconditionally remove any existing 'fahras' block,
# then insert a fresh one immediately before the closing `];` of the array.
patch_file() {
    local file="$1"
    [[ -f "$file" ]] || { echo "  ✗ missing: $file"; return 0; }
    cp -a "$file" "${file}.bak.${TS}"

    TOKEN="$TOKEN" FILE="$file" php <<'PHP'
<?php
$file  = getenv('FILE');
$token = getenv('TOKEN');
$src   = file_get_contents($file);
if ($src === false) { fwrite(STDERR, "read fail\n"); exit(1); }

// 1) Strip ANY previous 'fahras' block (greedy-ish, only between balanced [ ]).
$src = preg_replace(
    "/^[ \t]*'fahras'\s*=>\s*\[[^\]]*\],?\s*\r?\n/m",
    '',
    $src
);

// 2) Build the fresh block.
$block = "    'fahras' => [\n"
       . "        'enabled'        => true,\n"
       . "        'baseUrl'        => 'https://fahras.aqssat.co',\n"
       . "        'token'          => '" . addslashes($token) . "',\n"
       . "        'clientId'       => 'tayseer',\n"
       . "        'timeoutSec'     => 8,\n"
       . "        'cacheTtlSec'    => 300,\n"
       . "        'failurePolicy'  => 'closed',\n"
       . "        'overridePerm'   => 'customer.fahras.override',\n"
       . "        'logViewPerm'    => 'customer.fahras.log.view',\n"
       . "    ],\n";

// 3) Inject right before the LAST `];` (closes the top-level return array).
$pos = strrpos($src, '];');
if ($pos === false) {
    fwrite(STDERR, "no closing ']; found in $file\n");
    exit(1);
}
$new = substr($src, 0, $pos) . $block . substr($src, $pos);

if (file_put_contents($file, $new) === false) {
    fwrite(STDERR, "write fail $file\n"); exit(1);
}

// 4) Sanity-check by parsing the new content (without evaluating Yii::t).
//    Just confirm the literal fahras.token line is present.
if (!preg_match("/'token'\s*=>\s*'([^']{20,})'/", $block, $m)) {
    fwrite(STDERR, "verify fail: token literal missing\n");
    exit(1);
}
echo "    written token length = " . strlen($m[1]) . " chars\n";
PHP
}

for t in "${TENANTS[@]}"; do
    BASE="/var/www/${t}.aqssat.co"
    echo "── tenant: $t ──"
    [[ -d "$BASE" ]] || { echo "  ✗ missing dir"; continue; }

    for rel in \
        "environments/prod_${t}/common/config/params-local.php" \
        "common/config/params-local.php"
    do
        f="$BASE/$rel"
        echo "  [patch] $rel"
        patch_file "$f"
    done
    chown www-data:www-data "$BASE/common/config/params-local.php" || true
done

echo
echo "── reload Apache (invalidates mod_php opcache) ──"
systemctl reload apache2 && echo "  ✓ apache reloaded"

echo
echo "── verify each tenant by raw grep of the active file ──"
for t in "${TENANTS[@]}"; do
    BASE="/var/www/${t}.aqssat.co"
    f="$BASE/common/config/params-local.php"
    if grep -q "'fahras'" "$f"; then
        tok=$(grep -oP "'token'\s*=>\s*'\K[^']+" "$f" | head -n1)
        echo "  $t: ✓ fahras present  token_len=${#tok}  preview=${tok:0:20}…${tok: -6}"
    else
        echo "  $t: ✗ fahras MISSING in $f"
    fi
done

echo
echo "── live e2e through Yii (jadal) ──"
sudo -u www-data php /var/www/jadal.aqssat.co/scripts/e2e_test.php 2>&1 | head -n 22
