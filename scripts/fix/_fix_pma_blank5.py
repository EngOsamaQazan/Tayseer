import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check how PMA_PHP_SELF is set
    "grep -rn 'PMA_PHP_SELF' /usr/share/phpmyadmin/libraries/ 2>/dev/null | head -20",
    
    # 2. Check the index.php bootstrap
    "cat /usr/share/phpmyadmin/index.php",
    
    # 3. Check setup.php / bootstrap
    "grep -rn 'PMA_PHP_SELF' /usr/share/phpmyadmin/*.php 2>/dev/null | head -10",
    
    # 4. Try to reproduce via curl to see actual PHP error
    "curl -s -o /dev/null -w '%{http_code}' http://localhost/phpmyadmin/ 2>&1",
    
    # 5. Try with verbose to see PHP error
    "curl -s http://localhost/phpmyadmin/index.php 2>&1 | head -30",
    
    # 6. Check PHP error reporting in apache
    "grep -rn 'display_errors\\|error_reporting\\|error_log' /etc/php/*/apache2/php.ini 2>/dev/null | head -10",
    
    # 7. Enable error display temporarily and test
    """php -d display_errors=1 -r "
\\$_SERVER['REQUEST_URI'] = '/phpmyadmin/index.php?route=/database/sql&db=tayseer_watar';
\\$_SERVER['PHP_SELF'] = '/phpmyadmin/index.php';
\\$_SERVER['SCRIPT_NAME'] = '/phpmyadmin/index.php';
\\$_SERVER['SCRIPT_FILENAME'] = '/usr/share/phpmyadmin/index.php';
\\$_SERVER['DOCUMENT_ROOT'] = '/usr/share/phpmyadmin';
chdir('/usr/share/phpmyadmin');
require '/usr/share/phpmyadmin/index.php';
" 2>&1 | head -30""",
    
    # 8. Check Apache's PHP error log location
    "grep -rn 'error_log' /etc/php/*/apache2/ 2>/dev/null",
    
    # 9. Check if there are PHP errors in syslog
    "grep -i 'php.*fatal\\|php.*error\\|setValue\\|setcookie' /var/log/syslog 2>/dev/null | tail -10",
    
    # 10. Check Apache's specific site error logs
    "ls -la /var/log/apache2/*error* 2>/dev/null",
    
    # 11. Check ALL error logs for today
    "find /var/log -name '*error*' -mtime 0 2>/dev/null",
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
