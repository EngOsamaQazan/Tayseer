#!/bin/bash
# Fast deploy: pull latest code on all sites + re-apply env configs + clear OPcache
# Called by the webhook endpoint (gh-deploy.php) for instant code delivery.

set -euo pipefail

BRANCH="main"
LOG="/var/log/deploy-pull.log"

exec >> "$LOG" 2>&1
echo "--- $(date '+%Y-%m-%d %H:%M:%S') --- webhook pull triggered ---"

pull_site() {
  local SITE_DIR="$1"
  local ENV_DIR="$2"

  [ -d "$SITE_DIR/.git" ] || return 0

  cd "$SITE_DIR"
  git fetch origin "$BRANCH" --depth 1 -q
  git reset --hard "origin/$BRANCH" -q

  for cfg in common/config/main-local.php common/config/params-local.php \
             backend/web/index.php frontend/web/index.php \
             console/config/main-local.php console/config/params-local.php \
             yii; do
    [ -f "environments/$ENV_DIR/$cfg" ] && cp -f "environments/$ENV_DIR/$cfg" "$cfg"
  done
  chmod +x yii 2>/dev/null || true

  chown -R www-data:www-data backend/ common/ console/ frontend/ api/ vendor/ 2>/dev/null || true
  rm -rf backend/runtime/cache/* frontend/runtime/cache/* 2>/dev/null || true

  echo "  $SITE_DIR done"
}

pull_site "/var/www/jadal.aqssat.co" "prod_jadal" &
pull_site "/var/www/namaa.aqssat.co" "prod_namaa" &
pull_site "/var/www/watar.aqssat.co" "prod_watar" &
wait

# Clear OPcache without restarting PHP-FPM (fast)
php -r 'opcache_reset();' 2>/dev/null || true

# Landing page
LANDING_SRC="/var/www/jadal.aqssat.co/landing/index.html"
[ -f "$LANDING_SRC" ] && cp -f "$LANDING_SRC" /var/www/html/index.html 2>/dev/null || true

echo "--- pull complete ($(date '+%H:%M:%S')) ---"
