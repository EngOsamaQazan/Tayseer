import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

fix_script = r"""
import re

path = '/usr/share/phpmyadmin/libraries/classes/Header.php'
with open(path, 'r') as f:
    content = f.read()

# Fix 1: is_https
old1 = "'is_https' => $GLOBALS['config'] !== null && $GLOBALS['config']->isHttps()"
new1 = "'is_https' => $GLOBALS['config'] !== null ? $GLOBALS['config']->isHttps() : false"
content = content.replace(old1, new1)

# Fix 2: rootPath
old2 = "'rootPath' => $GLOBALS['config'] !== null && $GLOBALS['config']->getRootPath()"
new2 = "'rootPath' => $GLOBALS['config'] !== null ? $GLOBALS['config']->getRootPath() : ''"
content = content.replace(old2, new2)

with open(path, 'w') as f:
    f.write(content)

print("Fixed!")

# Verify
with open(path, 'r') as f:
    for i, line in enumerate(f, 1):
        if 'rootPath' in line or 'is_https' in line:
            print(f"Line {i}: {line.rstrip()}")
"""

# Upload and run the fix script
sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_header.py', 'w') as f:
    f.write(fix_script)
sftp.close()

stdin, stdout, stderr = ssh.exec_command('python3 /tmp/fix_header.py')
print(stdout.read().decode('utf-8', errors='replace'))
err = stderr.read().decode('utf-8', errors='replace')
if err: print(f'ERR: {err}')

# Restart Apache and clear OPcache
cmds = [
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # Full login test and SQL page test
    r"""rm -f /tmp/pma_test_cookies.txt 2>/dev/null

# Step 1: Get login page and token
RESP=$(curl -sk -c /tmp/pma_test_cookies.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
echo "Token: ${TOKEN:0:20}..."
echo "rootPath from home: $(echo "$RESP" | grep -oP "rootPath:\"[^\"]*\"")"

# Step 2: Login
LOGIN=$(curl -sk -b /tmp/pma_test_cookies.txt -c /tmp/pma_test_cookies.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' 2>/dev/null)
echo "Logged in: $(echo "$LOGIN" | grep -oP 'logged_in:\w+')"
echo "rootPath after login: $(echo "$LOGIN" | grep -oP "rootPath:\"[^\"]*\"")"

# Step 3: SQL page
SQL=$(curl -sk -b /tmp/pma_test_cookies.txt \
  'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL page size: ${#SQL}"
echo "SQL logged: $(echo "$SQL" | grep -oP 'logged_in:\w+')"
echo "SQL codemirror: $(echo "$SQL" | grep -c 'codemirror\|CodeMirror\|sqlquery')"
echo "SQL rootPath: $(echo "$SQL" | grep -oP "rootPath:\"[^\"]*\"")"
if [ ${#SQL} -lt 100 ]; then
  echo "SQL CONTENT: $SQL"
fi
""",
]

for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err2 = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'=== {cmd[:80]} ===')
    if out: print(out)
    if err2: print(f'ERR: {err2}')
    print()

ssh.close()
