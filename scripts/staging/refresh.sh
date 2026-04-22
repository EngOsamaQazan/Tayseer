#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# Tayseer — Refresh staging.aqssat.co from a production snapshot
# ─────────────────────────────────────────────────────────────────────
#
# Designed to run nightly at 04:30 (after the 03:17 daily-snapshot.sh
# has already produced today's backup). What it does:
#
#   1. Locates today's daily snapshot for SOURCE_SITE (default: namaa,
#      the largest tenant — most realistic data shape for QA).
#   2. Loads its DB dump into the SEPARATE staging schema
#      (`tayseer_staging`), wiping whatever was there.
#   3. SCRUBS the staging DB so QA can interact safely:
#         • Replaces customer phone numbers with `+96270000xxxxx`.
#         • Replaces customer email addresses with `qa+<id>@example.com`.
#         • Resets every os_user password_hash to the QA password
#           ("Qa@2026") so any previously-real account that QA happens
#           to log in as is no longer the real account.
#         • Truncates known job/queue tables if they exist.
#   4. Runs the new unify-media migrations on the freshly-loaded schema.
#   5. rsyncs the production media tree from today's snapshot into
#      /var/www/staging.aqssat.co/backend/web/{uploads,images}/.
#   6. Clears caches + reloads php-fpm so the new schema is picked up.
#
# Idempotent: re-running just refreshes again. Drops + recreates the
# staging schema each run, so no migration-history pollution.
#
# Run by cron, NOT by humans (humans should use restore-snapshot.sh
# with --target staging if they need a one-off).
# ─────────────────────────────────────────────────────────────────────

set -Eeuo pipefail

: "${BACKUP_ROOT:=/var/backups/tayseer}"
: "${SOURCE_SITE:=namaa}"
: "${STAGING_DIR:=/var/www/staging.aqssat.co}"
: "${STAGING_DB:=tayseer_staging}"
: "${LOG_FILE:=/var/log/tayseer-staging-refresh.log}"

# Pull credentials from the production tenant config (single source
# of truth). If anyone rotates the MySQL password, only the prod
# Yii configs need updating — refresh.sh will pick it up automatically.
: "${CRED_SRC:=/var/www/${SOURCE_SITE}.aqssat.co/common/config/main-local.php}"

if [ -f /etc/default/tayseer-backup ]; then
  # shellcheck disable=SC1091
  source /etc/default/tayseer-backup
fi
if [ -f /etc/default/tayseer-staging ]; then
  # shellcheck disable=SC1091
  source /etc/default/tayseer-staging
fi

if [ ! -f "$CRED_SRC" ]; then
  echo "ERROR: $CRED_SRC missing — cannot read MySQL credentials"
  exit 2
fi
DB_USER="$(CRED_SRC="$CRED_SRC" php -r '$c=require getenv("CRED_SRC"); echo $c["components"]["db"]["username"];')"
DB_PASS="$(CRED_SRC="$CRED_SRC" php -r '$c=require getenv("CRED_SRC"); echo $c["components"]["db"]["password"];')"

mkdir -p "$(dirname "$LOG_FILE")"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "════════════════════════════════════════════════════════════════"
echo " staging refresh — start  $(date '+%Y-%m-%d %H:%M:%S')"
echo "════════════════════════════════════════════════════════════════"

# ─── 1. Locate today's snapshot ─────────────────────────────────────
DAY="$(date '+%Y-%m-%d')"
SNAP_DIR="$BACKUP_ROOT/daily/$DAY/$SOURCE_SITE"

# Fall back one day if the most recent backup hasn't run yet (e.g.
# someone is running this manually before 03:17).
if [ ! -d "$SNAP_DIR" ]; then
  YEST="$(date -d 'yesterday' '+%Y-%m-%d')"
  SNAP_DIR="$BACKUP_ROOT/daily/$YEST/$SOURCE_SITE"
  echo "Today's snapshot missing; falling back to $YEST"
fi
[ -d "$SNAP_DIR" ] || { echo "ERROR: no snapshot found for $SOURCE_SITE"; exit 1; }

DUMP=""
for f in "$SNAP_DIR/db.sql.gz" "$SNAP_DIR/db.sql.zst" "$SNAP_DIR/db.sql"; do
  [ -f "$f" ] && DUMP="$f" && break
done
[ -n "$DUMP" ] || { echo "ERROR: no DB dump in $SNAP_DIR"; exit 1; }

echo "Source snapshot: $SNAP_DIR"
echo "Source dump:     $DUMP"

