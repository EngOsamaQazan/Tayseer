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

# 1. Check OPcache settings
print("=== OPcache Settings ===")
run("php -r 'echo \"OPcache enabled: \" . (function_exists(\"opcache_get_status\") ? json_encode(opcache_get_status()) : \"N/A\") . \"\\n\";'")
run("php -m | grep -i opcache")
run("php -i | grep -i 'opcache.enable' | head -5")

# 2. Force OPcache reset
print("=== Reset OPcache ===")
run("""cat > /tmp/reset_opcache.php << 'PHP'
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset OK\n";
} else {
    echo "OPcache not available\n";
}
PHP
php /tmp/reset_opcache.php""")

# 3. Create a PHP script to reset OPcache via web
run("""cat > /usr/share/phpmyadmin/reset_cache.php << 'PHP'
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset via web OK\n";
} else {
    echo "No OPcache\n";
}
echo "PHP version: " . PHP_VERSION . "\n";
file_put_contents('/tmp/pma_cache_test.txt', 'cache cleared at ' . date('Y-m-d H:i:s'));
echo "File write test OK\n";
PHP
chown www-data:www-data /usr/share/phpmyadmin/reset_cache.php
echo "created"
""")

# 4. Call it via web to reset web-process OPcache
run("curl -sk https://aqssat.co/phpmyadmin/reset_cache.php 2>/dev/null")
run("cat /tmp/pma_cache_test.txt 2>/dev/null")

# 5. Now clear log, restart, and retest
run("rm -f /tmp/pma_resp.log; systemctl restart apache2; echo 'restarted'")

# 6. Run the test again
run(r"""rm -f /tmp/pma_tc2.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_tc2.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
echo "Home: ${#RESP}"
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_tc2.txt -c /tmp/pma_tc2.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

DASH=$(curl -sk -b /tmp/pma_tc2.txt 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard: ${#DASH}"

SQL=$(curl -sk -b /tmp/pma_tc2.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL non-AJAX: ${#SQL}"

echo "=== pma_resp.log ==="
cat /tmp/pma_resp.log 2>/dev/null || echo 'EMPTY - no log'
""")

# 7. Clean up the reset file
run("rm -f /usr/share/phpmyadmin/reset_cache.php")

ssh.close()
