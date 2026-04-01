# -*- coding: utf-8 -*-
import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

LOCAL_BASE = r"C:\Users\PC\Desktop\Tayseer"

SITES = [
    "/var/www/jadal.aqssat.co",
    "/var/www/namaa.aqssat.co",
    "/var/www/jadal2.aqssat.co",
    "/var/www/namaa2.aqssat.co",
]

FILES_TO_UPLOAD = [
    "backend/modules/diwan/views/diwan/transactions.php",
    "backend/modules/diwan/views/diwan/search.php",
    "backend/modules/diwan/controllers/DiwanController.php",
]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("31.220.82.115", port=22, username="root", password="HAmAS12852", timeout=15)
sftp = client.open_sftp()

for site in SITES:
    print(f"\n=== Uploading to {site} ===")
    for rel_path in FILES_TO_UPLOAD:
        local_path = os.path.join(LOCAL_BASE, rel_path.replace("/", os.sep))
        remote_path = f"{site}/{rel_path}"
        
        if not os.path.exists(local_path):
            print(f"  SKIP (not found): {rel_path}")
            continue
        
        try:
            sftp.stat(os.path.dirname(remote_path))
        except FileNotFoundError:
            print(f"  SKIP (remote dir missing): {rel_path}")
            continue
        
        try:
            sftp.put(local_path, remote_path)
            print(f"  OK: {rel_path}")
        except Exception as e:
            print(f"  ERROR: {rel_path} -> {e}")

# Clear cache and restart Apache
stdin, stdout, stderr = client.exec_command("""
for site in /var/www/jadal.aqssat.co /var/www/namaa.aqssat.co /var/www/jadal2.aqssat.co /var/www/namaa2.aqssat.co; do
    rm -rf $site/backend/runtime/cache/* 2>/dev/null
done
systemctl restart apache2
""", timeout=30)
stdout.read()
print("\nCache cleared + Apache restarted")

sftp.close()
client.close()
print("\nDone!")