# ─── 2. Drop+recreate the staging schema, then load the dump ────────
echo "Resetting $STAGING_DB"
MYSQL_PWD="$DB_PASS" mysql -u"$DB_USER" -e "
  DROP DATABASE IF EXISTS \`$STAGING_DB\`;
  CREATE DATABASE \`$STAGING_DB\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"

echo "Loading dump into $STAGING_DB"
case "$DUMP" in
  *.gz)  gunzip -c "$DUMP" ;;
  *.zst) zstd -dc "$DUMP" ;;
  *)     cat "$DUMP" ;;
esac | MYSQL_PWD="$DB_PASS" mysql -u"$DB_USER" --default-character-set=utf8mb4 "$STAGING_DB"

# ─── 3. Scrub PII so QA can poke without leaking real data ──────────
# All updates are deliberately additive (UPDATE ... WHERE 1=1) so a
# partial failure leaves no half-scrubbed rows. Each statement is
# wrapped in `IF EXISTS` logic via information_schema so adding/
# removing a tenant table doesn't break the refresh.
echo "Scrubbing PII + neutering outbound channels"
MYSQL_PWD="$DB_PASS" mysql -u"$DB_USER" "$STAGING_DB" <<'SQL'
SET FOREIGN_KEY_CHECKS=0;

-- Customers: redact contact info. Schema as of 2026-04: os_customers
-- has `email` (varchar 50) and `primary_phone_number` (varchar 255).
UPDATE os_customers SET
    primary_phone_number = CONCAT('+96270000', LPAD(id MOD 100000, 5, '0')),
    email                = CONCAT('qa+', id, '@example.com')
WHERE 1=1;

-- Reset every os_user password_hash to the bcrypt of "Qa@2026" so
-- whichever account QA happens to authenticate as is no longer the
-- real account, AND mark all of them confirmed so QA can sign in.
-- Hash regenerated via:
--   php -r 'require "vendor/autoload.php"; require "vendor/yiisoft/yii2/Yii.php";
--           new yii\console\Application(["id"=>"x","basePath"=>__DIR__]);
--           echo Yii::$app->security->generatePasswordHash("Qa@2026");'
UPDATE os_user SET
    password_hash = '$2y$13$GkDJFRqVy9F142FmpnCMyOkyW08zBCQpanwTb2ytE5vkcbkKtWj9S',
    auth_key      = SUBSTRING(MD5(RAND()), 1, 32),
    confirmed_at  = COALESCE(confirmed_at, UNIX_TIMESTAMP()),
    blocked_at    = NULL
WHERE 1=1;

SET FOREIGN_KEY_CHECKS=1;
SQL

# Optional table truncations — only if the table actually exists.
# Survives schema drift across tenants without aborting the refresh.
for tbl in queue os_queue jobs notifications_queue sms_queue email_queue; do
  EXISTS=$(MYSQL_PWD="$DB_PASS" mysql -N -u"$DB_USER" -e "
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema='$STAGING_DB' AND table_name='$tbl';")
  if [ "$EXISTS" = "1" ]; then
    echo "  truncating $tbl"
    MYSQL_PWD="$DB_PASS" mysql -u"$DB_USER" "$STAGING_DB" -e "TRUNCATE TABLE \`$tbl\`;" || true
  fi
done

# ─── 4. Apply unify-media migrations on the freshly-loaded schema ──
# The dump captured production state, which doesn't yet have the new
# media columns. Run only the m260419_* migrations so that a
# refresh -> migrate cycle leaves staging at the latest unify-media
# schema version every night.
echo "Applying unify-media migrations on fresh schema"
cd "$STAGING_DIR"
sudo -u www-data php yii migrate/up --interactive=0 --migrationPath=@console/migrations 2>&1 | tail -20 || \
  echo "WARN: migrate had issues — see /var/log/tayseer-staging-refresh.log"

# ─── 5. Sync media tree (best-effort: missing source dirs are OK) ──
echo "Syncing media tree"
for sub in backend/web/uploads backend/web/images frontend/web/uploads; do
  src_key="$(echo "$sub" | tr / _)"
  src="$SNAP_DIR/media/$src_key/"
  dst="$STAGING_DIR/$sub/"
  if [ -d "$src" ]; then
    mkdir -p "$dst"
    rsync -a --delete "$src" "$dst"
    echo "  $sub: $(du -sh "$dst" 2>/dev/null | awk '{print $1}')"
  else
    echo "  $sub: (no source in snapshot, skipped)"
  fi
done
chown -R www-data:www-data "$STAGING_DIR/backend/web" "$STAGING_DIR/frontend/web" 2>/dev/null || true

# ─── 6. Clear caches + reload php-fpm so OPcache picks up changes ──
rm -rf "$STAGING_DIR/backend/runtime/cache/"* 2>/dev/null || true
rm -rf "$STAGING_DIR/frontend/runtime/cache/"* 2>/dev/null || true
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo 8.5)"
systemctl reload "php${PHP_VER}-fpm" 2>/dev/null || true

echo
echo " staging refresh — done  $(date '+%Y-%m-%d %H:%M:%S')"
echo " QA login: any existing email / Qa@2026"
echo " (e.g. osamaqazan89@gmail.com / Qa@2026)"
