import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Remove problematic .htaccess
    "rm -f /usr/share/phpmyadmin/.htaccess; echo 'htaccess removed'",
    
    # 2. Restart Apache
    "systemctl restart apache2; echo 'restarted'",
    
    # 3. Test dashboard again
    r"""rm -f /tmp/pma_fix_cookies.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_fix_cookies.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
echo "Home size: ${#RESP}"
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_fix_cookies.txt -c /tmp/pma_fix_cookies.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

# Dashboard (should work)
DASH=$(curl -sk -b /tmp/pma_fix_cookies.txt 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard size: ${#DASH}"

# SQL page (non-AJAX, probably 0)
SQL=$(curl -sk -b /tmp/pma_fix_cookies.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL non-AJAX size: ${#SQL}"

# SQL page (AJAX, should work)
SQLA=$(curl -sk -b /tmp/pma_fix_cookies.txt -H 'X-Requested-With: XMLHttpRequest' 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp&ajax_request=true' 2>/dev/null)
echo "SQL AJAX size: ${#SQLA}"
""",
    
    # 4. Look at getDisplay() method fully
    "sed -n '/private function getDisplay/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php",
    
    # 5. Look at Header getDisplay and Footer getDisplay
    "grep -n 'function getDisplay' /usr/share/phpmyadmin/libraries/classes/Header.php",
    "grep -n 'function getDisplay' /usr/share/phpmyadmin/libraries/classes/Footer.php",
    
    # 6. Check if there's a disableHeader property
    "grep -n 'headerIsSent\|isDisabled\|header.*disable\|disableHeader\|_header' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php | head -20",
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
