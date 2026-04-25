#!/usr/bin/env python3
"""Fetch production server stats (disk, RAM, CPU, uptime)."""
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

HOST = OLD_SERVER_IP
USER = 'root'
# Password from deploy - consider env var in production
PASSWORD = OLD_SERVER_PASS

def run(ssh, cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASSWORD, timeout=30, banner_timeout=30)
    try:
        print("=== DISK (df -h) ===")
        out, _ = run(ssh, "df -h")
        print(out)
        print("\n=== MEMORY (free -h) ===")
        out, _ = run(ssh, "free -h")
        print(out)
        print("\n=== UPTIME ===")
        out, _ = run(ssh, "uptime")
        print(out)
        print("\n=== CPU (cores) ===")
        out, _ = run(ssh, "nproc")
        print("Cores:", out.strip())
        out, _ = run(ssh, "grep -m1 'model name' /proc/cpuinfo 2>/dev/null || cat /proc/cpuinfo | head -5")
        print(out)
        print("\n=== OS ===")
        out, _ = run(ssh, "cat /etc/os-release 2>/dev/null | head -5")
        print(out)
        print("\n=== DISK (MB, all mounts) ===")
        out, _ = run(ssh, "df -BM --output=source,fstype,size,used,avail,pcent,target | column -t")
        print(out)
    finally:
        ssh.close()

if __name__ == '__main__':
    main()
