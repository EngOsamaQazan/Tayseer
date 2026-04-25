#!/usr/bin/env python3
"""
Step 2: Migrate ALL databases + ALL sites from old server to new server.
"""
# --- Credentials (loaded from scripts/credentials.py, git-ignored) ---
# Copy scripts/credentials.example.py to scripts/credentials.py and
# fill in the real values before running this script.
import os as _os, sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..'))
_sys.path.insert(0, _os.path.dirname(_os.path.abspath(__file__)))
try:
    from credentials import *  # noqa: F401,F403
except ImportError as _e:
    raise SystemExit(
        'Missing scripts/credentials.py — copy credentials.example.py and fill in real values.\n'
        f'Original error: {_e}'
    )
# ---------------------------------------------------------------------

import paramiko
import sys
import time
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

OLD_HOST = OLD_SERVER_IP
OLD_USER = 'root'
OLD_PASS = OLD_SERVER_PASS
OLD_DB_USER = 'root'

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS
NEW_DB_USER = DB_USER
NEW_DB_PASS = DB_PASS

DATABASES = [
    'namaa_erp', 'namaa_jadal', 'staging', 'namaa_khaldon',
    'fahras_db', 'access_db', 'tenanttenantJadel', 'dictionary',
    'baseel', 'ahwal', 'erb_digram', 'sass', 'tazej_food', 'bugzilla',
]

SITES = [
    '/var/www/jadal.aqssat.co',
    '/var/www/namaa.aqssat.co',
    '/var/www/old.jadal.aqssat.co',
    '/var/www/old.namaa.aqssat.co',
    '/var/www/fahras.aqssat.co',
    '/var/www/vite.jadal.aqssat.co',
    '/var/www/vite.namaa.aqssat.co',
    '/var/www/micro_services',
]


def connect(host, user, password):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, username=user, password=password, timeout=30, banner_timeout=30)
    ssh.get_transport().set_keepalive(15)
    return ssh


