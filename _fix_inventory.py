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
        print('[NONE]')
    print()

# 1. View the problematic line in view.php
run("view.php lines 195-220",
    "sed -n '195,220p' /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/view.php")

# 2. Check InventoryItems model - what columns exist
run("InventoryItems model attributes",
    "grep -n 'function\\|tableName\\|public \\$\\|attributeLabels\\|rules' /var/www/jadal.aqssat.co/backend/modules/inventoryItems/models/InventoryItems.php | head -20")

# 3. Check the database table columns
run("DB table columns",
    "mysql -u root -e \"SHOW COLUMNS FROM jadal_db.inventory_items;\" 2>/dev/null || mysql -u root -e \"SHOW COLUMNS FROM jadal.inventory_items;\" 2>/dev/null")

# 4. Check what the actual column name is
run("InventoryItems model full",
    "head -80 /var/www/jadal.aqssat.co/backend/modules/inventoryItems/models/InventoryItems.php")

# 5. Also check the relation in InvoiceItems model
run("grep 'name' in InventoryItems model",
    "grep -n 'name\\|item_name\\|title\\|label' /var/www/jadal.aqssat.co/backend/modules/inventoryItems/models/InventoryItems.php")

ssh.close()
