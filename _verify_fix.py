import paramiko
import sys
import urllib.request
import ssl
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[NONE]')
    print()

# Clear Yii2 runtime cache if needed (opcache)
run("Clear OPcache on jadal",
    "php -r \"if(function_exists('opcache_reset')) opcache_reset();\" 2>/dev/null; echo done")

# Test followUp/panel endpoint via curl (check HTTP status)
run("Test JADAL followUp/panel (HTTP status)",
    "curl -s -o /dev/null -w '%{http_code}' -k --cookie 'advanced-backend=test' 'https://jadal.aqssat.co/followUp/panel?contract_id=3807' 2>/dev/null || echo 'curl failed'")

# Check if new errors appeared after fix
run("New errors in jadal app.log (last 5 lines)",
    "tail -5 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null")

# === NOW CHECK ALL OTHER ERRORS FROM ALL PROJECTS TODAY ===

# Apache error log today
run("Apache main error.log today",
    "grep 'Fri Mar 28' /var/log/apache2/error.log 2>/dev/null | grep -v 'AH01630\\|AH02032\\|AH01797\\|resumption\\|does not exist' | head -15")

# System journal errors today
run("System journal errors today",
    "journalctl --since '2026-03-28 00:00:00' --until '2026-03-28 23:59:59' -p err --no-pager 2>/dev/null | tail -15")

# System journal warnings today
run("System journal warnings today",
    "journalctl --since '2026-03-28 00:00:00' --until '2026-03-28 23:59:59' -p warning --no-pager 2>/dev/null | grep -v 'journal\\|pam_unix\\|session opened\\|session closed\\|New session\\|Removed session' | tail -15")

# MariaDB errors today
run("MariaDB log today",
    "grep '2026-03-28' /var/log/mysql/error.log 2>/dev/null | grep -i 'error\\|warning' | head -10")

# Fail2Ban today
run("Fail2Ban actions today",
    "grep '2026-03-28' /var/log/fail2ban.log 2>/dev/null | grep -i 'ban\\|error' | tail -15")

# Certbot/SSL issues
run("Certbot log today",
    "grep '2026-03-28' /var/log/letsencrypt/letsencrypt.log 2>/dev/null | grep -i 'error\\|fail' | head -5")

# Apache access log - 500 errors today
run("HTTP 500 errors today (all projects)",
    """for f in /var/log/apache2/*access*.log; do
        count=$(grep -c '" 500 ' "$f" 2>/dev/null)
        if [ "$count" -gt "0" ]; then
            name=$(basename "$f")
            echo "$name: $count x 500 errors"
            grep '" 500 ' "$f" 2>/dev/null | tail -3
            echo "---"
        fi
    done""")

# PHP-FPM errors today
run("PHP-FPM errors today",
    "journalctl -u php*-fpm --since '2026-03-28 00:00:00' -p err --no-pager 2>/dev/null | tail -10")

ssh.close()
