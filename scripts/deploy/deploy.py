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
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS

SITES = [
    '/var/www/jadal.aqssat.co',
    '/var/www/namaa.aqssat.co',
]

def run_cmd(ssh, cmd, timeout=300):
    print(f"  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        for line in out.strip().split('\n')[:50]:
            try:
                print(f"    {line}")
            except UnicodeEncodeError:
                print(f"    {line.encode('ascii', 'replace').decode()}")
    if err.strip():
        for line in err.strip().split('\n')[:20]:
            try:
                print(f"    [err] {line}")
            except UnicodeEncodeError:
                print(f"    [err] {line.encode('ascii', 'replace').decode()}")
    if exit_code != 0:
        print(f"  EXIT CODE: {exit_code}")
    return exit_code, out, err

def deploy_site(ssh, site_path):
    site_name = site_path.split('/')[-1]
    print(f"\n{'='*60}")
    print(f"  Deploying: {site_name}")
    print(f"{'='*60}")

    run_cmd(ssh, f"cd {site_path} && git fetch origin main")
    run_cmd(ssh, f"cd {site_path} && git stash --include-untracked 2>/dev/null; true")
    run_cmd(ssh, f"cd {site_path} && git checkout main 2>&1")
    run_cmd(ssh, f"cd {site_path} && git reset --hard origin/main")

    code, out, _ = run_cmd(ssh,
        f"cd {site_path} && COMPOSER_ALLOW_SUPERUSER=1 composer update --no-dev --optimize-autoloader --no-interaction 2>&1",
        timeout=600)
    if code != 0:
        print(f"  WARNING: composer update failed with code {code}")

    run_cmd(ssh, f"cd {site_path} && php yii migrate --interactive=0 2>&1", timeout=120)
    run_cmd(ssh, f"cd {site_path} && php yii cache/flush-all 2>&1")

    print(f"\n  {site_name} DONE!")

def main():
    print(f"Connecting to {HOST}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    print("Connected!\n")

    run_cmd(ssh, "php -v | head -1")

    for site in SITES:
        deploy_site(ssh, site)

    print(f"\n  Restarting Apache to clear PHP opcache...")
    code, _, _ = run_cmd(ssh, "systemctl restart apache2")
    if code == 0:
        print("  Apache restarted — opcache cleared!")
    else:
        print("  WARNING: Apache restart failed, opcache may serve stale files")

    print(f"\n{'='*60}")
    print("  ALL DEPLOYMENTS COMPLETE!")
    print(f"{'='*60}")

    ssh.close()

if __name__ == '__main__':
    main()
