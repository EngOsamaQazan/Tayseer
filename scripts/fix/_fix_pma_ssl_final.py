import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check if mod_ssl properly sets HTTPS env
    "cat /etc/apache2/sites-enabled/aqssat.co-le-ssl.conf",
    
    # 2. Check which Apache SSL config is loaded
    "cat /etc/letsencrypt/options-ssl-apache.conf 2>/dev/null | head -20",
    
    # 3. Check if SSLEngine is set somewhere
    "grep -rn 'SSLEngine' /etc/apache2/ 2>/dev/null | head -10",
    
    # 4. Create a test PHP file to verify HTTPS detection
    """echo '<?php echo json_encode(["HTTPS"=>$_SERVER["HTTPS"]??"NOT","PORT"=>$_SERVER["SERVER_PORT"]??"NOT","SCHEME"=>$_SERVER["REQUEST_SCHEME"]??"NOT","REDIRECT_HTTPS"=>$_SERVER["REDIRECT_HTTPS"]??"NOT"]);' > /var/www/html/test_ssl.php""",
    
    # 5. Test it
    "curl -sk https://aqssat.co/test_ssl.php 2>&1",
    
    # 6. Check the mismatch detection JS
    "grep -rn 'https-mismatch\\|js-https-mismatch' /usr/share/phpmyadmin/js/ 2>/dev/null | head -5",
    "grep -rn 'https-mismatch\\|js-https-mismatch' /usr/share/phpmyadmin/templates/ 2>/dev/null | head -5",
    
    # 7. Find the mismatch detection template
    "grep -rn 'mismatch' /usr/share/phpmyadmin/templates/ 2>/dev/null | head -5",
    
    # 8. Get the mismatch template content
    "grep -B5 -A5 'mismatch' /usr/share/phpmyadmin/templates/login/form.twig 2>/dev/null",
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
