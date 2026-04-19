#!/usr/bin/env bash
set -u

# Use a real common name to get back at least one row from each source.
TERM=${1:-احمد}
ENC=$(printf '%s' "$TERM" | jq -sRr @uri 2>/dev/null || python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1]))" "$TERM")

print_one() {
    local label="$1" url="$2"
    echo "── $label ──"
    body=$(curl -sS --max-time 10 "$url")
    # Extract first row (best-effort) so we can see field shape, especially `account`.
    echo "$body" | python3 -c '
import sys, json
raw = sys.stdin.read()
try:
    j = json.loads(raw)
except Exception:
    print(raw[:600]); sys.exit()
data = j.get("data", j) if isinstance(j, dict) else j
if not isinstance(data, list): data = [data]
print(f"rows: {len(data)}")
for r in data[:1]:
    if isinstance(r, dict):
        print(json.dumps({k:v for k,v in r.items() if k in
            ("id","cid","name","national_id","account","status","remaining_amount","sell_date","created_on","party_type","_source")},
            ensure_ascii=False, indent=2))
    else:
        print(repr(r)[:600])
print("--- distinct account values seen ---")
seen=set()
for r in data:
    if isinstance(r, dict):
        seen.add(str(r.get("account","")))
for a in sorted(seen): print(repr(a))
'
    echo
}

print_one "zajal"  "https://zajal.cc/fahras-api.php?token=354afdf5357c&search=$ENC"
print_one "jadal"  "https://jadal.aqssat.co/fahras/api.php?token=b83ba7a49b72&db=jadal&search=$ENC"
print_one "namaa"  "https://jadal.aqssat.co/fahras/api.php?token=b83ba7a49b72&db=erp&search=$ENC"
print_one "watar"  "https://watar.aqssat.co/fahras/api.php?token=b83ba7a49b72&db=watar&search=$ENC"
print_one "majd"   "https://majd.aqssat.co/fahras/api.php?token=b83ba7a49b72&db=majd&search=$ENC"
print_one "bseel"  "https://bseel.com/FahrasBaselFullAPIs.php?token=bseel_fahras_2024&search=$ENC"
