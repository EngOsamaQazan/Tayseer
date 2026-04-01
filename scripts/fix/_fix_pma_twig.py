import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Fix Twig cache permissions
    "rm -rf /tmp/twig 2>/dev/null && mkdir -p /tmp/twig && chown -R www-data:www-data /tmp/twig && chmod 755 /tmp/twig && echo 'twig cache fixed'",
    
    # 2. Also fix the phpMyAdmin TempDir
    "chown -R www-data:www-data /tmp/phpmyadmin 2>/dev/null; chmod 755 /tmp/phpmyadmin 2>/dev/null; echo 'tempdir fixed'",
    
    # 3. Fix the phpMyAdmin tmp directory too
    "chown -R www-data:www-data /usr/share/phpmyadmin/tmp/ 2>/dev/null; echo 'pma tmp fixed'",
    
    # 4. Clear OPcache and restart
    """php -r "opcache_reset();" 2>/dev/null""",
    "systemctl restart apache2 2>&1; echo 'apache restarted'",
    
    # 5. Clear error log
    "truncate -s 0 /tmp/pma_errors.log 2>/dev/null; echo 'log cleared'",
    
    # 6. Test accessing the login page (should still work)
    "curl -sk -o /dev/null -w '%{http_code}' https://aqssat.co/phpmyadmin/index.php 2>&1",
    
    # 7. Now try a proper login test with curl - carefully
    """rm -f /tmp/pma_test_jar.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_test_jar.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\\K[^"]+' | head -1)
echo "Step 1 Token: $TOKEN"
echo "Step 1 Cookies:"
cat /tmp/pma_test_jar.txt 2>/dev/null
echo ""
echo "Step 2 Login..."
LOGIN_RESP=$(curl -sk -b /tmp/pma_test_jar.txt -c /tmp/pma_test_jar.txt -D /tmp/pma_login_headers.txt -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" 'https://aqssat.co/phpmyadmin/index.php' 2>/dev/null)
echo "Login response size: $(echo "$LOGIN_RESP" | wc -c)"
echo "Logged in: $(echo "$LOGIN_RESP" | grep -o 'logged_in:[a-z]*')"
echo "Login headers:"
grep -i 'set-cookie\\|location' /tmp/pma_login_headers.txt 2>/dev/null
echo ""
echo "Step 2 Cookies after login:"
cat /tmp/pma_test_jar.txt 2>/dev/null
""",
    
    # 8. Check PHP errors after all the above
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 9. Clean up test PHP files
    "rm -f /usr/share/phpmyadmin/version_check.php /usr/share/phpmyadmin/php_check.php /usr/share/phpmyadmin/test_debug.php 2>/dev/null; echo 'cleaned'",
]

for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'=== {cmd[:80]} ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()

ssh.close()
