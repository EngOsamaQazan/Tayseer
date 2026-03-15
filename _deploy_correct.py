# -*- coding: utf-8 -*-
"""Deploy to the correct namaa & jadal sites."""
import sys, os, subprocess

try:
    import paramiko
except ImportError:
    print("pip install paramiko"); sys.exit(1)

HOST = "31.220.82.115"
USER = "root"
PORT = 22
LOCAL_BASE = r"C:\Users\PC\Desktop\Tayseer"

SOURCE_FILES = [
    "backend/config/main.php",
    "backend/components/RouteAccessBehavior.php",
    "backend/controllers/PermissionsManagementController.php",
    "backend/dto/EntityDTO.php",
    "backend/models/Holiday.php",
    "backend/models/JudiciaryAuthority.php",
    "backend/models/JudiciaryDeadline.php",
    "backend/models/JudiciaryDefendantStage.php",
    "backend/models/JudiciaryRequestTemplate.php",
    "backend/models/JudiciarySeizedAsset.php",
    "backend/modules/diwan/controllers/DiwanController.php",
    "backend/modules/diwan/models/DiwanCorrespondence.php",
    "backend/modules/diwan/models/DiwanCorrespondenceQuery.php",
    "backend/modules/diwan/models/DiwanCorrespondenceSearch.php",
    "backend/modules/diwan/views/diwan/correspondence_index.php",
    "backend/modules/diwan/views/diwan/correspondence_view.php",
    "backend/modules/judiciary/controllers/JudiciaryController.php",
    "backend/modules/judiciary/models/Judiciary.php",
    "backend/modules/judiciary/views/judiciary/generate_request.php",
    "backend/modules/judiciaryActions/models/JudiciaryActions.php",
    "backend/modules/judiciaryAuthorities/JudiciaryAuthorities.php",
    "backend/modules/judiciaryAuthorities/controllers/JudiciaryAuthoritiesController.php",
    "backend/modules/judiciaryAuthorities/models/JudiciaryAuthoritySearch.php",
    "backend/modules/judiciaryAuthorities/views/judiciary-authorities/_columns.php",
    "backend/modules/judiciaryAuthorities/views/judiciary-authorities/_form.php",
    "backend/modules/judiciaryAuthorities/views/judiciary-authorities/create.php",
    "backend/modules/judiciaryAuthorities/views/judiciary-authorities/index.php",
    "backend/modules/judiciaryAuthorities/views/judiciary-authorities/update.php",
    "backend/modules/judiciaryAuthorities/views/judiciary-authorities/view.php",
    "backend/modules/judiciaryCustomersActions/models/JudiciaryCustomersActions.php",
    "backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php",
    "backend/modules/judiciaryRequestTemplates/JudiciaryRequestTemplates.php",
    "backend/modules/judiciaryRequestTemplates/controllers/JudiciaryRequestTemplatesController.php",
    "backend/modules/judiciaryRequestTemplates/models/JudiciaryRequestTemplateSearch.php",
    "backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/_columns.php",
    "backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/_form.php",
    "backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/create.php",
    "backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/index.php",
    "backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/update.php",
    "backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/view.php",
    "backend/modules/lawyers/controllers/LawyersController.php",
    "backend/modules/lawyers/models/Lawyers.php",
    "backend/modules/lawyers/models/LawyersSearch.php",
    "backend/modules/lawyers/views/lawyers/_columns.php",
    "backend/modules/lawyers/views/lawyers/_form.php",
    "backend/modules/lawyers/views/lawyers/_search.php",
    "backend/modules/lawyers/views/lawyers/create.php",
    "backend/modules/lawyers/views/lawyers/index.php",
    "backend/modules/lawyers/views/lawyers/update.php",
    "backend/modules/lawyers/views/lawyers/view.php",
    "backend/modules/officialHolidays/OfficialHolidays.php",
    "backend/modules/officialHolidays/controllers/OfficialHolidaysController.php",
    "backend/modules/officialHolidays/models/HolidaySearch.php",
    "backend/modules/officialHolidays/views/official-holidays/_columns.php",
    "backend/modules/officialHolidays/views/official-holidays/_form.php",
    "backend/modules/officialHolidays/views/official-holidays/create.php",
    "backend/modules/officialHolidays/views/official-holidays/index.php",
    "backend/modules/officialHolidays/views/official-holidays/update.php",
    "backend/modules/officialHolidays/views/official-holidays/view.php",
    "backend/modules/reports/views/index.php",
    "backend/modules/reports/views/reports/index.php",
    "backend/services/DiwanCorrespondenceService.php",
    "backend/services/EntityResolverService.php",
    "backend/services/HolidayService.php",
    "backend/services/JudiciaryDeadlineService.php",
    "backend/services/JudiciaryRequestGenerator.php",
    "backend/services/JudiciaryWorkflowService.php",
    "backend/views/layouts/_diwan-tabs.php",
    "backend/views/layouts/navigation.php",
    "backend/views/site/system-settings.php",
    "backend/web/css/lawyers-module.css",
    "backend/web/js/lawyers-module.js",
    "backend/web/js/script.js",
    "common/helper/Permissions.php",
    "console/migrations/m260312_100000_add_representative_type_and_signature_to_lawyers.php",
    "console/migrations/m260312_100001_create_judiciary_authorities_with_seed.php",
    "console/migrations/m260312_100002_create_diwan_correspondence.php",
    "console/migrations/m260312_100003_create_judiciary_seized_assets.php",
    "console/migrations/m260312_100004_create_judiciary_deadlines.php",
    "console/migrations/m260312_100005_create_holidays.php",
    "console/migrations/m260312_100006_create_judiciary_request_templates.php",
    "console/migrations/m260312_100007_add_dual_stage_to_judiciary.php",
    "console/migrations/m260312_100008_create_defendant_stage.php",
    "console/migrations/m260312_100009_update_request_status_values.php",
]

