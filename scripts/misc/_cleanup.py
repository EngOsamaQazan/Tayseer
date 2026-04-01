import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

stdin, stdout, stderr = client.exec_command("""
for site in /var/www/jadal.aqssat.co /var/www/namaa.aqssat.co /var/www/watar.aqssat.co; do
    rm -f "$site/backend/web/server-diagnostic.php" 2>/dev/null
    echo "Removed server-diagnostic.php from $site"
done
""", timeout=15)
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
print("Done!")
