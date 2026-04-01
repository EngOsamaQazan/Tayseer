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

# 1. Reinstall fresh phpMyAdmin (clean, no debug code)
run('cd /tmp && rm -rf phpMyAdmin-*-all-languages && tar xzf pma-latest.tar.gz && echo "extracted"')
run('rm -rf /usr/share/phpmyadmin && mv /tmp/phpMyAdmin-*-all-languages /usr/share/phpmyadmin && echo "installed"')

# 2. Write FIXED config (empty string instead of false)
run(r"""cat > /usr/share/phpmyadmin/config.inc.php << 'PHPCFG'
<?php
$cfg['blowfish_secret'] = '960a605ddc04967789cb19feae2cd29e7a8b1c3d';
$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['TempDir'] = '/tmp';
$cfg['NavigationTreeDbSeparator'] = '';
$cfg['NavigationTreeEnableGrouping'] = false;
PHPCFG
echo "config written"
""")

# 3. Apply Header.php fix for PHP 8.5
header_fix = """
path = '/usr/share/phpmyadmin/libraries/classes/Header.php'
with open(path, 'r') as f:
    c = f.read()
c = c.replace(
    "'is_https' => $GLOBALS['config'] !== null && $GLOBALS['config']->isHttps()",
    "'is_https' => $GLOBALS['config'] !== null ? $GLOBALS['config']->isHttps() : false"
)
c = c.replace(
    "'rootPath' => $GLOBALS['config'] !== null && $GLOBALS['config']->getRootPath()",
    "'rootPath' => $GLOBALS['config'] !== null ? $GLOBALS['config']->getRootPath() : ''"
)
with open(path, 'w') as f:
    f.write(c)
print('Header.php fixed')
"""
sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_header_final.py', 'w') as f:
    f.write(header_fix)
sftp.close()
run('python3 /tmp/fix_header_final.py')

# 4. Also fix NavigationTree.php to cast to string (safety)
navtree_fix = """
path = '/usr/share/phpmyadmin/libraries/classes/Navigation/NavigationTree.php'
with open(path, 'r') as f:
    c = f.read()

# Fix escapeString calls that might receive non-string values
c = c.replace(
    "$this->dbi->escapeString($GLOBALS['cfg']['NavigationTreeDbSeparator'])",
    "$this->dbi->escapeString((string) ($GLOBALS['cfg']['NavigationTreeDbSeparator'] ?? ''))"
)

with open(path, 'w') as f:
    f.write(c)
print('NavigationTree.php fixed')
"""
sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_navtree.py', 'w') as f:
    f.write(navtree_fix)
sftp.close()
run('python3 /tmp/fix_navtree.py')

# 5. Set permissions
run('chown -R www-data:www-data /usr/share/phpmyadmin && mkdir -p /usr/share/phpmyadmin/tmp && chmod 777 /usr/share/phpmyadmin/tmp && echo "perms ok"')

# 6. Clear caches and restart
run('rm -rf /tmp/twig 2>/dev/null; systemctl restart apache2; echo "ready"')

# 7. FULL TEST
run(r"""rm -f /tmp/pma_final.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_final.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
echo "Home: ${#RESP}"
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_final.txt -c /tmp/pma_final.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

DASH=$(curl -sk -b /tmp/pma_final.txt 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard: ${#DASH}"

SQL=$(curl -sk -b /tmp/pma_final.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL page: ${#SQL}"

STRUCT=$(curl -sk -b /tmp/pma_final.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/structure&db=namaa_erp' 2>/dev/null)
echo "Structure page: ${#STRUCT}"

if [ ${#SQL} -gt 100 ]; then
  echo "SQL contains codemirror: $(echo "$SQL" | grep -c 'codemirror\|CodeMirror')"
  echo "SQL logged_in: $(echo "$SQL" | grep -oP 'logged_in:\w+')"
fi
""")

ssh.close()