def get_password():
    return "HAmAS12852"

def ssh_exec(client, cmd, timeout=30):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    code = stdout.channel.recv_exit_status()
    return out, err, code

def mkdir_p(sftp, remote_dir):
    dirs_to_create = []
    d = remote_dir
    while d and d != '/':
        try:
            sftp.stat(d)
            break
        except FileNotFoundError:
            dirs_to_create.append(d)
            d = os.path.dirname(d)
    for d in reversed(dirs_to_create):
        try:
            sftp.mkdir(d)
        except Exception:
            pass

def upload_to_site(client, sftp, site_path, site_name):
    print(f"\n{'='*60}")
    print(f"  Deploying to: {site_name} ({site_path})")
    print(f"{'='*60}")

    # Check site structure
    out, _, code = ssh_exec(client, f"test -d {site_path}/backend && echo YES || echo NO")
    if "NO" in out:
        print(f"  [ERROR] {site_path}/backend does not exist!")
        return False

    ok, fail, skip = 0, 0, 0
    for rel_path in SOURCE_FILES:
        local_path = os.path.join(LOCAL_BASE, rel_path.replace('/', os.sep))
        remote_path = f"{site_path}/{rel_path}"

        if not os.path.exists(local_path):
            skip += 1
            continue

        remote_dir = os.path.dirname(remote_path)
        try:
            mkdir_p(sftp, remote_dir)
            sftp.put(local_path, remote_path)
            sftp.chmod(remote_path, 0o644)
            ok += 1
        except Exception as e:
            fail += 1
            print(f"  [FAIL] {rel_path}: {e}")

    print(f"  Upload: {ok} OK, {fail} FAIL, {skip} SKIP")

    # Fix permissions
    ssh_exec(client, f"chown -R www-data:www-data {site_path}/backend/ {site_path}/common/ {site_path}/console/ 2>/dev/null")

    # Clear cache
    ssh_exec(client, f"rm -rf {site_path}/backend/runtime/cache/* 2>/dev/null")
    print(f"  Cache cleared.")
    return True

