import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Remove debug .htaccess 
    r"""cat > /usr/share/phpmyadmin/.htaccess << 'HTEOF'
php_flag display_errors On
php_flag log_errors On
php_value error_log /tmp/pma_errors.log
HTEOF
echo ".htaccess simplified"
""",
    
    # 2. Clean up debug file
    "rm -f /usr/share/phpmyadmin/_debug_prepend.php /usr/share/phpmyadmin/index.php.bak 2>/dev/null; echo 'cleaned'",
    
    # 3. Restart
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 4. Test SQL page with FULL verbose output
    "curl -sk -v 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1",
    
    # 5. Test the home page to compare
    "curl -sk -v 'https://aqssat.co/phpmyadmin/index.php?route=/' 2>&1 | head -30",
    
    # 6. Test with a different route - database structure 
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/structure&db=namaa_erp' 2>&1 | head -5",
    
    # 7. Check PHP error log
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 8. Check Apache error log recent entries
    "tail -3 /var/log/apache2/aqssat_error.log 2>/dev/null",
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
