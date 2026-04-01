import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Get full curl response with headers to see Cloudflare headers
    "curl -skI https://aqssat.co/phpmyadmin/index.php 2>&1",
    
    # 2. Get the full HTML to see if Cloudflare scripts are injected
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -i 'cloudflare\\|cfs-style\\|cf-\\|rocket\\|email-decode\\|display.*none'",
    
    # 3. Check if there's a login form - maybe it's just the login page CSS issue
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -i 'login\\|pma_username\\|form'",
    
    # 4. Get full response body size to confirm it's not empty
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | wc -c",
    
    # 5. Check if there's an email-decode.min.js (Cloudflare Email Obfuscation)
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | grep -o 'email-decode[^\"]*'",
    
    # 6. Get the full page including Cloudflare injected scripts
    "curl -sk https://aqssat.co/phpmyadmin/index.php 2>&1 | tail -30",
    
    # 7. Check if accessing via IP works differently
    "curl -sk https://31.220.82.115/phpmyadmin/index.php 2>&1 | head -5",
    
    # 8. Fix option: Add .htaccess to disable Cloudflare features for phpMyAdmin
    # Or add a header that tells Cloudflare not to process
    """cat > /usr/share/phpmyadmin/.htaccess << 'HTEOF'
# Disable Cloudflare features that break phpMyAdmin
<IfModule mod_headers.c>
    Header set Cache-Control "no-cache, no-store, must-revalidate"
</IfModule>

# Force display of page in case Cloudflare hides it
php_flag display_errors Off
HTEOF
echo "htaccess written"
""",
    
    # 9. Better fix: Add inline script to override Cloudflare's cfs-style
    # Modify phpMyAdmin's index.php to add a workaround
    """cp /usr/share/phpmyadmin/index.php /usr/share/phpmyadmin/index.php.bak""",
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