def run_db_migrations(client, db_name, is_jadal=False):
    print(f"\n{'='*60}")
    print(f"  DB Migrations: {db_name}")
    print(f"{'='*60}")

    # Check if tables already exist from earlier migration
    out, _, code = ssh_exec(client, f"mysql -u root -e \"SHOW TABLES LIKE 'os_judiciary_authorities'\" {db_name} 2>&1")
    if "os_judiciary_authorities" in out:
        print(f"  Tables already exist in {db_name} (migrated earlier)")
        return

    all_sqls = [
        "ALTER TABLE os_lawyers ADD COLUMN representative_type VARCHAR(20) DEFAULT 'delegate' AFTER notes",
        "ALTER TABLE os_lawyers ADD COLUMN signature_image VARCHAR(500) NULL DEFAULT NULL AFTER representative_type",
        """CREATE TABLE IF NOT EXISTS os_judiciary_authorities (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            authority_type VARCHAR(50) NOT NULL,
            notes TEXT NULL,
            is_deleted TINYINT(1) DEFAULT 0,
            created_at INT(11) NULL,
            updated_at INT(11) NULL,
            created_by INT(11) NULL,
            company_id INT(11) NULL,
            INDEX idx_auth_type (authority_type),
            INDEX idx_auth_company (company_id, is_deleted)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8""",
        """INSERT IGNORE INTO os_judiciary_authorities (name, authority_type, is_deleted, created_at, updated_at) VALUES
            ('دائرة الأراضي والمساحة', 'land', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
            ('إدارة ترخيص السواقين والمركبات', 'licensing', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
            ('دائرة مراقبة الشركات', 'companies_registry', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
            ('وزارة الصناعة والتجارة', 'industry_trade', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
            ('الأمن العام', 'security', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
            ('المحكمة الشرعية', 'court', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
            ('الضمان الاجتماعي', 'social_security', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())""",
        """CREATE TABLE IF NOT EXISTS os_diwan_correspondence (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            communication_type VARCHAR(20) NOT NULL,
            related_module VARCHAR(50) NOT NULL DEFAULT 'judiciary',
            related_record_id INT(11) NULL,
            customer_id INT(11) NULL,
            direction VARCHAR(10) NOT NULL,
            recipient_type VARCHAR(20) NULL,
            authority_id INT(11) NULL,
            bank_id INT(11) NULL,
            job_id INT(11) NULL,
            notification_method VARCHAR(30) NULL,
            delivery_date DATE NULL,
            notification_result VARCHAR(20) NULL,
            reference_number VARCHAR(100) NULL,
            purpose VARCHAR(100) NULL,
            parent_id INT(11) NULL,
            response_result VARCHAR(50) NULL,
            response_amount DECIMAL(12,2) NULL,
            correspondence_date DATE NOT NULL,
            content_summary TEXT NULL,
            image VARCHAR(500) NULL,
            follow_up_date DATE NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            notes TEXT NULL,
            company_id INT(11) NULL,
            is_deleted TINYINT(1) DEFAULT 0,
            created_at INT(11) NULL,
            updated_at INT(11) NULL,
            created_by INT(11) NULL,
            updated_by INT(11) NULL,
            INDEX idx_module_record (related_module, related_record_id),
            INDEX idx_type_status (communication_type, status),
            INDEX idx_customer (customer_id),
            INDEX idx_follow_up (follow_up_date, status),
            INDEX idx_parent (parent_id),
            INDEX idx_company_del (company_id, is_deleted)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8""",
        """CREATE TABLE IF NOT EXISTS os_judiciary_seized_assets (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            judiciary_id INT(11) NOT NULL,
            customer_id INT(11) NOT NULL,
            asset_type VARCHAR(30) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'seizure_requested',
            authority_id INT(11) NULL,
            correspondence_id INT(11) NULL,
            description VARCHAR(500) NULL,
            amount DECIMAL(12,2) NULL,
            notes TEXT NULL,
            is_deleted TINYINT(1) DEFAULT 0,
            created_at INT(11) NULL,
            updated_at INT(11) NULL,
            created_by INT(11) NULL,
            INDEX idx_sa_judiciary (judiciary_id),
            INDEX idx_sa_customer (customer_id),
            INDEX idx_sa_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8""",
        """CREATE TABLE IF NOT EXISTS os_judiciary_deadlines (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            judiciary_id INT(11) NOT NULL,
            customer_id INT(11) NULL,
            deadline_type VARCHAR(30) NOT NULL,
            day_type VARCHAR(10) NOT NULL,
            label VARCHAR(255) NOT NULL,
            start_date DATE NOT NULL,
            deadline_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            related_communication_id INT(11) NULL,
            related_customer_action_id INT(11) NULL,
            notes TEXT NULL,
            is_deleted TINYINT(1) DEFAULT 0,
            created_at INT(11) NULL,
            updated_at INT(11) NULL,
            created_by INT(11) NULL,
            INDEX idx_dl_judiciary (judiciary_id),
            INDEX idx_dl_customer (customer_id),
            INDEX idx_dl_status (status),
            INDEX idx_dl_date (deadline_date),
            INDEX idx_dl_type_status (deadline_type, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8""",
        """CREATE TABLE IF NOT EXISTS os_official_holidays (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            holiday_date DATE NOT NULL,
            name VARCHAR(255) NOT NULL,
            year INT(11) NOT NULL,
            source VARCHAR(20) NOT NULL DEFAULT 'manual',
            created_at INT(11) NULL,
            UNIQUE KEY idx_oh_date (holiday_date),
            INDEX idx_oh_year (year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8""",
        """CREATE TABLE IF NOT EXISTS os_judiciary_request_templates (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            template_type VARCHAR(50) NOT NULL,
            template_content LONGTEXT NULL,
            is_combinable TINYINT(1) DEFAULT 1,
            sort_order INT(11) DEFAULT 0,
            is_deleted TINYINT(1) DEFAULT 0,
            created_at INT(11) NULL,
            updated_at INT(11) NULL,
            created_by INT(11) NULL,
            INDEX idx_rt_type (template_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8""",
        "ALTER TABLE os_judiciary ADD COLUMN furthest_stage VARCHAR(30) DEFAULT 'case_preparation' AFTER case_status",
        "ALTER TABLE os_judiciary ADD COLUMN bottleneck_stage VARCHAR(30) DEFAULT 'case_preparation' AFTER furthest_stage",
        """CREATE TABLE IF NOT EXISTS os_judiciary_defendant_stage (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            judiciary_id INT(11) NOT NULL,
            customer_id INT(11) NOT NULL,
            current_stage VARCHAR(30) NOT NULL DEFAULT 'case_preparation',
            stage_updated_at DATETIME NULL,
            notes TEXT NULL,
            UNIQUE KEY idx_ds_unique (judiciary_id, customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8""",
        "UPDATE os_judiciary_customers_actions SET request_status = 'submitted' WHERE request_status = 'pending'",
    ]

    if is_jadal:
        sqls = [all_sqls[0], all_sqls[1], all_sqls[2], all_sqls[3], all_sqls[7], all_sqls[8]]
    else:
        sqls = all_sqls

    for i, sql in enumerate(sqls):
        label = sql.strip()[:60].replace('\n', ' ')
        escaped = sql.replace("'", "'\\''").replace('\n', ' ')
        out, err, code = ssh_exec(client, f"""mysql -u root {db_name} -e '{escaped}' 2>&1""")
        if code == 0:
            print(f"  [OK] {i+1}. {label}...")
        elif "Duplicate column" in (out+err) or "already exists" in (out+err):
            print(f"  [SKIP] {i+1}. Already exists")
        else:
            print(f"  [WARN] {i+1}. {(out+err).strip()[:150]}")

