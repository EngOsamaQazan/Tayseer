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

# 1. Check latest errors in app.log (the 500 from update + view)
run("Latest errors in jadal app.log",
    "tail -60 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -A5 'error\\|Error\\|Exception'")

# 2. Check the view action - it uses renderAjax
run("actionView in controller",
    "grep -n 'function actionView\\|renderAjax\\|render(' /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/controllers/InventoryInvoicesController.php | head -15")

# 3. Check actionUpdate 
run("actionUpdate in controller",
    "grep -n 'function actionUpdate' /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/controllers/InventoryInvoicesController.php")

# 4. Get lines around actionUpdate
run("actionUpdate code",
    "sed -n '/function actionUpdate/,/^    }/p' /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/controllers/InventoryInvoicesController.php | head -40")

# 5. Check view.php - is Permissions imported?
run("view.php imports/use",
    "head -15 /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/view.php")

# 6. Check if Permissions class has INVINV_APPROVE
run("INVINV_APPROVE in Permissions",
    "grep -n 'INVINV_APPROVE\\|INVENTORY_INVOICES' /var/www/jadal.aqssat.co/common/helper/Permissions.php")

# 7. Check user 1 auth assignments  
run("User 1 permissions",
    "mysql -u root namaa_jadal -e \"SELECT item_name FROM os_auth_assignment WHERE user_id = 1;\"")

# 8. Check the full error from app.log for the update action
run("Full latest error",
    "tail -100 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -B2 -A15 'actionUpdate\\|update\\|Internal Server'")

ssh.close()
