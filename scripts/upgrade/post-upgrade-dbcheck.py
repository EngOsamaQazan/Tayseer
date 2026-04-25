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

def run_cmd(ssh, cmd, timeout=300):
    print(f"\n  $ {cmd}", flush=True)
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
        for line in out.strip().split('\n')[:60]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if err.strip():
        for line in err.strip().split('\n')[:20]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    print(f"  -> exit: {exit_code}", flush=True)
    chan.close()
    return exit_code, out, err

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    # Try sudo mysql (MariaDB 10.11 uses unix_socket auth for root)
    print("=" * 60)
    print("  DATABASE ACCESS CHECK")
    print("=" * 60, flush=True)

    run_cmd(ssh, "sudo mysql -e 'SHOW DATABASES;' 2>&1")
    run_cmd(ssh, "sudo mysql -e 'SELECT COUNT(*) as jadal_tables FROM information_schema.tables WHERE table_schema=\"jadal\";' 2>&1")
    run_cmd(ssh, "sudo mysql -e 'SELECT COUNT(*) as namaa_tables FROM information_schema.tables WHERE table_schema=\"namaa\";' 2>&1")

    # Check MariaDB upgrade status
    run_cmd(ssh, "mariadb-upgrade --check-only 2>&1 | tail -10")

    # Run mariadb-upgrade if needed
    print("\n--- Running mariadb-upgrade ---", flush=True)
    run_cmd(ssh, "sudo mariadb-upgrade 2>&1 | tail -20", timeout=120)

    # Verify Yii2 app DB connections work
    print("\n--- Testing Yii2 DB connections ---", flush=True)
    run_cmd(ssh, "cd /var/www/jadal.aqssat.co && php yii migrate/history 5 2>&1 | tail -10")
    run_cmd(ssh, "cd /var/www/namaa.aqssat.co && php yii migrate/history 5 2>&1 | tail -10")

    # Check kernel version (should be Debian 12 kernel now)
    print("\n" + "=" * 60)
    print("  KERNEL & SECURITY")
    print("=" * 60, flush=True)
    
    run_cmd(ssh, "uname -r")
    run_cmd(ssh, "ufw status numbered 2>&1")
    run_cmd(ssh, "fail2ban-client status sshd 2>&1")

    # Check if there are any failed services
    print("\n--- Failed services ---", flush=True)
    run_cmd(ssh, "systemctl --failed 2>&1")

    # Check listening ports
    print("\n--- Listening ports ---", flush=True)
    run_cmd(ssh, "ss -tlnp 2>&1")

    print("\n" + "=" * 60)
    print("  ALL CHECKS DONE!")
    print("=" * 60, flush=True)

    ssh.close()

if __name__ == '__main__':
    main()
