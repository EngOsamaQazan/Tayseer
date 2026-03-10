"""
Full Deploy Script — Tayseer ERP
Handles: DB changes + Full backend replacement + Cache clear + Apache restart
"""
import paramiko
import os
import stat
import time

SERVER = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'
SITES = ['namaa', 'jadal']
DBS = {'namaa': 'namaa_erp', 'jadal': 'namaa_jadal'}
DB_USER = 'osama'
DB_PASS = 'O$amaDaTaBase@123'

LOCAL_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
LOCAL_BACKEND = os.path.join(LOCAL_ROOT, 'backend')

SKIP_DIRS = {
    'node_modules', 'runtime', '.git', '__pycache__',
    'images', 'uploads',
}
SKIP_FILES = {'.env', 'index.php', 'index-test.php', 'robots.txt'}

CREATE_PINNED_TABLE_SQL = """
CREATE TABLE IF NOT EXISTS `os_pinned_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `item_type` VARCHAR(50) NOT NULL,
    `item_id` INT NOT NULL,
    `label` VARCHAR(255) DEFAULT NULL,
    `extra_info` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_item` (`user_id`, `item_type`, `item_id`),
    KEY `idx_user_type` (`user_id`, `item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
"""

def connect():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SERVER, username=USER, password=PASS, timeout=60, banner_timeout=60, auth_timeout=60)
    return ssh

def run(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=120)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

def apply_db_changes(ssh):
    print('\n' + '='*60)
    print('  DATABASE CHANGES')
    print('='*60)
    
    sql_content = CREATE_PINNED_TABLE_SQL.strip()
    run(ssh, f"cat > /tmp/tayseer_db.sql << 'SQLEOF'\n{sql_content}\nSQLEOF")
    
    for site in SITES:
        db = DBS[site]
        print(f'\n--- {site} ({db}) ---')
        
        escaped_pass = DB_PASS.replace('$', '\\$')
        cmd = f'mysql -u {DB_USER} -p"{escaped_pass}" {db} < /tmp/tayseer_db.sql'
        out, err = run(ssh, cmd)
        if err and 'warning' not in err.lower() and 'error' in err.lower():
            print(f'  WARN: {err.strip()}')
        else:
            print(f'  OK: os_pinned_items table ensured')
    
    run(ssh, 'rm -f /tmp/tayseer_db.sql')

def collect_files(local_dir, base_path=''):
    """Collect all files to deploy, respecting skip rules."""
    files = []
    for entry in os.listdir(local_dir):
        full_path = os.path.join(local_dir, entry)
        rel_path = os.path.join(base_path, entry) if base_path else entry
        
        if os.path.isdir(full_path):
            if entry in SKIP_DIRS:
                continue
            files.extend(collect_files(full_path, rel_path))
        else:
            if entry in SKIP_FILES and base_path == 'web':
                continue
            files.append(rel_path)
    return files

def deploy_full(ssh):
    print('\n' + '='*60)
    print('  FULL BACKEND DEPLOY')
    print('='*60)
    
    sftp = ssh.open_sftp()
    
    all_files = collect_files(LOCAL_BACKEND)
    total = len(all_files)
    print(f'\n  Found {total} files to deploy')
    
    for site in SITES:
        remote_root = f'/var/www/{site}.aqssat.co/backend'
        print(f'\n--- Deploying to {site} ({total} files) ---')
        
        ok_count = 0
        fail_count = 0
        
        for i, rel_path in enumerate(all_files, 1):
            local_path = os.path.join(LOCAL_BACKEND, rel_path.replace('/', os.sep))
            remote_path = f'{remote_root}/{rel_path}'.replace('\\', '/')
            
            remote_dir = '/'.join(remote_path.split('/')[:-1])
            try:
                sftp.stat(remote_dir)
            except FileNotFoundError:
                run(ssh, f'mkdir -p "{remote_dir}"')
            
            try:
                sftp.put(local_path, remote_path)
                ok_count += 1
                if i % 50 == 0 or i == total:
                    print(f'  [{i}/{total}] uploaded... ({ok_count} ok, {fail_count} fail)')
            except Exception as e:
                fail_count += 1
                print(f'  FAIL: {rel_path} -> {e}')
        
        print(f'  Done: {ok_count} uploaded, {fail_count} failed')
        
        print(f'  Setting permissions...')
        run(ssh, f'chown -R www-data:www-data {remote_root}')
        run(ssh, f'find {remote_root} -type d -exec chmod 755 {{}} \\;')
        run(ssh, f'find {remote_root} -type f -exec chmod 644 {{}} \\;')
        
        print(f'  Flushing cache...')
        site_root = f'/var/www/{site}.aqssat.co'
        out, err = run(ssh, f'cd {site_root} && php yii cache/flush-all 2>&1')
        print(f'  Cache: {out.strip() or err.strip() or "done"}')
        
        run(ssh, f'rm -rf {remote_root}/runtime/cache/*')
        run(ssh, f'rm -rf {remote_root}/runtime/debug/*')
        print(f'  Runtime cache cleared')
    
    sftp.close()

def restart_apache(ssh):
    print('\n' + '='*60)
    print('  RESTARTING APACHE')
    print('='*60)
    out, err = run(ssh, 'systemctl restart apache2')
    print(f'  Apache: {err.strip() or "restarted OK"}')

def main():
    start = time.time()
    print('='*60)
    print('  TAYSEER FULL DEPLOY')
    print(f'  Server: {SERVER}')
    print(f'  Sites: {", ".join(SITES)}')
    print('='*60)
    
    ssh = connect()
    print('\n  Connected to server')
    
    apply_db_changes(ssh)
    deploy_full(ssh)
    restart_apache(ssh)
    
    ssh.close()
    
    elapsed = time.time() - start
    print('\n' + '='*60)
    print(f'  DEPLOY COMPLETE ({elapsed:.0f}s)')
    print('='*60)

if __name__ == '__main__':
    main()
