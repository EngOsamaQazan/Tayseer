import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = {
    'composer_outdated': 'cd /var/www/html && composer outdated --direct 2>/dev/null | head -40 || echo N/A',
    'php_packages': 'dpkg -l | grep php8 | awk "{print $2, $3}" 2>/dev/null | head -40',
    'auto_updates_config': 'cat /etc/apt/apt.conf.d/20auto-upgrades 2>/dev/null || echo NOT_CONFIGURED',
    'needrestart': 'needrestart -b 2>/dev/null | head -20 || echo NOT_INSTALLED',
    'disk_usage': 'df -h / | tail -1',
    'memory': 'free -h | head -2',
    'webmin_latest_check': 'apt-cache policy webmin 2>/dev/null | head -5',
    'mariadb_latest_check': 'apt-cache policy mariadb-server 2>/dev/null | head -5',
    'certbot_latest_check': 'apt-cache policy certbot 2>/dev/null | head -5',
    'fail2ban_latest_check': 'apt-cache policy fail2ban 2>/dev/null | head -5',
    'apache_latest_check': 'apt-cache policy apache2 2>/dev/null | head -5',
    'composer_self_check': 'composer self-update --dry-run 2>&1 | head -5',
    'php_sury_repo': 'apt-cache policy php8.5 2>/dev/null | head -5',
    'all_installed_services': 'systemctl list-units --type=service --state=running --no-pager 2>/dev/null | head -40',
    'snap_packages': 'snap list 2>/dev/null || echo NOT_INSTALLED',
}

for label, cmd in commands.items():
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    result = out if out else (err if err else 'EMPTY')
    print(f'=== {label} ===')
    print(result)
    print()

ssh.close()
