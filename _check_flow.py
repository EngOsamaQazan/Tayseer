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

# 1. Show index.php
print("=== index.php ===")
run("cat /usr/share/phpmyadmin/index.php")

# 2. Check Routing.php dispatch
print("=== Routing dispatch ===")
run("grep -n 'response\|dispatch\|__invoke\|callController\|call_user_func' /usr/share/phpmyadmin/libraries/classes/Routing.php | head -20")

# 3. Show the Routing::callControllerForCurrentRoute method
print("=== callControllerForCurrentRoute ===")
run("sed -n '/function callControllerForCurrentRoute/,/^    }/p' /usr/share/phpmyadmin/libraries/classes/Routing.php")

# 4. Check if response() is called somewhere as a shutdown function
print("=== shutdown/destruct references ===")
run("grep -rn 'register_shutdown\|__destruct\|->response()' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php")
run("grep -rn '->response()' /usr/share/phpmyadmin/index.php /usr/share/phpmyadmin/libraries/classes/Routing.php 2>/dev/null")

ssh.close()
