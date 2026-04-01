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

# Read current index.php
stdin, stdout, stderr = ssh.exec_command('cat /usr/share/phpmyadmin/index.php')
content = stdout.read().decode('utf-8', errors='replace')

# Replace the debug file_put_contents with header() call
old_debug = """// DEBUG: log every request
@file_put_contents('/tmp/pma_index.log', date('H:i:s') . " START " . ($_SERVER['REQUEST_URI'] ?? '?') . "\\n", FILE_APPEND);
register_shutdown_function(function() {
    $e = error_get_last();
    $msg = $e ? json_encode($e) : 'OK';
    @file_put_contents('/tmp/pma_index.log', date('H:i:s') . " END " . ($_SERVER['REQUEST_URI'] ?? '?') . " ob=" . ob_get_level() . " err=" . $msg . "\\n", FILE_APPEND);
});"""

new_debug = """// DEBUG: add header marker
@header('X-PMA-Debug: index-executed-' . time());"""

if old_debug in content:
    content = content.replace(old_debug, new_debug)
    sftp = ssh.open_sftp()
    with sftp.file('/usr/share/phpmyadmin/index.php', 'w') as f:
        f.write(content)
    sftp.close()
    print("Marker added to index.php")
else:
    print("Could not find debug code to replace")
    # Just add the header after autoload
    old_autoload = "require AUTOLOAD_FILE;"
    if old_autoload in content:
        content = content.replace(old_autoload, old_autoload + "\n\n" + new_debug, 1)
        sftp = ssh.open_sftp()
        with sftp.file('/usr/share/phpmyadmin/index.php', 'w') as f:
            f.write(content)
        sftp.close()
        print("Marker added after autoload")

run("systemctl restart apache2; echo restarted")

# Test with verbose curl to see headers
run("curl -sk -D- -o /dev/null https://aqssat.co/phpmyadmin/index.php 2>/dev/null | head -20")

# Test SQL page headers
print("=== SQL page headers (no auth) ===")
run("curl -sk -D- -o /dev/null 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null | head -20")

# Test with login
run(r"""rm -f /tmp/pma_tc4.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_tc4.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_tc4.txt -c /tmp/pma_tc4.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1

echo "=== Dashboard headers ==="
curl -sk -b /tmp/pma_tc4.txt -D- -o /dev/null 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null | head -10

echo "=== SQL page headers (after login) ==="
curl -sk -b /tmp/pma_tc4.txt -D- 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null | head -20
""")

ssh.close()
