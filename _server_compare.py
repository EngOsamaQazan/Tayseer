import paramiko
import sys

SERVERS = {
    'NEW (31.220.82.115)': {
        'host': '31.220.82.115',
        'user': 'root',
        'password': 'HAmAS12852',
    },
    'OLD (54.38.236.112)': {
        'host': '54.38.236.112',
        'user': 'root',
        'password': 'Hussain@1986',
    },
}

COMMANDS = """
echo "===SYSTEM==="
uname -a
echo "===CPU==="
lscpu | grep -E 'Model name|CPU\(s\)|Thread|MHz|cache'
echo "===MEMORY==="
free -m
echo "===DISK_TYPE==="
cat /sys/block/sda/queue/rotational 2>/dev/null || cat /sys/block/vda/queue/rotational 2>/dev/null || echo "unknown"
lsblk -d -o name,rota,size,type 2>/dev/null | head -5
echo "===DISK_IO==="
dd if=/dev/zero of=/tmp/_bench bs=1M count=256 oflag=direct 2>&1 | tail -1
rm -f /tmp/_bench
echo "===APACHE_MPM==="
apachectl -V 2>/dev/null | grep -i 'mpm\|prefork\|worker\|event'
echo "===APACHE_CONF==="
grep -rE 'KeepAlive|MaxKeepAlive|Timeout|MaxRequestWorkers|MaxSpareServers|MinSpareServers|StartServers|ServerLimit' /etc/apache2/apache2.conf 2>/dev/null | head -15
echo "===PHP_SAPI==="
php -r "echo php_sapi_name();" 2>/dev/null
echo ""
echo "===PHP_FPM_STATUS==="
systemctl is-active php*-fpm 2>/dev/null; systemctl list-units --type=service --state=running 2>/dev/null | grep -i php
echo "===PHP_FPM_POOL==="
cat /etc/php/*/fpm/pool.d/www.conf 2>/dev/null | grep -E '^pm\.' | head -10
echo "===OPCACHE_CLI==="
php -r "
\$c = opcache_get_configuration();
\$s = opcache_get_status(false);
echo 'memory_consumption=' . (\$c['directives']['opcache.memory_consumption'] ?? 'N/A') . PHP_EOL;
echo 'max_accelerated_files=' . (\$c['directives']['opcache.max_accelerated_files'] ?? 'N/A') . PHP_EOL;
echo 'revalidate_freq=' . (\$c['directives']['opcache.revalidate_freq'] ?? 'N/A') . PHP_EOL;
echo 'validate_timestamps=' . (\$c['directives']['opcache.validate_timestamps'] ?? 'N/A') . PHP_EOL;
echo 'jit=' . (\$c['directives']['opcache.jit'] ?? 'N/A') . PHP_EOL;
echo 'hit_rate=' . round(\$s['opcache_statistics']['opcache_hit_rate'] ?? 0, 1) . '%' . PHP_EOL;
echo 'cached_scripts=' . (\$s['opcache_statistics']['num_cached_scripts'] ?? 0) . PHP_EOL;
echo 'cache_full=' . (\$s['opcache_statistics']['oom_restarts'] ?? 0) . PHP_EOL;
" 2>/dev/null
echo "===MYSQL_VARS==="
mysql -e "
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
SHOW VARIABLES LIKE 'innodb_io_capacity';
SHOW VARIABLES LIKE 'innodb_io_capacity_max';
SHOW VARIABLES LIKE 'innodb_read_io_threads';
SHOW VARIABLES LIKE 'innodb_write_io_threads';
SHOW VARIABLES LIKE 'innodb_flush_log_at_trx_commit';
SHOW VARIABLES LIKE 'innodb_flush_method';
SHOW VARIABLES LIKE 'innodb_log_file_size';
SHOW VARIABLES LIKE 'tmp_table_size';
SHOW VARIABLES LIKE 'max_heap_table_size';
SHOW VARIABLES LIKE 'table_open_cache';
SHOW VARIABLES LIKE 'query_cache_type';
SHOW VARIABLES LIKE 'query_cache_size';
SHOW VARIABLES LIKE 'max_connections';
SHOW VARIABLES LIKE 'thread_cache_size';
SHOW VARIABLES LIKE 'join_buffer_size';
SHOW VARIABLES LIKE 'sort_buffer_size';
SHOW VARIABLES LIKE 'skip_name_resolve';
" 2>/dev/null
echo "===MYSQL_STATUS==="
mysql -e "
SHOW GLOBAL STATUS LIKE 'Threads_connected';
SHOW GLOBAL STATUS LIKE 'Threads_running';
SHOW GLOBAL STATUS LIKE 'Slow_queries';
SHOW GLOBAL STATUS LIKE 'Created_tmp_disk_tables';
SHOW GLOBAL STATUS LIKE 'Created_tmp_tables';
SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool_read_requests';
SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool_reads';
SHOW GLOBAL STATUS LIKE 'Qcache_hits';
SHOW GLOBAL STATUS LIKE 'Uptime';
SHOW GLOBAL STATUS LIKE 'Questions';
SHOW GLOBAL STATUS LIKE 'Handler_read_rnd_next';
" 2>/dev/null
echo "===APACHE_WORKERS==="
ps aux | grep -c '[a]pache\|[h]ttpd'
echo "===HTTP2_CHECK==="
grep -r 'Protocols' /etc/apache2/sites-enabled/ 2>/dev/null | head -5
echo "===VHOST_CHECK==="
ls /etc/apache2/sites-enabled/ 2>/dev/null
echo "===DONE==="
"""

def run_on_server(name, config):
    print(f"\n{'='*70}")
    print(f"  {name}")
    print(f"{'='*70}")
    
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        client.connect(
            config['host'],
            username=config['user'],
            password=config['password'],
            timeout=15,
            banner_timeout=15,
        )
        
        stdin, stdout, stderr = client.exec_command(COMMANDS, timeout=120)
        output = stdout.read().decode('utf-8', errors='replace')
        errors = stderr.read().decode('utf-8', errors='replace')
        
        print(output)
        if errors:
            errs = [l for l in errors.strip().split('\n') if l and 'Warning' not in l]
            if errs:
                print("STDERR:", '\n'.join(errs[:5]))
        
    except Exception as e:
        print(f"ERROR: {e}")
    finally:
        client.close()

for name, cfg in SERVERS.items():
    run_on_server(name, cfg)
