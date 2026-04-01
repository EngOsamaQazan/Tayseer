import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = {
    'exim_config_type': "cat /etc/exim4/update-exim4.conf.conf 2>/dev/null || echo NO_CONFIG",
    'exim_mailname': "cat /etc/mailname 2>/dev/null || echo NO_MAILNAME",
    'exim_aliases': "cat /etc/aliases 2>/dev/null | head -20 || echo NO_ALIASES",
    'exim_queue': "exim4 -bpc 2>/dev/null || echo NO_QUEUE_INFO",
    'hostname': "hostname -f 2>/dev/null",
    'php_mail_config': "php -i 2>/dev/null | grep -i 'sendmail_path' | head -3",
    'exim_listening': "ss -tlnp | grep -E ':25|:587|:465' 2>/dev/null || echo NO_MAIL_PORTS",
    'exim_packages': "dpkg -l | grep exim | awk '{print $2, $3}' 2>/dev/null",
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
