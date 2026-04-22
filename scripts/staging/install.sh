#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# Tayseer — staging.aqssat.co bootstrap (run on the SERVER, as root)
# ─────────────────────────────────────────────────────────────────────
#
# Wires up the staging tier from scratch. Idempotent: safe to re-run.
# Steps:
#   1. Create /var/www/staging.aqssat.co + folder skeleton.
#   2. Clone the `unify-media` branch into that directory.
#   3. Apply environments/prod_staging configs.
#   4. Create the `tayseer_staging` MySQL schema (empty — refresh.sh
#      fills it from the most recent backup).
#   5. Drop the Apache vhost into sites-available + enable it.
#   6. Provision Let's Encrypt cert.
#   7. Install /etc/cron.d/tayseer-staging-refresh (nightly 04:30).
#
# Run after scripts/backup/install.sh — refresh.sh depends on the
# backup volume.
# ─────────────────────────────────────────────────────────────────────

set -Eeuo pipefail

if [ "$(id -u)" != "0" ]; then
  echo "Run me as root (sudo)."; exit 1
fi

: "${REPO_URL:=https://github.com/EngOsamaQazan/Tayseer.git}"
: "${BRANCH:=unify-media}"
: "${STAGING_DIR:=/var/www/staging.aqssat.co}"
: "${STAGING_DB:=tayseer_staging}"
: "${STAGING_HOST:=staging.aqssat.co}"
: "${LE_EMAIL:=admin@aqssat.co}"

SRC_DIR="$(cd "$(dirname "$0")" && pwd)"

# ─── 1. Workspace ───────────────────────────────────────────────────
echo "Bootstrapping $STAGING_HOST in $STAGING_DIR"
mkdir -p "$STAGING_DIR"

# ─── 2. Clone or fetch unify-media branch ───────────────────────────
if [ ! -d "$STAGING_DIR/.git" ]; then
  echo "First-time clone of $BRANCH"
  cd /tmp
  rm -rf staging_temp
  git clone --depth 1 --branch "$BRANCH" "$REPO_URL" staging_temp
  rsync -a --exclude='.env' --exclude='runtime/' staging_temp/ "$STAGING_DIR/"
  rm -rf staging_temp
  cd "$STAGING_DIR"
  git init -q
  git remote add origin "$REPO_URL"
  git fetch -q origin "$BRANCH" --depth 1
  git reset --hard "origin/$BRANCH" -q
else
  cd "$STAGING_DIR"
  git fetch -q origin "$BRANCH" --depth 1
  git reset --hard "origin/$BRANCH" -q
fi

# ─── 3. Apply staging environment configs ───────────────────────────
cd "$STAGING_DIR"
ENV_DIR="environments/prod_staging"
[ -d "$ENV_DIR" ] || { echo "ERROR: $ENV_DIR missing — pull a newer commit"; exit 1; }

for cfg in common/config/main-local.php common/config/params-local.php \
           backend/web/index.php frontend/web/index.php \
           console/config/main-local.php console/config/params-local.php yii; do
  if [ -f "$ENV_DIR/$cfg" ]; then
    mkdir -p "$(dirname "$cfg")"
    cp -f "$ENV_DIR/$cfg" "$cfg"
  fi
done
chmod +x yii 2>/dev/null || true

# Cookie keys — only touch if missing so existing sessions survive.
for cfg in backend/config/main-local.php backend/config/params-local.php \
           frontend/config/main-local.php frontend/config/params-local.php; do
  if [ ! -f "$cfg" ] && [ -f "$ENV_DIR/$cfg" ]; then
    mkdir -p "$(dirname "$cfg")"
    cp "$ENV_DIR/$cfg" "$cfg"
    KEY="$(openssl rand -hex 32)"
    sed -i "s/=> ''/=> '$KEY'/" "$cfg"
  fi
done

# Composer install (production deps only — no xdebug).
# COMPOSER_ALLOW_SUPERUSER=1 forces composer to load plugins even when
# running as root, which is the only way the yiisoft/yii2-composer
# plugin can populate vendor/yiisoft/extensions.php. Without that file
# every Yii extension's Bootstrap class is skipped on every request —
# most visibly, dektrium/yii2-user never registers the @dektrium/user
# alias and /user/login returns "Unable to resolve the request".
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --no-interaction --optimize-autoloader 2>&1 || \
  echo "WARN: composer install had issues"

# Belt-and-braces: if extensions.php is empty (the symptom of a prior
# install having silently skipped plugins), nuke vendor/ and reinstall
# from scratch so the post-install hook actually fires.
if [ ! -s vendor/yiisoft/extensions.php ]; then
  echo "vendor/yiisoft/extensions.php empty — forcing full reinstall"
  rm -rf vendor
  composer install --no-dev --no-interaction --optimize-autoloader
