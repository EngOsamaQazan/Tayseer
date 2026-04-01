import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check removeCookie function
    "sed -n '/function removeCookie/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 2. Check around lines 888-910
    "sed -n '885,910p' /usr/share/phpmyadmin/libraries/classes/Config.php",
    
    # 3. Test what PMA_PHP_SELF would be in a web context
    """php -r "echo parse_url('/phpmyadmin/index.php?route=/database/sql&db=tayseer_watar', PHP_URL_PATH);" 2>&1""",
    
    # 4. Test setcookie with clean path on PHP 8.5
    """php -r "var_dump(setcookie('test', 'val', ['path'=>'/phpmyadmin/','expires'=>0,'samesite'=>'Strict']));" 2>&1""",
    
    # 5. Check the actual cookie_path by simulating
    """cd /usr/share/phpmyadmin && php -r "
\\$path = '/phpmyadmin/index.php';
\\$parts = explode('/', rtrim(str_replace('\\\\\\\\', '/', \\$path), '/'));
if (substr(\\$parts[count(\\$parts) - 1], -4) === '.php') {
    \\$parts = array_slice(\\$parts, 0, count(\\$parts) - 1);
}
\\$parts[] = '';
\\$result = implode('/', \\$parts);
echo 'Path result: [' . \\$result . ']';
echo ' Length: ' . strlen(\\$result);
echo ' Hex: ' . bin2hex(\\$result);
" 2>&1""",
    
    # 6. Check if there's any .htaccess rewriting going on
    "cat /usr/share/phpmyadmin/.htaccess 2>/dev/null",
    
    # 7. Check actual PHP error log (not just generic log)
    "find /var/log -name '*.log' -newer /usr/share/phpmyadmin/index.php -exec grep -l 'phpmyadmin\\|setcookie\\|ValueError' {} \\; 2>/dev/null",
    
    # 8. Check syslog for PHP errors  
    "journalctl -u apache2 --since '1 hour ago' --no-pager 2>/dev/null | tail -20",
    
    # 9. Get PHP error log path
    "php -r \"echo ini_get('error_log');\" 2>/dev/null",
    
    # 10. Check apache error log for cookie errors
    "grep -i 'cookie\\|ValueError\\|Fatal' /var/log/apache2/error.log 2>/dev/null | tail -10",
    
    # 11. Check PHP's display_errors setting
    "php -r \"echo 'display_errors: ' . ini_get('display_errors') . PHP_EOL . 'log_errors: ' . ini_get('log_errors') . PHP_EOL . 'error_log: ' . ini_get('error_log');\" 2>/dev/null",
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
