#!/usr/bin/env bash
# Test the matches dedupe with the exact case the user reported.
#
# Token resolution (never hard-code — anything in git is forever):
#   1. $FAHRAS_TOKEN environment variable
#   2. /root/.fahras_tayseer_token  (root-readable file on prod)
set -u
TOKEN="${FAHRAS_TOKEN:-}"
if [[ -z "$TOKEN" && -r /root/.fahras_tayseer_token ]]; then
    TOKEN=$(< /root/.fahras_tayseer_token)
fi
if [[ -z "$TOKEN" ]]; then
    echo "ERR: no Fahras token. Export FAHRAS_TOKEN=… or place it in /root/.fahras_tayseer_token" >&2
    exit 1
fi
URL='https://fahras.aqssat.co/admin/api/check.php'
NID='9981031741'

echo "── Test: کيفورک @ jadal ──"
curl -sS "${URL}?token=${TOKEN}&client=tayseer&id_number=${NID}" | python3 -c '
import sys, json
r = json.load(sys.stdin)
print("verdict   :", r.get("verdict"))
print("reason_ar :", r.get("reason_ar"))
print("matches   :", len(r.get("matches", [])))
for m in r.get("matches", []):
    print(" -", m.get("account"), "|", m.get("source"), "|", m.get("name"),
          "|", m.get("national_id"), "|", m.get("remaining_amount"),
          "|", m.get("status"), "|cid=", m.get("contract_id"))
'
