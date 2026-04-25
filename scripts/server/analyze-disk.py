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

def run_cmd(ssh, cmd, timeout=30):
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
    chan.close()
    return out.strip()

def show(ssh, label, cmd):
    result = run_cmd(ssh, cmd)
    print(f"  {label}:", flush=True)
    if result:
        for line in result.split('\n')[:30]:
            print(f"    {line}", flush=True)
    else:
        print(f"    (empty)", flush=True)
    return result

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)

    print("=" * 60)
    print("  DISK USAGE ANALYSIS")
    print("=" * 60, flush=True)

    print("\n--- Top-level /var/www sizes ---", flush=True)
    show(ssh, "Each site", "du -sh /var/www/*/ 2>/dev/null | sort -rh")

    print("\n--- Largest dirs on disk ---", flush=True)
    show(ssh, "Top 15", "du -sh /* 2>/dev/null | sort -rh | head -15")

    print("\n--- /var/log size ---", flush=True)
    show(ssh, "Logs", "du -sh /var/log/ 2>/dev/null")
    show(ssh, "Largest logs", "find /var/log -type f -size +10M 2>/dev/null -exec ls -lh {} \\; | awk '{print $5, $9}' | sort -rh | head -10")

    print("\n--- apt cache ---", flush=True)
    show(ssh, "Cache size", "du -sh /var/cache/apt/ 2>/dev/null")

    print("\n--- Old PHP versions ---", flush=True)
    show(ssh, "PHP packages", "dpkg -l 'php*' 2>/dev/null | grep '^ii' | awk '{print $2}' | grep -E 'php[0-9]' | sort")

    print("\n--- /tmp size ---", flush=True)
    show(ssh, "tmp", "du -sh /tmp/ 2>/dev/null")

    print("\n--- Journal logs ---", flush=True)
    show(ssh, "Journal", "journalctl --disk-usage 2>/dev/null")

    print("\n--- Runtime/cache inside sites ---", flush=True)
    for site in ['jadal.aqssat.co', 'namaa.aqssat.co', 'old.jadal.aqssat.co', 'old.namaa.aqssat.co', 'staging.aqssat.co', 'new_staging.aqssat.co']:
        path = f"/var/www/{site}"
        result = run_cmd(ssh, f"test -d {path} && du -sh {path}/backend/runtime/ {path}/frontend/runtime/ {path}/backend/web/assets/ {path}/vendor/ 2>/dev/null || echo 'N/A'")
        print(f"  [{site}]: {result}", flush=True)

    print("\n--- Vendor duplication ---", flush=True)
    show(ssh, "All vendor dirs", "find /var/www -maxdepth 3 -name vendor -type d 2>/dev/null -exec du -sh {} \\;")

    print("\n--- Are old/staging sites actually used? ---", flush=True)
    for site in ['old.jadal.aqssat.co', 'old.namaa.aqssat.co', 'staging.aqssat.co', 'new_staging.aqssat.co']:
        path = f"/var/www/{site}"
        result = run_cmd(ssh, f"test -d {path}/backend/runtime/logs && find {path}/backend/runtime/logs -name '*.log' -mtime -7 2>/dev/null | head -3 || echo 'No recent logs'")
        print(f"  [{site}] recent activity: {result if result else 'None'}", flush=True)

    print("\n--- Micro services ---", flush=True)
    show(ssh, "micro_services", "du -sh /var/www/micro_services/ 2>/dev/null")
    show(ssh, "Contents", "ls /var/www/micro_services/ 2>/dev/null")

    print("\n--- Database sizes ---", flush=True)
    show(ssh, "DB sizes", """mysql -e "SELECT table_schema AS db, ROUND(SUM(data_length+index_length)/1024/1024,1) AS size_mb FROM information_schema.TABLES WHERE table_schema NOT IN ('information_schema','performance_schema','mysql','sys') GROUP BY table_schema ORDER BY size_mb DESC;" 2>/dev/null""")

    print("\n" + "=" * 60)
    print("  DONE")
    print("=" * 60, flush=True)
    ssh.close()

if __name__ == '__main__':
    main()
