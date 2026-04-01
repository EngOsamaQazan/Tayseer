import paramiko
import sys
import time
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=120):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('OK')
    print()

# ============================================================
# FIX 1: old.namaa SSL cert (currently using old.jadal cert)
# ============================================================
run("FIX1: Renew old.namaa SSL cert",
    "certbot certonly --apache -d old.namaa.aqssat.co --non-interactive --agree-tos 2>&1")

# ============================================================
# FIX 2: Disable HTTP/2 module (incompatible with prefork MPM)
# ============================================================
run("FIX2: Disable mod_http2",
    "a2dismod http2 2>&1 && echo DISABLED")

# ============================================================
# FIX 3: Fix old.jadal header.php PHP warnings
# ============================================================
fix_header = r"""
sed -i 's/if (!empty($avatar->path))/if (isset($avatar) \&\& !empty($avatar->path))/g' /var/www/old.jadal.aqssat.co/backend/views/layouts/header.php
"""
run("FIX3: Fix old.jadal header.php null $avatar", fix_header.strip())
run("FIX3: Verify fix applied",
    "grep -c 'isset.*avatar' /var/www/old.jadal.aqssat.co/backend/views/layouts/header.php")

# Also fix old.namaa if same issue exists
run("FIX3b: Check old.namaa header",
    "grep -c 'empty.*avatar->path' /var/www/old.namaa.aqssat.co/backend/views/layouts/header.php 2>/dev/null || echo NO_FILE")
fix_namaa = r"""
sed -i 's/if (!empty($avatar->path))/if (isset($avatar) \&\& !empty($avatar->path))/g' /var/www/old.namaa.aqssat.co/backend/views/layouts/header.php 2>/dev/null
"""
run("FIX3b: Fix old.namaa header.php", fix_namaa.strip())

# ============================================================
# FIX 4: Clean orphan Apache log files for deleted projects
# ============================================================
orphan_logs = [
    'jadal2.aqssat.co-access.log',
    'jadal2.aqssat.co-error.log',
    'namaa2.aqssat.co-access.log',
    'namaa2.aqssat.co-error.log',
    'vite.jadal.aqssat.co-access.log',
    'vite.jadal.aqssat.co-error.log',
    'vite.namaa.aqssat.co-access.log',
    'vite.namaa.aqssat.co-error.log',
    'sass_access.log',
    'sass_error.log',
    'aqssat.co-access.log',
    'aqssat.co-error.log',
]
log_files = ' '.join([f'/var/log/apache2/{l}' for l in orphan_logs])
run("FIX4: Remove orphan log files", f"rm -f {log_files} && echo CLEANED")

# ============================================================
# FIX 5: Harden Fail2Ban - Add Apache scanner jails
# ============================================================
fail2ban_config = """
[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
logpath = /var/log/apache2/*error.log
maxretry = 2
bantime = 86400

[apache-noscript]
enabled = true
port = http,https
filter = apache-noscript
logpath = /var/log/apache2/*error.log
maxretry = 3
bantime = 86400

[apache-overflows]
enabled = true
port = http,https
filter = apache-overflows
logpath = /var/log/apache2/*error.log
maxretry = 2
bantime = 86400

[apache-shellshock]
enabled = true
port = http,https
filter = apache-shellshock
logpath = /var/log/apache2/*error.log
maxretry = 1
bantime = 604800
"""

run("FIX5: Create Fail2Ban Apache jails",
    f"cat > /etc/fail2ban/jail.d/apache.conf << 'EOF'{fail2ban_config}EOF")

run("FIX5: Restart Fail2Ban",
    "systemctl restart fail2ban 2>&1 && echo RESTARTED")

run("FIX5: Verify Fail2Ban jails",
    "fail2ban-client status 2>/dev/null")

# ============================================================
# FIX 6: Fix MariaDB env vars warning
# ============================================================
run("FIX6: Fix MariaDB env warning",
    "mkdir -p /etc/systemd/system/mariadb.service.d && "
    "cat > /etc/systemd/system/mariadb.service.d/override.conf << 'EOF'\n"
    "[Service]\n"
    "Environment=\"MYSQLD_OPTS=\"\n"
    "Environment=\"_WSREP_NEW_CLUSTER=\"\n"
    "EOF\n"
    "systemctl daemon-reload 2>&1 && echo FIXED")

# ============================================================
# FINAL: Reload Apache and verify
# ============================================================
run("FINAL: Reload Apache", "systemctl reload apache2 2>&1 && echo RELOADED")
run("FINAL: Test Apache config", "apachectl configtest 2>&1")
run("FINAL: Check for remaining warnings",
    "apachectl configtest 2>&1 | grep -i warn || echo NO_WARNINGS")
run("FINAL: Fail2Ban status", "fail2ban-client status 2>/dev/null")

ssh.close()
print("=== ALL FIXES COMPLETE ===")
