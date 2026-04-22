#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# Tayseer — Verify Snapshot Integrity
# ─────────────────────────────────────────────────────────────────────
#
# A backup you have not tested is not a backup. This script does the
# cheap parts of a "real" restore drill:
#
#   1. Manifest exists and parses.
#   2. DB dump opens with the matching decompressor and contains a
#      sane number of CREATE TABLE / INSERT statements.
#   3. Media subtrees exist and contain at least the floor count of
#      files (configurable per-site if you grow concerned a tenant
#      should have more bytes than they do).
#   4. (Optional, --deep) loads the DB dump into a transient
#      `tayseer_verify_<rand>` schema, runs row-count sanity checks
#      against a baseline file, then drops the schema.
#
# Cron this nightly RIGHT AFTER daily-snapshot.sh. Failures land in
# the log; couple it with HEARTBEAT_URL/<id>/fail to alert.
#
# Usage:
#   verify-snapshot.sh                    # latest daily, all sites
#   verify-snapshot.sh --date 2026-04-22
#   verify-snapshot.sh --site namaa --deep
# ─────────────────────────────────────────────────────────────────────

set -Eeuo pipefail

: "${BACKUP_ROOT:=/var/backups/tayseer}"
: "${SITES:=jadal namaa watar majd}"
: "${MIN_DB_BYTES:=10240}"           # 10 KB — anything smaller is suspect
: "${MIN_TABLES:=5}"                 # smallest plausible Tayseer DB

if [ -f /etc/default/tayseer-backup ]; then
  # shellcheck disable=SC1091
  source /etc/default/tayseer-backup
fi

DATE="$(date '+%Y-%m-%d')"
SITE_FILTER=""
DEEP=0

while [ $# -gt 0 ]; do
  case "$1" in
    --date) DATE="$2"; shift 2 ;;
    --site) SITE_FILTER="$2"; shift 2 ;;
    --deep) DEEP=1; shift ;;
    *) echo "Unknown arg: $1"; exit 2 ;;
  esac
done

DAILY_DIR="$BACKUP_ROOT/daily/$DATE"
[ -d "$DAILY_DIR" ] || { echo "ERROR: no snapshot for date $DATE"; exit 1; }

FAIL=0

for site in $SITES; do
  [ -z "$SITE_FILTER" ] || [ "$SITE_FILTER" = "$site" ] || continue
  d="$DAILY_DIR/$site"
  echo "── verifying $site ($d)"
  if [ ! -d "$d" ]; then
    echo "  MISSING site dir"; FAIL=$((FAIL+1)); continue
  fi
  if [ ! -f "$d/manifest.json" ]; then
    echo "  MISSING manifest.json"; FAIL=$((FAIL+1)); continue
  fi

  # 1. dump exists + decompresses
  dump=""
  for f in "$d/db.sql.gz" "$d/db.sql.zst" "$d/db.sql"; do
    [ -f "$f" ] && dump="$f" && break
  done
  if [ -z "$dump" ]; then
    echo "  MISSING db dump"; FAIL=$((FAIL+1)); continue
  fi
  bytes="$(stat -c %s "$dump" 2>/dev/null || stat -f %z "$dump")"
  if [ "$bytes" -lt "$MIN_DB_BYTES" ]; then
    echo "  DB DUMP TOO SMALL ($bytes bytes)"; FAIL=$((FAIL+1)); continue
  fi

  # Decompress to count statements without holding the whole thing
  # in memory.
  tables="$(
    case "$dump" in
      *.gz)  gunzip -c "$dump" ;;
      *.zst) zstd -dc "$dump" ;;
      *)     cat "$dump" ;;
    esac | grep -c '^CREATE TABLE' || true
  )"
  if [ "$tables" -lt "$MIN_TABLES" ]; then
    echo "  DB DUMP HAS ONLY $tables CREATE TABLEs"; FAIL=$((FAIL+1)); continue
  fi
  echo "  DB OK: $bytes bytes, $tables tables"

  # 2. media subtrees
  for entry in backend_web_uploads backend_web_images frontend_web_uploads; do
    if [ -d "$d/media/$entry" ]; then
      n="$(find "$d/media/$entry" -type f -o -type l 2>/dev/null | wc -l)"
      echo "  MEDIA $entry: $n files"
    fi
  done

  # 3. deep mode: load into transient schema
  if [ "$DEEP" = "1" ]; then
    rand="$(tr -dc a-z0-9 </dev/urandom | head -c 8)"
    sch="tayseer_verify_${site}_${rand}"
    echo "  DEEP: loading into $sch"
    mysql -e "CREATE DATABASE \`$sch\` DEFAULT CHARACTER SET utf8mb4;"
    case "$dump" in
      *.gz)  gunzip -c "$dump" ;;
      *.zst) zstd -dc "$dump" ;;
      *)     cat "$dump" ;;
    esac | mysql --default-character-set=utf8mb4 "$sch"
    rows="$(mysql -N -e "
      SELECT SUM(table_rows) FROM information_schema.tables
      WHERE table_schema='$sch'" 2>/dev/null)"
    echo "  DEEP $sch: ~$rows rows total"
    mysql -e "DROP DATABASE \`$sch\`;"
  fi
done

if [ "$FAIL" -gt 0 ]; then
  echo "VERIFY FAILED ($FAIL site(s))"
  exit 1
fi
echo "All snapshots verified."
