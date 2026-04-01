import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Restart Apache (not just reload)
    "systemctl restart apache2 2>&1; echo 'restarted'",
    
    # 2. Test with verbose headers
    """curl -sk -v 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | grep -E 'HTTP/|Location:|Content-Length:|Content-Type:|Set-Cookie:' | head -10""",
    
    # 3. Full output
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | wc -c",
    
    # 4. Full output first 50 lines
    "curl -sk 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1 | head -50",
    
    # 5. Check errors
    "cat /tmp/pma_errors.log 2>/dev/null",
    
    # 6. Check aqssat Apache error log
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
