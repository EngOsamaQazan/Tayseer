import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=60):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

DATABASES = {
    'jadal': 'namaa_jadal',
    'namaa': 'namaa_erp',
    'watar': 'tayseer_watar',
}

SQL_CREATE_TABLES = """
CREATE TABLE IF NOT EXISTS os_user_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NULL,
    icon VARCHAR(50) DEFAULT 'fa-tag',
    color VARCHAR(20) DEFAULT '#64748B',
    sort_order INT DEFAULT 0,
    company_id INT NULL COMMENT 'Multi-tenant: NULL = global',
    is_system TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_slug_company (slug, company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS os_user_category_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    assigned_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_category (user_id, category_id),
    KEY idx_category (category_id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
"""

SQL_SEED_CATEGORIES = """
INSERT IGNORE INTO os_user_categories (slug, name_ar, name_en, icon, color, sort_order, is_system, is_active)
VALUES
    ('manager',        'مدير',           'Manager',        'fa-star',          '#D97706', 0, 1, 1),
    ('employee',       'موظف',           'Employee',       'fa-id-badge',      '#3B82F6', 1, 1, 1),
    ('sales_employee', 'موظف مبيعات',    'Sales Employee', 'fa-shopping-cart', '#0ea5e9', 2, 1, 1),
    ('vendor',         'مورد بضائع',     'Vendor',         'fa-truck',         '#F59E0B', 3, 1, 1),
    ('investor',       'مستثمر (شريك)',  'Investor',       'fa-briefcase',     '#8B5CF6', 4, 1, 1),
    ('court_agent',    'مندوب محكمة',    'Court Agent',    'fa-gavel',         '#800020', 5, 1, 1),
    ('branch_manager', 'مدير فرع',       'Branch Manager', 'fa-building',      '#059669', 6, 1, 1);
"""

SQL_MAP_MANAGERS = """
INSERT IGNORE INTO os_user_category_map (user_id, category_id, assigned_by)
SELECT aa.user_id, uc.id, 1
FROM os_auth_assignment aa
CROSS JOIN os_user_categories uc
WHERE uc.slug = 'manager'
  AND uc.is_active = 1
  AND (aa.item_name = 'مدير' OR aa.item_name = 'Manager' OR aa.item_name = 'مدير النظام')
  AND aa.user_id NOT IN (
      SELECT ucm.user_id FROM os_user_category_map ucm WHERE ucm.category_id = uc.id
  );
"""

SQL_MAP_EMPLOYEES = """
INSERT IGNORE INTO os_user_category_map (user_id, category_id, assigned_by)
SELECT DISTINCT u.id, uc.id, 1
FROM os_user u
CROSS JOIN os_user_categories uc
WHERE uc.slug = 'employee'
  AND uc.is_active = 1
  AND u.id NOT IN (
      SELECT ucm.user_id FROM os_user_category_map ucm WHERE ucm.category_id = uc.id
  );
"""

# ================================================================
# STEP 1: Create tables and seed on all 3 databases
# ================================================================
print("=" * 60)
print("STEP 1: Creating tables and seeding categories")
print("=" * 60)

for site, db in DATABASES.items():
    print(f"\n--- {site} ({db}) ---")
    
    # Check if table already exists
    out, _ = run(f"mysql -u root {db} -e \"SHOW TABLES LIKE 'os_user_categories';\" 2>&1")
    if 'os_user_categories' in out:
        print(f"  [INFO] os_user_categories already exists in {db}")
    
    # Create tables
    out, _ = run(f'mysql -u root {db} -e "{SQL_CREATE_TABLES}" 2>&1')
    if 'ERROR' in out:
        print(f"  [ERROR] Creating tables: {out}")
    else:
        print(f"  [OK] Tables created/verified")
    
    # Seed categories
    out, _ = run(f'mysql -u root {db} -e "{SQL_SEED_CATEGORIES}" 2>&1')
    if 'ERROR' in out:
        print(f"  [ERROR] Seeding categories: {out}")
    else:
        print(f"  [OK] Categories seeded")
    
    # Map managers
    out, _ = run(f'mysql -u root {db} -e "{SQL_MAP_MANAGERS}" 2>&1')
    if 'ERROR' in out:
        print(f"  [ERROR] Mapping managers: {out}")
    else:
        print(f"  [OK] Managers mapped")

    # Map employees  
    out, _ = run(f'mysql -u root {db} -e "{SQL_MAP_EMPLOYEES}" 2>&1')
    if 'ERROR' in out:
        print(f"  [ERROR] Mapping employees: {out}")
    else:
        print(f"  [OK] Employees mapped")
    
    # Verify
    out, _ = run(f'mysql -u root {db} -e "SELECT slug, name_ar, is_active FROM os_user_categories ORDER BY sort_order;" 2>&1')
    print(f"\n  Categories:")
    print(f"  {out}")
    
    out, _ = run(f'mysql -u root {db} -e "SELECT uc.slug, COUNT(ucm.user_id) as user_count FROM os_user_categories uc LEFT JOIN os_user_category_map ucm ON uc.id = ucm.category_id GROUP BY uc.slug ORDER BY uc.sort_order;" 2>&1')
    print(f"  Category user counts:")
    print(f"  {out}")

# ================================================================
# STEP 2: Install kartik-v/yii2-bootstrap5-dropdown on all sites
# ================================================================
print("\n" + "=" * 60)
print("STEP 2: Installing missing kartik package on all sites")
print("=" * 60)

for site in DATABASES.keys():
    path = f"/var/www/{site}.aqssat.co"
    # Check if already installed
    out, _ = run(f"ls {path}/vendor/kartik-v/yii2-bootstrap5-dropdown/composer.json 2>/dev/null")
    if 'composer.json' in out:
        print(f"\n  [{site}] kartik-v/yii2-bootstrap5-dropdown already installed")
    else:
        print(f"\n  [{site}] Installing kartik-v/yii2-bootstrap5-dropdown...")
        out, _ = run(f"cd {path} && composer require kartik-v/yii2-bootstrap5-dropdown --no-interaction 2>&1", timeout=120)
        if 'error' in out.lower() and 'warning' not in out.lower():
            print(f"  [ERROR] {out[-500:]}")
        else:
            print(f"  [OK] Installed successfully")

# ================================================================
# STEP 3: Restart Apache to clear OPcache
# ================================================================
print("\n" + "=" * 60)
print("STEP 3: Restarting Apache")
print("=" * 60)

out, _ = run("service apache2 graceful 2>&1")
print(f"  {out}")

print("\n" + "=" * 60)
print("ALL DONE!")
print("=" * 60)

ssh.close()
