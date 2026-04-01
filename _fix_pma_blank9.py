import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check if JS files are accessible
    "curl -skI https://aqssat.co/phpmyadmin/js/dist/cross_framing_protection.js 2>&1 | head -10",
    
    # 2. Check jQuery
    "curl -skI https://aqssat.co/phpmyadmin/js/vendor/jquery/jquery.min.js 2>&1 | head -10",
    
    # 3. Check the cross_framing_protection.js content
    "cat /usr/share/phpmyadmin/js/dist/cross_framing_protection.js",
    
    # 4. Check the JS directory exists and has files
    "ls -la /usr/share/phpmyadmin/js/dist/cross_framing_protection.js 2>/dev/null",
    "ls -la /usr/share/phpmyadmin/js/vendor/jquery/jquery.min.js 2>/dev/null",
    
    # 5. Check where cross_framing_protection.js is loaded in the HTML
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep 'cross_framing'",
    
    # 6. Check DNS resolution
    "host aqssat.co 2>/dev/null",
    "nslookup aqssat.co 2>/dev/null | head -10",
    
    # 7. Check what the full HTML source looks like for script tags
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep '<script'",
    
    # 8. Check the Apache vhost config for aqssat.co
    "grep -rn 'aqssat' /etc/apache2/sites-enabled/ 2>/dev/null | head -10",
    
    # 9. Check if the phpmyadmin alias is in the right vhost
    "cat /etc/apache2/sites-enabled/aqssat.co.conf 2>/dev/null | head -30",
    "cat /etc/apache2/sites-enabled/aqssat.co-le-ssl.conf 2>/dev/null | head -30",
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
