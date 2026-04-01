import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = {
    'webmin_version_detail': "dpkg -s webmin 2>/dev/null | grep -E 'Version|Status'",
    'webmin_latest_api': "curl -s 'https://api.github.com/repos/webmin/webmin/releases/latest' 2>/dev/null | grep -oP '\"tag_name\":\\s*\"\\K[^\"]+' || echo CURL_FAIL",
    'yii2_app_location': "find /var/www -name 'composer.json' -maxdepth 3 2>/dev/null | head -10",
    'yii2_composer_json': "cat /var/www/html/composer.json 2>/dev/null | head -40 || find /var/www -name 'composer.lock' -maxdepth 4 2>/dev/null | head -5",
    'www_structure': "ls -la /var/www/ 2>/dev/null && echo '---' && ls /var/www/html/ 2>/dev/null | head -20",
    'old_kernel_cleanup': "dpkg -l 'linux-image*' 2>/dev/null | grep '^ii' | wc -l",
    'exim_backport': "apt-cache policy exim4-daemon-light 2>/dev/null | head -8",
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
