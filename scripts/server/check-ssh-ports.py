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

import socket
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = OLD_SERVER_IP
COMMON_SSH_PORTS = [22, 2222, 2022, 8022, 222, 2200, 22222]

print(f"Scanning common SSH ports on {HOST}...", flush=True)
for port in COMMON_SSH_PORTS:
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(3)
        result = s.connect_ex((HOST, port))
        if result == 0:
            print(f"  Port {port}: OPEN", flush=True)
        else:
            print(f"  Port {port}: closed", flush=True)
        s.close()
    except Exception as e:
        print(f"  Port {port}: error - {e}", flush=True)

print("\nChecking other common ports...", flush=True)
for port in [80, 443, 3306]:
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(3)
        result = s.connect_ex((HOST, port))
        if result == 0:
            print(f"  Port {port}: OPEN", flush=True)
        else:
            print(f"  Port {port}: closed", flush=True)
        s.close()
    except Exception as e:
        print(f"  Port {port}: error - {e}", flush=True)
