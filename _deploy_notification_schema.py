import paramiko
import sys
import os

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

SERVER_HOST = '31.220.82.115'
SSH_USER = 'root'
SSH_PASS = 'HAmAS12852'

SITES = ['jadal', 'namaa', 'watar']
DBS = {
    'jadal': 'namaa_jadal',
    'namaa': 'namaa_namaa',
    'watar': 'namaa_watar',
}
LOCAL_BASE = r'c:\Users\PC\Desktop\Tayseer'

FILES_TO_DEPLOY = [
    'backend/modules/notification/controllers/NotificationController.php',
    'backend/modules/notification/models/Notification.php',
    'backend/modules/notification/models/NotificationSearch.php',
    'backend/modules/notification/views/notification/_columns.php',
    'backend/modules/notification/views/notification/_form.php',
    'backend/modules/notification/views/notification/_search.php',
    'backend/modules/notification/views/notification/_user-columns.php',
    'backend/modules/notification/views/notification/index.php',
    'backend/modules/notification/views/notification/center.php',
    'backend/views/layouts/main.php',
    'backend/config/main.php',
    'backend/web/js/notification-poller.js',
    'common/services/NotificationService.php',
    'common/components/notificationComponent.php',
    'api/helpers/NotificationsHandler.php',
    'composer.json',
]

MIGRATION_SQL = r"""
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_notification' AND COLUMN_NAME = 'read_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE os_notification ADD COLUMN read_at INT NULL AFTER is_unread', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_notification' AND COLUMN_NAME = 'entity_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE os_notification ADD COLUMN entity_type VARCHAR(50) NULL AFTER type_of_notification', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_notification' AND COLUMN_NAME = 'entity_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE os_notification ADD COLUMN entity_id INT NULL AFTER entity_type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_notification' AND COLUMN_NAME = 'priority');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE os_notification ADD COLUMN priority TINYINT DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_notification' AND COLUMN_NAME = 'group_key');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE os_notification ADD COLUMN group_key VARCHAR(100) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_notification' AND COLUMN_NAME = 'channel');
SET @sql = IF(@col_exists = 0, "ALTER TABLE os_notification ADD COLUMN channel VARCHAR(20) DEFAULT 'in_app'", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE os_notification MODIFY is_unread TINYINT(1) DEFAULT 1;
ALTER TABLE os_notification MODIFY is_hidden TINYINT(1) DEFAULT 0;
"""

INDEX_SQL = [
    "CREATE INDEX idx_notif_recipient_unread ON os_notification (recipient_id, is_unread);",
    "CREATE INDEX idx_notif_recipient_created ON os_notification (recipient_id, created_time);",
    "CREATE INDEX idx_notif_entity ON os_notification (entity_type, entity_id);",
    "CREATE INDEX idx_notif_group ON os_notification (group_key);",
]


def run(ssh, cmd, timeout=60):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err


