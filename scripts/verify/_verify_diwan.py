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

urls = {
    "transactions": "https://jadal.aqssat.co/diwan/transactions",
    "reports": "https://jadal.aqssat.co/diwan/reports",
    "search": "https://jadal.aqssat.co/diwan/search",
    "correspondence": "https://jadal.aqssat.co/diwan/correspondence-index",
}

for name, url in urls.items():
    out = ssh_exec(f"curl -s -o /dev/null -w '%{{http_code}}' '{url}' 2>&1")
    status = out.strip().replace("'", "")
    icon = "OK" if status in ['200', '302'] else "FAIL"
    print(f"  [{icon}] {name}: {status}")

client.close()
