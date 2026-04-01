# -*- coding: utf-8 -*-
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("31.220.82.115", port=22, username="root", password="HAmAS12852", timeout=15)

def ssh_exec(cmd, timeout=30):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    return stdout.read().decode('utf-8', errors='replace')

# Get latest jadal errors
out = ssh_exec("tail -300 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -B2 -A25 'error\\|Exception' | tail -80")
with open("_diwan_err.txt", "w", encoding="utf-8") as f:
    f.write(out)

# Get latest jadal 500s from access log
out2 = ssh_exec("grep ' 500 ' /var/log/apache2/jadal.aqssat.co-access.log 2>/dev/null | tail -10")
with open("_diwan_err.txt", "a", encoding="utf-8") as f:
    f.write("\n\n=== Access log 500s ===\n" + out2)

# Check the transactions action in DiwanController
out3 = ssh_exec("grep -n 'actionTransactions\\|function.*[Tt]ransaction' /var/www/jadal.aqssat.co/backend/modules/diwan/controllers/DiwanController.php 2>/dev/null")
with open("_diwan_err.txt", "a", encoding="utf-8") as f:
    f.write("\n\n=== DiwanController actions ===\n" + out3)

print("Saved to _diwan_err.txt")
client.close()
