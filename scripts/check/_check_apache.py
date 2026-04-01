import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
    return out

# 1. Check Apache config for phpmyadmin
print("=== Apache phpmyadmin config ===")
run("grep -rn 'phpmyadmin' /etc/apache2/sites-enabled/ /etc/apache2/conf-enabled/ /etc/apache2/conf.d/ 2>/dev/null")

# 2. Check Alias directives
print("=== Alias/Directory directives ===")
run("grep -rn 'Alias\\|Directory.*phpmyadmin' /etc/apache2/ 2>/dev/null | head -20")

# 3. Check phpMyAdmin conf
print("=== phpMyAdmin conf file ===")
run("cat /etc/apache2/conf-enabled/phpmyadmin.conf 2>/dev/null || cat /etc/phpmyadmin/apache.conf 2>/dev/null || echo 'not found'")

# 4. Is there another installation?
print("=== Find phpMyAdmin installations ===")
run("find / -name 'index.php' -path '*/phpmyadmin/*' 2>/dev/null | head -10")

# 5. Check if there's a phpmyadmin symlink
print("=== Check /usr/share/phpmyadmin ===")
run("ls -la /usr/share/phpmyadmin/ | head -5")
run("file /usr/share/phpmyadmin")

# 6. Check virtual host for aqssat.co
print("=== aqssat.co vhost ===")
run("cat /etc/apache2/sites-enabled/aqssat.co-le-ssl.conf 2>/dev/null | head -40")

ssh.close()
