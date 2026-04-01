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

# Read index.php
stdin, stdout, stderr = ssh.exec_command('cat /usr/share/phpmyadmin/index.php')
index_content = stdout.read().decode('utf-8', errors='replace')
print(f"index.php size: {len(index_content)}")

# Inject debug after require AUTOLOAD_FILE
old_line = "require AUTOLOAD_FILE;"
debug_code = """require AUTOLOAD_FILE;

// DEBUG: log every request
@file_put_contents('/tmp/pma_index.log', date('H:i:s') . " START " . ($_SERVER['REQUEST_URI'] ?? '?') . "\\n", FILE_APPEND);
register_shutdown_function(function() {
    $e = error_get_last();
    $msg = $e ? json_encode($e) : 'OK';
    @file_put_contents('/tmp/pma_index.log', date('H:i:s') . " END " . ($_SERVER['REQUEST_URI'] ?? '?') . " ob=" . ob_get_level() . " err=" . $msg . "\\n", FILE_APPEND);
});"""

if old_line in index_content:
    new_content = index_content.replace(old_line, debug_code, 1)
    sftp = ssh.open_sftp()
    with sftp.file('/usr/share/phpmyadmin/index.php', 'w') as f:
        f.write(new_content)
    sftp.close()
    print("index.php patched")
else:
    print("ERROR: Could not find autoload line")

# Clear log and restart
run("rm -f /tmp/pma_index.log; chown www-data:www-data /usr/share/phpmyadmin/index.php; systemctl restart apache2; echo ready")

# Test
run(r"""rm -f /tmp/pma_tc3.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_tc3.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
echo "Home: ${#RESP}"
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_tc3.txt -c /tmp/pma_tc3.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

DASH=$(curl -sk -b /tmp/pma_tc3.txt 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard: ${#DASH}"

SQL=$(curl -sk -b /tmp/pma_tc3.txt 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL non-AJAX: ${#SQL}"

echo "=== INDEX LOG ==="
cat /tmp/pma_index.log 2>/dev/null || echo 'EMPTY'
""")

ssh.close()