def main():
    password = get_password()
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, port=PORT, username=USER, password=password, timeout=15)
    print("Connected!")

    sftp = client.open_sftp()

    # Check which DBs each site uses
    sites = {
        "namaa.aqssat.co": "/var/www/namaa.aqssat.co",
        "jadal.aqssat.co": "/var/www/jadal.aqssat.co",
        "namaa2.aqssat.co": "/var/www/namaa2.aqssat.co",
        "jadal2.aqssat.co": "/var/www/jadal2.aqssat.co",
    }

    print("=== Checking site structures ===")
    active_sites = {}
    for name, path in sites.items():
        out, _, code = ssh_exec(client, f"test -d {path}/backend && echo YES || echo NO")
        has_backend = "YES" in out
        db_name = ""
        if has_backend:
            out2, _, _ = ssh_exec(client, f"grep 'dbname' {path}/common/config/main-local.php 2>/dev/null")
            if "dbname" in out2:
                import re
                m = re.search(r'dbname=(\w+)', out2)
                db_name = m.group(1) if m else "unknown"
        status = f"OK (DB: {db_name})" if has_backend else "NO backend"
        print(f"  {name}: {status}")
        if has_backend:
            active_sites[name] = {"path": path, "db": db_name}

    # Upload files to each active site
    for name, info in active_sites.items():
        upload_to_site(client, sftp, info["path"], name)

    sftp.close()

    # Run DB migrations for unique databases
    migrated_dbs = set()
    for name, info in active_sites.items():
        db = info["db"]
        if db and db not in migrated_dbs:
            is_jadal = "jadal" in db
            run_db_migrations(client, db, is_jadal=is_jadal)
            migrated_dbs.add(db)

    # Clear OPcache
    ssh_exec(client, "systemctl restart apache2 2>/dev/null")
    print("\n  Apache restarted.")

    # Test each site
    print(f"\n{'='*60}")
    print("  Final verification")
    print(f"{'='*60}")
    for name in active_sites:
        out, _, _ = ssh_exec(client, f"curl -sk -o /dev/null -w '%{{http_code}}' https://{name}/ 2>&1")
        out2, _, _ = ssh_exec(client, f"curl -sk -o /dev/null -w '%{{http_code}}' https://{name}/site/login 2>&1")
        print(f"  {name}: Home={out.strip()}, Login={out2.strip()}")

    client.close()
    print("\nDone!")

if __name__ == "__main__":
    main()
