import paramiko
import sys
import time

SERVER = {
    'host': '31.220.82.115',
    'user': 'root',
    'password': 'HAmAS12852',
}

def run(client, cmd, desc=""):
    if desc:
        print(f"\n{'-'*60}")
        print(f"  {desc}")
        print(f"{'-'*60}")
    stdin, stdout, stderr = client.exec_command(cmd, timeout=120)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        important = [l for l in err.split('\n') if l and 'Warning' not in l and 'INFO' not in l]
        if important:
            print("STDERR:", '\n'.join(important[:5]))
    return out

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SERVER['host'], username=SERVER['user'], password=SERVER['password'], timeout=15)

print("="*60)
print("  FIXING NEW SERVER - 31.220.82.115")
print("="*60)

# ═══════════════════════════════════════════════════════
# 1. MySQL / MariaDB Optimization
# ═══════════════════════════════════════════════════════
run(client, """
# Find MariaDB config file
CONF_DIR=""
if [ -d /etc/mysql/mariadb.conf.d ]; then
    CONF_DIR="/etc/mysql/mariadb.conf.d"
elif [ -d /etc/mysql/conf.d ]; then
    CONF_DIR="/etc/mysql/conf.d"
else
    CONF_DIR="/etc/mysql"
fi

cat > "$CONF_DIR/99-tayseer-perf.cnf" << 'MYSQL_EOF'
[mysqld]
# ═══ InnoDB Buffer Pool — use 60% of 12GB RAM ═══
innodb_buffer_pool_size = 7G
innodb_buffer_pool_instances = 4

# ═══ InnoDB I/O — optimized for SSD ═══
innodb_io_capacity = 2000
innodb_io_capacity_max = 4000
innodb_read_io_threads = 8
innodb_write_io_threads = 8
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# ═══ InnoDB Log ═══
innodb_log_file_size = 256M
innodb_log_buffer_size = 64M

# ═══ Temp Tables — avoid disk temp tables ═══
tmp_table_size = 256M
max_heap_table_size = 256M

# ═══ Table & Thread Cache ═══
table_open_cache = 4000
table_definition_cache = 2000
thread_cache_size = 64
max_connections = 200

# ═══ Buffers ═══
sort_buffer_size = 4M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
join_buffer_size = 4M
max_allowed_packet = 64M

# ═══ Query Cache (MariaDB) ═══
query_cache_type = ON
query_cache_size = 128M
query_cache_limit = 4M
query_cache_min_res_unit = 2048

# ═══ Logging ═══
slow_query_log = 1
long_query_time = 1
slow_query_log_file = /var/log/mysql/slow.log

# ═══ Performance ═══
skip_name_resolve = 1
MYSQL_EOF

echo "MySQL config written to $CONF_DIR/99-tayseer-perf.cnf"
cat "$CONF_DIR/99-tayseer-perf.cnf"
""", "1. Writing MySQL optimization config")

# ═══════════════════════════════════════════════════════
# 2. OPcache Optimization
# ═══════════════════════════════════════════════════════
run(client, """
# Find PHP version
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)
echo "PHP Version: $PHP_VER"

# Find opcache config
OPCACHE_INI=""
for f in /etc/php/$PHP_VER/apache2/conf.d/*opcache* /etc/php/$PHP_VER/mods-available/opcache.ini; do
    if [ -f "$f" ]; then
        OPCACHE_INI="$f"
        echo "Found OPcache config: $f"
        break
    fi
done

if [ -z "$OPCACHE_INI" ]; then
    OPCACHE_INI="/etc/php/$PHP_VER/apache2/conf.d/99-opcache-tayseer.ini"
    echo "Creating new OPcache config: $OPCACHE_INI"
fi

# Create optimized OPcache config
cat > /etc/php/$PHP_VER/apache2/conf.d/99-opcache-tayseer.ini << 'OPCACHE_EOF'
; Tayseer OPcache Optimization
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.validate_timestamps=1
opcache.interned_strings_buffer=32
opcache.fast_shutdown=1
opcache.save_comments=1
opcache.enable_file_override=1
opcache.huge_code_pages=0
OPCACHE_EOF

echo ""
echo "OPcache config written:"
cat /etc/php/$PHP_VER/apache2/conf.d/99-opcache-tayseer.ini
""", "2. Optimizing OPcache")

# ═══════════════════════════════════════════════════════
# 3. Apache Optimization - Enable HTTP/2 + MPM tuning
# ═══════════════════════════════════════════════════════
run(client, """
# Add HTTP/2 to all SSL VirtualHosts
for vhost in /etc/apache2/sites-enabled/*-le-ssl.conf; do
    if [ -f "$vhost" ]; then
        if ! grep -q 'Protocols' "$vhost"; then
            # Add Protocols directive after <VirtualHost> opening
            sed -i '/<VirtualHost/a\\    Protocols h2 http/1.1' "$vhost"
            echo "Added HTTP/2 to: $vhost"
        else
            echo "HTTP/2 already in: $vhost"
        fi
    fi
done

echo ""
echo "Current Apache MPM config:"
grep -rn 'MaxRequestWorkers' /etc/apache2/mods-enabled/mpm_prefork.conf 2>/dev/null || echo "No prefork config found"
""", "3. Enabling HTTP/2 on all SSL VirtualHosts")

