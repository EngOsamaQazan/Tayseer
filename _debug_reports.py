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

# Search for diwan/reports error
out = ssh_exec("grep -B2 -A30 'diwan.*reports\\|reports.*diwan' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -B2 -A20 'error\\|Exception\\|Fatal' | tail -40")
print("=== diwan reports errors ===")
print(out[:3000])

# Also check the NameHelper class
out = ssh_exec("ls -la /var/www/jadal.aqssat.co/backend/helpers/NameHelper.php 2>&1")
print("\n=== NameHelper.php ===")
print(out)

# Check _diwan-tabs
out = ssh_exec("cat /var/www/jadal.aqssat.co/backend/views/layouts/_diwan-tabs.php 2>&1")
print("\n=== _diwan-tabs.php content ===")
print(out[:2000])

# Check the reports error in access log
out = ssh_exec("grep 'diwan/reports' /var/log/apache2/jadal.aqssat.co-access.log | grep ' 500 ' | tail -3")
print("\n=== reports 500 in access log ===")
print(out)

# Check the exact error in access log time
out = ssh_exec("grep -B2 -A35 '08:46:38\\|10:46' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -B2 -A20 'error\\|Exception' | tail -40")
print("\n=== Error at 08:46/10:46 ===")
print(out[:3000])

client.close()
