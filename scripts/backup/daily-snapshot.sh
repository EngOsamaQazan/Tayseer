#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# Tayseer — Daily Backup Snapshot
# ─────────────────────────────────────────────────────────────────────
#
# Snapshots EVERYTHING that is hard or impossible to recreate from
# `git reset --hard origin/main`:
#
#   1. MySQL database for each tenant site (mysqldump, single-tx,
#      gzipped, with routines/triggers/events).
#   2. Media trees on disk (`backend/web/uploads/`,
#      `backend/web/images/`, `frontend/web/uploads/`) using rsync
#      with --link-dest so unchanged bytes are HARD-LINKED across
#      daily snapshots — daily incrementals cost almost nothing.
#   3. Optional: pushes the freshest snapshot off-box (rsync over SSH
#      to a remote host) so a destroyed VPS does not destroy the
#      backups too.
#
# Designed to be run by cron once a day. All operations are
# idempotent: re-running on the same calendar day overwrites the
# day's snapshot in place (the rsync hard-link layout makes this safe
# and cheap).
#
# Why not VPS-provider snapshots only?
#   Those are great for "last week the server died" — useless for
#   "yesterday someone CASCADE-deleted half the customers". File-level
#   + DB-level snapshots give us point-in-time recovery for individual
#   tables and individual customer images, not just whole-disk.
#
# Retention defaults (override via env):
#   KEEP_DAILY=14   — daily snapshots for two weeks
#   KEEP_WEEKLY=8   — weekly snapshots (Sundays) for two months
#   KEEP_MONTHLY=12 — monthly snapshots (1st of month) for a year
#
# Exit codes:
#   0  — every step succeeded
#   1  — one or more steps failed (cron will email the admin)
#   2  — misconfiguration (paths missing, credentials unreadable)
#
# Tested against bash 5.x on Ubuntu 22.04. Requires: mysqldump,
# rsync, gzip (or zstd if BACKUP_COMPRESSOR=zstd), php (for reading
# Yii config files).
# ─────────────────────────────────────────────────────────────────────

set -Eeuo pipefail

# ─── Configurable knobs (override via /etc/default/tayseer-backup) ──
: "${BACKUP_ROOT:=/var/backups/tayseer}"
: "${WEB_ROOT:=/var/www}"
: "${SITES:=jadal namaa watar majd}"
: "${KEEP_DAILY:=14}"
: "${KEEP_WEEKLY:=8}"
: "${KEEP_MONTHLY:=12}"
: "${BACKUP_COMPRESSOR:=gzip}"   # gzip | zstd
: "${LOG_FILE:=/var/log/tayseer-backup.log}"
: "${HEARTBEAT_URL:=}"            # e.g. https://hc-ping.com/<uuid>
: "${OFFSITE_RSYNC_TARGET:=}"     # e.g. backup@host:/backups/tayseer
: "${OFFSITE_SSH_KEY:=/root/.ssh/id_ed25519_backup}"

if [ -f /etc/default/tayseer-backup ]; then
  # shellcheck disable=SC1091
  source /etc/default/tayseer-backup
fi

# ─── Logging ────────────────────────────────────────────────────────
mkdir -p "$(dirname "$LOG_FILE")"
exec > >(tee -a "$LOG_FILE") 2>&1

TS_NOW="$(date '+%Y-%m-%d %H:%M:%S')"
DAY_TAG="$(date '+%Y-%m-%d')"
DOW="$(date '+%u')"        # 1=Mon..7=Sun
DOM="$(date '+%d')"        # 01..31

echo "════════════════════════════════════════════════════════════════"
echo " Tayseer backup run — start  $TS_NOW"
echo "════════════════════════════════════════════════════════════════"

# Tracks step failures without aborting the whole run; we want to
# attempt every site even if one fails so a single tenant outage
# does not skip everything else.
FAILURES=0

trap 'echo "FATAL: unexpected error at line $LINENO"; FAILURES=$((FAILURES+1))' ERR

# ─── Helpers ────────────────────────────────────────────────────────

