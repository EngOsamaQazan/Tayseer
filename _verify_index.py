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

# 1. Show the actual index.php content
print("=== index.php content ===")
run("cat /usr/share/phpmyadmin/index.php")

# 2. Check conf-enabled directory
print("=== conf-enabled ===")
run("ls -la /etc/apache2/conf-enabled/")

# 3. Check OPcache file cache
print("=== OPcache file cache ===")
run("php -i | grep 'opcache.file_cache ' | head -3")
run("php -i | grep 'opcache.file_cache_only' | head -3")

# 4. Create a simple PHP test file in phpmyadmin dir
print("=== PHP execution test ===")
run(r"""cat > /usr/share/phpmyadmin/test_exec.php << 'PHP'
<?php
file_put_contents('/tmp/pma_exec_test.txt', 'EXECUTED at ' . date('H:i:s') . ' URI=' . ($_SERVER['REQUEST_URI'] ?? '?'));
echo 'OK';
PHP
chown www-data:www-data /usr/share/phpmyadmin/test_exec.php
curl -sk https://aqssat.co/phpmyadmin/test_exec.php 2>/dev/null
echo ""
cat /tmp/pma_exec_test.txt 2>/dev/null
echo ""
rm -f /usr/share/phpmyadmin/test_exec.php /tmp/pma_exec_test.txt
""")

# 5. Check if strict_types is causing the issue
print("=== PHP syntax check ===")
run("php -l /usr/share/phpmyadmin/index.php")

ssh.close()
