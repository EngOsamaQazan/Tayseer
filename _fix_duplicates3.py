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

def mysql(db, sql, timeout=60):
    # Write SQL to a temp file to avoid shell escaping issues
    run(f"cat > /tmp/fix_cats.sql << 'SQLEOF'\n{sql}\nSQLEOF")
    out, _ = run(f"mysql -u root {db} < /tmp/fix_cats.sql 2>&1", timeout=timeout)
    return out

DATABASES = {
    'jadal': 'namaa_jadal',
    'namaa': 'namaa_erp',
    'watar': 'tayseer_watar',
}

CLEANUP_SQL = """
SET @sql_safe_updates = @@sql_safe_updates;
SET sql_safe_updates = 0;

-- Create a temp table with the keeper IDs (lowest id per slug)
DROP TEMPORARY TABLE IF EXISTS _cat_keepers;
CREATE TEMPORARY TABLE _cat_keepers AS
SELECT slug, MIN(id) AS keeper_id
FROM os_user_categories
WHERE company_id IS NULL
GROUP BY slug;

-- Update category_map: point all references to the keeper_id
UPDATE os_user_category_map ucm
JOIN os_user_categories uc ON ucm.category_id = uc.id
JOIN _cat_keepers k ON uc.slug = k.slug
SET ucm.category_id = k.keeper_id
WHERE ucm.category_id != k.keeper_id
  AND uc.company_id IS NULL;

-- Delete duplicate map rows (keep lowest id per user+category pair)
DELETE t1 FROM os_user_category_map t1
INNER JOIN os_user_category_map t2
  ON t1.user_id = t2.user_id
  AND t1.category_id = t2.category_id
  AND t1.id > t2.id;

-- Drop the unique key that allows NULL duplicates
ALTER TABLE os_user_categories DROP INDEX uk_slug_company;

-- Delete duplicate categories (keep lowest id per slug where company_id IS NULL)
DELETE uc FROM os_user_categories uc
JOIN _cat_keepers k ON uc.slug = k.slug
WHERE uc.company_id IS NULL
  AND uc.id != k.keeper_id;

-- Re-create unique key
ALTER TABLE os_user_categories ADD UNIQUE KEY uk_slug_company (slug, company_id);

DROP TEMPORARY TABLE _cat_keepers;

SET sql_safe_updates = @sql_safe_updates;

-- Show final state
SELECT id, slug, name_ar, company_id, is_active FROM os_user_categories ORDER BY sort_order, id;
SELECT uc.slug, COUNT(DISTINCT ucm.user_id) AS users
FROM os_user_categories uc
LEFT JOIN os_user_category_map ucm ON uc.id = ucm.category_id
GROUP BY uc.id, uc.slug
ORDER BY uc.sort_order;
"""

for site, db in DATABASES.items():
    print(f"\n{'='*60}")
    print(f"  Cleaning {site} ({db})")
    print(f"{'='*60}")
    
    out = mysql(db, CLEANUP_SQL)
    print(out)

ssh.close()
print("\nDone!")
