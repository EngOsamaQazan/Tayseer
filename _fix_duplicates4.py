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

-- Keeper = lowest id per slug
DROP TEMPORARY TABLE IF EXISTS _keepers;
CREATE TEMPORARY TABLE _keepers AS
SELECT slug, MIN(id) AS kid FROM os_user_categories WHERE company_id IS NULL GROUP BY slug;

-- Duplicate category IDs (not the keeper)
DROP TEMPORARY TABLE IF EXISTS _dupes;
CREATE TEMPORARY TABLE _dupes AS
SELECT uc.id AS dup_id, k.kid AS keeper_id
FROM os_user_categories uc
JOIN _keepers k ON uc.slug = k.slug
WHERE uc.company_id IS NULL AND uc.id != k.kid;

-- 1) Delete map rows pointing to duplicate categories WHERE user already has a keeper mapping
DELETE ucm FROM os_user_category_map ucm
JOIN _dupes d ON ucm.category_id = d.dup_id
WHERE EXISTS (
    SELECT 1 FROM os_user_category_map ucm2
    WHERE ucm2.user_id = ucm.user_id AND ucm2.category_id = d.keeper_id
);

-- Re-create temp since MySQL may have invalidated it
DROP TEMPORARY TABLE IF EXISTS _dupes2;
CREATE TEMPORARY TABLE _dupes2 AS
SELECT uc.id AS dup_id, k.kid AS keeper_id
FROM os_user_categories uc
JOIN _keepers k ON uc.slug = k.slug
WHERE uc.company_id IS NULL AND uc.id != k.kid;

-- 2) Update remaining map rows to point to keeper
UPDATE os_user_category_map ucm
JOIN _dupes2 d ON ucm.category_id = d.dup_id
SET ucm.category_id = d.keeper_id;

-- 3) Drop the unique key
ALTER TABLE os_user_categories DROP INDEX uk_slug_company;

-- 4) Delete duplicate category rows
DROP TEMPORARY TABLE IF EXISTS _keepers2;
CREATE TEMPORARY TABLE _keepers2 AS
SELECT slug, MIN(id) AS kid FROM os_user_categories WHERE company_id IS NULL GROUP BY slug;

DELETE uc FROM os_user_categories uc
JOIN _keepers2 k ON uc.slug = k.slug
WHERE uc.company_id IS NULL AND uc.id != k.kid;

-- 5) Re-add unique key
ALTER TABLE os_user_categories ADD UNIQUE KEY uk_slug_company (slug, company_id);

-- Cleanup
DROP TEMPORARY TABLE IF EXISTS _keepers;
DROP TEMPORARY TABLE IF EXISTS _keepers2;
DROP TEMPORARY TABLE IF EXISTS _dupes;
DROP TEMPORARY TABLE IF EXISTS _dupes2;

SET sql_safe_updates = @sql_safe_updates;

-- Verify
SELECT id, slug, name_ar, is_active FROM os_user_categories ORDER BY sort_order, id;
SELECT uc.slug, uc.name_ar, COUNT(DISTINCT ucm.user_id) AS mapped_users
FROM os_user_categories uc
LEFT JOIN os_user_category_map ucm ON uc.id = ucm.category_id
GROUP BY uc.id, uc.slug, uc.name_ar
ORDER BY uc.sort_order;
"""

for site, db in DATABASES.items():
    print(f"\n{'='*60}")
    print(f"  {site} ({db})")
    print(f"{'='*60}")
    
    out = mysql(db, CLEANUP_SQL)
    if 'ERROR' in out:
        print(f"  [ERROR] {out}")
    else:
        print(out)

ssh.close()
print("\nDone!")
