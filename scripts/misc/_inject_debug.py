import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

# Read the current ResponseRenderer.php
stdin, stdout, stderr = ssh.exec_command('cat /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php')
content = stdout.read().decode('utf-8', errors='replace')
print(f"File size: {len(content)} bytes")

# Inject debug into the response() method
old_response = '''    public function response(): void
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
    }'''

new_response = '''    public function response(): void
    {
        $buffer = OutputBuffering::getInstance();
        if (empty($this->HTML)) {
            $this->HTML = $buffer->getContents();
        }

        $logFile = '/tmp/pma_renderer.log';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $isAjax = $this->isAjax() ? 'true' : 'false';
        $htmlLen = strlen($this->HTML);
        $headerEnabled = ($this->header !== null && $this->header->isEnabled) ? 'true' : 'false';
        error_log("RESP: uri=$uri ajax=$isAjax htmlLen=$htmlLen headerEnabled=$headerEnabled disabled=" . ($this->isDisabled ? 'true' : 'false') . "\\n", 3, $logFile);

        if ($this->isAjax()) {
            $result = $this->ajaxResponse();
            error_log("RESP_AJAX: len=" . strlen($result) . "\\n", 3, $logFile);
            echo $result;
        } else {
            try {
                $result = $this->getDisplay();
                error_log("RESP_DISPLAY: len=" . strlen($result) . "\\n", 3, $logFile);
                echo $result;
            } catch (\\Throwable $e) {
                error_log("RESP_ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\\n", 3, $logFile);
                echo "Error: " . $e->getMessage();
            }
        }

        $buffer->flush();
        exit;
    }'''

if old_response in content:
    content = content.replace(old_response, new_response)
    print("Code injected successfully")
else:
    print("WARNING: Could not find response() method to inject into")
    # Try to find it
    import re
    match = re.search(r'public function response\(\)', content)
    if match:
        print(f"Found response() at position {match.start()}")
        # Show context
        print(content[match.start():match.start()+500])
    else:
        print("response() not found at all!")

# Also need to check if Header has isEnabled as public
stdin2, stdout2, stderr2 = ssh.exec_command("grep -n 'isEnabled' /usr/share/phpmyadmin/libraries/classes/Header.php | head -5")
print("\nHeader isEnabled:")
print(stdout2.read().decode('utf-8', errors='replace'))

# Upload the modified file
sftp = ssh.open_sftp()
with sftp.file('/usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php', 'w') as f:
    f.write(content)
sftp.close()
print("File uploaded")

# Restart Apache
stdin, stdout, stderr = ssh.exec_command('systemctl restart apache2')
stdout.read()
print("Apache restarted")

# Clear log
ssh.exec_command('rm -f /tmp/pma_renderer.log')

# Test
stdin, stdout, stderr = ssh.exec_command(r"""rm -f /tmp/pma_test_c.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_test_c.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_test_c.txt -c /tmp/pma_test_c.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1

# Dashboard
DASH=$(curl -sk -b /tmp/pma_test_c.txt 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard: ${#DASH}"

# SQL (non-AJAX)
SQL=$(curl -sk -b /tmp/pma_test_c.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL: ${#SQL}"

cat /tmp/pma_renderer.log 2>/dev/null
""")
print("\n=== Test Results ===")
print(stdout.read().decode('utf-8', errors='replace'))
err = stderr.read().decode('utf-8', errors='replace')
if err: print(f"ERR: {err}")

ssh.close()
