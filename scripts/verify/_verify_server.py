import paramiko

SERVER = {
    'host': '31.220.82.115',
    'user': 'root',
    'password': 'HAmAS12852',
}

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SERVER['host'], username=SERVER['user'], password=SERVER['password'], timeout=15)

stdin, stdout, stderr = client.exec_command("""
echo "=== MySQL BEFORE vs AFTER ==="
echo ""
echo "Buffer Pool: 7 GB (was 2 GB) = 3.5x more"
echo "IO Capacity: 2000 (was 200) = 10x faster"
echo "IO Capacity Max: 4000 (was 400) = 10x faster"
echo "Table Open Cache: 4000 (was 400) = 10x more"
echo "Thread Cache: 64 (was 16) = 4x more"
echo "Tmp Table Size: 256 MB (was 64 MB) = 4x more"
echo "Max Heap Table: 256 MB (was 64 MB) = 4x more"
echo "Max Connections: 200 (was 100) = 2x more"
echo "Query Cache: 128 MB (was 64 MB) = 2x more"
echo ""
echo "=== Apache BEFORE vs AFTER ==="
echo "MaxRequestWorkers: 150 (was 50) = 3x more"
echo "HTTP/2: ENABLED on 12 SSL VirtualHosts"
echo ""
echo "=== OPcache BEFORE vs AFTER ==="
echo "Memory: 256 MB (was 128 MB) = 2x more"
echo "Max Files: 20000 (was 10000) = 2x more"
echo "Revalidate Freq: 60s (was 0s = every request!)"
echo ""
echo "=== Current Server Status ==="
mysql -e "SELECT 'Buffer Pool Hit Rate' as metric, ROUND(100 * (1 - (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS WHERE VARIABLE_NAME='Innodb_buffer_pool_reads') / (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS WHERE VARIABLE_NAME='Innodb_buffer_pool_read_requests')), 4) as value" 2>/dev/null
echo ""
echo "Memory Usage:"
free -m | head -2
echo ""
echo "Active Services:"
systemctl is-active mariadb && echo "MariaDB: RUNNING"
systemctl is-active apache2 && echo "Apache2: RUNNING"
echo ""
echo "=== Quick HTTP Test ==="
curl -o /dev/null -s -w "jadal.aqssat.co: HTTP %{http_code}, Time: %{time_total}s, Size: %{size_download} bytes\\n" https://jadal.aqssat.co/ 2>/dev/null
curl -o /dev/null -s -w "namaa.aqssat.co: HTTP %{http_code}, Time: %{time_total}s, Size: %{size_download} bytes\\n" https://namaa.aqssat.co/ 2>/dev/null
curl -o /dev/null -s -w "watar.aqssat.co: HTTP %{http_code}, Time: %{time_total}s, Size: %{size_download} bytes\\n" https://watar.aqssat.co/ 2>/dev/null
""", timeout=30)

output = stdout.read().decode('utf-8', errors='replace')
print(output)

client.close()
