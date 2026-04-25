#!/usr/bin/env python3
"""
Step 1: Setup new Contabo server with all required software.
Installs: PHP 8.3+, MariaDB, Apache, Composer, UFW, Fail2ban, phpMyAdmin, Swap
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
import sys
import time
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS

DB_USER = DB_USER
DB_PASS = DB_PASS

DATABASES = [
    'namaa_erp', 'namaa_jadal', 'staging', 'namaa_khaldon',
    'fahras_db', 'access_db', 'tenanttenantJadel', 'dictionary',
    'baseel', 'ahwal', 'erb_digram', 'sass', 'tazej_food', 'bugzilla',
]


def run(ssh, cmd, timeout=600, show=True):
    if show:
        print(f"  $ {cmd[:150]}{'...' if len(cmd) > 150 else ''}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if show and out:
        for line in out.split('\n')[:30]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if code != 0 and err:
        for line in err.split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out, err


def main():
    print(f"Connecting to new server {NEW_HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    # Phase 1: System update
    print("=" * 60)
    print("  PHASE 1: System Update")
    print("=" * 60, flush=True)
    run(ssh, "apt update && apt upgrade -y", timeout=600)
    run(ssh, "apt install -y curl wget gnupg2 software-properties-common apt-transport-https ca-certificates lsb-release unzip git sshpass")

    # Phase 2: PHP 8.3 + extensions (via Sury repo for Debian 13)
    print("\n" + "=" * 60)
    print("  PHASE 2: PHP 8.3 + Extensions")
    print("=" * 60, flush=True)

    run(ssh, "php -v 2>/dev/null && echo 'PHP already installed' || echo 'PHP not found'")

    code, out, _ = run(ssh, "php -v 2>/dev/null | head -1")
    if 'PHP' not in out:
        run(ssh, "curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb && dpkg -i /tmp/debsuryorg-archive-keyring.deb")
        run(ssh, 'sh -c \'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list\'')
        run(ssh, "apt update")

    run(ssh, " ".join([
        "apt install -y",
        "php8.3 php8.3-cli php8.3-common php8.3-mysql php8.3-xml",
        "php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl",
        "php8.3-bcmath php8.3-soap php8.3-readline php8.3-bz2",
        "php8.3-opcache libapache2-mod-php8.3"
    ]), timeout=300)

    # Phase 3: Apache
    print("\n" + "=" * 60)
    print("  PHASE 3: Apache Configuration")
    print("=" * 60, flush=True)
    run(ssh, "apt install -y apache2")
    run(ssh, "a2enmod rewrite headers ssl php8.3 2>/dev/null; true")
    run(ssh, "a2dismod mpm_event 2>/dev/null; a2enmod mpm_prefork 2>/dev/null; true")

    php_ini = "/etc/php/8.3/apache2/php.ini"
    run(ssh, f"""
test -f {php_ini} && (
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' {php_ini} ;
sed -i 's/post_max_size = .*/post_max_size = 25M/' {php_ini} ;
sed -i 's/memory_limit = .*/memory_limit = 256M/' {php_ini} ;
sed -i 's/max_execution_time = .*/max_execution_time = 120/' {php_ini} ;
sed -i 's/expose_php = .*/expose_php = Off/' {php_ini} ;
sed -i 's/display_errors = .*/display_errors = Off/' {php_ini} ;
sed -i 's/;opcache.enable=.*/opcache.enable=1/' {php_ini} ;
sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=256/' {php_ini} ;
sed -i 's/;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=20000/' {php_ini} ;
sed -i 's/;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' {php_ini}
) || echo 'php.ini not found at {php_ini}, will configure later'
""")

    run(ssh, """
grep -q 'ServerTokens Prod' /etc/apache2/conf-available/security.conf 2>/dev/null || (
echo 'ServerTokens Prod' >> /etc/apache2/conf-available/security.conf ;
echo 'ServerSignature Off' >> /etc/apache2/conf-available/security.conf
)
a2enconf security 2>/dev/null; true
""")

    # Phase 4: MariaDB
    print("\n" + "=" * 60)
    print("  PHASE 4: MariaDB Installation")
    print("=" * 60, flush=True)
    run(ssh, "apt install -y mariadb-server mariadb-client", timeout=300)
    run(ssh, "systemctl enable mariadb && systemctl start mariadb")

    db_cmds = []
    for db in DATABASES:
        db_cmds.append(f"CREATE DATABASE IF NOT EXISTS `{db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
        db_cmds.append(f"GRANT ALL PRIVILEGES ON `{db}`.* TO '{DB_USER}'@'localhost';")

    run(ssh, f"""mysql -u root -e "
