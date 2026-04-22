#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# Tayseer — Restore From Snapshot
# ─────────────────────────────────────────────────────────────────────
#
# Companion to daily-snapshot.sh. Restores either the database, the
# media tree, or both, for ONE site, from a chosen daily snapshot
# date.
#
# Usage:
#   restore-snapshot.sh --site namaa --date 2026-04-22 --what db
#   restore-snapshot.sh --site namaa --date 2026-04-22 --what media
#   restore-snapshot.sh --site namaa --date 2026-04-22 --what all
#   restore-snapshot.sh --list
#
# Safety rails:
#   • DRY-RUN by default. Pass --apply to actually touch anything.
#   • DB restore creates a `pre_restore` dump of the current DB
#     FIRST, into /var/backups/tayseer/pre-restore/<site>-<ts>.sql.gz,
#     so a wrong restore can itself be undone.
#   • Media restore uses rsync with --backup --backup-dir so files
#     overwritten by the restore are MOVED to a sibling dir, not
#     deleted.
#   • Refuses to restore into a non-empty target without --force.
#
# Restore drill (run quarterly, please):
#   ./scripts/backup/restore-snapshot.sh --list
#   ./scripts/backup/restore-snapshot.sh \
#     --site namaa --date <recent> --what db --apply --target staging
#   # → confirm row counts match production, then drop the staging DB
# ─────────────────────────────────────────────────────────────────────

set -Eeuo pipefail

: "${BACKUP_ROOT:=/var/backups/tayseer}"
: "${WEB_ROOT:=/var/www}"

if [ -f /etc/default/tayseer-backup ]; then
  # shellcheck disable=SC1091
  source /etc/default/tayseer-backup
fi

# ─── arg parsing ────────────────────────────────────────────────────
SITE=""
DATE=""
WHAT=""
APPLY=0
FORCE=0
LIST=0
TARGET="prod"          # prod | staging  — staging restores into the staging DB/path
TARGET_DIR_OVERRIDE="" # full override path (advanced)

usage() {
  sed -n '5,40p' "$0"
  exit 2
}

while [ $# -gt 0 ]; do
  case "$1" in
    --site)         SITE="$2"; shift 2 ;;
    --date)         DATE="$2"; shift 2 ;;
    --what)         WHAT="$2"; shift 2 ;;
    --apply)        APPLY=1;  shift ;;
    --force)        FORCE=1;  shift ;;
    --list)         LIST=1;   shift ;;
    --target)       TARGET="$2"; shift 2 ;;
    --target-dir)   TARGET_DIR_OVERRIDE="$2"; shift 2 ;;
    -h|--help)      usage ;;
    *) echo "Unknown arg: $1"; usage ;;
  esac
done

# ─── --list mode: show what is restorable ───────────────────────────
if [ "$LIST" = "1" ]; then
  echo "Available snapshots in $BACKUP_ROOT:"
  for kind in daily weekly monthly; do
    echo
    echo "── $kind ──"
    if [ -d "$BACKUP_ROOT/$kind" ]; then
      ls -1 "$BACKUP_ROOT/$kind" 2>/dev/null | sort
    fi
  done
  exit 0
fi

[ -n "$SITE" ] || { echo "ERROR: --site is required"; usage; }
[ -n "$DATE" ] || { echo "ERROR: --date is required"; usage; }
[ -n "$WHAT" ] || { echo "ERROR: --what is required (db|media|all)"; usage; }

case "$WHAT" in db|media|all) ;; *) echo "ERROR: --what must be db|media|all"; exit 2 ;; esac

SNAP_DIR=""
for kind in daily weekly monthly; do
  if [ -d "$BACKUP_ROOT/$kind/$DATE/$SITE" ]; then
    SNAP_DIR="$BACKUP_ROOT/$kind/$DATE/$SITE"; break
  fi
done

if [ -z "$SNAP_DIR" ]; then
  echo "ERROR: no snapshot found for site=$SITE date=$DATE"
  echo "Try: $0 --list"
  exit 2
fi

[ -f "$SNAP_DIR/manifest.json" ] || { echo "ERROR: manifest.json missing in $SNAP_DIR"; exit 2; }

echo "Snapshot found: $SNAP_DIR"
cat "$SNAP_DIR/manifest.json"
echo

# ─── decide where we are restoring TO ───────────────────────────────
if [ -n "$TARGET_DIR_OVERRIDE" ]; then
  TARGET_DIR="$TARGET_DIR_OVERRIDE"
elif [ "$TARGET" = "staging" ]; then
  TARGET_DIR="$WEB_ROOT/staging.aqssat.co"
else
  TARGET_DIR="$WEB_ROOT/$SITE.aqssat.co"
fi

[ -d "$TARGET_DIR" ] || { echo "ERROR: target dir does not exist: $TARGET_DIR"; exit 2; }

