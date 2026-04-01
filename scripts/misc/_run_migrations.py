import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

sites = [
    ('/var/www/jadal.aqssat.co', 'JADAL'),
    ('/var/www/namaa.aqssat.co', 'NAMAA'),
    ('/var/www/watar.aqssat.co', 'WATAR'),
]

for path, name in sites:
    print(f"\n{'='*50}")
    print(f"  Running migrations on {name}")
    print(f"{'='*50}")
    stdin, stdout, stderr = client.exec_command(
        f"cd {path} && php yii migrate/up --interactive=0 2>&1",
        timeout=120
    )
    print(stdout.read().decode('utf-8', errors='replace'))
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if err:
        print(f"STDERR: {err}")

client.close()
print("\nDone!")
