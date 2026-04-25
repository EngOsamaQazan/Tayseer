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
import urllib.request
import ssl

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS

SITES = [
    'https://jadal.aqssat.co',
    'https://namaa.aqssat.co',
]

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

print("Checking if sites are reachable via HTTPS...", flush=True)
for url in SITES:
    try:
        req = urllib.request.urlopen(url, timeout=10, context=ctx)
        print(f"  {url} -> HTTP {req.status}", flush=True)
    except Exception as e:
        print(f"  {url} -> ERROR: {e}", flush=True)

print(f"\nTrying SSH connection to {HOST}...", flush=True)
for attempt in range(10):
    try:
        print(f"  Attempt {attempt + 1}/10...", flush=True)
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(HOST, username=USER, password=PASS, timeout=15)
        print("  Connected!", flush=True)
        
        stdin, stdout, stderr = ssh.exec_command("uptime")
        print(f"  uptime: {stdout.read().decode().strip()}", flush=True)
        
        stdin, stdout, stderr = ssh.exec_command("cat /etc/os-release | grep PRETTY")
        print(f"  os: {stdout.read().decode().strip()}", flush=True)
        
        ssh.close()
        break
    except Exception as e:
        print(f"  Failed: {e}", flush=True)
        if attempt < 9:
            print(f"  Waiting 15 seconds...", flush=True)
            time.sleep(15)
        else:
            print("  Could not connect after 10 attempts.", flush=True)
