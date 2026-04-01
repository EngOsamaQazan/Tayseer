import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check Apache error log for recent errors
    "tail -30 /var/log/apache2/error.log 2>/dev/null | grep -i 'php\\|fatal\\|error\\|pma' | tail -10",
    
    # 2. Check PHP error log
    "tail -30 /tmp/pma_errors.log 2>/dev/null | tail -10",
    
    # 3. Enable PHP error display temporarily and test SQL page
    r"""rm -f /tmp/pma_test_cookies2.txt 2>/dev/null

# Login first
RESP=$(curl -sk -c /tmp/pma_test_cookies2.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_test_cookies2.txt -c /tmp/pma_test_cookies2.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1

# Now test the SQL page with verbose headers
curl -sk -b /tmp/pma_test_cookies2.txt -D- \
  'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null | head -30
""",

    # 4. Test if the issue is the AJAX request type (phpMyAdmin uses AJAX)
    r"""curl -sk -b /tmp/pma_test_cookies2.txt \
  -H 'X-Requested-With: XMLHttpRequest' \
  'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp&ajax_request=true' 2>/dev/null | head -20
""",

    # 5. Test structure page too
    r"""SQL_STRUCT=$(curl -sk -b /tmp/pma_test_cookies2.txt \
  'https://aqssat.co/phpmyadmin/index.php?route=/database/structure&db=namaa_erp' 2>/dev/null)
echo "Structure page size: ${#SQL_STRUCT}"
echo "First 200 chars: ${SQL_STRUCT:0:200}"
""",

    # 6. Test the main dashboard (should work)
    r"""DASH=$(curl -sk -b /tmp/pma_test_cookies2.txt \
  'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard size: ${#DASH}"
echo "logged_in: $(echo "$DASH" | grep -oP 'logged_in:\w+')"
""",

    # 7. Enable PHP error logging in htaccess for SQL page
    r"""cat > /usr/share/phpmyadmin/.htaccess << 'HT'
php_flag log_errors on
php_value error_log /tmp/pma_sql_errors.log
php_flag display_errors on
HT
echo "htaccess set"
systemctl restart apache2
""",

    # 8. Test SQL page again with errors displayed
    r"""rm -f /tmp/pma_sql_errors.log 2>/dev/null
curl -sk -b /tmp/pma_test_cookies2.txt \
  'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null
echo ""
echo "--- Error log ---"
cat /tmp/pma_sql_errors.log 2>/dev/null || echo "no errors logged"
""",
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
