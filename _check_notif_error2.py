import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=15):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

# Check jadal app.log
out, _ = run("wc -l /var/www/jadal.aqssat.co/backend/runtime/logs/app.log")
print("=== LOG FILE SIZE ===")
print(out)

out, _ = run("tail -200 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log")
print("=== JADAL APP.LOG (last 200 lines) ===")
print(out[-8000:] if len(out) > 8000 else out)

# Check if there are other log files
out, _ = run("ls -la /var/www/jadal.aqssat.co/backend/runtime/logs/")
print("=== LOG DIRECTORY ===")
print(out)

# Check the notification controller file on server
out, _ = run("cat /var/www/jadal.aqssat.co/backend/modules/notification/controllers/NotificationController.php")
print("=== CONTROLLER ON SERVER ===")
print(out[:3000])

# Check the index view on server
out, _ = run("cat /var/www/jadal.aqssat.co/backend/modules/notification/views/notification/index.php")
print("=== INDEX VIEW ON SERVER ===")
print(out)

# Check PHP error log
out, _ = run("tail -50 /var/log/php*.log 2>/dev/null; tail -50 /var/log/php/error.log 2>/dev/null; tail -50 /var/log/php8.*/error.log 2>/dev/null")
print("=== PHP ERROR LOG ===")
print(out[-3000:] if len(out) > 3000 else out)

ssh.close()
