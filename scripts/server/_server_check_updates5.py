import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = {
    'namaa_composer': "cd /var/www/namaa2.aqssat.co && COMPOSER_ALLOW_SUPERUSER=1 composer outdated --direct 2>/dev/null | head -30",
    'jadal_composer': "cd /var/www/jadal2.aqssat.co && COMPOSER_ALLOW_SUPERUSER=1 composer outdated --direct 2>/dev/null | head -30",
    'watar_composer': "cd /var/www/watar.aqssat.co && COMPOSER_ALLOW_SUPERUSER=1 composer outdated --direct 2>/dev/null | head -30",
    'namaa_yii_version': "cd /var/www/namaa2.aqssat.co && grep yiisoft composer.json 2>/dev/null | head -10",
    'jadal_yii_version': "cd /var/www/jadal2.aqssat.co && grep yiisoft composer.json 2>/dev/null | head -10",
    'watar_yii_version': "cd /var/www/watar.aqssat.co && grep yiisoft composer.json 2>/dev/null | head -10",
    'bugzilla_version': "cat /var/www/html/bugzilla/Bugzilla/Constants.pm 2>/dev/null | grep BUGZILLA_VERSION | head -3 || echo NO_BUGZILLA",
    'ssl_cert_expiry': "for domain in namaa2.aqssat.co jadal2.aqssat.co watar.aqssat.co; do echo \"==$domain==\"; echo | openssl s_client -servername $domain -connect $domain:443 2>/dev/null | openssl x509 -noout -dates 2>/dev/null || echo NO_CERT; done",
    'php_opcache': "php -i 2>/dev/null | grep -i opcache.enable | head -3",
}

for label, cmd in commands.items():
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=120)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    result = out if out else (err if err else 'EMPTY')
    print(f'=== {label} ===')
    print(result)
    print()

ssh.close()
