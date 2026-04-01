# -*- coding: utf-8 -*-
import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("31.220.82.115", port=22, username="root", password="HAmAS12852", timeout=15)

def ssh_exec(cmd, timeout=30):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode('utf-8', errors='replace')

# 1. Latest access log for diwan/transactions
out = ssh_exec("grep 'diwan/transactions' /var/log/apache2/jadal.aqssat.co-access.log | tail -5")
print("=== Latest diwan/transactions access ===")
print(out)

# 2. Check if ErrorAction.php exists
out = ssh_exec("ls -la /var/www/jadal.aqssat.co/vendor/yiisoft/yii2/web/ErrorAction.php 2>&1")
print("=== ErrorAction.php ===")
print(out)

# 3. Check Permissions.php on server has diwan route
out = ssh_exec("grep -n 'diwan' /var/www/jadal.aqssat.co/common/helper/Permissions.php 2>/dev/null")
print("=== Permissions.php diwan entries ===")
print(out)

# 4. Check getActionPermission for diwan
out = ssh_exec("grep -B2 -A15 'getActionPermission' /var/www/jadal.aqssat.co/common/helper/Permissions.php 2>/dev/null | head -40")
print("=== getActionPermission ===")
print(out)

# 5. Check the diwan action map in Permissions
out = ssh_exec("grep -B2 -A10 \"diwan/diwan\" /var/www/jadal.aqssat.co/common/helper/Permissions.php 2>/dev/null")
print("=== diwan/diwan action map ===")
print(out)

# 6. Most recent app.log entries (last 10 lines)
out = ssh_exec("tail -15 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null")
print("=== Last 15 lines of app.log ===")
print(out[:2000])

client.close()
