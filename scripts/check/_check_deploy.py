import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

stdin, stdout, stderr = client.exec_command("""
echo "=== Latest Git Log (jadal) ==="
cd /var/www/jadal.aqssat.co && git log -3 --oneline 2>/dev/null
echo ""
echo "=== .htaccess has Gzip? ==="
grep -c 'mod_deflate' /var/www/jadal.aqssat.co/backend/web/.htaccess 2>/dev/null && echo "YES - Gzip enabled" || echo "NO - old version"
echo ""
echo "=== Has assetVersion? ==="
grep -c 'assetVersion' /var/www/jadal.aqssat.co/common/config/params.php 2>/dev/null && echo "YES - cache busting fixed" || echo "NO - old version"
echo ""
echo "=== Has performance indexes migration? ==="
ls -la /var/www/jadal.aqssat.co/console/migrations/m260327_000001_add_performance_indexes.php 2>/dev/null && echo "YES" || echo "NO - not deployed yet"
echo ""
echo "=== Has server-diagnostic? ==="
ls -la /var/www/jadal.aqssat.co/backend/web/server-diagnostic.php 2>/dev/null && echo "YES" || echo "NO"
""", timeout=15)

print(stdout.read().decode('utf-8', errors='replace'))
client.close()