# Reads a key out of common/config/main-local.php for a site without
# parsing PHP by hand. We invoke PHP itself, which is the only
# portable way to handle the various quoting styles the file uses.
read_db_setting() {
  local site_dir="$1" key="$2"
  local cfg="$site_dir/common/config/main-local.php"
  [ -f "$cfg" ] || { echo ""; return 0; }
  php -r "
    \$c = require '$cfg';
    \$db = \$c['components']['db'] ?? [];
    if ('$key' === 'dbname') {
      if (preg_match('/dbname=([^;]+)/', \$db['dsn'] ?? '', \$m)) echo \$m[1];
    } elseif ('$key' === 'host') {
      if (preg_match('/host=([^;]+)/', \$db['dsn'] ?? '', \$m)) echo \$m[1];
      else echo 'localhost';
    } elseif ('$key' === 'username') {
      echo \$db['username'] ?? '';
    } elseif ('$key' === 'password') {
      echo \$db['password'] ?? '';
    }
  " 2>/dev/null || echo ""
}

compress() {
  case "$BACKUP_COMPRESSOR" in
    zstd) zstd -q -19 --rm "$1" -o "$1.zst" && rm -f "$1" 2>/dev/null || true ;;
    *)    gzip -9 "$1" ;;
  esac
}

heartbeat() {
  [ -n "$HEARTBEAT_URL" ] || return 0
  curl -fsS -m 10 --retry 3 "$HEARTBEAT_URL/$1" -o /dev/null || true
}

# ─── Layout ─────────────────────────────────────────────────────────
# /var/backups/tayseer/
#   daily/2026-04-22/
#     namaa/db.sql.gz
#     namaa/media/uploads/...
#     namaa/media/images/...
#     namaa/media/frontend-uploads/...
#     namaa/manifest.json
#   weekly/2026-W17/         (symlink to a Sunday daily)
#   monthly/2026-04/         (symlink to the 1st-of-month daily)
#
# rsync's --link-dest points at YESTERDAY's media directory so
# unchanged image files become inodes shared with yesterday — a 50 GB
# media tree that gains 100 MB/day costs ~100 MB on disk per snapshot,
# not 50 GB.

DAILY_DIR="$BACKUP_ROOT/daily/$DAY_TAG"
PREV_DAY="$(date -d 'yesterday' '+%Y-%m-%d')"
PREV_DAILY_DIR="$BACKUP_ROOT/daily/$PREV_DAY"

mkdir -p "$DAILY_DIR" "$BACKUP_ROOT/weekly" "$BACKUP_ROOT/monthly"

heartbeat "start"

# ─── Per-site loop ──────────────────────────────────────────────────

for site in $SITES; do
  site_dir="$WEB_ROOT/$site.aqssat.co"
  out_dir="$DAILY_DIR/$site"
  mkdir -p "$out_dir/media"

  echo
  echo "──── $site ────"

  if [ ! -d "$site_dir" ]; then
    echo "  SKIP: $site_dir does not exist"
    continue
  fi

  # 1. DB DUMP  ─────────────────────────────────────────────────────
  db_name="$(read_db_setting "$site_dir" dbname)"
  db_user="$(read_db_setting "$site_dir" username)"
  db_pass="$(read_db_setting "$site_dir" password)"
  db_host="$(read_db_setting "$site_dir" host)"

  if [ -z "$db_name" ] || [ -z "$db_user" ]; then
    echo "  ERROR: could not read DB credentials from $site_dir"
    FAILURES=$((FAILURES+1))
    continue
  fi

  echo "  DB:   $db_user@$db_host/$db_name"
  dump_file="$out_dir/db.sql"

  # --single-transaction  : consistent snapshot of InnoDB, no locks
  # --quick               : stream rows one-by-one (huge tables)
  # --routines/triggers/events : full schema fidelity
  # --hex-blob            : safe for binary payloads
  # --set-gtid-purged=OFF : harmless on non-replicated, required on RDS
  # We pass the password via an env var so it never appears in `ps`.
  if MYSQL_PWD="$db_pass" mysqldump \
        --host="$db_host" \
        --user="$db_user" \
        --single-transaction \
        --quick \
        --routines \
        --triggers \
        --events \
        --hex-blob \
        --default-character-set=utf8mb4 \
        --set-gtid-purged=OFF \
        "$db_name" > "$dump_file" 2>"$out_dir/db.dump.err"; then
    compress "$dump_file"
    rm -f "$out_dir/db.dump.err"
    db_size="$(du -h "$out_dir/db.sql."* 2>/dev/null | awk '{print $1}')"
    echo "  DB OK ($db_size)"
  else
    echo "  DB FAILED — see $out_dir/db.dump.err"
    FAILURES=$((FAILURES+1))
  fi

  # 2. MEDIA TREE (rsync with --link-dest) ──────────────────────────
  rsync_opts=(-a --delete --numeric-ids)
  if [ -d "$PREV_DAILY_DIR/$site/media" ]; then
    rsync_opts+=(--link-dest="$PREV_DAILY_DIR/$site/media")
  fi

  for sub in backend/web/uploads backend/web/images frontend/web/uploads; do
    src="$site_dir/$sub/"
    [ -d "$src" ] || continue
    dst="$out_dir/media/$(echo "$sub" | tr / _)"
    mkdir -p "$dst"
    if rsync "${rsync_opts[@]}" "$src" "$dst/"; then
      bytes_new="$(du -sh --exclude='*.part-*' "$dst" 2>/dev/null | awk '{print $1}')"
      echo "  MEDIA $sub OK ($bytes_new on disk after dedup)"
    else
      echo "  MEDIA $sub FAILED"
      FAILURES=$((FAILURES+1))
    fi
  done

  # 3. Tiny manifest so a future restore knows what it's looking at.
  cat > "$out_dir/manifest.json" <<JSON
{
  "site":      "$site",
  "host":      "$(hostname)",
  "snapshot":  "$DAY_TAG",
  "taken_at":  "$TS_NOW",
  "db_name":   "$db_name",
  "db_host":   "$db_host",
  "compressor":"$BACKUP_COMPRESSOR",
  "media_subs":["backend_web_uploads","backend_web_images","frontend_web_uploads"]
}
JSON

