import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=15):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

# Find the app.log
out, err = run("find /var/www -name 'app.log' -path '*/backend/runtime/*' 2>/dev/null | head -5")
print("=== LOG FILES ===")
print(out)

# Try common locations
out, err = run("tail -100 /var/www/tayseer/backend/runtime/logs/app.log 2>/dev/null")
if not out.strip():
    out, err = run("tail -100 /var/www/html/backend/runtime/logs/app.log 2>/dev/null")
if not out.strip():
    out, err = run("tail -100 /var/www/html/tayseer/backend/runtime/logs/app.log 2>/dev/null")

print("=== RECENT ERRORS ===")
print(out[-5000:] if len(out) > 5000 else out)

# Also check Apache/Nginx error logs
out2, _ = run("tail -30 /var/log/apache2/error.log 2>/dev/null || tail -30 /var/log/nginx/error.log 2>/dev/null || tail -30 /var/log/httpd/error_log 2>/dev/null")
print("=== WEB SERVER ERRORS ===")
print(out2[-3000:] if len(out2) > 3000 else out2)

ssh.close()
