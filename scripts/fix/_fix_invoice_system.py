import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[OK]')
    print()

DB = 'namaa_jadal'

# ══════════════════════════════════════════════════════
# 1. Create os_user_categories table + seed defaults
# ══════════════════════════════════════════════════════
run("1a. Create os_user_categories table",
    f"""mysql -u root {DB} -e "
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
" """)

run("1b. Create os_user_category_map table (if not exists)",
    f"""mysql -u root {DB} -e "
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
" """)

run("1c. Seed default categories",
    f"""mysql -u root {DB} -e "
INSERT IGNORE INTO os_user_categories (slug, name_ar, name_en, icon, color, sort_order, is_system, is_active) VALUES
('manager',        'مدير',                'Manager',        'fa-star',          '#D97706', 0, 1, 1),
('employee',       'موظف',                'Employee',       'fa-id-badge',      '#3B82F6', 1, 1, 1),
('sales_employee', 'موظف مبيعات',         'Sales Employee', 'fa-shopping-cart', '#0ea5e9', 2, 1, 1),
('vendor',         'مورد بضائع',          'Vendor',         'fa-truck',         '#F59E0B', 3, 1, 1),
('investor',       'مستثمر (شريك)',       'Investor',       'fa-briefcase',     '#8B5CF6', 4, 1, 1),
('court_agent',    'مندوب محكمة',         'Court Agent',    'fa-gavel',         '#800020', 5, 1, 1),
('branch_manager', 'مدير فرع',            'Branch Manager', 'fa-building',      '#059669', 6, 1, 1);
" """)

run("1d. Verify categories",
    f"""mysql -u root {DB} -e "SELECT id, slug, name_ar FROM os_user_categories;" """)

# ══════════════════════════════════════════════════════
# 2. Assign user id=1 as 'manager' + 'sales_employee'
# ══════════════════════════════════════════════════════
run("2a. Assign manager category to user 1",
    f"""mysql -u root {DB} -e "
INSERT IGNORE INTO os_user_category_map (user_id, category_id, assigned_by)
SELECT 1, id, 1 FROM os_user_categories WHERE slug = 'manager';
" """)

run("2b. Assign sales_employee category to user 1 (so they can approve reception too)",
    f"""mysql -u root {DB} -e "
INSERT IGNORE INTO os_user_category_map (user_id, category_id, assigned_by)
SELECT 1, id, 1 FROM os_user_categories WHERE slug = 'sales_employee';
" """)

run("2c. Verify user categories",
    f"""mysql -u root {DB} -e "
SELECT u.id, u.username, uc.slug, uc.name_ar
FROM os_user u
JOIN os_user_category_map ucm ON ucm.user_id = u.id
JOIN os_user_categories uc ON uc.id = ucm.category_id;
" """)

# ══════════════════════════════════════════════════════
# 3. Check current invoice #9 stock movements (to understand duplication)
# ══════════════════════════════════════════════════════
run("3a. Invoice #9 details",
    f"""mysql -u root {DB} -e "SELECT id, status, posted_at, total_amount, branch_id, created_by FROM os_inventory_invoices WHERE id = 9;" """)

run("3b. Stock movements for invoice #9",
    f"""mysql -u root {DB} -e "SELECT id, item_id, type, quantity, reference_type, reference_id FROM os_stock_movements WHERE reference_type = 'invoice' AND reference_id = 9;" """)

run("3c. Invoice #9 line items",
    f"""mysql -u root {DB} -e "SELECT id, inventory_items_id, number, single_price FROM os_items_inventory_invoices WHERE inventory_invoices_id = 9 AND is_deleted = 0;" """)

ssh.close()
