import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check MySQL root authentication plugin
    "mysql -u root -e \"SELECT user, host, plugin FROM mysql.user WHERE user='root';\" 2>&1",
    
    # 2. Check all MySQL users
    "mysql -u root -e \"SELECT user, host, plugin FROM mysql.user;\" 2>&1",
    
    # 3. Check if root can auth with password via TCP (like phpMyAdmin does)
    "mysql -u root -pHAmAS12852 -h 127.0.0.1 -e 'SELECT 1' 2>&1",
    
    # 4. Check what phpMyAdmin sees when authenticating
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 5. Try with osama user (the one pre-filled in the login form earlier)
    "mysql -u root -e \"SELECT user, host, plugin FROM mysql.user WHERE user='osama';\" 2>&1",
    
    # 6. Check which databases exist
    "mysql -u root -e 'SHOW DATABASES' 2>&1",
    
    # 7. Now let me ask: what happens when you TRY to log in via phpMyAdmin?
    # Let me test with the phpMyAdmin PHP auth directly
    """php -d display_errors=1 -r '
define("ROOT_PATH", "/usr/share/phpmyadmin/");
define("PHPMYADMIN", true);
require ROOT_PATH . "libraries/constants.php";
require ROOT_PATH . "vendor/autoload.php";

// Test MySQL connection
\$link = @mysqli_connect("localhost", "root", "HAmAS12852");
if (\$link) {
    echo "MySQL connection OK\n";
    echo "Server info: " . mysqli_get_server_info(\$link) . "\n";
    mysqli_close(\$link);
} else {
    echo "MySQL connection FAILED: " . mysqli_connect_error() . "\n";
}

// Test via 127.0.0.1
\$link2 = @mysqli_connect("127.0.0.1", "root", "HAmAS12852");
if (\$link2) {
    echo "MySQL 127.0.0.1 connection OK\n";
    mysqli_close(\$link2);
} else {
    echo "MySQL 127.0.0.1 FAILED: " . mysqli_connect_error() . "\n";
}
' 2>&1""",
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
