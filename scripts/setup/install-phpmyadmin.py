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

PHPMYADMIN_VERSION = '5.2.3'
PHPMYADMIN_URL = f'https://files.phpmyadmin.net/phpMyAdmin/{PHPMYADMIN_VERSION}/phpMyAdmin-{PHPMYADMIN_VERSION}-all-languages.zip'
INSTALL_DIR = '/usr/share/phpmyadmin'
TEMP_DIR = '/tmp/phpmyadmin_install'

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

def main():
    print(f"{'='*60}")
    print(f"  Installing phpMyAdmin {PHPMYADMIN_VERSION} on {HOST}")
    print(f"{'='*60}")

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    try:
        print(f"\n[1/8] Connecting to {HOST}...")
        ssh.connect(HOST, port=22, username=USER, password=PASS, timeout=60, banner_timeout=60, auth_timeout=60)
        print("  Connected!")

        print(f"\n[2/8] Checking current phpMyAdmin version...")
        run_cmd(ssh, f"test -f {INSTALL_DIR}/libraries/classes/Version.php && grep -oP \"VERSION = '\\K[^']+\" {INSTALL_DIR}/libraries/classes/Version.php || echo 'Not found or old version'")
        run_cmd(ssh, f"test -f {INSTALL_DIR}/ChangeLog && head -3 {INSTALL_DIR}/ChangeLog || echo 'No ChangeLog'")

        print(f"\n[3/8] Installing unzip if not present...")
        run_cmd(ssh, "apt-get install -y unzip wget 2>/dev/null || yum install -y unzip wget 2>/dev/null")

        print(f"\n[4/8] Downloading phpMyAdmin {PHPMYADMIN_VERSION}...")
        run_cmd(ssh, f"rm -rf {TEMP_DIR} && mkdir -p {TEMP_DIR}")
        code, _, _ = run_cmd(ssh, f"wget -q '{PHPMYADMIN_URL}' -O {TEMP_DIR}/phpmyadmin.zip")
        if code != 0:
            print("  ERROR: Download failed!")
            return

        print(f"\n[5/8] Extracting...")
        run_cmd(ssh, f"cd {TEMP_DIR} && unzip -q phpmyadmin.zip")

        print(f"\n[6/8] Backing up current phpMyAdmin config...")
        run_cmd(ssh, f"test -f {INSTALL_DIR}/config.inc.php && cp {INSTALL_DIR}/config.inc.php {TEMP_DIR}/config.inc.php.bak || echo 'No existing config'")
        run_cmd(ssh, f"test -d {INSTALL_DIR} && mv {INSTALL_DIR} {INSTALL_DIR}.old.$(date +%Y%m%d) || echo 'No existing install'")

        print(f"\n[7/8] Installing new version...")
        run_cmd(ssh, f"mv {TEMP_DIR}/phpMyAdmin-{PHPMYADMIN_VERSION}-all-languages {INSTALL_DIR}")
        run_cmd(ssh, f"test -f {TEMP_DIR}/config.inc.php.bak && cp {TEMP_DIR}/config.inc.php.bak {INSTALL_DIR}/config.inc.php || echo 'No config to restore'")
        run_cmd(ssh, f"mkdir -p {INSTALL_DIR}/tmp && chmod 777 {INSTALL_DIR}/tmp")
        run_cmd(ssh, f"chown -R www-data:www-data {INSTALL_DIR} 2>/dev/null || chown -R apache:apache {INSTALL_DIR} 2>/dev/null")

        print(f"\n[8/8] Verifying installation...")
        run_cmd(ssh, f"grep -oP \"VERSION = '\\K[^']+\" {INSTALL_DIR}/libraries/classes/Version.php 2>/dev/null || echo 'Version check failed'")

        print(f"\n[9/8] Checking Apache/Nginx config for phpMyAdmin...")
        run_cmd(ssh, "test -f /etc/apache2/conf-available/phpmyadmin.conf && echo 'Apache config exists' || echo 'No Apache config'")
        run_cmd(ssh, "test -f /etc/nginx/snippets/phpmyadmin.conf && echo 'Nginx config exists' || echo 'No Nginx config'")

        code, out, _ = run_cmd(ssh, "test -f /etc/apache2/conf-available/phpmyadmin.conf && cat /etc/apache2/conf-available/phpmyadmin.conf | head -5 || echo 'skip'")
        if 'skip' in out:
            print("\n  Creating Apache alias config...")
            apache_conf = f"""Alias /phpmyadmin {INSTALL_DIR}
<Directory {INSTALL_DIR}>
    Options SymLinksIfOwnerMatch
    DirectoryIndex index.php
    AllowOverride All
    Require all granted
</Directory>"""
            run_cmd(ssh, f"echo '{apache_conf}' > /etc/apache2/conf-available/phpmyadmin.conf")
            run_cmd(ssh, "a2enconf phpmyadmin 2>/dev/null")
            run_cmd(ssh, "systemctl reload apache2 2>/dev/null || service apache2 reload 2>/dev/null")

        print(f"\n  Cleaning up...")
        run_cmd(ssh, f"rm -rf {TEMP_DIR}")

        print(f"\n{'='*60}")
        print(f"  phpMyAdmin {PHPMYADMIN_VERSION} installed successfully!")
        print(f"  Access: http://{HOST}/phpmyadmin")
        print(f"{'='*60}")

    except Exception as e:
        print(f"\nERROR: {e}")
    finally:
        ssh.close()

if __name__ == '__main__':
    main()
