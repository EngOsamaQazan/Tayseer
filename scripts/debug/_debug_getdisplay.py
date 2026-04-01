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

# 1. Check sendHttpHeaders
print("=== sendHttpHeaders ===")
run("grep -n 'function sendHttpHeaders' /usr/share/phpmyadmin/libraries/classes/Header.php")
run("sed -n '/function sendHttpHeaders/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Header.php")

# 2. Check if Content-Length is set anywhere in phpMyAdmin code
print("=== Content-Length references ===")
run("grep -rn 'Content-Length' /usr/share/phpmyadmin/libraries/classes/ 2>/dev/null")

# 3. Now wrap getDisplay in try-catch
fix_script = r"""
path = '/usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php'
with open(path, 'r') as f:
    c = f.read()

old = '''        if ($this->isAjax()) {
            $result = $this->ajaxResponse();
            @header('X-PMA-Ajax-Len: ' . strlen($result));
            echo $result;
        } else {
            $display = $this->getDisplay();
            @header('X-PMA-Display-Len: ' . strlen($display));
            echo $display;
        }'''

new = '''        if ($this->isAjax()) {
            $result = $this->ajaxResponse();
            @header('X-PMA-Ajax-Len: ' . strlen($result));
            echo $result;
        } else {
            try {
                @header('X-PMA-Before-Display: 1');
                $display = $this->getDisplay();
                @header('X-PMA-Display-Len: ' . strlen($display));
                echo $display;
            } catch (\Throwable $e) {
                @header('X-PMA-Error: ' . substr(str_replace(["\n","\r"], " ", $e->getMessage()), 0, 200));
                @header('X-PMA-Error-File: ' . $e->getFile() . ':' . $e->getLine());
                echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            }
        }'''

if old in c:
    c = c.replace(old, new)
    with open(path, 'w') as f:
        f.write(c)
    print('Patched with try-catch')
else:
    print('ERROR: Could not find code to patch')
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_try_catch.py', 'w') as f:
    f.write(fix_script)
sftp.close()
run('python3 /tmp/fix_try_catch.py')

run("systemctl restart apache2; echo restarted")

# Test
run(r"""rm -f /tmp/pma_tc6.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_tc6.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_tc6.txt -c /tmp/pma_tc6.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

echo "=== Dashboard ==="
curl -sk -b /tmp/pma_tc6.txt -D- -o /dev/null 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null | grep -i 'pma\|content-length'

echo "=== SQL page ==="
curl -sk -b /tmp/pma_tc6.txt -D- 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null | head -5
echo ""
curl -sk -b /tmp/pma_tc6.txt -D- -o /dev/null 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null | grep -i 'pma\|content-length\|error'
""")

ssh.close()
