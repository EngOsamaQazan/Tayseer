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

CLEANUP_SQL = """
-- Step 1: For each slug, keep only the row with the LOWEST id.
-- First update category_map to point to the keeper id.
UPDATE os_user_category_map ucm
JOIN os_user_categories uc_old ON ucm.category_id = uc_old.id
JOIN (
    SELECT slug, MIN(id) AS keeper_id
    FROM os_user_categories
    WHERE company_id IS NULL
    GROUP BY slug
) AS keepers ON uc_old.slug = keepers.slug AND uc_old.company_id IS NULL
SET ucm.category_id = keepers.keeper_id
WHERE ucm.category_id != keepers.keeper_id;

-- Step 2: Remove duplicate user_category_map rows that now have the same (user_id, category_id)
DELETE t1 FROM os_user_category_map t1
INNER JOIN os_user_category_map t2
WHERE t1.id > t2.id
  AND t1.user_id = t2.user_id
  AND t1.category_id = t2.category_id;

-- Step 3: Delete duplicate category rows (keep lowest id per slug)
DELETE FROM os_user_categories
WHERE id NOT IN (
    SELECT keeper_id FROM (
        SELECT MIN(id) AS keeper_id
        FROM os_user_categories
        WHERE company_id IS NULL
        GROUP BY slug
    ) AS t
)
AND company_id IS NULL;

-- Step 4: Drop old unique key and add a proper one that handles NULLs
-- We'll use a virtual column approach or just replace NULL with 0
ALTER TABLE os_user_categories
    DROP INDEX IF EXISTS uk_slug_company;

ALTER TABLE os_user_categories
    MODIFY company_id INT DEFAULT 0 NOT NULL;

UPDATE os_user_categories SET company_id = 0 WHERE company_id IS NULL;

ALTER TABLE os_user_categories
    ADD UNIQUE KEY uk_slug_company (slug, company_id);
"""

for site, db in DATABASES.items():
    print(f"\n{'='*60}")
    print(f"  Cleaning {site} ({db})")
    print(f"{'='*60}")
    
    # Run cleanup SQL line by line to see errors
    for stmt in CLEANUP_SQL.strip().split(';'):
        stmt = stmt.strip()
        if not stmt or stmt.startswith('--'):
            continue
        # Remove comment lines from the statement
        lines = [l for l in stmt.split('\n') if not l.strip().startswith('--')]
        stmt = '\n'.join(lines).strip()
        if not stmt:
            continue
        
        out, _ = run(f'mysql -u root {db} -e "{stmt};" 2>&1')
        if 'ERROR' in out:
            # Some errors are OK (like DROP INDEX IF EXISTS not supported)
            if 'check that it exists' in out.lower() or 'check that column' in out.lower():
                print(f"  [SKIP] {out.strip()}")
            else:
                print(f"  [ERROR] {out.strip()}")
                print(f"  [SQL] {stmt[:100]}...")
        else:
            short = stmt.split('\n')[0][:60]
            print(f"  [OK] {short}...")
    
    # Verify final state
    out, _ = run(f'mysql -u root {db} -e "SELECT id, slug, name_ar, company_id FROM os_user_categories ORDER BY sort_order, id;" 2>&1')
    print(f"\n  Final categories:")
    for line in out.strip().split('\n'):
        print(f"    {line}")
    
    out, _ = run(f'mysql -u root {db} -e "SELECT uc.slug, COUNT(DISTINCT ucm.user_id) as users FROM os_user_categories uc LEFT JOIN os_user_category_map ucm ON uc.id = ucm.category_id GROUP BY uc.slug ORDER BY uc.sort_order;" 2>&1')
    print(f"\n  Category user counts:")
    for line in out.strip().split('\n'):
        print(f"    {line}")

ssh.close()
print("\nDone!")