def main():
    print("=" * 60)
    print("  NOTIFICATION SYSTEM DEPLOYMENT")
    print("  Code files + Database migration")
    print("=" * 60)

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SERVER_HOST, username=SSH_USER, password=SSH_PASS, timeout=15)
    sftp = ssh.open_sftp()

    # ── Step 1: Deploy code files ──
    print(f"\n{'─'*60}")
    print("  STEP 1: Deploying code files")
    print(f"{'─'*60}")

    for site in SITES:
        base_remote = f'/var/www/{site}.aqssat.co'
        print(f"\n  [{site}]")

        for f in FILES_TO_DEPLOY:
            local_path = os.path.join(LOCAL_BASE, f.replace('/', '\\'))
            remote_path = f'{base_remote}/{f}'

            if not os.path.exists(local_path):
                print(f"    SKIP {f} (local file not found)")
                continue

            remote_dir = os.path.dirname(remote_path).replace('\\', '/')
            run(ssh, f"mkdir -p {remote_dir}")

            try:
                sftp.put(local_path, remote_path)
                out, _ = run(ssh, f"php -l {remote_path} 2>&1")
                if 'No syntax errors' in out or remote_path.endswith('.js') or remote_path.endswith('.json'):
                    print(f"    OK  {f}")
                else:
                    print(f"    WARN {f}: {out.strip()}")
            except Exception as e:
                print(f"    ERR {f}: {e}")

    # ── Step 2: Run database migration ──
    print(f"\n{'─'*60}")
    print("  STEP 2: Running database migration")
    print(f"{'─'*60}")

    for site in SITES:
        db = DBS[site]
        print(f"\n  [{site}] Database: {db}")

        table_check, _ = run(ssh, f"mysql -u root {db} -e \"SHOW TABLES LIKE 'os_notification';\" 2>&1")
        if 'os_notification' not in table_check:
            print(f"    SKIP - os_notification table does not exist")
            continue

        run(ssh, f"cat > /tmp/notif_migration.sql << 'SQLDONE'\n{MIGRATION_SQL.strip()}\nSQLDONE")
        out, err = run(ssh, f"mysql -u root {db} < /tmp/notif_migration.sql 2>&1")
        if out.strip():
            print(f"    Output: {out.strip()[:200]}")

        for idx_sql in INDEX_SQL:
            out2, _ = run(ssh, f'mysql -u root {db} -e "{idx_sql}" 2>&1')
            idx_name = idx_sql.split(' ')[2]
            if 'Duplicate' in out2 or 'already exists' in out2.lower():
                print(f"    INDEX {idx_name}: already exists (OK)")
            elif out2.strip() == '':
                print(f"    INDEX {idx_name}: created")
            else:
                print(f"    INDEX {idx_name}: {out2.strip()[:100]}")

        desc_out, _ = run(ssh, f"mysql -u root {db} -e \"DESCRIBE os_notification;\" 2>&1")
        new_cols = ['read_at', 'entity_type', 'entity_id', 'priority', 'group_key', 'channel']
        found = [c for c in new_cols if c in desc_out]
        missing = [c for c in new_cols if c not in desc_out]

        if not missing:
            print(f"    [SUCCESS] All 6 new columns exist")
        else:
            print(f"    Found: {', '.join(found)}")
            print(f"    MISSING: {', '.join(missing)}")

    # ── Step 3: Restart Apache & clear cache ──
    print(f"\n{'─'*60}")
    print("  STEP 3: Restart Apache & clear cache")
    print(f"{'─'*60}")

    run(ssh, "service apache2 graceful 2>&1")
    print("  Apache restarted")

    for site in SITES:
        out, _ = run(ssh, f"cd /var/www/{site}.aqssat.co && php yii cache/flush-all 2>&1")
        print(f"  [{site}] Cache: {out.strip()[:80]}")

    # ── Step 4: Verify ──
    print(f"\n{'─'*60}")
    print("  STEP 4: Verification")
    print(f"{'─'*60}")

    for site in SITES:
        out, _ = run(ssh, f"curl -s -o /dev/null -w '%{{http_code}}' https://{site}.aqssat.co/notification/notification/index -k 2>&1")
        status = out.strip().replace("'", "")
        result = "OK (redirect to login)" if status == "302" else f"Status {status}"
        print(f"  [{site}] /notification/index -> HTTP {status} - {result}")

        out2, _ = run(ssh, f"curl -s -o /dev/null -w '%{{http_code}}' https://{site}.aqssat.co/notification/notification/center -k 2>&1")
        status2 = out2.strip().replace("'", "")
        result2 = "OK (redirect to login)" if status2 == "302" else f"Status {status2}"
        print(f"  [{site}] /notification/center -> HTTP {status2} - {result2}")

    sftp.close()
    ssh.close()
    print(f"\n{'='*60}")
    print("  ALL DONE!")
    print(f"{'='*60}")


if __name__ == '__main__':
    main()
