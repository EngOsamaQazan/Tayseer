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
import time

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS

SITES = ['/var/www/jadal.aqssat.co', '/var/www/namaa.aqssat.co']

def run_cmd(ssh, cmd, timeout=60):
    print(f"  $ {cmd}", flush=True)
    chan = ssh.get_transport().open_session()
    chan.settimeout(timeout)
    chan.exec_command(cmd)
    out_data = b''
    err_data = b''
    while True:
        if chan.recv_ready():
            out_data += chan.recv(65536)
            continue
        if chan.recv_stderr_ready():
            err_data += chan.recv_stderr(65536)
            continue
        if chan.exit_status_ready():
            while chan.recv_ready():
                out_data += chan.recv(65536)
            while chan.recv_stderr_ready():
                err_data += chan.recv_stderr(65536)
            break
        time.sleep(0.1)
    exit_code = chan.recv_exit_status()
    out = out_data.decode('utf-8', errors='replace')
    err = err_data.decode('utf-8', errors='replace')
    if out.strip():
        for line in out.strip().split('\n')[:50]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if err.strip():
        for line in err.strip().split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    print(f"  -> exit: {exit_code}", flush=True)
    chan.close()
    return exit_code, out, err

def main():
    print(f"Connecting to {HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    # =============================================
    # FIX 1: Set YII_DEBUG=false, YII_ENV='prod'
    # =============================================
    print("=" * 60)
    print("  FIX 1: Set production mode (YII_DEBUG=false, YII_ENV=prod)")
    print("=" * 60, flush=True)

    for site in SITES:
        name = site.split('/')[-1]
        index = f"{site}/backend/web/index.php"
        print(f"\n  [{name}]", flush=True)
        run_cmd(ssh, f"sed -i \"s/define('YII_DEBUG', true)/define('YII_DEBUG', false)/\" {index}")
        run_cmd(ssh, f"sed -i \"s/define('YII_ENV', 'dev')/define('YII_ENV', 'prod')/\" {index}")
        run_cmd(ssh, f"grep -E 'YII_DEBUG|YII_ENV' {index}")

    # =============================================
    # FIX 2: Hide Apache version in headers
    # =============================================
    print("\n" + "=" * 60)
    print("  FIX 2: Hide Apache version in headers")
    print("=" * 60, flush=True)

    run_cmd(ssh, "grep -r 'ServerTokens' /etc/apache2/ 2>/dev/null | head -5")
    run_cmd(ssh, "grep -r 'ServerSignature' /etc/apache2/ 2>/dev/null | head -5")

    run_cmd(ssh, """cat >> /etc/apache2/conf-available/security.conf << 'EOF'

ServerTokens Prod
ServerSignature Off
EOF
""")
    run_cmd(ssh, "a2enconf security 2>&1; true")

    # Also hide PHP version
    run_cmd(ssh, "grep 'expose_php' /etc/php/8.5/apache2/php.ini 2>/dev/null | head -1")
    run_cmd(ssh, "sed -i 's/expose_php = On/expose_php = Off/' /etc/php/8.5/apache2/php.ini 2>/dev/null; true")

    # =============================================
    # FIX 3: Optimize PHP production settings
    # =============================================
    print("\n" + "=" * 60)
    print("  FIX 3: Optimize PHP 8.5 production settings")
    print("=" * 60, flush=True)

    php_ini = "/etc/php/8.5/apache2/php.ini"
    run_cmd(ssh, f"ls {php_ini} 2>&1")

    # display_errors = Off (don't show errors to users)
    run_cmd(ssh, f"sed -i 's/^display_errors = On/display_errors = Off/' {php_ini}")
    # log_errors = On (log them instead)
    run_cmd(ssh, f"sed -i 's/^log_errors = Off/log_errors = On/' {php_ini}")
    # OPcache optimization
    run_cmd(ssh, f"sed -i 's/^;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' {php_ini}")
    run_cmd(ssh, f"sed -i 's/^opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' {php_ini}")
    run_cmd(ssh, f"sed -i 's/^;opcache.memory_consumption=.*/opcache.memory_consumption=256/' {php_ini}")
    run_cmd(ssh, f"sed -i 's/^;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=20000/' {php_ini}")

    # =============================================
    # FIX 4: Flush cache and restart
    # =============================================
    print("\n" + "=" * 60)
    print("  FIX 4: Restart Apache + flush cache")
    print("=" * 60, flush=True)

    run_cmd(ssh, "systemctl restart apache2 2>&1")

    for site in SITES:
        name = site.split('/')[-1]
        run_cmd(ssh, f"cd {site} && php yii cache/flush-all 2>&1")

    # =============================================
    # Verify
    # =============================================
    print("\n" + "=" * 60)
    print("  VERIFY")
    print("=" * 60, flush=True)

    run_cmd(ssh, "systemctl status apache2 2>&1 | head -3")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")
    run_cmd(ssh, "curl -sI https://jadal.aqssat.co 2>/dev/null | grep -iE 'server|x-powered'")

    for site in SITES:
        name = site.split('/')[-1]
        print(f"\n  [{name}]", flush=True)
        run_cmd(ssh, f"grep -E 'YII_DEBUG|YII_ENV' {site}/backend/web/index.php")

    print("\n" + "=" * 60)
    print("  ALL FIXES APPLIED!")
    print("=" * 60, flush=True)
    ssh.close()

if __name__ == '__main__':
    main()
