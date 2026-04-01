import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

cmds = [
    # 1. Test MySQL connection as www-data user
    """su -s /bin/bash -c 'php -r "echo @mysqli_connect(\\"localhost\\",\\"root\\",\\"\\") ? \\"OK\\" : \\"FAIL: \\".mysqli_connect_error();"' www-data 2>&1""",
    
    # 2. Test with password
    """su -s /bin/bash -c 'php -r "echo @mysqli_connect(\\"localhost\\",\\"root\\",\\"HAmAS12852\\") ? \\"OK\\" : \\"FAIL: \\".mysqli_connect_error();"' www-data 2>&1""",
    
    # 3. Check if root uses unix_socket or mysql_native_password
    "mysql -u root -e \"SELECT user, host, plugin, authentication_string FROM mysql.user WHERE user='root';\" 2>&1",
    
    # 4. Check MySQL version
    "mysql -u root -e 'SELECT VERSION()' 2>&1",
    
    # 5. Check if unix_socket plugin is active
    "mysql -u root -e \"SHOW PLUGINS\" 2>&1 | grep -i unix",
    
    # 6. Fix root to use mysql_native_password with a proper password
    """mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('HAmAS12852');" 2>&1""",
    
    # 7. Test login after fix
    """mysql -u root -pHAmAS12852 -e 'SELECT 1' 2>&1""",
    
    # 8. Test as www-data with the password
    """su -s /bin/bash -c 'php -r "echo @mysqli_connect(\\"localhost\\",\\"root\\",\\"HAmAS12852\\") ? \\"OK\\" : \\"FAIL: \\".mysqli_connect_error();"' www-data 2>&1""",
    
    # 9. Also test osama user
    """su -s /bin/bash -c 'php -r "echo @mysqli_connect(\\"localhost\\",\\"osama\\",\\"\\") ? \\"OK\\" : \\"FAIL: \\".mysqli_connect_error();"' www-data 2>&1""",
    
    # 10. Check osama's password
    "mysql -u root -pHAmAS12852 -e \"SELECT user, host, plugin, authentication_string FROM mysql.user WHERE user='osama';\" 2>&1",
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
