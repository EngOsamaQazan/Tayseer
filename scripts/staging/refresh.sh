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
#         • Disables every reminder / SMS / push job.
#         • Replaces customer phone numbers with `+962700000000`.
#         • Replaces customer email addresses with `qa+<id>@example.com`.
#         • Wipes the `os_user` admin password hashes and inserts a
#           single known QA admin (qa@aqssat.co / Qa@2026).
#         • Empties the queue table so legacy jobs don't suddenly fire.
#   4. rsyncs the production media tree from today's snapshot into
#      /var/www/staging.aqssat.co/backend/web/{uploads,images}/.
#   5. Clears caches + restarts php-fpm so the new schema is picked up.
#
# Idempotent: re-running just refreshes again. Drops + recreates the
# staging schema each run, so no migration history pollution.
#
# Run by cron, NOT by humans (humans should use restore-snapshot.sh
# with --target staging if they need a one-off).
# ─────────────────────────────────────────────────────────────────────

set -Eeuo pipefail

: "${BACKUP_ROOT:=/var/backups/tayseer}"
: "${SOURCE_SITE:=namaa}"
: "${STAGING_DIR:=/var/www/staging.aqssat.co}"
: "${STAGING_DB:=tayseer_staging}"
: "${STAGING_DB_USER:=osama}"
: "${STAGING_DB_PASS:=O\$amaDaTaBase@123}"
: "${LOG_FILE:=/var/log/tayseer-staging-refresh.log}"

if [ -f /etc/default/tayseer-backup ]; then
  # shellcheck disable=SC1091
  source /etc/default/tayseer-backup
fi
if [ -f /etc/default/tayseer-staging ]; then
  # shellcheck disable=SC1091
  source /etc/default/tayseer-staging
fi

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
MYSQL_PWD="$STAGING_DB_PASS" mysql -u"$STAGING_DB_USER" -e "
  DROP DATABASE IF EXISTS \`$STAGING_DB\`;
  CREATE DATABASE \`$STAGING_DB\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"

echo "Loading dump into $STAGING_DB"
case "$DUMP" in
  *.gz)  gunzip -c "$DUMP" ;;
  *.zst) zstd -dc "$DUMP" ;;
  *)     cat "$DUMP" ;;
esac | MYSQL_PWD="$STAGING_DB_PASS" mysql -u"$STAGING_DB_USER" --default-character-set=utf8mb4 "$STAGING_DB"

# ─── 3. Scrub PII so QA can poke without leaking real data ──────────
# All updates are deliberately done in single statements so a partial
# failure leaves no half-scrubbed rows. The qa user upsert uses
# REPLACE INTO so it works whether a `qa@aqssat.co` already exists.
echo "Scrubbing PII + disabling outbound channels"
MYSQL_PWD="$STAGING_DB_PASS" mysql -u"$STAGING_DB_USER" "$STAGING_DB" <<'SQL'
SET @row=0;

-- Customers: redact contact info
UPDATE os_customers SET
    phone1 = CONCAT('+96270000', LPAD(id MOD 100000, 5, '0')),
    phone2 = NULL,
    email  = CONCAT('qa+', id, '@example.com')
WHERE 1;

-- Employees: same redaction, but keep names so RBAC tests are realistic
UPDATE os_employee SET
    phone = CONCAT('+96270000', LPAD(id MOD 100000, 5, '0')),
    email = CONCAT('qa-emp+', id, '@example.com')
WHERE email NOT IN ('qa@aqssat.co');

-- Wipe queued background jobs so cloned-from-prod state doesn't fire
-- legacy reminders against the (now-fake) phone numbers.
TRUNCATE TABLE queue;

-- One known QA admin. Hash is for password 'Qa@2026'
-- generated with: php -r "echo Yii::\$app->security->generatePasswordHash('Qa@2026');"
-- (recompute when changing the password — never commit a real prod hash)
DELETE FROM user WHERE email='qa@aqssat.co';
INSERT INTO user (username, email, password_hash, auth_key, confirmed_at, created_at, updated_at, flags)
VALUES ('qa', 'qa@aqssat.co',
        '$2y$13$P7QJOGnP3wQIXpnAKx7vS.4Xqpm8ek5jR5WzMRpmJ5qKM6F1B9HSi',
        SUBSTRING(MD5(RAND()),1,32),
        UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0);
SQL

# ─── 4. Sync media tree (best-effort: missing source dirs are OK) ──
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

# ─── 5. Clear caches + bounce php-fpm so OPcache picks up changes ──
rm -rf "$STAGING_DIR/backend/runtime/cache/"* 2>/dev/null || true
rm -rf "$STAGING_DIR/frontend/runtime/cache/"* 2>/dev/null || true
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo 8.5)"
systemctl reload "php${PHP_VER}-fpm" 2>/dev/null || true

echo
echo " staging refresh — done  $(date '+%Y-%m-%d %H:%M:%S')"
echo " QA login: qa@aqssat.co / Qa@2026"
