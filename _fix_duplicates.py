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

for site, db in DATABASES.items():
    print(f"\n{'='*60}")
    print(f"  {site} ({db})")
    print(f"{'='*60}")
    
    # Check for duplicates - show all rows with company_id
    out, _ = run(f'mysql -u root {db} -e "SELECT id, slug, name_ar, company_id, is_system FROM os_user_categories ORDER BY slug, id;" 2>&1')
    print(f"\n  All category rows:")
    print(f"  {out}")
    
    # Check category_map to see which category_ids are actually used
    out, _ = run(f'mysql -u root {db} -e "SELECT ucm.category_id, uc.slug, uc.company_id, COUNT(*) as cnt FROM os_user_category_map ucm JOIN os_user_categories uc ON ucm.category_id = uc.id GROUP BY ucm.category_id, uc.slug, uc.company_id;" 2>&1')
    print(f"\n  Used categories in map:")
    print(f"  {out}")

ssh.close()
