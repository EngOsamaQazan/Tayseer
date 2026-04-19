#!/usr/bin/env bash
# Live test that the message no longer mentions "شركة جدل للتقسيط"
#
# The Fahras API token is read from one of (in order):
#   1. $FAHRAS_TOKEN environment variable
#   2. /root/.fahras_tayseer_token (root-readable file on prod)
# Tokens MUST NEVER be hard-coded in this file — anything pushed to git
# is permanently exposed in history even after deletion.
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

# Use a national_id that we know exists in remote_clients (jadal source).
NID='9941026320'
NAME='محمد امجد احمد ظاهر'

echo "── Test 1: by national_id ──"
curl -sS "${URL}?token=${TOKEN}&client=tayseer&id_number=${NID}" | python3 -c '
import sys, json
r = json.load(sys.stdin)
print("verdict   :", r.get("verdict"))
print("reason_ar :", r.get("reason_ar"))
print("matches   :")
for m in r.get("matches", []):
    print(" -", m.get("account"), "|", m.get("source"), "|", m.get("name"), "|", m.get("remaining_amount"))
'

echo
echo "── Test 2: by name ──"
NAME_ENC=$(python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1]))" "$NAME")
curl -sS "${URL}?token=${TOKEN}&client=tayseer&name=${NAME_ENC}" | python3 -c '
import sys, json
r = json.load(sys.stdin)
print("verdict   :", r.get("verdict"))
print("reason_ar :", r.get("reason_ar"))
print("matches   :")
for m in r.get("matches", []):
    print(" -", m.get("account"), "|", m.get("source"), "|", m.get("name"), "|", m.get("remaining_amount"))
'
