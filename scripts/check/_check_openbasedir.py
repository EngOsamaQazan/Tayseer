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

# 1. Create test PHP that checks open_basedir and writes
print("=== PHP open_basedir test via web ===")
run(r"""cat > /usr/share/phpmyadmin/phpinfo_test.php << 'PHP'
<?php
echo "open_basedir: " . ini_get('open_basedir') . "\n";
echo "error_reporting: " . error_reporting() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "tmp test: ";
$r = @file_put_contents('/tmp/pma_test_write.txt', 'hello');
echo ($r !== false ? 'OK' : 'FAILED') . "\n";
echo "local test: ";
$r2 = @file_put_contents(__DIR__ . '/test_write_local.txt', 'hello');
echo ($r2 !== false ? 'OK' : 'FAILED') . "\n";
PHP
chown www-data:www-data /usr/share/phpmyadmin/phpinfo_test.php
echo "created"
""")

run("curl -sk https://aqssat.co/phpmyadmin/phpinfo_test.php 2>/dev/null")

# 2. Check tayseer security config (might have open_basedir)
print("=== tayseer-security.conf ===")
run("cat /etc/apache2/conf-available/tayseer-security.conf")

# 3. Check tayseer-performance.conf  
print("=== tayseer-performance.conf ===")
run("cat /etc/apache2/conf-available/tayseer-performance.conf")

# 4. Check php.ini for open_basedir
print("=== PHP ini open_basedir ===")
run("php -i | grep open_basedir")

# 5. Clean up
run("rm -f /usr/share/phpmyadmin/phpinfo_test.php /usr/share/phpmyadmin/test_write_local.txt /tmp/pma_test_write.txt")

ssh.close()
