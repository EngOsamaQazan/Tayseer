import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=600):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('OK')
    print()
    return out

# ============================================================
# STEP 1: CREATE FULL BACKUP
# ============================================================
run("Create backup directory",
    "mkdir -p /root/backup_20260327")

run("Backup Apache configs",
    "tar czf /root/backup_20260327/apache_configs.tar.gz /etc/apache2/sites-available/ /etc/apache2/sites-enabled/ 2>&1")

run("Backup projects to delete",
    "tar czf /root/backup_20260327/deleted_projects.tar.gz "
    "/var/www/jadal2.aqssat.co "
    "/var/www/namaa2.aqssat.co "
    "/var/www/old-jadal "
    "/var/www/old-namaa "
    "/var/www/micro_services "
    "/var/www/document_errors "
    "/var/www/a.py "
    "/var/www/html/jadal.aqssat.co "
    "/var/www/html/namaa.aqssat.co "
    "/var/www/html/jadal2.aqssat.co "
    "/var/www/html/namaa2.aqssat.co "
    "/var/www/html/tes.php "
    "2>&1")

run("Verify backup files",
    "ls -lh /root/backup_20260327/")

ssh.close()
print("=== BACKUP COMPLETE ===")
