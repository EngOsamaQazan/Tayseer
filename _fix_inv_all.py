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

for proj in ['namaa.aqssat.co', 'watar.aqssat.co']:
    vp = f'/var/www/{proj}/backend/modules/inventoryInvoices/views/inventory-invoices/view.php'
    run(f"Backup {proj}", f"cp {vp} {vp}.bak.20260328")
    run(f"Fix {proj}", f"sed -i 's/\\$item->name/\\$item->item_name/g' {vp}")
    run(f"Verify {proj}", f"sed -n '204,210p' {vp}")
    run(f"Syntax {proj}", f"php -l {vp} 2>&1")

ssh.close()
