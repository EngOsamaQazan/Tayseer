"""
Tayseer ERP — Full Server Backup Script
========================================
Downloads database dumps + all uploaded files from production servers
to a local backup directory using incremental SFTP sync.

Usage:
    python backup.py                  # Backup all sites
    python backup.py --site jadal     # Backup jadal only
    python backup.py --site namaa     # Backup namaa only
    python backup.py --db-only        # Database only (skip files)
    python backup.py --files-only     # Files only (skip database)
    python backup.py --dry-run        # Show what would be downloaded
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
import os
import sys
import stat
import time
import argparse
from datetime import datetime

os.environ['PYTHONIOENCODING'] = 'utf-8'

# ═══════════════════════════════════════════════════════════
#  Server Configuration
# ═══════════════════════════════════════════════════════════

HOST = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS

SITES = {
    'jadal': {
        'path': '/var/www/jadal.aqssat.co',
        'db_name': 'namaa_jadal',
        'db_user': 'osama',
        'db_pass': LEGACY_DB_PASS,
    },
    'namaa': {
        'path': '/var/www/namaa.aqssat.co',
        'db_name': 'namaa_erp',
        'db_user': 'osama',
        'db_pass': LEGACY_DB_PASS,
    },
}

FOLDERS_TO_SYNC = [
    'backend/web/images/imagemanager',
    'backend/web/images/employeeImage',
    'backend/web/images/lawar_images',
    'backend/web/uploads/customers/documents',
    'backend/web/uploads/customers/photos',
    'backend/web/uploads/judiciary_customers_actions',
    'backend/web/uploads/judiciary_decisions',
    'backend/web/uploads/investors',
]

LOCAL_BACKUP_ROOT = os.path.join(os.path.expanduser('~'), 'TayseerBackups')

# ═══════════════════════════════════════════════════════════
#  Helpers
# ═══════════════════════════════════════════════════════════

class BackupStats:
    def __init__(self):
        self.files_checked = 0
        self.files_downloaded = 0
        self.files_skipped = 0
        self.bytes_downloaded = 0
        self.errors = []
        self.start_time = time.time()

    def summary(self):
        elapsed = time.time() - self.start_time
        mins = int(elapsed // 60)
        secs = int(elapsed % 60)
        size_mb = self.bytes_downloaded / (1024 * 1024)
        return (
            f"\n{'='*60}\n"
            f"  Backup Summary\n"
            f"{'='*60}\n"
            f"  Files checked:    {self.files_checked:,}\n"
            f"  Files downloaded: {self.files_downloaded:,}\n"
            f"  Files skipped:    {self.files_skipped:,} (already up-to-date)\n"
            f"  Total downloaded: {size_mb:,.1f} MB\n"
            f"  Duration:         {mins}m {secs}s\n"
            f"  Errors:           {len(self.errors)}\n"
            f"{'='*60}"
        )


def run_cmd(ssh, cmd, timeout=600):
    """Execute command on remote server and return (exit_code, stdout, stderr)."""
    print(f"  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if exit_code != 0 and err.strip():
        for line in err.strip().split('\n')[:10]:
            try:
                print(f"    [err] {line}")
            except UnicodeEncodeError:
                print(f"    [err] {line.encode('ascii', 'replace').decode()}")
    return exit_code, out, err


def format_size(size_bytes):
    """Human-readable file size."""
    for unit in ['B', 'KB', 'MB', 'GB']:
        if size_bytes < 1024:
            return f"{size_bytes:.1f} {unit}"
        size_bytes /= 1024
    return f"{size_bytes:.1f} TB"


def sftp_sync_folder(sftp, remote_dir, local_dir, stats, dry_run=False):
    """
    Recursively sync a remote directory to local via SFTP.
    Only downloads files that are new or modified (by comparing mtime + size).
    """
    try:
        entries = sftp.listdir_attr(remote_dir)
    except FileNotFoundError:
        print(f"    [skip] Remote dir not found: {remote_dir}")
        return
    except Exception as e:
        stats.errors.append(f"{remote_dir}: {e}")
        print(f"    [error] {remote_dir}: {e}")
        return

    os.makedirs(local_dir, exist_ok=True)

    for entry in entries:
        remote_path = remote_dir + '/' + entry.filename
        local_path = os.path.join(local_dir, entry.filename)

        if stat.S_ISDIR(entry.st_mode):
            sftp_sync_folder(sftp, remote_path, local_path, stats, dry_run)
        else:
            stats.files_checked += 1
            need_download = True

            if os.path.exists(local_path):
                local_stat = os.stat(local_path)
                local_size = local_stat.st_size
                remote_size = entry.st_size
                local_mtime = local_stat.st_mtime
                remote_mtime = entry.st_mtime

                if local_size == remote_size and local_mtime >= remote_mtime:
                    need_download = False

            if need_download:
                if dry_run:
                    print(f"    [would download] {entry.filename} ({format_size(entry.st_size)})")
                    stats.files_downloaded += 1
                    stats.bytes_downloaded += entry.st_size
                else:
                    try:
                        sftp.get(remote_path, local_path)
                        os.utime(local_path, (entry.st_atime, entry.st_mtime))
                        stats.files_downloaded += 1
                        stats.bytes_downloaded += entry.st_size
                        if stats.files_downloaded % 100 == 0:
                            print(f"    ... {stats.files_downloaded:,} files downloaded ({format_size(stats.bytes_downloaded)})")
                    except Exception as e:
                        stats.errors.append(f"{remote_path}: {e}")
                        print(f"    [error] {entry.filename}: {e}")
            else:
                stats.files_skipped += 1


# ═══════════════════════════════════════════════════════════
#  Backup Actions
# ═══════════════════════════════════════════════════════════

def backup_database(ssh, sftp, site_name, site_cfg, backup_dir):
    """Dump the MySQL database on the server and download it."""
    print(f"\n  [DB] Dumping {site_cfg['db_name']}...")

    remote_dump = f"/tmp/tayseer_backup_{site_name}.sql.gz"
    dump_cmd = (
        f"mysqldump --single-transaction --quick --routines --triggers "
        f"-u{site_cfg['db_user']} -p'{site_cfg['db_pass']}' "
        f"{site_cfg['db_name']} | gzip > {remote_dump}"
    )

    code, _, _ = run_cmd(ssh, dump_cmd, timeout=600)
    if code != 0:
        print(f"  [ERROR] mysqldump failed for {site_name}")
        return False

    db_dir = os.path.join(backup_dir, 'database')
    os.makedirs(db_dir, exist_ok=True)

    timestamp = datetime.now().strftime('%Y-%m-%d_%H-%M')
    local_file = os.path.join(db_dir, f"{site_cfg['db_name']}_{timestamp}.sql.gz")

    print(f"  [DB] Downloading dump...")
    sftp.get(remote_dump, local_file)
    remote_size = sftp.stat(remote_dump).st_size
    print(f"  [DB] Done — {format_size(remote_size)} saved to {local_file}")

    run_cmd(ssh, f"rm -f {remote_dump}")
    return True


def backup_files(sftp, site_name, site_cfg, backup_dir, stats, dry_run=False):
    """Incrementally sync all uploaded files from the server."""
    print(f"\n  [FILES] Syncing uploaded files for {site_name}...")

    for folder in FOLDERS_TO_SYNC:
        remote_dir = site_cfg['path'] + '/' + folder
        local_dir = os.path.join(backup_dir, 'files', folder)

        short_name = folder.split('/')[-1]
        print(f"\n    Syncing: {short_name}/")

        sftp_sync_folder(sftp, remote_dir, local_dir, stats, dry_run)


def backup_configs(sftp, site_name, site_cfg, backup_dir):
    """Download configuration files."""
    print(f"\n  [CONFIG] Downloading config files for {site_name}...")

    config_dir = os.path.join(backup_dir, 'config')
    os.makedirs(config_dir, exist_ok=True)

    config_files = [
        'common/config/main-local.php',
        'common/config/params-local.php',
        'backend/config/main.php',
    ]

    for cfg in config_files:
        remote_path = site_cfg['path'] + '/' + cfg
        local_path = os.path.join(config_dir, cfg.replace('/', '_'))
        try:
            sftp.get(remote_path, local_path)
            print(f"    OK: {cfg}")
        except FileNotFoundError:
            print(f"    [skip] {cfg} not found")
        except Exception as e:
            print(f"    [error] {cfg}: {e}")


# ═══════════════════════════════════════════════════════════
#  Main
# ═══════════════════════════════════════════════════════════

def main():
    parser = argparse.ArgumentParser(description='Tayseer ERP — Full Server Backup')
    parser.add_argument('--site', choices=['jadal', 'namaa'], help='Backup specific site only')
    parser.add_argument('--db-only', action='store_true', help='Database dump only (skip files)')
    parser.add_argument('--files-only', action='store_true', help='Files only (skip database)')
    parser.add_argument('--dry-run', action='store_true', help='Show what would be downloaded without downloading')
    args = parser.parse_args()

    sites_to_backup = {args.site: SITES[args.site]} if args.site else SITES

    print(f"{'='*60}")
    print(f"  Tayseer ERP — Server Backup")
    print(f"  {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"  Sites: {', '.join(sites_to_backup.keys())}")
    print(f"  Mode: {'DRY RUN' if args.dry_run else 'LIVE'}")
    if args.db_only:
        print(f"  Scope: Database only")
    elif args.files_only:
        print(f"  Scope: Files only")
    else:
        print(f"  Scope: Full backup (DB + Files + Config)")
    print(f"  Local dir: {LOCAL_BACKUP_ROOT}")
    print(f"{'='*60}")

    print(f"\n  Connecting to {HOST}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    except Exception as e:
        print(f"  [FATAL] SSH connection failed: {e}")
        sys.exit(1)
    print("  Connected!")

    sftp = ssh.open_sftp()
    overall_stats = BackupStats()

    for site_name, site_cfg in sites_to_backup.items():
        print(f"\n{'='*60}")
        print(f"  Backing up: {site_name}")
        print(f"{'='*60}")

        backup_dir = os.path.join(LOCAL_BACKUP_ROOT, site_name)
        os.makedirs(backup_dir, exist_ok=True)

        if not args.files_only:
            backup_database(ssh, sftp, site_name, site_cfg, backup_dir)

        if not args.db_only:
            backup_files(sftp, site_name, site_cfg, backup_dir, overall_stats, args.dry_run)

        if not args.db_only and not args.files_only:
            backup_configs(sftp, site_name, site_cfg, backup_dir)

    sftp.close()
    ssh.close()

    print(overall_stats.summary())

    if overall_stats.errors:
        print("\n  Errors encountered:")
        for err in overall_stats.errors[:20]:
            print(f"    - {err}")

    print(f"\n  Backup location: {LOCAL_BACKUP_ROOT}")
    print(f"  {'='*60}")
    print(f"  BACKUP COMPLETE!")
    print(f"  {'='*60}\n")


if __name__ == '__main__':
    main()
