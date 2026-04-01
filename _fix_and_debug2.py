import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'CMD: {cmd[:80]}')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
    return out

# 1. Re-extract fresh phpMyAdmin
run('cd /tmp && rm -rf /usr/share/phpmyadmin && PMA_DIR=$(ls -d phpMyAdmin-*-all-languages 2>/dev/null | head -1) && cp -r "$PMA_DIR" /usr/share/phpmyadmin && echo "reinstalled fresh"')

# 2. Write config
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

# 3. Apply Header.php fix via Python on server
header_fix_script = r"""
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

# 4. Now patch ResponseRenderer.php via Python on server
renderer_fix_script = '''
path = '/usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php'
with open(path, 'r') as f:
    c = f.read()

old = """    public function response(): void
    {
        $buffer = OutputBuffering::getInstance();
        if (empty($this->HTML)) {
            $this->HTML = $buffer->getContents();
        }

        if ($this->isAjax()) {
            echo $this->ajaxResponse();
        } else {
            echo $this->getDisplay();
        }

        $buffer->flush();
        exit;
    }"""

new = """    public function response(): void
    {
        $buffer = OutputBuffering::getInstance();
        if (empty($this->HTML)) {
            $this->HTML = $buffer->getContents();
        }

        $logMsg = "RESP: uri=" . ($_SERVER['REQUEST_URI'] ?? '?') . " ajax=" . ($this->isAjax() ? '1' : '0') . " html=" . strlen($this->HTML) . " disabled=" . ($this->isDisabled ? '1' : '0') . "\\n";
        @error_log($logMsg, 3, '/tmp/pma_resp.log');

        if ($this->isAjax()) {
            echo $this->ajaxResponse();
        } else {
            $display = $this->getDisplay();
            @error_log("DISP: len=" . strlen($display) . "\\n", 3, '/tmp/pma_resp.log');
            echo $display;
        }

        $buffer->flush();
        exit;
    }"""

if old in c:
    c = c.replace(old, new)
    with open(path, 'w') as f:
        f.write(c)
    print('ResponseRenderer.php patched')
else:
    print('ERROR: Could not find response() method')
'''
sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_renderer.py', 'w') as f:
    f.write(renderer_fix_script)
sftp.close()
run('python3 /tmp/fix_renderer.py')

# 5. Permissions
run('chown -R www-data:www-data /usr/share/phpmyadmin')

# 6. Clear caches and restart
run('rm -rf /tmp/twig 2>/dev/null; rm -f /tmp/pma_resp.log 2>/dev/null; systemctl restart apache2; echo "ready"')

# 7. Test
result = run(r"""rm -f /tmp/pma_tc.txt 2>/dev/null
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