fi

# Permissions
chown -R www-data:www-data backend/ common/ console/ frontend/ api/ vendor/ 2>/dev/null || true
mkdir -p backend/runtime backend/web/assets frontend/runtime frontend/web/assets
chmod -R 775 backend/runtime backend/web/assets frontend/runtime frontend/web/assets

# ─── 4. Create the staging schema if it does not exist ──────────────
echo "Ensuring MySQL schema $STAGING_DB"

# Borrow credentials from a known production tenant (jadal). The Yii
# config files are the canonical place we already store DB passwords;
# duplicating them in this script would be a stale-credential trap.
CRED_SRC="${CRED_SRC:-/var/www/jadal.aqssat.co/common/config/main-local.php}"
if [ ! -f "$CRED_SRC" ]; then
  echo "ERROR: $CRED_SRC missing — cannot read DB credentials"
  exit 2
fi
DB_USER="$(CRED_SRC="$CRED_SRC" php -r '$c=require getenv("CRED_SRC"); echo $c["components"]["db"]["username"];')"
DB_PASS="$(CRED_SRC="$CRED_SRC" php -r '$c=require getenv("CRED_SRC"); echo $c["components"]["db"]["password"];')"
DB_HOST="$(CRED_SRC="$CRED_SRC" php -r '$c=require getenv("CRED_SRC"); preg_match("/host=([^;]+)/",$c["components"]["db"]["dsn"],$m); echo $m[1] ?? "localhost";')"

MYSQL_PWD="$DB_PASS" mysql -h"$DB_HOST" -u"$DB_USER" \
  -e "CREATE DATABASE IF NOT EXISTS \`$STAGING_DB\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Make sure the staging Yii config actually points at the staging DB
# (catches the historical bug where staging silently used namaa_erp).
STAGING_DB_IN_CFG="$(php -r '$c=require "common/config/main-local.php"; preg_match("/dbname=([^;]+)/",$c["components"]["db"]["dsn"],$m); echo $m[1];' 2>/dev/null || echo "")"
if [ "$STAGING_DB_IN_CFG" != "$STAGING_DB" ]; then
  echo "ERROR: staging common/config/main-local.php points at '$STAGING_DB_IN_CFG'"
  echo "       not '$STAGING_DB'. Refusing to continue (would corrupt prod)."
  exit 2
fi

# Run migrations (the schema starts empty; refresh.sh later replaces
# everything with a snapshot, but a fresh schema avoids confusing the
# Yii bootstrap on first request).
php yii migrate/up --interactive=0 || true

# ─── 5. Apache vhost ────────────────────────────────────────────────
VHOST_SRC="$SRC_DIR/staging.aqssat.co.conf"
VHOST_DST="/etc/apache2/sites-available/staging.aqssat.co.conf"
if [ -f "$VHOST_SRC" ]; then
  cp -f "$VHOST_SRC" "$VHOST_DST"
  a2ensite staging.aqssat.co.conf
  apache2ctl configtest && systemctl reload apache2
  echo "Apache vhost installed"
else
  echo "WARN: $VHOST_SRC missing"
fi

# ─── 6. Let's Encrypt (only if certbot is installed and DNS resolves)
if command -v certbot >/dev/null 2>&1; then
  if host "$STAGING_HOST" >/dev/null 2>&1; then
    echo "Provisioning Let's Encrypt cert for $STAGING_HOST"
    certbot --apache --non-interactive --agree-tos --email "$LE_EMAIL" \
            -d "$STAGING_HOST" --redirect || \
      echo "WARN: certbot failed — check DNS A-record for $STAGING_HOST"
  else
    echo "WARN: $STAGING_HOST does not resolve yet — point DNS, then run: certbot --apache -d $STAGING_HOST"
  fi
else
  echo "WARN: certbot not installed — skipping cert"
fi

# ─── 7. Cron: nightly refresh from production snapshot ──────────────
install -m 0750 -o root -g root "$SRC_DIR/refresh.sh" /opt/tayseer-staging-refresh.sh
cat > /etc/cron.d/tayseer-staging-refresh <<'EOF'
# Tayseer staging refresh. Managed by scripts/staging/install.sh.
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MAILTO=root

# Runs at 04:30 — one hour after the daily backup, so today's
# snapshot is guaranteed to exist.
30 4 * * * root /opt/tayseer-staging-refresh.sh
EOF
chmod 0644 /etc/cron.d/tayseer-staging-refresh

echo
echo "Done. Manual first refresh:"
echo "  sudo /opt/tayseer-staging-refresh.sh"
echo
echo "Then point your browser at https://$STAGING_HOST"
echo "  QA login: qa@aqssat.co / Qa@2026"
