import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

sftp = client.open_sftp()

local = r'c:\Users\PC\Desktop\Tayseer\backend\modules\inventoryInvoices\views\inventory-invoices\create-wizard.php'
remote = '/var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php'

import os
local_size = os.path.getsize(local)
sftp.put(local, remote)
remote_stat = sftp.stat(remote)
print(f'Local: {local_size} | Remote: {remote_stat.st_size} | Match: {local_size == remote_stat.st_size}')

sftp.close()

# Restart PHP + clear cache
stdin, stdout, stderr = client.exec_command(
    'chown www-data:www-data /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php && '
    'cd /var/www/jadal.aqssat.co && rm -rf backend/runtime/cache/* && '
    'php yii cache/flush-all 2>/dev/null && '
    'PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.\\".\\".PHP_MINOR_VERSION;" 2>/dev/null) && '
    'systemctl restart php${PHP_VER}-fpm 2>/dev/null && '
    'systemctl reload apache2 2>/dev/null && '
    'echo DONE',
    timeout=20
)
print(stdout.read().decode('utf-8', errors='replace'))

# Verify: check that the new JS code exists (document.getElementById pattern)
stdin, stdout, stderr = client.exec_command(
    'grep -c "document.getElementById" /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php',
    timeout=10
)
count = stdout.read().decode().strip()
print(f'document.getElementById count: {count}')

# Verify: no more \\$btn
stdin, stdout, stderr = client.exec_command(
    'grep -c "\\\\$btn" /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php',
    timeout=10
)
count = stdout.read().decode().strip()
print(f'\\$btn count (should be 0): {count}')

# Verify: e.preventDefault exists
stdin, stdout, stderr = client.exec_command(
    'grep -c "e.preventDefault" /var/www/jadal.aqssat.co/backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php',
    timeout=10
)
count = stdout.read().decode().strip()
print(f'e.preventDefault count: {count}')

client.close()
print('UPLOAD COMPLETE')
