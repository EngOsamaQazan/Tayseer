import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # Change the default value directly in the source config file
    "sed -i \"s/\\$cfg\\['NavigationTreeDbSeparator'\\] = '_'/\\$cfg['NavigationTreeDbSeparator'] = false/\" /usr/share/phpmyadmin/libraries/config.default.php",
    # Verify
    "grep 'NavigationTreeDbSeparator' /usr/share/phpmyadmin/libraries/config.default.php",
    # Also check NavigationTreeEnableGrouping in default
    "grep 'NavigationTreeEnableGrouping' /usr/share/phpmyadmin/libraries/config.default.php",
    # Change it too if exists
    "sed -i \"s/\\$cfg\\['NavigationTreeEnableGrouping'\\] = true/\\$cfg['NavigationTreeEnableGrouping'] = false/\" /usr/share/phpmyadmin/libraries/config.default.php",
    # Also clear phpMyAdmin temp/session files
    "rm -rf /tmp/twig /tmp/phpmyadmin 2>/dev/null; echo done",
    # Restart PHP-FPM or Apache to clear sessions
    "systemctl restart php*-fpm 2>/dev/null; systemctl restart apache2 2>/dev/null; systemctl restart nginx 2>/dev/null; echo restarted",
]
for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    print(f'=== {cmd[:70]} ===')
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
ssh.close()