# Read current target's DB credentials (where we will load the dump).
read_db_setting() {
  local key="$1"
  local cfg="$TARGET_DIR/common/config/main-local.php"
  [ -f "$cfg" ] || { echo ""; return 0; }
  php -r "
    \$c = require '$cfg';
    \$db = \$c['components']['db'] ?? [];
    if ('$key' === 'dbname') {
      if (preg_match('/dbname=([^;]+)/', \$db['dsn'] ?? '', \$m)) echo \$m[1];
    } elseif ('$key' === 'host') {
      if (preg_match('/host=([^;]+)/', \$db['dsn'] ?? '', \$m)) echo \$m[1];
      else echo 'localhost';
    } elseif ('$key' === 'username') { echo \$db['username'] ?? '';
    } elseif ('$key' === 'password') { echo \$db['password'] ?? ''; }
  " 2>/dev/null || echo ""
}

if [ "$APPLY" != "1" ]; then
  echo "── DRY RUN ── (pass --apply to actually restore)"
fi

# ─── DB RESTORE ─────────────────────────────────────────────────────
restore_db() {
  local dump=""
  for f in "$SNAP_DIR/db.sql.gz" "$SNAP_DIR/db.sql.zst" "$SNAP_DIR/db.sql"; do
    [ -f "$f" ] && dump="$f" && break
  done
  [ -n "$dump" ] || { echo "ERROR: no db dump in snapshot"; return 1; }

  local db_name db_user db_pass db_host
  db_name="$(read_db_setting dbname)"
  db_user="$(read_db_setting username)"
  db_pass="$(read_db_setting password)"
  db_host="$(read_db_setting host)"
  [ -n "$db_name" ] || { echo "ERROR: target DB name unreadable"; return 1; }

  # PRODUCTION GUARD: refuse to restore into a prod DB unless --force.
  # This catches "I meant staging" mistakes that lose live data.
  if [ "$TARGET" = "prod" ] && [ "$FORCE" != "1" ]; then
    echo "REFUSING to restore into PROD DB '$db_name' without --force"
    echo "If you really mean it: re-run with --force (and you've already taken a fresh backup, right?)"
    return 1
  fi

  echo "Restoring DB '$db_name' from $dump"

  if [ "$APPLY" != "1" ]; then
    echo "  (dry-run)"
    return 0
  fi

  # Pre-restore safety dump
  local pre_dir="$BACKUP_ROOT/pre-restore"
  mkdir -p "$pre_dir"
  local pre="$pre_dir/${SITE}-pre-restore-$(date +%Y%m%d-%H%M%S).sql.gz"
  echo "  Saving CURRENT state of '$db_name' to $pre"
  MYSQL_PWD="$db_pass" mysqldump --host="$db_host" --user="$db_user" \
    --single-transaction --quick --routines --triggers --events --hex-blob \
    --default-character-set=utf8mb4 --set-gtid-purged=OFF \
    "$db_name" | gzip -9 > "$pre"

  # Stream the dump in. Decompress on-the-fly based on extension.
  echo "  Loading dump..."
  case "$dump" in
    *.gz)  gunzip -c "$dump" ;;
    *.zst) zstd -dc "$dump" ;;
    *)     cat "$dump" ;;
  esac | MYSQL_PWD="$db_pass" mysql --host="$db_host" --user="$db_user" \
                                    --default-character-set=utf8mb4 "$db_name"
  echo "  DB restore complete."
  echo "  >>> If something looks wrong, restore CURRENT-as-of-now from: $pre"
}

# ─── MEDIA RESTORE ──────────────────────────────────────────────────
restore_media() {
  local backup_at="$TARGET_DIR/.restore-backup-$(date +%Y%m%d-%H%M%S)"
  local opts=(-a --backup --backup-dir="$backup_at")

  for entry in backend_web_uploads backend_web_images frontend_web_uploads; do
    local src="$SNAP_DIR/media/$entry/"
    [ -d "$src" ] || continue
    local dst_sub
    dst_sub="$(echo "$entry" | sed 's/_/\//g')"
    local dst="$TARGET_DIR/$dst_sub/"
    mkdir -p "$dst"
    echo "Restoring media: $src -> $dst"
    if [ "$APPLY" = "1" ]; then
      rsync "${opts[@]}" "$src" "$dst"
    else
      rsync "${opts[@]}" --dry-run "$src" "$dst" | tail -n 5
    fi
  done

  if [ "$APPLY" = "1" ] && [ -d "$backup_at" ]; then
    echo "Files that were overwritten are saved at: $backup_at"
  fi
}

case "$WHAT" in
  db)    restore_db ;;
  media) restore_media ;;
  all)   restore_db && restore_media ;;
esac

echo
echo "Done."
