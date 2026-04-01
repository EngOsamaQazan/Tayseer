import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Remove .htaccess completely
    "rm -f /usr/share/phpmyadmin/.htaccess; echo 'htaccess removed'",
    
    # 2. Restart Apache
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 3. Test SQL page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | wc -c",
    
    # 4. Full SQL page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -10",
    
    # 5. Test database structure page
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/structure&db=namaa_erp' 2>&1 | wc -c",
    
    # 6. Test home page (should work)
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php' 2>&1 | wc -c",
    
    # 7. Check the ResponseRenderer getDisplay method
    "grep -n 'function getDisplay' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php",
    "sed -n '/function getDisplay/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php",
    
    # 8. Check hasDatabase
    "grep -n 'function hasDatabase' /usr/share/phpmyadmin/libraries/classes/Controllers/Database/AbstractController.php 2>/dev/null",
    "sed -n '/function hasDatabase/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Controllers/Database/AbstractController.php 2>/dev/null",
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
