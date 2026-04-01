import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = {
    'bugzilla_size': "du -sh /var/www/html/bugzilla /var/www/html/bugzilla-5.0.6.tar.gz 2>/dev/null",
    'bugzilla_apache_config': "grep -rl bugzilla /etc/apache2/sites-enabled/ /etc/apache2/sites-available/ /etc/apache2/conf-enabled/ /etc/apache2/conf-available/ 2>/dev/null || echo NO_CONFIG_FOUND",
    'bugzilla_cron': "crontab -l 2>/dev/null | grep -i bugzilla || echo NO_CRON",
    'bugzilla_db': "mariadb -e \"SHOW DATABASES LIKE '%bug%';\" 2>/dev/null || echo NO_DB_ACCESS",
}

for label, cmd in commands.items():
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    result = out if out else (err if err else 'EMPTY')
    print(f'=== {label} ===')
    print(result)
    print()

ssh.close()
