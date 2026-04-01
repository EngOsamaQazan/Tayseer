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

# Check RouteAccessBehavior around the inventoryInvoices route handling
run("RouteAccessBehavior full route check",
    "sed -n '120,160p' /var/www/jadal.aqssat.co/backend/components/RouteAccessBehavior.php")

run("RouteAccessBehavior - checkRouteAccess method",
    "sed -n '80,155p' /var/www/jadal.aqssat.co/backend/components/RouteAccessBehavior.php")

# Check Permissions getActionPermissionMap for inventoryInvoices
run("Permission map for inventoryInvoices",
    "grep -B2 -A10 'inventoryInvoices' /var/www/jadal.aqssat.co/common/helper/Permissions.php")

ssh.close()
