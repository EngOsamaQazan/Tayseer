import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

cmd = "grep -n 'followUp\\|error' /var/www/jadal.aqssat.co/backend/runtime/logs/app.log | tail -30"

i, o, e = c.exec_command(cmd, timeout=15)
print(o.read().decode('utf-8', errors='replace'))
c.close()
