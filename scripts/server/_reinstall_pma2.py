import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

def run(cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'CMD: {cmd[:100]}')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
    return out

# 1. Download and install fresh
run('cd /tmp && rm -f pma-latest.tar.gz && wget -q https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz -O pma-latest.tar.gz && echo "downloaded" && ls -lh pma-latest.tar.gz', timeout=60)

# 2. Extract
run('cd /tmp && rm -rf phpMyAdmin-*-all-languages && tar xzf pma-latest.tar.gz && ls -d phpMyAdmin-*-all-languages')

# 3. Move to final location
run('rm -rf /usr/share/phpmyadmin && mv /tmp/phpMyAdmin-*-all-languages /usr/share/phpmyadmin && echo "moved"')

# 4. Write config
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
$cfg['NavigationTreeDbSeparator'] = false;
$cfg['NavigationTreeEnableGrouping'] = false;
PHPCFG
echo "config written"
""")

# 5. Apply Header.php fix via Python on server
header_fix_script = """
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
with sftp.file('/tmp/fix_header.py', 'w') as f:
    f.write(header_fix_script)
sftp.close()
run('python3 /tmp/fix_header.py')

# 6. Patch ResponseRenderer.php to add debug logging
renderer_fix_script = r"""
path = '/usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php'
with open(path, 'r') as f:
    c = f.read()

old_code = '        if ($this->isAjax()) {\n            echo $this->ajaxResponse();\n        } else {\n            echo $this->getDisplay();\n        }'

new_code = '''        $logMsg = "RESP: uri=" . ($_SERVER['REQUEST_URI'] ?? '?') . " ajax=" . ($this->isAjax() ? '1' : '0') . " html=" . strlen($this->HTML) . " disabled=" . ($this->isDisabled ? '1' : '0') . "\\n";
        @error_log($logMsg, 3, '/tmp/pma_resp.log');

        if ($this->isAjax()) {
            echo $this->ajaxResponse();
        } else {
            $display = $this->getDisplay();
            @error_log("DISP: len=" . strlen($display) . "\\n", 3, '/tmp/pma_resp.log');
            echo $display;
        }'''

if old_code in c:
    c = c.replace(old_code, new_code)
    with open(path, 'w') as f:
        f.write(c)
    print('ResponseRenderer.php patched successfully')
else:
    print('Could not find exact code block, trying line by line...')
    lines = c.split('\n')
    for i, line in enumerate(lines):
        if 'isAjax()' in line and 'echo' not in line and 'if' in line:
            print(f'  Line {i+1}: {line.strip()}')
"""
sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_renderer.py', 'w') as f:
    f.write(renderer_fix_script)
sftp.close()
run('python3 /tmp/fix_renderer.py')

# 7. Permissions
run('chown -R www-data:www-data /usr/share/phpmyadmin && mkdir -p /usr/share/phpmyadmin/tmp && chmod 777 /usr/share/phpmyadmin/tmp && echo "perms ok"')

# 8. Clear caches and restart
run('rm -rf /tmp/twig 2>/dev/null; rm -f /tmp/pma_resp.log 2>/dev/null; systemctl restart apache2; echo "ready"')

# 9. Test
run(r"""rm -f /tmp/pma_tc.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_tc.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
echo "Home: ${#RESP}"
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_tc.txt -c /tmp/pma_tc.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

DASH=$(curl -sk -b /tmp/pma_tc.txt 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard: ${#DASH}"

SQL=$(curl -sk -b /tmp/pma_tc.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL non-AJAX: ${#SQL}"

echo "=== DEBUG LOG ==="
cat /tmp/pma_resp.log 2>/dev/null || echo 'no log'
""")

ssh.close()
