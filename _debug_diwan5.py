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

# Search for 141.105.60.174 errors in app.log (user who got 500)
out = ssh_exec("grep -B2 -A30 '141.105.60.174' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -B2 -A30 'error\\|Exception\\|Fatal' | tail -60")
print("=== 141.105.60.174 errors ===")
print(out[:3000])

# Also search for errors around 11:34 in app log (09:34 +0100 = 11:34 +0300)
out2 = ssh_exec("grep -B2 -A30 '11:34\\|11:33\\|11:35' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -B2 -A30 'error\\|Exception' | tail -80")
print("\n=== Errors around 11:34 ===")
print(out2[:3000])

# Check RouteAccessBehavior on server - check line 130
out3 = ssh_exec("sed -n '125,140p' /var/www/jadal.aqssat.co/backend/components/RouteAccessBehavior.php 2>/dev/null")
print("\n=== RouteAccessBehavior lines 125-140 ===")
print(out3)

# Get latest 500 errors from access log
out4 = ssh_exec("grep ' 500 ' /var/log/apache2/jadal.aqssat.co-access.log | grep 'diwan' | tail -5")
print("\n=== Latest diwan 500s ===")
print(out4)

client.close()
