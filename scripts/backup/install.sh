#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# Tayseer — Backup System Installer (run on the SERVER, as root)
# ─────────────────────────────────────────────────────────────────────
#
# One-shot bootstrap that:
#   1. Creates /var/backups/tayseer with right perms
#   2. Drops a stub /etc/default/tayseer-backup the operator can edit
#      (off-site target, heartbeat URL, retention overrides)
#   3. Installs the three scripts into /opt/tayseer-backup/
#   4. Wires up cron via /etc/cron.d/tayseer-backup
#   5. Configures logrotate so the log file does not grow forever
#
# Idempotent — safe to re-run after pulling new versions of the
# scripts; only files that changed are touched.
#
# Run from the repo root on the server:
#   sudo bash scripts/backup/install.sh
#
# Or from any clone:
#   sudo bash /var/www/jadal.aqssat.co/scripts/backup/install.sh
# ─────────────────────────────────────────────────────────────────────

set -Eeuo pipefail

if [ "$(id -u)" != "0" ]; then
  echo "Run me as root (sudo)."; exit 1
fi

SRC_DIR="$(cd "$(dirname "$0")" && pwd)"
DST_DIR="/opt/tayseer-backup"
BACKUP_ROOT_DEFAULT="/var/backups/tayseer"

echo "Installing Tayseer backup system from $SRC_DIR"

# 1. backup root
mkdir -p "$BACKUP_ROOT_DEFAULT"/{daily,weekly,monthly,pre-restore}
chown root:root "$BACKUP_ROOT_DEFAULT"
chmod 750       "$BACKUP_ROOT_DEFAULT"

# 2. config stub — only created if missing so operator edits survive
if [ ! -f /etc/default/tayseer-backup ]; then
  cat > /etc/default/tayseer-backup <<'EOF'
# Tayseer backup configuration. Edit and re-run install.sh to apply.
# Lines beginning with `#` are comments.

# Where snapshots live (must be on a volume with enough free space —
# rule of thumb: 3× largest tenant DB + 2× largest media tree).
BACKUP_ROOT=/var/backups/tayseer

# Production sites this server hosts. One word each, matches
# /var/www/<NAME>.aqssat.co.
SITES="jadal namaa watar majd"

# Retention. Generous defaults; reduce on tiny disks.
KEEP_DAILY=14
KEEP_WEEKLY=8
KEEP_MONTHLY=12

# Compressor: gzip (universal) or zstd (faster + smaller, install zstd first).
BACKUP_COMPRESSOR=gzip

# Optional: HealthChecks.io / cronitor / similar dead-man-switch URL.
# Leave blank to disable. The script appends `/fail` on errors.
HEARTBEAT_URL=

# Optional: off-site rsync target. Leave blank to disable.
# Example: backup@offsite.example.com:/srv/backups/tayseer
OFFSITE_RSYNC_TARGET=
OFFSITE_SSH_KEY=/root/.ssh/id_ed25519_backup

# Where to log
LOG_FILE=/var/log/tayseer-backup.log
EOF
  echo "Wrote /etc/default/tayseer-backup (edit it, then re-run install.sh)"
else
  echo "Kept existing /etc/default/tayseer-backup"
fi

# 3. install scripts
mkdir -p "$DST_DIR"
for f in daily-snapshot.sh restore-snapshot.sh verify-snapshot.sh; do
  install -m 0750 -o root -g root "$SRC_DIR/$f" "$DST_DIR/$f"
done
echo "Installed scripts into $DST_DIR"

# 4. cron — daily at 03:17, verify at 03:55. Times deliberately
# odd-minute to avoid collisions with the rest of the cron herd.
cat > /etc/cron.d/tayseer-backup <<'EOF'
# Tayseer backups. Managed by scripts/backup/install.sh.
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MAILTO=root

17 3 * * * root /opt/tayseer-backup/daily-snapshot.sh
55 3 * * * root /opt/tayseer-backup/verify-snapshot.sh
EOF
chmod 0644 /etc/cron.d/tayseer-backup
echo "Wrote /etc/cron.d/tayseer-backup"

# 5. logrotate
cat > /etc/logrotate.d/tayseer-backup <<'EOF'
/var/log/tayseer-backup.log {
  weekly
  rotate 12
  compress
  delaycompress
  missingok
  notifempty
  create 0640 root adm
}
EOF
chmod 0644 /etc/logrotate.d/tayseer-backup
echo "Wrote /etc/logrotate.d/tayseer-backup"

# 6. sanity probe
echo
echo "── sanity probe ──"
for bin in mysqldump mysql rsync gzip php; do
  if command -v "$bin" >/dev/null 2>&1; then
    echo "  $bin: OK ($(command -v $bin))"
  else
    echo "  $bin: MISSING — please apt install"
  fi
done
if [ "$(grep -c BACKUP_COMPRESSOR=zstd /etc/default/tayseer-backup || true)" -gt 0 ]; then
  command -v zstd >/dev/null 2>&1 || echo "  zstd: MISSING — apt install zstd"
fi

echo
echo "Install complete. Run a manual test now:"
echo "  sudo /opt/tayseer-backup/daily-snapshot.sh"
echo "Then verify:"
echo "  sudo /opt/tayseer-backup/verify-snapshot.sh"
echo "Restore drill (DRY-RUN):"
echo "  sudo /opt/tayseer-backup/restore-snapshot.sh --site namaa --date $(date +%F) --what db --target staging"
