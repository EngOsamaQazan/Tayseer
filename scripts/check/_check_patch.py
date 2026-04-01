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

# 1. Check what the response() method looks like now
print("=== Current response() method ===")
run("sed -n '408,430p' /usr/share/phpmyadmin/libraries/classes/ResponseRenderer.php")

# 2. Test if PHP can write to /tmp from www-data
print("=== PHP write test ===")
run("su -s /bin/bash -c 'php -r \"error_log(\\\"test\\\\n\\\", 3, \\\"/tmp/pma_resp.log\\\"); echo \\\"ok\\\";\"' www-data")
run("cat /tmp/pma_resp.log 2>/dev/null || echo 'no file'")

# 3. Simple PHP test to write to file
print("=== Simple write test ===")
run("su -s /bin/bash -c 'php -r \"file_put_contents(\\\"/tmp/pma_write_test.txt\\\", \\\"hello\\\"); echo \\\"wrote\\\";\"' www-data")
run("cat /tmp/pma_write_test.txt 2>/dev/null || echo 'no file'")

# 4. Check Apache error log for recent fatal errors
print("=== Apache error log (last 20 lines) ===")
run("tail -20 /var/log/apache2/error.log 2>/dev/null")

# 5. Check PHP error log
print("=== PHP error log ===")
run("tail -20 /var/log/php8.5-fpm.log 2>/dev/null; tail -20 /var/log/php_errors.log 2>/dev/null")

ssh.close()
