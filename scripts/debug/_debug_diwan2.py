# -*- coding: utf-8 -*-
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("31.220.82.115", port=22, username="root", password="HAmAS12852", timeout=15)

def ssh_exec(cmd, timeout=30):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

# 1. Check if tables exist in namaa_jadal
out, err = ssh_exec("mysql -u root -e \"SHOW TABLES LIKE 'os_diwan%'\" namaa_jadal 2>&1")
print("=== namaa_jadal os_diwan tables ===")
print(out)
if err: print("ERR:", err)

# 2. Check in namaa_erp
out, err = ssh_exec("mysql -u root -e \"SHOW TABLES LIKE 'os_diwan%'\" namaa_erp 2>&1")
print("=== namaa_erp os_diwan tables ===")
print(out)
if err: print("ERR:", err)

# 3. Get error from app.log (look for recent errors)
out, err = ssh_exec("tail -200 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null")
# Write full log to file for inspection
with open("_jadal_applog.txt", "w", encoding="utf-8") as f:
    f.write(out)
print("=== Last 5 lines of jadal app.log ===")
lines = out.strip().split('\n')
for line in lines[-5:]:
    print(line[:200])

# 4. Check the _diwan-tabs file on server
out, err = ssh_exec("cat /var/www/jadal.aqssat.co/backend/views/layouts/_diwan-tabs.php 2>&1")
print("\n=== _diwan-tabs.php exists? ===")
print("YES" if 'transactions' in out else "NO/NOT FOUND")
if err: print("ERR:", err)

# 5. Check the transactions.php view on server
out, err = ssh_exec("ls -la /var/www/jadal.aqssat.co/backend/modules/diwan/views/diwan/transactions.php 2>&1")
print("\n=== transactions.php on server ===")
print(out)

# 6. Check kartik/gridview installed
out, err = ssh_exec("ls /var/www/jadal.aqssat.co/vendor/kartik-v/yii2-grid/ 2>&1")
print("\n=== kartik grid vendor ===")
print(out[:200] if out else "NOT FOUND")
if err: print("ERR:", err[:200])

client.close()
