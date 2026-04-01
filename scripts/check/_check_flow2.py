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

# 1. Show ResponseRenderer constructor (around line 165)
print("=== ResponseRenderer constructor ===")
run("sed -n '140,175p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php")

# 2. Full callControllerForRoute
print("=== callControllerForRoute full ===")
run("sed -n '153,210p' /usr/share/phpmyadmin/libraries/classes/Routing.php")

# 3. Check Common::run() - what does it do
print("=== Common::run() ===")
run("grep -n 'function run' /usr/share/phpmyadmin/libraries/classes/Common.php | head -5")
run("sed -n '/function run/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Common.php | head -100")

# 4. Check if OutputBuffering is started in the constructor
print("=== OutputBuffering in constructor ===")
run("grep -n 'OutputBuffering' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php | head -10")

# 5. Check how response() sends output during shutdown
print("=== Response method with context ===")
run("sed -n '405,435p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php")

ssh.close()
