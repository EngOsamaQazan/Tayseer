#!/usr/bin/env python3
"""
Step 5: Update all deploy scripts to use new server IP and credentials.
Run this AFTER migration is verified and working.

This script finds all Python files in scripts/ that reference the old server
and updates them to use the new server details.
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

import os
import re
import sys

SCRIPTS_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

# ============================================================
# ⚠️  UPDATE THESE
# ============================================================
OLD_HOST = OLD_SERVER_IP
OLD_PASS = OLD_SERVER_PASS

NEW_HOST = 'YOUR_NEW_SERVER_IP'
NEW_PASS = 'YOUR_NEW_SERVER_PASSWORD'
# ============================================================

SKIP_DIRS = {'migration', '__pycache__', '.git'}


def find_scripts():
    """Find all .py files that reference the old server."""
    matches = []
    for root, dirs, files in os.walk(SCRIPTS_DIR):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for f in files:
            if not f.endswith('.py'):
                continue
            path = os.path.join(root, f)
            try:
                with open(path, 'r', encoding='utf-8') as fh:
                    content = fh.read()
                if OLD_HOST in content or OLD_PASS in content:
                    matches.append(path)
            except Exception:
                pass
    return matches


def update_file(path, dry_run=False):
    """Replace old server details with new ones in a file."""
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    original = content

    content = content.replace(OLD_HOST, NEW_HOST)
    content = content.replace(OLD_PASS, NEW_PASS)

    if content == original:
        return False

    if not dry_run:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)

    return True


def main():
    if NEW_HOST == 'YOUR_NEW_SERVER_IP':
        print("ERROR: Update NEW_HOST and NEW_PASS before running!")
        sys.exit(1)

    print("Scanning deploy scripts for old server references...\n")

    scripts = find_scripts()

    if not scripts:
        print("No scripts found with old server references.")
        return

    print(f"Found {len(scripts)} scripts referencing old server:\n")
    for i, path in enumerate(scripts, 1):
        rel = os.path.relpath(path, SCRIPTS_DIR)
        print(f"  {i}. {rel}")

    print(f"\nWill replace:")
    print(f"  IP:   {OLD_HOST} -> {NEW_HOST}")
    print(f"  Pass: {'*' * len(OLD_PASS)} -> {'*' * len(NEW_PASS)}")

    confirm = input("\nProceed with update? (y/n): ").strip().lower()
    if confirm != 'y':
        print("Cancelled.")
        return

    updated = 0
    for path in scripts:
        if update_file(path):
            rel = os.path.relpath(path, SCRIPTS_DIR)
            print(f"  Updated: {rel}")
            updated += 1

    print(f"\n{updated} scripts updated successfully!")
    print("\nDon't forget to also update:")
    print("  - environments/prod*/common/config/main-local.php (DB credentials)")
    print("  - Any CI/CD pipelines")
    print("  - DNS records")


if __name__ == '__main__':
    main()
