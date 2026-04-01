import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = {
    'composer_latest': "COMPOSER_ALLOW_SUPERUSER=1 composer --version 2>&1 && echo '---' && curl -s https://getcomposer.org/versions 2>/dev/null | head -5 || echo CURL_FAIL",
    'webmin_latest': "curl -s https://www.webmin.com/download.html 2>/dev/null | grep -oP 'webmin[-_]\\K[0-9]+\\.[0-9]+' | head -3 || echo CURL_FAIL",
    'kernel_installed': "dpkg -l 'linux-image*' 2>/dev/null | grep '^ii' | head -5",
    'php_sury_updates': "apt-cache policy php8.5-cli 2>/dev/null | head -5",
    'php83_sury_updates': "apt-cache policy php8.3-cli 2>/dev/null | head -5",
    'exim_version': "exim4 --version 2>/dev/null | head -2 || echo NOT_INSTALLED",
    'exim_policy': "apt-cache policy exim4 2>/dev/null | head -5",
    'all_upgradable_count': "apt list --upgradable 2>/dev/null | grep -c upgradable || echo 0",
    'last_apt_upgrade': "grep 'Commandline.*upgrade' /var/log/apt/history.log 2>/dev/null | tail -5 || echo NO_HISTORY",
    'reboot_required': "ls /var/run/reboot-required 2>/dev/null && cat /var/run/reboot-required 2>/dev/null || echo NO_REBOOT_REQUIRED",
    'yii2_composer': "cd /var/www/html && cat composer.json 2>/dev/null | grep -E 'yiisoft|kartik|name|version' | head -20 || echo NO_COMPOSER",
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
