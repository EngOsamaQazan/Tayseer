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
projects = ['jadal.aqssat.co', 'namaa.aqssat.co', 'watar.aqssat.co']

# ══════════════════════════════════════════════════════
# 1. Reverse premature stock for pending invoices
# ══════════════════════════════════════════════════════
run("1a. Find all pending invoices with stock movements",
    f"""mysql -u root {DB} -e "
SELECT i.id, i.status, i.posted_at
FROM os_inventory_invoices i
WHERE i.status IN ('pending_reception', 'pending_manager', 'draft')
AND i.posted_at IS NULL
AND i.is_deleted = 0
AND EXISTS (SELECT 1 FROM os_stock_movements sm WHERE sm.reference_type = 'invoice' AND sm.reference_id = i.id);
" """)

# Reverse stock quantities for invoice #9
run("1b. Reverse quantities for invoice #9",
    f"""mysql -u root {DB} -e "
UPDATE os_inventory_item_quantities iq
JOIN os_items_inventory_invoices ii ON ii.inventory_items_id = iq.item_id
SET iq.quantity = GREATEST(0, iq.quantity - ii.number)
WHERE ii.inventory_invoices_id = 9
AND ii.is_deleted = 0
AND iq.is_deleted = 0
AND iq.company_id = 1;
" """)

# Remove the premature stock movements
run("1c. Delete premature stock movements for invoice #9",
    f"""mysql -u root {DB} -e "
DELETE FROM os_stock_movements WHERE reference_type = 'invoice' AND reference_id = 9;
" """)

run("1d. Verify no stock movements for invoice #9",
    f"""mysql -u root {DB} -e "SELECT COUNT(*) as movements FROM os_stock_movements WHERE reference_type = 'invoice' AND reference_id = 9;" """)

# ══════════════════════════════════════════════════════
# 2. Add STATUS_REJECTED_RECEPTION to status column
# ══════════════════════════════════════════════════════
run("2. Check status column type",
    f"""mysql -u root {DB} -e "SHOW COLUMNS FROM os_inventory_invoices LIKE 'status';" """)

# ══════════════════════════════════════════════════════
# 3. Create tables on NAMAA and WATAR DBs too
# ══════════════════════════════════════════════════════
for db_name in ['namaa_erp', 'tayseer_watar']:
    run(f"3a. Create user_categories on {db_name}",
        f"""mysql -u root {db_name} -e "
CREATE TABLE IF NOT EXISTS os_user_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NULL,
    icon VARCHAR(50) DEFAULT 'fa-tag',
    color VARCHAR(20) DEFAULT '#64748B',
    sort_order INT DEFAULT 0,
    company_id INT NULL,
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
INSERT IGNORE INTO os_user_categories (slug, name_ar, name_en, icon, color, sort_order, is_system, is_active) VALUES
('manager',        'مدير',                'Manager',        'fa-star',          '#D97706', 0, 1, 1),
('employee',       'موظف',                'Employee',       'fa-id-badge',      '#3B82F6', 1, 1, 1),
('sales_employee', 'موظف مبيعات',         'Sales Employee', 'fa-shopping-cart', '#0ea5e9', 2, 1, 1),
('vendor',         'مورد بضائع',          'Vendor',         'fa-truck',         '#F59E0B', 3, 1, 1),
('investor',       'مستثمر (شريك)',       'Investor',       'fa-briefcase',     '#8B5CF6', 4, 1, 1),
('court_agent',    'مندوب محكمة',         'Court Agent',    'fa-gavel',         '#800020', 5, 1, 1),
('branch_manager', 'مدير فرع',            'Branch Manager', 'fa-building',      '#059669', 6, 1, 1);
" """)
    
    run(f"3b. Assign admin user categories on {db_name}",
        f"""mysql -u root {db_name} -e "
INSERT IGNORE INTO os_user_category_map (user_id, category_id, assigned_by)
SELECT 1, id, 1 FROM os_user_categories WHERE slug IN ('manager', 'sales_employee', 'employee');
" """)

ssh.close()
