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

# Get full error context from jadal app.log
out = ssh_exec("grep -B5 -A40 'Exception\\|Error\\|Fatal' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | tail -100")
print("=== Jadal errors ===")
print(out[:4000])

# Also check Apache error log for jadal
out2 = ssh_exec("tail -50 /var/log/apache2/error.log 2>/dev/null | grep -i jadal")
print("\n=== Apache errors for jadal ===")
print(out2[:2000])

# Check Apache error log specific to jadal
out3 = ssh_exec("tail -50 /var/log/apache2/jadal.aqssat.co-error.log 2>/dev/null")
print("\n=== jadal error log ===")
print(out3[:2000])

# Get the most recent error from any apache error log
out4 = ssh_exec("ls -la /var/log/apache2/*jadal* 2>/dev/null")
print("\n=== jadal apache log files ===")
print(out4)

# Try PHP error log
out5 = ssh_exec("tail -30 /var/log/php*.log 2>/dev/null; tail -30 /var/log/apache2/error.log 2>/dev/null")
print("\n=== PHP/Apache error log ===")
print(out5[:3000])

client.close()
