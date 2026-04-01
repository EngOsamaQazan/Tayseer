import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
cmd = 'sed -n "544,620p" /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php'
stdin, stdout, stderr = client.exec_command(cmd, timeout=15)
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