done

# ─── Weekly / Monthly tags (symlinks; cost: 1 inode each) ───────────
if [ "$DOW" = "7" ]; then
  WEEK_TAG="$(date '+%Y-W%V')"
  ln -sfn "$DAILY_DIR" "$BACKUP_ROOT/weekly/$WEEK_TAG"
  echo
  echo "Weekly tag: $WEEK_TAG -> $DAILY_DIR"
fi
if [ "$DOM" = "01" ]; then
  MONTH_TAG="$(date '+%Y-%m')"
  ln -sfn "$DAILY_DIR" "$BACKUP_ROOT/monthly/$MONTH_TAG"
  echo "Monthly tag: $MONTH_TAG -> $DAILY_DIR"
fi

# ─── Retention prune ────────────────────────────────────────────────
prune_dir() {
  local kind="$1" keep="$2"
  local base="$BACKUP_ROOT/$kind"
  [ -d "$base" ] || return 0
  # List entries (real dirs OR symlinks) sorted oldest first; drop
  # everything except the most recent $keep.
  mapfile -t entries < <(ls -1 "$base" 2>/dev/null | sort)
  local total=${#entries[@]}
  local drop=$((total - keep))
  if [ "$drop" -le 0 ]; then return 0; fi
  for ((i=0; i<drop; i++)); do
    local target="$base/${entries[$i]}"
    if [ -L "$target" ]; then
      rm -f "$target"
    else
      rm -rf "$target"
    fi
    echo "Pruned $kind/${entries[$i]}"
  done
}

prune_dir daily   "$KEEP_DAILY"
prune_dir weekly  "$KEEP_WEEKLY"
prune_dir monthly "$KEEP_MONTHLY"

# ─── Off-site push (optional) ───────────────────────────────────────
# We push only the FRESHEST daily snapshot to keep bandwidth low. The
# remote retains its own history (typically with a similar prune
# script). If the off-site is unreachable we record a failure but do
# not delete the local snapshot.
if [ -n "$OFFSITE_RSYNC_TARGET" ]; then
  echo
  echo "──── off-site push ────"
  if rsync -az --delete \
       -e "ssh -i $OFFSITE_SSH_KEY -o StrictHostKeyChecking=accept-new -o BatchMode=yes" \
       "$DAILY_DIR/" "$OFFSITE_RSYNC_TARGET/$DAY_TAG/"; then
    echo "Off-site push OK"
  else
    echo "Off-site push FAILED"
    FAILURES=$((FAILURES+1))
  fi
fi

# ─── Summary ────────────────────────────────────────────────────────
TS_END="$(date '+%Y-%m-%d %H:%M:%S')"
echo
echo "════════════════════════════════════════════════════════════════"
echo " Tayseer backup run — end    $TS_END   failures=$FAILURES"
echo " Today's snapshot: $DAILY_DIR"
echo " Total used:       $(du -sh "$BACKUP_ROOT" 2>/dev/null | awk '{print $1}')"
echo "════════════════════════════════════════════════════════════════"

if [ "$FAILURES" -gt 0 ]; then
  heartbeat "fail"
  exit 1
fi
heartbeat ""
exit 0
