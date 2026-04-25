"""
One-time server setup: initialize git in both site directories
so GitHub Actions can deploy via git pull.
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

SERVER = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS
GH_TOKEN = GH_TOKEN
REPO_URL = f'https://{GH_TOKEN}@github.com/EngOsamaQazan/TayseerV3.0.git'
BRANCH = 'main'

SITES = [
    '/var/www/vite.jadal.aqssat.co',
    '/var/www/vite.namaa.aqssat.co',
]

def run_cmd(ssh, cmd, label=''):
    if label:
        print(f'  {label}...')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=120)
    exit_code = stdout.channel.recv_exit_status()
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        for line in out.split('\n')[:10]:
            print(f'    {line}')
    if exit_code != 0 and err:
        for line in err.split('\n')[:5]:
            print(f'    [ERR] {line}')
    return exit_code, out

def main():
    print(f'Connecting to {SERVER}...')
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SERVER, username=USER, password=PASS, timeout=30)
    print('Connected!\n')

    run_cmd(ssh, 'which git || apt-get install -y git', 'Checking git')

    for site in SITES:
        print(f'\n=== {site} ===')

        run_cmd(ssh, f'ls -la {site}/.git 2>/dev/null || echo "NO_GIT"', 'Checking .git')

        cmds = [
            f'cd {site} && git init',
            f'cd {site} && git remote remove origin 2>/dev/null; git remote add origin {REPO_URL}',
            f'cd {site} && git fetch origin {BRANCH} --depth 1',
            f'cd {site} && git checkout -B {BRANCH}',
            f'cd {site} && git reset --hard origin/{BRANCH}',
            f'cd {site} && chown -R www-data:www-data backend/ common/ console/ 2>/dev/null',
            f'cd {site} && chmod -R 775 backend/runtime/ 2>/dev/null',
            f'cd {site} && rm -rf backend/runtime/cache/*',
        ]

        for cmd in cmds:
            short = cmd.split('&&')[-1].strip()[:60]
            code, out = run_cmd(ssh, cmd, short)
            if code != 0:
                print(f'    WARNING: exit code {code}')

        print(f'  Done!')

    ssh.close()
    print('\nServer setup complete! GitHub Actions can now deploy automatically.')

if __name__ == '__main__':
    main()
