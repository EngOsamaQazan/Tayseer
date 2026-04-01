import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

commands = [
    ("Check bugzilla DB", "mariadb -e \"SHOW DATABASES;\" 2>/dev/null | grep -i bug || echo NO_BUGZILLA_DB"),
    ("Delete bugzilla directory", "rm -rf /var/www/html/bugzilla && echo DELETED_DIR || echo FAILED_DIR"),
    ("Delete bugzilla tar.gz", "rm -f /var/www/html/bugzilla-5.0.6.tar.gz && echo DELETED_TAR || echo FAILED_TAR"),
    ("Verify deletion", "ls -la /var/www/html/bugzilla 2>&1 && echo STILL_EXISTS || echo CONFIRMED_DELETED"),
    ("Verify tar deletion", "ls -la /var/www/html/bugzilla-5.0.6.tar.gz 2>&1 && echo STILL_EXISTS || echo CONFIRMED_DELETED"),
    ("Disk space after", "df -h / | tail -1"),
]

for label, cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    result = out if out else (err if err else 'EMPTY')
    print(f'=== {label} ===')
    print(result)
    print()

ssh.close()
