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

view_path = '/var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/view.php'

# Backup
run("Backup view.php", f"cp {view_path} {view_path}.bak.20260328")

# Fix: $item->name => $item->item_name
run("Fix name -> item_name",
    f"sed -i 's/\\$item->name/\\$item->item_name/g' {view_path}")

# Verify
run("Verify fix (line 206)",
    f"sed -n '204,210p' {view_path}")

# PHP syntax check
run("PHP syntax check",
    f"php -l {view_path} 2>&1")

# Also check if same error exists in NAMAA and WATAR
for proj in ['namaa.aqssat.co', 'watar.aqssat.co']:
    vp = f'/var/www/{proj}/backend/modules/inventoryInvoices/views/inventory-invoices/view.php'
    run(f"Check {proj} for same bug",
        f"grep -n '\\$item->name' {vp} 2>/dev/null")

ssh.close()
