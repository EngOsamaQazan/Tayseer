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
    chan.close()
    return exit_code, out, err

def check(ssh, label, cmd):
    code, out, err = run_cmd(ssh, cmd)
    result = out.strip() if out.strip() else err.strip()
    print(f"  {label}: {result}", flush=True)
    return code, out, err

def main():
    print(f"Connecting to {HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    for site in SITES:
        name = site.split('/')[-1]
        print(f"{'='*60}", flush=True)
        print(f"  {name}", flush=True)
        print(f"{'='*60}", flush=True)

        # 1. Check index.php - YII_DEBUG and YII_ENV
        print("\n  --- index.php settings ---", flush=True)
        check(ssh, "backend/web/index.php",
              f"grep -E 'YII_DEBUG|YII_ENV' {site}/backend/web/index.php")

        # 2. Check main-local.php - debug/gii
        print("\n  --- main-local.php ---", flush=True)
        check(ssh, "debug/gii guard",
              f"grep -c 'class_exists' {site}/backend/config/main-local.php")

        # 3. Check common/config for cache settings
        print("\n  --- Cache config ---", flush=True)
        check(ssh, "cache component",
              f"grep -A2 'cache' {site}/common/config/main.php | head -5")

        # 4. Check PHP error display settings
        print("\n  --- PHP error display ---", flush=True)
        check(ssh, "display_errors",
              f"php -r \"echo ini_get('display_errors');\"")
        check(ssh, "error_reporting",
              f"php -r \"echo ini_get('error_reporting');\"")

        # 5. Check PHP 8.5 opcache
        print("\n  --- OPcache ---", flush=True)
        check(ssh, "opcache.enable",
              f"php -r \"echo ini_get('opcache.enable');\"")

        # 6. Check for PHP errors in logs
        print("\n  --- Recent PHP errors (last 5) ---", flush=True)
        _, out, _ = run_cmd(ssh,
            f"find {site}/backend/runtime/logs/ -name '*.log' -mtime -1 2>/dev/null | head -1")
        if out.strip():
            logfile = out.strip()
            _, errors, _ = run_cmd(ssh, f"grep -i 'error\\|fatal\\|deprecated' {logfile} 2>/dev/null | tail -5")
            if errors.strip():
                for line in errors.strip().split('\n')[:5]:
                    try:
                        print(f"    {line[:150]}", flush=True)
                    except:
                        pass
            else:
                print("    No errors found", flush=True)
        else:
            print("    No recent log files", flush=True)

        # 7. Check Apache error log for this site
        print("\n  --- Apache errors (last 5) ---", flush=True)
        _, out, _ = run_cmd(ssh,
            f"grep -i '{name}' /var/log/apache2/error.log 2>/dev/null | grep -i 'php\\|fatal\\|error' | tail -5")
        if out.strip():
            for line in out.strip().split('\n')[:5]:
                try:
                    print(f"    {line[:150]}", flush=True)
                except:
                    pass
        else:
            print("    No errors found", flush=True)

        # 8. Check git status
        print("\n  --- Git status ---", flush=True)
        check(ssh, "branch", f"cd {site} && git branch --show-current")
        check(ssh, "commit", f"cd {site} && git log -1 --oneline")

        # 9. HTTP response check
        print("\n  --- HTTP check ---", flush=True)
        domain = name
        check(ssh, "status", f"curl -sL -o /dev/null -w '%{{http_code}}' https://{domain}")
        _, out, _ = run_cmd(ssh,
            f"curl -sI https://{domain} 2>/dev/null | grep -iE 'x-powered|server|x-debug'")
        if out.strip():
            print(f"  Headers: {out.strip()}", flush=True)
        else:
            print(f"  Headers: No PHP version exposed (good)", flush=True)

        # 10. Check file permissions
        print("\n  --- Permissions ---", flush=True)
        check(ssh, "runtime writable",
              f"test -w {site}/backend/runtime && echo 'YES' || echo 'NO'")
        check(ssh, "web/assets writable",
              f"test -w {site}/backend/web/assets && echo 'YES' || echo 'NO'")

        print("", flush=True)

    print(f"\n{'='*60}", flush=True)
    print("  SUMMARY OF ISSUES", flush=True)
    print(f"{'='*60}", flush=True)

    # Check the critical issues
    issues = []
    for site in SITES:
        name = site.split('/')[-1]
        _, out, _ = run_cmd(ssh, f"grep 'YII_DEBUG.*true' {site}/backend/web/index.php")
        if 'true' in out:
            issues.append(f"[{name}] YII_DEBUG is TRUE (should be false in production)")
        _, out, _ = run_cmd(ssh, f"grep \"YII_ENV.*'dev'\" {site}/backend/web/index.php")
        if "'dev'" in out:
            issues.append(f"[{name}] YII_ENV is 'dev' (should be 'prod' in production)")

    if issues:
        for issue in issues:
            print(f"  WARNING: {issue}", flush=True)
    else:
        print("  No critical issues found!", flush=True)

    print(f"\n{'='*60}", flush=True)
    print("  DONE", flush=True)
    print(f"{'='*60}", flush=True)
    ssh.close()

if __name__ == '__main__':
    main()
