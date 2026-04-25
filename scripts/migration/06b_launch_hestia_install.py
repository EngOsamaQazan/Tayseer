#!/usr/bin/env python3
"""
Launch patched HestiaCP installer on server via nohup.
Patches: skips OS check (uses bookworm identity), skips conflict check.
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


def run(ssh, cmd, timeout=60):
    print(f"  $ {cmd[:180]}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if out:
        for line in out.split('\n')[:25]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if code != 0 and err:
        for line in err.split('\n')[:5]:
            try:
                print(f"    [err] {line}", flush=True)
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

    # Kill old processes
    print("=" * 60)
    print("  CLEANUP: Kill old installer processes")
    print("=" * 60, flush=True)
    run(ssh, "pkill -9 -f 'hst-install' 2>/dev/null; sleep 1; true")
    run(ssh, "pkill -9 -f 'run_hestia' 2>/dev/null; sleep 1; true")
    run(ssh, "rm -rf /usr/local/hestia /root/hst-install-debian.sh 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/hestia.list /etc/apt/sources.list.d/nginx.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/mariadb.list /etc/apt/sources.list.d/apache2.list 2>/dev/null; true")
    run(ssh, "rm -f /etc/apt/sources.list.d/php.list 2>/dev/null; true")
    run(ssh, "apt-get update -qq 2>/dev/null; true")

    # Verify OS patch
    print("\n" + "=" * 60)
    print("  PATCH: OS identity -> Debian 12 Bookworm")
    print("=" * 60, flush=True)
    _, ver = run(ssh, "cat /etc/debian_version")
    if '12' not in ver:
        print("  Applying OS patch...", flush=True)
        run(ssh, "cp /etc/debian_version /etc/debian_version.bak.trixie 2>/dev/null; true")
        run(ssh, "cp /etc/os-release /etc/os-release.bak.trixie 2>/dev/null; true")
        run(ssh, "echo '12.0' > /etc/debian_version")
        sed_cmds = [
            "sed -i 's/VERSION_ID=\"13\"/VERSION_ID=\"12\"/' /etc/os-release",
            "sed -i 's/VERSION=\"13 (trixie)\"/VERSION=\"12 (bookworm)\"/' /etc/os-release",
            "sed -i 's/VERSION_CODENAME=trixie/VERSION_CODENAME=bookworm/' /etc/os-release",
            "sed -i 's/trixie/bookworm/g' /etc/os-release",
        ]
        for cmd in sed_cmds:
            run(ssh, cmd)
    run(ssh, "cat /etc/debian_version")
    run(ssh, "grep VERSION_CODENAME /etc/os-release")

    # Upload script
    print("\n" + "=" * 60)
    print("  UPLOAD: Install script")
    print("=" * 60, flush=True)
    sftp = ssh.open_sftp()
    sftp.put(SCRIPT_LOCAL, '/root/run_hestia_install.sh')
    sftp.close()
    run(ssh, "chmod +x /root/run_hestia_install.sh")
    print("  Script uploaded!", flush=True)

    # Launch installer detached
    print("\n" + "=" * 60)
    print("  LAUNCH: Starting installer in background")
    print("=" * 60, flush=True)
    channel = ssh.get_transport().open_session()
    channel.exec_command("nohup bash /root/run_hestia_install.sh > /root/hestia_nohup.log 2>&1 < /dev/null &")
    time.sleep(5)

    _, count = run(ssh, "ps aux | grep run_hestia | grep -v grep | wc -l")
    if count.strip() != '0':
        print("\n  Installer is RUNNING!", flush=True)
    else:
        print("\n  Checking if it already finished...", flush=True)

    _, log = run(ssh, "cat /root/hestia_install.log 2>/dev/null | tail -5")

    # Monitor
    print("\n" + "=" * 60)
    print("  MONITORING: Checking progress every 60 seconds")
    print("=" * 60, flush=True)

    last_line_count = 0
    for i in range(40):
        time.sleep(60)

        _, log_tail = run(ssh, "tail -3 /root/hestia_install.log 2>/dev/null")
        _, lines = run(ssh, "wc -l < /root/hestia_install.log 2>/dev/null")
        current_count = int(lines.strip()) if lines.strip().isdigit() else 0

        print(f"\n  [{i+1} min] Log lines: {current_count} (+{current_count - last_line_count})", flush=True)
        last_line_count = current_count

        if 'finished with exit code' in log_tail:
            print("\n  Installation COMPLETED!", flush=True)
            break

        _, running = run(ssh, "ps aux | grep -E 'run_hestia|hst-install' | grep -v grep | wc -l")
        if running.strip() == '0':
            print(f"\n  Process ended at minute {i+1}.", flush=True)
            break

    # Results
    print("\n" + "=" * 60)
    print("  RESULTS")
    print("=" * 60, flush=True)
    run(ssh, "tail -40 /root/hestia_install.log 2>/dev/null")

    # Restore OS identity
    print("\n  Restoring OS identity...", flush=True)
    run(ssh, "test -f /etc/debian_version.bak.trixie && cp /etc/debian_version.bak.trixie /etc/debian_version; true")
    run(ssh, "test -f /etc/os-release.bak.trixie && cp /etc/os-release.bak.trixie /etc/os-release; true")
    run(ssh, "cat /etc/debian_version")

    # Service check
    print("\n  Service status:", flush=True)
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
