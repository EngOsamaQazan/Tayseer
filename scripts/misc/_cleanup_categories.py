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

# Remove duplicate categories (keep the lowest ID for each slug)
run("Remove duplicate categories",
    f"""mysql -u root {DB} -e "
DELETE c2 FROM os_user_categories c2
INNER JOIN os_user_categories c1
ON c1.slug = c2.slug AND c1.id < c2.id
WHERE (c1.company_id IS NULL AND c2.company_id IS NULL)
   OR (c1.company_id = c2.company_id);
" """)

run("Verify cleaned categories",
    f"""mysql -u root {DB} -e "SELECT id, slug, name_ar FROM os_user_categories ORDER BY sort_order;" """)

# Clean up duplicate user_category_map entries
run("Clean duplicate user_category_map",
    f"""mysql -u root {DB} -e "
DELETE m2 FROM os_user_category_map m2
INNER JOIN os_user_category_map m1
ON m1.user_id = m2.user_id AND m1.id < m2.id
WHERE m1.category_id IN (SELECT id FROM os_user_categories)
AND m2.category_id NOT IN (SELECT id FROM os_user_categories);
" """)

# Remove orphan mappings (category_id that doesn't exist anymore)
run("Remove orphan mappings",
    f"""mysql -u root {DB} -e "
DELETE FROM os_user_category_map
WHERE category_id NOT IN (SELECT id FROM os_user_categories);
" """)

# Ensure user 1 has manager + sales_employee from correct IDs
run("Re-assign user 1 categories",
    f"""mysql -u root {DB} -e "
DELETE FROM os_user_category_map WHERE user_id = 1;
INSERT INTO os_user_category_map (user_id, category_id, assigned_by)
SELECT 1, id, 1 FROM os_user_categories WHERE slug IN ('manager', 'sales_employee', 'employee');
" """)

run("Final verify user categories",
    f"""mysql -u root {DB} -e "
SELECT u.id, u.username, uc.id as cat_id, uc.slug, uc.name_ar
FROM os_user u
JOIN os_user_category_map ucm ON ucm.user_id = u.id
JOIN os_user_categories uc ON uc.id = ucm.category_id
ORDER BY u.id, uc.sort_order;
" """)

# Check stock movements table structure
run("Stock movements columns",
    f"""mysql -u root {DB} -e "SHOW COLUMNS FROM os_stock_movements;" """)

# Check if stock was already added for invoice #9
run("Stock movements for invoice #9",
    f"""mysql -u root {DB} -e "SELECT * FROM os_stock_movements WHERE reference_type = 'invoice' AND reference_id = 9;" """)

# Check current quantities for items in invoice #9
run("Item quantities for invoice #9 items",
    f"""mysql -u root {DB} -e "
SELECT iq.item_id, ii.item_name, iq.quantity, iq.company_id
FROM os_inventory_item_quantities iq
JOIN os_inventory_items ii ON ii.id = iq.item_id
WHERE iq.item_id IN (101, 99, 106, 107, 104, 109)
AND iq.is_deleted = 0;
" """)

ssh.close()