# ═══════════════════════════════════════════════════════
# 4. Apache Prefork MPM Tuning
# ═══════════════════════════════════════════════════════
run(client, """
# Optimize Prefork MPM for 12GB RAM server
MPM_CONF="/etc/apache2/mods-enabled/mpm_prefork.conf"
if [ ! -f "$MPM_CONF" ]; then
    MPM_CONF="/etc/apache2/mods-available/mpm_prefork.conf"
fi

if [ -f "$MPM_CONF" ]; then
    cp "$MPM_CONF" "${MPM_CONF}.bak"
    cat > "$MPM_CONF" << 'MPM_EOF'
<IfModule mpm_prefork_module>
    StartServers            8
    MinSpareServers         5
    MaxSpareServers         20
    MaxRequestWorkers       150
    ServerLimit             150
    MaxConnectionsPerChild  3000
</IfModule>
MPM_EOF
    echo "MPM Prefork optimized:"
    cat "$MPM_CONF"
else
    echo "MPM Prefork config not found at $MPM_CONF"
fi
""", "4. Tuning Apache MPM Prefork")

# ═══════════════════════════════════════════════════════
# 5. Restart Services
# ═══════════════════════════════════════════════════════
print(f"\n{'-'*60}")
print(f"  5. Restarting MySQL...")
print(f"{'-'*60}")
run(client, "systemctl restart mariadb 2>&1 || systemctl restart mysql 2>&1")
time.sleep(3)

# Verify MySQL started correctly
result = run(client, """
systemctl is-active mariadb 2>/dev/null || systemctl is-active mysql 2>/dev/null
echo ""
mysql -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size'" 2>/dev/null
mysql -e "SHOW VARIABLES LIKE 'innodb_io_capacity'" 2>/dev/null
mysql -e "SHOW VARIABLES LIKE 'table_open_cache'" 2>/dev/null
mysql -e "SHOW VARIABLES LIKE 'thread_cache_size'" 2>/dev/null
mysql -e "SHOW VARIABLES LIKE 'tmp_table_size'" 2>/dev/null
mysql -e "SHOW VARIABLES LIKE 'innodb_io_capacity_max'" 2>/dev/null
""", "5a. Verifying MySQL config applied")

print(f"\n{'-'*60}")
print(f"  5b. Restarting Apache...")
print(f"{'-'*60}")
run(client, "systemctl restart apache2 2>&1")
time.sleep(2)

# Verify Apache
run(client, """
systemctl is-active apache2
echo ""
echo "Apache workers after restart:"
ps aux | grep -c '[a]pache'
""", "5c. Verifying Apache restart")

# ═══════════════════════════════════════════════════════
# 6. Final Verification
# ═══════════════════════════════════════════════════════
run(client, """
echo "=== FINAL STATUS ==="
echo ""
echo "MySQL:"
systemctl is-active mariadb 2>/dev/null || systemctl is-active mysql 2>/dev/null
echo ""
echo "Apache:"
systemctl is-active apache2
echo ""
echo "Key MySQL Settings (AFTER optimization):"
mysql -e "
SELECT 
    (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='innodb_buffer_pool_size') / 1024/1024/1024 as buffer_pool_GB,
    (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='innodb_io_capacity') as io_capacity,
    (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='innodb_io_capacity_max') as io_capacity_max,
    (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='table_open_cache') as table_cache,
    (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='thread_cache_size') as thread_cache,
    (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='tmp_table_size') / 1024/1024 as tmp_table_MB,
    (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='max_heap_table_size') / 1024/1024 as max_heap_MB
" 2>/dev/null
echo ""
echo "Memory After Changes:"
free -m
echo ""
echo "OPcache (from web - check via browser for real values):"
php -r "
$c = opcache_get_configuration();
echo 'memory_consumption=' . (\$c['directives']['opcache.memory_consumption'] ?? 'N/A') . PHP_EOL;
echo 'max_accelerated_files=' . (\$c['directives']['opcache.max_accelerated_files'] ?? 'N/A') . PHP_EOL;
echo 'revalidate_freq=' . (\$c['directives']['opcache.revalidate_freq'] ?? 'N/A') . PHP_EOL;
" 2>/dev/null
""", "6. FINAL VERIFICATION")

# Clear Yii2 caches on all sites
run(client, """
for site in /var/www/jadal.aqssat.co /var/www/namaa.aqssat.co /var/www/watar.aqssat.co; do
    if [ -d "$site" ]; then
        cd "$site"
        rm -rf backend/runtime/cache/* frontend/runtime/cache/* 2>/dev/null
        php yii cache/flush-all 2>/dev/null || true
        echo "Cleared cache for: $site"
    fi
done
""", "7. Clearing all Yii2 caches")

client.close()
print(f"\n{'='*60}")
print("  ALL DONE! Server optimized.")
print("="*60)
