import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Set proper phpMyAdmin config
    r"""cat > /usr/share/phpmyadmin/config.inc.php << 'PHPCFG'
<?php
$cfg['blowfish_secret'] = '960a605ddc04967789cb19feae2cd29e';
$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['TempDir'] = '/tmp';

$cfg['NavigationTreeDbSeparator'] = false;
$cfg['NavigationTreeEnableGrouping'] = false;
$cfg['PmaAbsoluteUri'] = 'https://aqssat.co/phpmyadmin/';
$cfg['CookieSameSite'] = 'Lax';
PHPCFG
echo "config written"
""",
    
    # 2. Clear sessions and caches
    "rm -f /var/lib/php/sessions/sess_* 2>/dev/null; rm -rf /tmp/twig 2>/dev/null; mkdir -p /tmp/twig; chown www-data:www-data /tmp/twig; chmod 777 /tmp/twig; echo 'cleared'",
    
    # 3. Clear OPcache and restart
    """php -r "opcache_reset();" 2>/dev/null""",
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 4. Test login via curl with root/HAmAS12852
    """rm -f /tmp/pma_v.txt /tmp/pma_vh.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_v.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\\K[^"]+' | head -1)
echo "Token: $TOKEN"
LOGIN=$(curl -sk -b /tmp/pma_v.txt -c /tmp/pma_v.txt -D /tmp/pma_vh.txt -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" 'https://aqssat.co/phpmyadmin/index.php' 2>/dev/null)
echo "Login size: $(echo "$LOGIN" | wc -c)"
echo "Logged in: $(echo "$LOGIN" | grep -o 'logged_in:[a-z]*')"
echo "Cookies after login:"
cat /tmp/pma_v.txt 2>/dev/null | grep -v '^#'
echo ""
echo "Auth headers:"
grep -i 'pmaAuth' /tmp/pma_vh.txt 2>/dev/null
""",
    
    # 5. If logged in, test SQL page
    """SQL_RESP=$(curl -sk -b /tmp/pma_v.txt -c /tmp/pma_v.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL page size: $(echo "$SQL_RESP" | wc -c)"
echo "Logged in on SQL page: $(echo "$SQL_RESP" | grep -o 'logged_in:[a-z]*')"
echo "SQL form found: $(echo "$SQL_RESP" | grep -c 'sqlquery\\|SQL_query\\|CodeMirror\\|codemirror')"
""",
    
    # 6. Check errors
    "cat /tmp/pma_errors.log 2>/dev/null",
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
