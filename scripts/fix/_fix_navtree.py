import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=10)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out: print(out)
    if err: print(f'ERR: {err}')
    print()
    return out

# 1. Show NavigationTree.php around line 228
print("=== NavigationTree.php lines 220-240 ===")
run("sed -n '220,240p' /usr/share/phpmyadmin/libraries/classes/Navigation/NavigationTree.php")

# 2. Show DatabaseInterface.php around line 2225
print("=== DatabaseInterface.php escapeString ===")
run("sed -n '2220,2235p' /usr/share/phpmyadmin/libraries/classes/DatabaseInterface.php")

# 3. Show more context around line 228
print("=== NavigationTree.php wider context ===")
run("sed -n '200,250p' /usr/share/phpmyadmin/libraries/classes/Navigation/NavigationTree.php")

ssh.close()