CREATE USER IF NOT EXISTS '{DB_USER}'@'localhost' IDENTIFIED BY '{DB_PASS}';
{' '.join(db_cmds)}
FLUSH PRIVILEGES;
" """)

    # Phase 5: Composer
    print("\n" + "=" * 60)
    print("  PHASE 5: Composer Installation")
    print("=" * 60, flush=True)
    run(ssh, "curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer")
    run(ssh, "composer --version")

    # Phase 6: Swap (2GB)
    print("\n" + "=" * 60)
    print("  PHASE 6: Swap Space (2GB)")
    print("=" * 60, flush=True)
    run(ssh, """
swapon --show | grep -q '/swapfile' && echo 'Swap already exists' || (
  fallocate -l 2G /swapfile &&
  chmod 600 /swapfile &&
  mkswap /swapfile &&
  swapon /swapfile &&
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab &&
  sysctl vm.swappiness=10 &&
  grep -q 'vm.swappiness' /etc/sysctl.conf || echo 'vm.swappiness=10' >> /etc/sysctl.conf
)
""")

    # Phase 7: Firewall + Fail2ban
    print("\n" + "=" * 60)
    print("  PHASE 7: Security (UFW + Fail2ban)")
    print("=" * 60, flush=True)
    run(ssh, "apt install -y ufw fail2ban")
    run(ssh, "ufw allow 22 && ufw allow 80 && ufw allow 443 && ufw --force enable")

    run(ssh, """cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
backend = systemd

[sshd]
enabled = true
port = 22
filter = sshd
maxretry = 3
bantime = 86400
EOF
""")
    run(ssh, "systemctl enable fail2ban && systemctl restart fail2ban")

    # Phase 8: Create ALL site directories
    print("\n" + "=" * 60)
    print("  PHASE 8: Site Directories")
    print("=" * 60, flush=True)
    sites = [
        'jadal.aqssat.co', 'namaa.aqssat.co',
        'old.jadal.aqssat.co', 'old.namaa.aqssat.co',
        'fahras.aqssat.co',
        'vite.jadal.aqssat.co', 'vite.namaa.aqssat.co',
    ]
    for site in sites:
        run(ssh, f"mkdir -p /var/www/{site}")
    run(ssh, "mkdir -p /var/www/micro_services")
    run(ssh, "chown -R www-data:www-data /var/www/")

    # Phase 9: Certbot
    print("\n" + "=" * 60)
    print("  PHASE 9: Certbot (SSL)")
    print("=" * 60, flush=True)
    run(ssh, "apt install -y certbot python3-certbot-apache")

    # Phase 10: phpMyAdmin
    print("\n" + "=" * 60)
    print("  PHASE 10: phpMyAdmin")
    print("=" * 60, flush=True)
    run(ssh, """
cd /tmp &&
wget -q https://files.phpmyadmin.net/phpMyAdmin/5.2.3/phpMyAdmin-5.2.3-all-languages.tar.gz &&
tar xzf phpMyAdmin-5.2.3-all-languages.tar.gz &&
rm -rf /usr/share/phpmyadmin &&
mv phpMyAdmin-5.2.3-all-languages /usr/share/phpmyadmin &&
mkdir -p /usr/share/phpmyadmin/tmp &&
chmod 777 /usr/share/phpmyadmin/tmp &&
rm -f /tmp/phpMyAdmin-5.2.3-all-languages.tar.gz
""", timeout=120)

    run(ssh, """cat > /etc/apache2/conf-available/phpmyadmin.conf << 'EOF'
Alias /phpmyadmin /usr/share/phpmyadmin
<Directory /usr/share/phpmyadmin>
    Options FollowSymLinks
    DirectoryIndex index.php
    AllowOverride All
    Require all granted
</Directory>
EOF
""")
    run(ssh, "a2enconf phpmyadmin 2>/dev/null; true")

    # Restart Apache
    run(ssh, "systemctl restart apache2")

    # Verification
    print("\n" + "=" * 60)
    print("  VERIFICATION")
    print("=" * 60, flush=True)
    run(ssh, "php -v | head -1")
    run(ssh, "mysql --version")
    run(ssh, "apache2 -v | head -1")
    run(ssh, "composer --version 2>/dev/null | head -1")
    run(ssh, "ufw status | head -10")
    run(ssh, "free -h")
    run(ssh, "df -h /")
    run(ssh, "ls /var/www/")

    print("\n" + "=" * 60)
    print("  SERVER SETUP COMPLETE!")
    print(f"  Host: {NEW_HOST}")
    print(f"  DB User: {DB_USER}")
    print("  Next: Run 02_migrate_data.py")
    print("=" * 60, flush=True)

    ssh.close()


if __name__ == '__main__':
    main()
