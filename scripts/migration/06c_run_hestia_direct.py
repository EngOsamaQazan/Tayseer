#!/usr/bin/env python3
"""
Run HestiaCP installer directly via SSH.
Fixes Windows line endings, patches OS identity, patches conflict check.
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
import time
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS

SCRIPT_LOCAL = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'hestia_remote_install.sh')


def run(ssh, cmd, timeout=120):
    print(f"  $ {cmd[:180]}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if out:
        for line in out.split('\n')[:25]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out


def main():
    print(f"\nConnecting to {NEW_HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    ssh.get_transport().set_keepalive(30)
    print("Connected!\n", flush=True)

    # Cleanup
    print("=" * 60)
    print("  CLEANUP")
    print("=" * 60, flush=True)
    run(ssh, "pkill -9 -f 'hst-install' 2>/dev/null; true")
    run(ssh, "pkill -9 -f 'run_hestia' 2>/dev/null; true")
    run(ssh, "sleep 2")
    run(ssh, "rm -rf /usr/local/hestia /root/hst-install-debian.sh 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/hestia.list /etc/apt/sources.list.d/nginx.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/mariadb.list /etc/apt/sources.list.d/apache2.list 2>/dev/null; true")

    # Patch OS
    print("\n" + "=" * 60)
    print("  PATCH OS -> Debian 12")
    print("=" * 60, flush=True)
    _, ver = run(ssh, "cat /etc/debian_version")
    if '12' not in ver:
        run(ssh, "cp /etc/debian_version /etc/debian_version.bak.trixie 2>/dev/null; true")
        run(ssh, "cp /etc/os-release /etc/os-release.bak.trixie 2>/dev/null; true")
        run(ssh, "echo '12.0' > /etc/debian_version")
        run(ssh, '''sed -i 's/VERSION_ID="13"/VERSION_ID="12"/' /etc/os-release''')
        run(ssh, '''sed -i 's/VERSION="13 (trixie)"/VERSION="12 (bookworm)"/' /etc/os-release''')
        run(ssh, '''sed -i 's/VERSION_CODENAME=trixie/VERSION_CODENAME=bookworm/' /etc/os-release''')
        run(ssh, '''sed -i 's/trixie/bookworm/g' /etc/os-release''')
    run(ssh, "cat /etc/debian_version && grep VERSION_CODENAME /etc/os-release")

    # Upload script with Unix line endings
    print("\n" + "=" * 60)
    print("  UPLOAD SCRIPT (with Unix line endings)")
    print("=" * 60, flush=True)
    with open(SCRIPT_LOCAL, 'r') as f:
        content = f.read()
    content_unix = content.replace('\r\n', '\n').replace('\r', '\n')
    sftp = ssh.open_sftp()
    with sftp.open('/root/run_hestia_install.sh', 'w') as remote_f:
        remote_f.write(content_unix)
    sftp.close()
    run(ssh, "chmod +x /root/run_hestia_install.sh")
    run(ssh, "file /root/run_hestia_install.sh")
    print("  Uploaded with Unix line endings!", flush=True)

    # Run directly with long timeout
    print("\n" + "=" * 60)
    print("  RUNNING INSTALLER (15-25 minutes, please wait)")
    print("=" * 60, flush=True)

    start_time = time.time()
    print(f"  Started at: {time.strftime('%H:%M:%S')}", flush=True)
    code, out = run(ssh, "bash /root/run_hestia_install.sh", timeout=2400)
    elapsed = int(time.time() - start_time)
    print(f"\n  Finished in {elapsed // 60}m {elapsed % 60}s (exit code: {code})", flush=True)

    # Restore OS identity
    print("\n" + "=" * 60)
    print("  RESTORE OS IDENTITY")
    print("=" * 60, flush=True)
    run(ssh, "test -f /etc/debian_version.bak.trixie && cp /etc/debian_version.bak.trixie /etc/debian_version; true")
    run(ssh, "test -f /etc/os-release.bak.trixie && cp /etc/os-release.bak.trixie /etc/os-release; true")
    run(ssh, "cat /etc/debian_version")

    # Results
    print("\n" + "=" * 60)
    print("  RESULTS")
    print("=" * 60, flush=True)
    run(ssh, "tail -30 /root/hestia_install.log 2>/dev/null")

    # Services
    print("\n  Services:", flush=True)
    for svc in ['hestia', 'apache2', 'mariadb']:
        _, status = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        state = "RUNNING" if status.strip() == 'active' else "NOT RUNNING"
        print(f"    {svc}: {state}", flush=True)

    run(ssh, "ss -tlnp | grep :8083 || echo 'Port 8083 NOT listening'")

    print("\n" + "=" * 60)
    print(f"  Panel:    https://{NEW_HOST}:8083")
    print(f"  User:     admin")
    print(f"  Password: {HESTIA_PASS}")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
