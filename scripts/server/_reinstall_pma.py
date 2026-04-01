import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Check current phpMyAdmin version and PHP version
    "php -v 2>&1 | head -1",
    
    # 2. Backup current config
    "cp /usr/share/phpmyadmin/config.inc.php /tmp/pma_config_backup.php 2>/dev/null; echo 'config backed up'",
    
    # 3. Remove old phpMyAdmin completely
    "rm -rf /usr/share/phpmyadmin; echo 'old pma removed'",
    
    # 4. Download latest phpMyAdmin (5.2.3 is latest for PHP 8.x)
    # Actually let's get the LATEST version which should support PHP 8.5
    "cd /tmp && wget -q https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz -O pma-latest.tar.gz && echo 'downloaded' && ls -lh pma-latest.tar.gz",
    
    # 5. Extract and install
    """cd /tmp && tar xzf pma-latest.tar.gz && PMA_DIR=$(ls -d phpMyAdmin-*-all-languages 2>/dev/null | head -1) && echo "Extracted: $PMA_DIR" && mv "$PMA_DIR" /usr/share/phpmyadmin && echo 'installed'""",
    
    # 6. Check new version
    "grep 'Version' /usr/share/phpmyadmin/README 2>/dev/null || grep -r 'PMA_VERSION' /usr/share/phpmyadmin/libraries/classes/Version.php 2>/dev/null | head -3",
    
    # 7. Set proper config
    r"""cat > /usr/share/phpmyadmin/config.inc.php << 'PHPCFG'
<?php
$cfg['blowfish_secret'] = '960a605ddc04967789cb19feae2cd29e7a8b1c3d';
$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['TempDir'] = '/tmp';
$cfg['NavigationTreeDbSeparator'] = false;
$cfg['NavigationTreeEnableGrouping'] = false;
PHPCFG
echo "config written"
""",
    
    # 8. Set permissions
    "chown -R www-data:www-data /usr/share/phpmyadmin; echo 'permissions set'",
    
    # 9. Create tmp directory
    "mkdir -p /usr/share/phpmyadmin/tmp && chown www-data:www-data /usr/share/phpmyadmin/tmp && chmod 777 /usr/share/phpmyadmin/tmp; echo 'tmp dir ready'",
    
    # 10. Clear all caches
    "rm -rf /tmp/twig 2>/dev/null; rm -f /var/lib/php/sessions/sess_* 2>/dev/null; echo 'caches cleared'",
    
    # 11. Restart Apache
    "systemctl restart apache2 2>&1; echo 'apache restarted'",
    
    # 12. Test home page
    "curl -sk -o /dev/null -w '%{http_code} size:%{size_download}' https://aqssat.co/phpmyadmin/index.php 2>&1",
    
    # 13. Test SQL page (without auth, should show login)
    "curl -sk -o /dev/null -w '%{http_code} size:%{size_download}' 'https://aqssat.co/phpmyadmin/index.php?route=/database/sql&db=namaa_erp' 2>&1",
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
