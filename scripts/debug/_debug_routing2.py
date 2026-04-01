import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Find where routes are defined
    "ls /usr/share/phpmyadmin/libraries/routes* 2>/dev/null",
    "find /usr/share/phpmyadmin/libraries -name '*route*' 2>/dev/null",
    
    # 2. Check Routing class for route resolution
    "head -100 /usr/share/phpmyadmin/libraries/classes/Routing.php 2>/dev/null",
    
    # 3. Check the routes file
    "wc -l /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null",
    "grep 'database' /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null | head -10",
    
    # 4. Check if routes use a different pattern
    "head -50 /usr/share/phpmyadmin/libraries/routes.php 2>/dev/null",
    
    # 5. Check Common::run for what happens with database routes
    "grep -n 'database\\|checkDb\\|hasDatabase\\|GLOBALS.*db' /usr/share/phpmyadmin/libraries/classes/Common.php 2>/dev/null | head -20",
    
    # 6. Check the full Common::run function - specifically the db check part
    "sed -n '/GLOBALS..db/,+10p' /usr/share/phpmyadmin/libraries/classes/Common.php 2>/dev/null | head -30",
    
    # 7. Check the setCookie for the config auth session
    "grep -n 'config.*auth\\|auth.*config' /usr/share/phpmyadmin/libraries/classes/ -r 2>/dev/null | head -10",
    
    # 8. Actually, let's trace the exact flow - check if there's a "setcookie" ValueError
    # by testing the config auth login flow via PHP
    """php -d 'error_log=/tmp/pma_php_errors.log' -d 'log_errors=1' -d 'display_errors=0' -r '
chdir("/usr/share/phpmyadmin");
\$_SERVER["REQUEST_URI"] = "/phpmyadmin/index.php?route=/database/sql&db=namaa_erp";
\$_SERVER["PHP_SELF"] = "/phpmyadmin/index.php";
\$_SERVER["SCRIPT_NAME"] = "/phpmyadmin/index.php";
\$_SERVER["SCRIPT_FILENAME"] = "/usr/share/phpmyadmin/index.php";
\$_SERVER["DOCUMENT_ROOT"] = "/usr/share/phpmyadmin";
\$_SERVER["HTTPS"] = "on";
\$_SERVER["SERVER_PORT"] = "443";
\$_SERVER["HTTP_HOST"] = "aqssat.co";
\$_SERVER["REQUEST_SCHEME"] = "https";
\$_SERVER["REQUEST_METHOD"] = "GET";
\$_GET["route"] = "/database/sql";
\$_GET["db"] = "namaa_erp";
\$_REQUEST["route"] = "/database/sql";
\$_REQUEST["db"] = "namaa_erp";
ob_start();
require "/usr/share/phpmyadmin/index.php";
\$output = ob_get_contents();
ob_end_clean();
echo "OUTPUT_LENGTH=" . strlen(\$output) . "\n";
echo substr(\$output, 0, 500);
' 2>&1 | head -20""",
    
    # 9. Check PHP errors from the above test
    "cat /tmp/pma_php_errors.log 2>/dev/null | tail -5",
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
