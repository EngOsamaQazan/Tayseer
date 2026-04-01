import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = {
    'running_services': 'systemctl list-units --type=service --state=running --no-pager --plain 2>/dev/null | awk "{print $1}" | head -50',
    'php_packages_installed': "dpkg -l 'php*' 2>/dev/null | grep '^ii' | awk '{print $2, $3}' | head -50",
    'composer_outdated': 'cd /var/www/html && COMPOSER_ALLOW_SUPERUSER=1 composer outdated --direct 2>/dev/null | head -40 || echo NO_COMPOSER_JSON',
    'composer_version_check': 'COMPOSER_ALLOW_SUPERUSER=1 composer self-update --dry-run 2>&1 | head -10',
    'snap_packages': 'snap list 2>/dev/null || echo SNAP_NOT_INSTALLED',
    'pip_outdated': 'pip3 list --outdated 2>/dev/null | head -20 || echo PIP_NOT_AVAILABLE',
    'webmin_repo': 'grep -r webmin /etc/apt/sources.list.d/ 2>/dev/null || echo NO_WEBMIN_REPO',
    'security_check': 'unattended-upgrades --dry-run 2>&1 | tail -10 || echo NOT_AVAILABLE',
    'kernel_check': 'dpkg -l linux-image-* 2>/dev/null | grep "^ii" | awk "{print $2, $3}" | tail -5',
}

for label, cmd in commands.items():
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    result = out if out else (err if err else 'EMPTY')
    print(f'=== {label} ===')
    print(result)
    print()

ssh.close()
