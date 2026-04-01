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

# Patch ResponseRenderer.php to add header markers
fix_script = r"""
path = '/usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php'
with open(path, 'r') as f:
    c = f.read()

# Find the debug code we injected before
old = '''        $logMsg = "RESP: uri=" . ($_SERVER['REQUEST_URI'] ?? '?') . " ajax=" . ($this->isAjax() ? '1' : '0') . " html=" . strlen($this->HTML) . " disabled=" . ($this->isDisabled ? '1' : '0') . "\\n";
        @error_log($logMsg, 3, '/tmp/pma_resp.log');

        if ($this->isAjax()) {
            echo $this->ajaxResponse();
        } else {
            $display = $this->getDisplay();
            @error_log("DISP: len=" . strlen($display) . "\\n", 3, '/tmp/pma_resp.log');
            echo $display;
        }'''

new = '''        @header('X-PMA-Response: html=' . strlen($this->HTML) . ',ajax=' . ($this->isAjax() ? '1' : '0') . ',disabled=' . ($this->isDisabled ? '1' : '0'));

        if ($this->isAjax()) {
            $result = $this->ajaxResponse();
            @header('X-PMA-Ajax-Len: ' . strlen($result));
            echo $result;
        } else {
            $display = $this->getDisplay();
            @header('X-PMA-Display-Len: ' . strlen($display));
            echo $display;
        }'''

if old in c:
    c = c.replace(old, new)
    with open(path, 'w') as f:
        f.write(c)
    print('Patched with header markers')
else:
    # Maybe the original code is still there
    old2 = '''        if ($this->isAjax()) {
            echo $this->ajaxResponse();
        } else {
            echo $this->getDisplay();
        }'''
    if old2 in c:
        new2 = '''        @header('X-PMA-Response: html=' . strlen($this->HTML) . ',ajax=' . ($this->isAjax() ? '1' : '0') . ',disabled=' . ($this->isDisabled ? '1' : '0'));

        if ($this->isAjax()) {
            $result = $this->ajaxResponse();
            @header('X-PMA-Ajax-Len: ' . strlen($result));
            echo $result;
        } else {
            $display = $this->getDisplay();
            @header('X-PMA-Display-Len: ' . strlen($display));
            echo $display;
        }'''
        c = c.replace(old2, new2)
        with open(path, 'w') as f:
            f.write(c)
        print('Patched original code with header markers')
    else:
        print('ERROR: Could not find code to patch')
        # Show current response method
        import re
        m = re.search(r'public function response\(\).*?exit;', c, re.DOTALL)
        if m:
            print('Current response():')
            print(m.group()[:500])
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_resp_headers.py', 'w') as f:
    f.write(fix_script)
sftp.close()
run('python3 /tmp/fix_resp_headers.py')

run("systemctl restart apache2; echo restarted")

# Test - login and get SQL page with full headers
run(r"""rm -f /tmp/pma_tc5.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_tc5.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_tc5.txt -c /tmp/pma_tc5.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

echo "=== Dashboard headers ==="
curl -sk -b /tmp/pma_tc5.txt -D- -o /dev/null 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null | grep -i 'pma\|content-length'

echo "=== SQL page headers ==="
curl -sk -b /tmp/pma_tc5.txt -D- -o /dev/null 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null | grep -i 'pma\|content-length'

echo "=== Structure page headers ==="
curl -sk -b /tmp/pma_tc5.txt -D- -o /dev/null 'https://aqssat.co/phpmyadmin/index.php?route=/database/structure&db=namaa_erp' 2>/dev/null | grep -i 'pma\|content-length'
""")

ssh.close()