def run(ssh, cmd, timeout=600, show=True):
    if show:
        print(f"  $ {cmd[:150]}{'...' if len(cmd) > 150 else ''}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if show and out:
        for line in out.split('\n')[:20]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if code != 0 and err:
        for line in err.split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out, err


def phase1_dump_databases(old_ssh):
    print("=" * 60)
    print("  PHASE 1: Dump ALL Databases on Old Server")
    print("=" * 60, flush=True)

    for db in DATABASES:
        dump_file = f"/tmp/{db}_migration.sql.gz"
        print(f"\n  Dumping {db}...", flush=True)
        cmd = (
            f"mysqldump -u {OLD_DB_USER} "
            f"--single-transaction --routines --triggers --events "
            f"--add-drop-table {db} 2>/dev/null | gzip > {dump_file}"
        )
        code, _, _ = run(old_ssh, cmd, timeout=600)
        run(old_ssh, f"ls -lh {dump_file}")

    print("\n  All databases dumped!", flush=True)


def phase2_transfer_dumps(old_ssh):
    print("\n" + "=" * 60)
    print("  PHASE 2: Transfer Database Dumps to New Server")
    print("=" * 60, flush=True)

    run(old_ssh, "apt install -y sshpass 2>/dev/null; true")

    for db in DATABASES:
        dump_file = f"/tmp/{db}_migration.sql.gz"
        print(f"\n  Transferring {db}...", flush=True)
        cmd = (
            f"sshpass -p '{NEW_PASS}' scp -o StrictHostKeyChecking=no "
            f"{dump_file} {NEW_USER}@{NEW_HOST}:{dump_file}"
        )
        code, _, _ = run(old_ssh, cmd, timeout=600)
        if code != 0:
            print(f"  WARNING: Transfer failed for {db}, skipping", flush=True)

    print("\n  All dumps transferred!", flush=True)


def phase3_restore_databases(new_ssh):
    print("\n" + "=" * 60)
    print("  PHASE 3: Restore ALL Databases on New Server")
    print("=" * 60, flush=True)

    for db in DATABASES:
        dump_file = f"/tmp/{db}_migration.sql.gz"
        print(f"\n  Restoring {db}...", flush=True)

        code, out, _ = run(new_ssh, f"test -f {dump_file} && echo 'EXISTS' || echo 'MISSING'")
        if 'MISSING' in out:
            print(f"  SKIP: dump not found for {db}", flush=True)
            continue

        run(new_ssh, f"mysql -u root -e 'DROP DATABASE IF EXISTS `{db}`; CREATE DATABASE `{db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'")
        code, _, _ = run(new_ssh, f"gunzip < {dump_file} | mysql -u root `{db}`", timeout=600)
        if code != 0:
            print(f"  WARNING: Restore had issues for {db}", flush=True)

        run(new_ssh, f"mysql -u root -e \"SELECT COUNT(*) as tables_count FROM information_schema.tables WHERE table_schema='{db}';\"")

    run(new_ssh, f"""mysql -u root -e "
{' '.join([f"GRANT ALL PRIVILEGES ON `{db}`.* TO '{NEW_DB_USER}'@'localhost';" for db in DATABASES])}
FLUSH PRIVILEGES;
" """)
    print("\n  All databases restored!", flush=True)


def phase4_transfer_sites(old_ssh, new_ssh):
    print("\n" + "=" * 60)
    print("  PHASE 4: Transfer ALL Sites")
    print("=" * 60, flush=True)

    for site_path in SITES:
        site_name = site_path.split('/')[-1]
        archive = f"/tmp/{site_name.replace('.', '_')}_site.tar.gz"

        code, out, _ = run(old_ssh, f"test -d {site_path} && echo 'EXISTS' || echo 'MISSING'")
        if 'MISSING' in out:
            print(f"\n  SKIP: {site_path} not found on old server", flush=True)
            continue

        print(f"\n  Archiving {site_name}...", flush=True)
        cmd = (
            f"cd {site_path} && tar czf {archive} "
            f"--exclude='.git' "
            f"--exclude='*/runtime/logs/*' "
            f"--exclude='*/web/assets/*' "
            f"--warning=no-file-changed "
            f". 2>/dev/null"
        )
        run(old_ssh, cmd, timeout=600)
        run(old_ssh, f"ls -lh {archive}")

        print(f"  Transferring {site_name}...", flush=True)
        cmd = (
            f"sshpass -p '{NEW_PASS}' scp -o StrictHostKeyChecking=no "
            f"{archive} {NEW_USER}@{NEW_HOST}:{archive}"
        )
        code, _, _ = run(old_ssh, cmd, timeout=1800)
        if code != 0:
            print(f"  WARNING: Transfer failed for {site_name}", flush=True)
            continue

        print(f"  Extracting {site_name} on new server...", flush=True)
        run(new_ssh, f"mkdir -p {site_path}")
        run(new_ssh, f"tar xzf {archive} -C {site_path} 2>/dev/null; true")

        dirs = ' '.join([
            f"{site_path}/backend/runtime",
            f"{site_path}/frontend/runtime",
            f"{site_path}/console/runtime",
            f"{site_path}/backend/web/assets",
            f"{site_path}/frontend/web/assets",
        ])
        run(new_ssh, f"mkdir -p {dirs} 2>/dev/null; true")
        run(new_ssh, f"chmod -R 777 {dirs} 2>/dev/null; true")

    run(new_ssh, "chown -R www-data:www-data /var/www/")
    print("\n  All sites transferred!", flush=True)


def phase5_update_configs(new_ssh):
    print("\n" + "=" * 60)
    print("  PHASE 5: Update DB Configs on New Server")
    print("=" * 60, flush=True)

    yii_sites = [
        ('/var/www/jadal.aqssat.co', 'namaa_jadal', 'prod_jadal'),
        ('/var/www/namaa.aqssat.co', 'namaa_erp', 'prod_namaa'),
        ('/var/www/old.jadal.aqssat.co', 'namaa_jadal', None),
        ('/var/www/old.namaa.aqssat.co', 'namaa_erp', None),
    ]

    for site_path, db_name, env_name in yii_sites:
        conf = f"{site_path}/common/config/main-local.php"
        code, out, _ = run(new_ssh, f"test -f {conf} && echo 'EXISTS' || echo 'MISSING'")
        if 'MISSING' in out:
            continue

        print(f"\n  Updating {conf}...", flush=True)
        run(new_ssh, f"sed -i \"s/'username' => '.*'/'username' => '{NEW_DB_USER}'/\" {conf}")
        run(new_ssh, f"sed -i \"s/'password' => '.*'/'password' => '{NEW_DB_PASS}'/\" {conf}")

        index = f"{site_path}/backend/web/index.php"
        run(new_ssh, f"test -f {index} && sed -i \"s/YII_DEBUG', true/YII_DEBUG', false/\" {index}; true")
        run(new_ssh, f"test -f {index} && sed -i \"s/YII_ENV', 'dev'/YII_ENV', 'prod'/\" {index}; true")

    print("\n  Configs updated!", flush=True)


def phase6_composer_and_migrate(new_ssh):
    print("\n" + "=" * 60)
    print("  PHASE 6: Composer + Migrations (main sites)")
    print("=" * 60, flush=True)

    main_sites = [
        '/var/www/jadal.aqssat.co',
        '/var/www/namaa.aqssat.co',
    ]

    for site_path in main_sites:
        site_name = site_path.split('/')[-1]
        code, out, _ = run(new_ssh, f"test -f {site_path}/composer.json && echo 'EXISTS' || echo 'MISSING'")
        if 'MISSING' in out:
            continue

        print(f"\n  [{site_name}] composer install...", flush=True)
        run(new_ssh,
            f"cd {site_path} && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction 2>&1",
            timeout=600)

        print(f"  [{site_name}] migrations...", flush=True)
        run(new_ssh, f"cd {site_path} && php yii migrate --interactive=0 2>&1", timeout=120)

        print(f"  [{site_name}] cache flush...", flush=True)
        run(new_ssh, f"cd {site_path} && php yii cache/flush-all 2>&1")

    run(new_ssh, "chown -R www-data:www-data /var/www/")
    print("\n  Composer + Migrations done!", flush=True)


def phase7_cleanup(old_ssh, new_ssh):
    print("\n" + "=" * 60)
    print("  PHASE 7: Cleanup temp files")
    print("=" * 60, flush=True)

    for db in DATABASES:
        run(old_ssh, f"rm -f /tmp/{db}_migration.sql.gz", show=False)
        run(new_ssh, f"rm -f /tmp/{db}_migration.sql.gz", show=False)

    for site_path in SITES:
        name = site_path.split('/')[-1].replace('.', '_')
        run(old_ssh, f"rm -f /tmp/{name}_site.tar.gz", show=False)
        run(new_ssh, f"rm -f /tmp/{name}_site.tar.gz", show=False)

    print("  Cleanup done!", flush=True)


def main():
    print(f"Connecting to OLD server ({OLD_HOST})...", flush=True)
    old_ssh = connect(OLD_HOST, OLD_USER, OLD_PASS)
    print("Connected to old server!", flush=True)

    print(f"Connecting to NEW server ({NEW_HOST})...", flush=True)
    new_ssh = connect(NEW_HOST, NEW_USER, NEW_PASS)
    print("Connected to new server!\n", flush=True)

    start = time.time()

    phase1_dump_databases(old_ssh)
    phase2_transfer_dumps(old_ssh)
    phase3_restore_databases(new_ssh)
    phase4_transfer_sites(old_ssh, new_ssh)
    phase5_update_configs(new_ssh)
    phase6_composer_and_migrate(new_ssh)
    phase7_cleanup(old_ssh, new_ssh)

    elapsed = time.time() - start

    print("\n" + "=" * 60)
    print("  DATA MIGRATION COMPLETE!")
    print(f"  Time: {elapsed / 60:.1f} minutes")
    print(f"  Databases: {len(DATABASES)}")
    print(f"  Sites: {len(SITES)}")
    print("  Next: Run 03_setup_vhosts_ssl.py")
    print("=" * 60, flush=True)

    old_ssh.close()
    new_ssh.close()


if __name__ == '__main__':
    main()
