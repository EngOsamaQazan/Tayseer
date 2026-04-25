"""
Tayseer Project Credentials — TEMPLATE
========================================
Copy this file to `scripts/credentials.py` and fill in the real values.
`scripts/credentials.py` is git-ignored and must NEVER be committed.

All cloud / deploy / migration / backup scripts under `scripts/` import
from `credentials.py`, so every value below has to be defined.
"""

# === GoDaddy API ============================================================
GODADDY_API_KEY     = ''   # from https://developer.godaddy.com/keys
GODADDY_API_SECRET  = ''
GODADDY_DOMAIN      = ''   # e.g. 'example.com'

# === New Server (Contabo / production target) ==============================
NEW_SERVER_IP   = ''       # e.g. '203.0.113.10'
NEW_SERVER_USER = 'root'
NEW_SERVER_PASS = ''

# === Old Server (OVH / legacy origin) ======================================
OLD_SERVER_IP   = ''
OLD_SERVER_USER = 'root'
OLD_SERVER_PASS = ''

# === HestiaCP control panel ================================================
HESTIA_URL  = f'https://{NEW_SERVER_IP}:8083' if NEW_SERVER_IP else ''
HESTIA_USER = 'admin'
HESTIA_PASS = ''

# === Application database (target) =========================================
DB_USER = 'tayseer_db'
DB_PASS = ''

# === Legacy DB credentials (used by backup.py for the old server) ==========
LEGACY_DB_USER = 'osama'
LEGACY_DB_PASS = ''

# === GitHub deploy token ===================================================
# Personal Access Token (classic) with repo:read scope used by
# scripts/setup_server_git.py to bootstrap `git pull` deploys on the server.
GH_TOKEN = ''
