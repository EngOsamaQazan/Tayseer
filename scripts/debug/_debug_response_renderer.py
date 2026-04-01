import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Look at the ResponseRenderer response() method
    "grep -n 'function response\|function getDisplay\|isAjax\|Content-Length\|ob_get\|ob_end\|header(' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php | head -30",
    
    # 2. Look at the response() method fully
    "sed -n '/function response/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php | head -60",
    
    # 3. Look at OutputBuffering
    "grep -n 'function start\|function stop\|function getContents\|ob_start\|ob_get\|ob_end' /usr/share/phpmyadmin/libraries/classes/OutputBuffering.php",
    
    # 4. Check if there's a PHP 8.5 deprecation/error when rendering
    # Add error handler to catch everything
    r"""cat > /tmp/pma_debug_response.php << 'PHPCODE'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/pma_response_debug.log');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP_ERR[$errno]: $errstr in $errfile:$errline\n", 3, '/tmp/pma_response_debug.log');
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        error_log("SHUTDOWN_ERR: " . json_encode($err) . "\n", 3, '/tmp/pma_response_debug.log');
    }
    error_log("SHUTDOWN: ob_level=" . ob_get_level() . " ob_len=" . (ob_get_length() !== false ? ob_get_length() : 'false') . "\n", 3, '/tmp/pma_response_debug.log');
});

error_log("=== REQUEST: " . $_SERVER['REQUEST_URI'] . " ===\n", 3, '/tmp/pma_response_debug.log');
PHPCODE
echo "debug file created"
""",
    
    # 5. Set auto_prepend in .htaccess
    r"""cat > /usr/share/phpmyadmin/.htaccess << 'HT'
php_flag log_errors on
php_value error_log /tmp/pma_response_debug.log
php_flag display_errors off
php_value auto_prepend_file /tmp/pma_debug_response.php
HT
echo "htaccess set"
""",
    
    # 6. Restart Apache
    "systemctl restart apache2; echo 'restarted'",
    
    # 7. Clear the debug log
    "rm -f /tmp/pma_response_debug.log; echo 'log cleared'",
    
    # 8. Login and test SQL page (direct, non-AJAX)
    r"""rm -f /tmp/pma_dbg_cookies.txt 2>/dev/null
RESP=$(curl -sk -c /tmp/pma_dbg_cookies.txt https://aqssat.co/phpmyadmin/index.php 2>/dev/null)
TOKEN=$(echo "$RESP" | grep -oP 'name="token" value="\K[^"]+' | head -1)
curl -sk -b /tmp/pma_dbg_cookies.txt -c /tmp/pma_dbg_cookies.txt -L \
  -d "pma_username=root&pma_password=HAmAS12852&server=1&token=$TOKEN" \
  'https://aqssat.co/phpmyadmin/index.php' > /dev/null 2>&1
echo "Login done"

# Test SQL page (non-AJAX) 
SQL=$(curl -sk -b /tmp/pma_dbg_cookies.txt \
  'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>/dev/null)
echo "SQL page size: ${#SQL}"

# Also test dashboard (non-AJAX, should work)
DASH=$(curl -sk -b /tmp/pma_dbg_cookies.txt \
  'https://aqssat.co/phpmyadmin/index.php?route=/' 2>/dev/null)
echo "Dashboard size: ${#DASH}"
""",
    
    # 9. Check the debug log
    "cat /tmp/pma_response_debug.log 2>/dev/null || echo 'no log'",
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
