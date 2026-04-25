#!/usr/bin/env python3
"""
Server Performance Optimization Script
=======================================
Applies all FREE performance optimizations to the new Contabo server.
No plan upgrades needed - only configuration tuning.

Optimizations:
  1. PHP OPcache + JIT (PHP 8.5)
  2. PHP-FPM pool tuning
  3. Apache: compression, caching, keep-alive
  4. MariaDB: buffer pool, query cache, tmp tables
  5. Yii2: production mode, cache flush, autoloader
  6. System: kernel networking, cleanup
"""
# --- Credentials (loaded from scripts/credentials.py, git-ignored) ---
# Copy scripts/credentials.example.py to scripts/credentials.py and
# fill in the real values before running this script.
import os as _os, sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..'))
_sys.path.insert(0, _os.path.dirname(_os.path.abspath(__file__)))
try:
    from credentials import *  # noqa: F401,F403
except ImportError as _e:
    raise SystemExit(
        'Missing scripts/credentials.py — copy credentials.example.py and fill in real values.\n'
        f'Original error: {_e}'
    )
# ---------------------------------------------------------------------

import paramiko
import sys
import time
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

NEW_HOST = NEW_SERVER_IP
NEW_USER = 'root'
NEW_PASS = NEW_SERVER_PASS

SITES = [
    '/var/www/vite.jadal.aqssat.co',
    '/var/www/vite.namaa.aqssat.co',
]


def run(ssh, cmd, timeout=120, show=True):
    if show:
        print(f"  $ {cmd[:200]}{'...' if len(cmd) > 200 else ''}", flush=True)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    if show and out:
        for line in out.split('\n')[:40]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if code != 0 and err:
        for line in err.split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    return code, out, err


def phase_header(num, title):
    print(f"\n{'=' * 60}")
    print(f"  PHASE {num}: {title}")
    print(f"{'=' * 60}", flush=True)


def main():
    print(f"\n{'=' * 60}")
    print(f"  SERVER PERFORMANCE OPTIMIZATION")
    print(f"  Target: {NEW_HOST}")
    print(f"{'=' * 60}\n", flush=True)

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(NEW_HOST, username=NEW_USER, password=NEW_PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    # =============================================
    # PHASE 0: Diagnostic snapshot
    # =============================================
    phase_header(0, "Diagnostic Snapshot (before)")
    run(ssh, "php -v 2>&1 | head -1")
    run(ssh, "apache2 -v 2>&1 | head -1")
    run(ssh, "mysql --version 2>&1 | head -1")
    run(ssh, "free -h | head -3")
    run(ssh, "nproc")
    run(ssh, "df -h / | tail -1")

    _, ram_kb, _ = run(ssh, "grep MemTotal /proc/meminfo | awk '{print $2}'", show=False)
    total_ram_mb = int(ram_kb) // 1024 if ram_kb.isdigit() else 4096
    print(f"\n  Total RAM: {total_ram_mb} MB", flush=True)

    _, cpu_count, _ = run(ssh, "nproc", show=False)
    cpus = int(cpu_count) if cpu_count.isdigit() else 2
    print(f"  CPU cores: {cpus}", flush=True)

    # Check current PHP handler (mod_php vs FPM)
    _, php_handler, _ = run(ssh, "apache2ctl -M 2>/dev/null | grep -i 'php\\|proxy_fcgi'", show=False)
    using_fpm = 'proxy_fcgi' in php_handler or 'fcgi' in php_handler.lower()
    using_mod_php = 'php' in php_handler.lower() and not using_fpm
    print(f"  PHP handler: {'PHP-FPM' if using_fpm else 'mod_php' if using_mod_php else 'unknown'}", flush=True)

    # Detect PHP version for config paths
    _, php_ver_full, _ = run(ssh, "php -r \"echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;\"", show=False)
    php_ver = php_ver_full.strip() if php_ver_full.strip() else '8.5'
    print(f"  PHP version: {php_ver}", flush=True)

    # =============================================
    # PHASE 1: PHP OPcache + JIT
    # =============================================
    phase_header(1, "PHP OPcache + JIT Optimization")

    opcache_ini = f"""
; === Tayseer OPcache Production Config ===
; Aggressive caching - requires Apache/FPM restart on deploy

opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=20000
opcache.max_wasted_percentage=10

; No file change checks in production (fastest)
opcache.validate_timestamps=0
opcache.revalidate_freq=0

; Preloading support
opcache.file_update_protection=0
opcache.consistency_checks=0

; JIT (PHP 8.x) - tracing mode for best web performance
opcache.jit=1255
opcache.jit_buffer_size=128M

; Realpath cache - reduces stat() calls
realpath_cache_size=4096K
realpath_cache_ttl=600

; Output buffering
output_buffering=4096

; Production safety
display_errors=Off
display_startup_errors=Off
log_errors=On
expose_php=Off

; Generous limits for ERP
upload_max_filesize=64M
post_max_size=64M
memory_limit=512M
max_execution_time=300
max_input_time=300
max_input_vars=5000
date.timezone=Asia/Amman

; Session
session.gc_maxlifetime=7200
session.cookie_httponly=1
session.cookie_secure=1
session.use_strict_mode=1
"""

    for ctx in ['apache2', 'fpm', 'cli']:
        conf_dir = f"/etc/php/{php_ver}/{ctx}/conf.d"
        run(ssh, f"test -d {conf_dir} && echo '{ctx}: EXISTS' || echo '{ctx}: MISSING'")

    for ctx in ['apache2', 'fpm']:
        conf_dir = f"/etc/php/{php_ver}/{ctx}/conf.d"
        run(ssh, f"""test -d {conf_dir} && cat > {conf_dir}/99-tayseer-perf.ini << 'OPCEOF'
{opcache_ini}
OPCEOF
echo "Wrote {conf_dir}/99-tayseer-perf.ini"
""")

    # Remove old 99-custom.ini if it conflicts
    for ctx in ['apache2', 'fpm', 'cli']:
        run(ssh, f"rm -f /etc/php/{php_ver}/{ctx}/conf.d/99-custom.ini 2>/dev/null; true")

    print("\n  Verifying OPcache + JIT...", flush=True)
    run(ssh, f"php -d 'opcache.enable_cli=1' -r \"echo 'OPcache: '.ini_get('opcache.enable').'\\n'; echo 'JIT: '.ini_get('opcache.jit').'\\n'; echo 'JIT buffer: '.ini_get('opcache.jit_buffer_size').'\\n';\"")

    # =============================================
    # PHASE 2: PHP-FPM Pool Tuning
    # =============================================
    phase_header(2, "PHP-FPM Pool Tuning")

    # Calculate optimal FPM settings based on RAM
    # Each PHP-FPM worker uses ~40-80MB. Reserve 1GB for OS+DB
    available_for_fpm = max(total_ram_mb - 1024, 512)
    max_children = min(available_for_fpm // 60, cpus * 10, 50)
    start_servers = max(max_children // 4, 2)
    min_spare = max(max_children // 4, 2)
    max_spare = max(max_children // 2, 4)

    print(f"  Calculated FPM settings (based on {total_ram_mb}MB RAM, {cpus} CPUs):", flush=True)
    print(f"    max_children     = {max_children}", flush=True)
    print(f"    start_servers    = {start_servers}", flush=True)
    print(f"    min_spare        = {min_spare}", flush=True)
    print(f"    max_spare        = {max_spare}", flush=True)

    fpm_pool_conf = f"""
; === Tayseer FPM Pool - Production ===
[www]
user = www-data
group = www-data
listen = /run/php/php{php_ver}-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = {max_children}
pm.start_servers = {start_servers}
pm.min_spare_servers = {min_spare}
pm.max_spare_servers = {max_spare}
pm.max_requests = 1000
pm.process_idle_timeout = 10s

request_terminate_timeout = 300
catch_workers_output = yes
decorate_workers_output = no
"""

    fpm_pool_path = f"/etc/php/{php_ver}/fpm/pool.d/www.conf"
    code, _, _ = run(ssh, f"test -f {fpm_pool_path} && echo 'FPM pool found'")
    if code == 0:
        run(ssh, f"cp {fpm_pool_path} {fpm_pool_path}.bak.$(date +%Y%m%d)")
        run(ssh, f"""cat > {fpm_pool_path} << 'FPMEOF'
{fpm_pool_conf}
FPMEOF
echo "FPM pool config updated"
""")
    else:
        print("  PHP-FPM pool not found - may be using mod_php or HestiaCP managed", flush=True)

    # =============================================
    # PHASE 3: Apache Optimization
    # =============================================
    phase_header(3, "Apache Compression + Caching + KeepAlive")

    run(ssh, "a2enmod deflate expires headers rewrite ssl 2>/dev/null; true")

    # Compression config
    run(ssh, """cat > /etc/apache2/conf-available/tayseer-performance.conf << 'APEOF'
# === Tayseer Performance Config ===

# -- GZIP Compression --
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css
    AddOutputFilterByType DEFLATE text/javascript application/javascript application/x-javascript
    AddOutputFilterByType DEFLATE application/json application/xml application/xhtml+xml
    AddOutputFilterByType DEFLATE image/svg+xml application/font-woff2 application/font-woff
    DeflateCompressionLevel 6
    BrowserMatch "^Mozilla/4" gzip-only-text/html
    BrowserMatch "^Mozilla/4\\.0[678]" no-gzip
    BrowserMatch "\\\\bMSIE" !no-gzip !gzip-only-text/html
</IfModule>

# -- Browser Caching --
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 month"
    ExpiresByType text/html "access plus 0 seconds"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType text/javascript "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"
    ExpiresByType application/font-woff2 "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# -- Cache-Control Headers --
<IfModule mod_headers.c>
    <FilesMatch "\.(ico|pdf|flv|jpg|jpeg|png|gif|webp|js|css|swf|woff|woff2|ttf|eot|svg)$">
        Header set Cache-Control "max-age=31536000, public, immutable"
    </FilesMatch>
    <FilesMatch "\.(html|htm|php)$">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
    </FilesMatch>
</IfModule>

# -- KeepAlive --
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# -- Security --
ServerTokens Prod
ServerSignature Off
FileETag MTime Size
APEOF
""")

    run(ssh, "a2enconf tayseer-performance 2>/dev/null; true")

    # Remove old conflicting configs
    run(ssh, "a2disconf security-headers 2>/dev/null; true")

    # Security headers (merge into main perf config site-level)
    run(ssh, """cat > /etc/apache2/conf-available/tayseer-security.conf << 'SECEOF'
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
</IfModule>
SECEOF
""")
    run(ssh, "a2enconf tayseer-security 2>/dev/null; true")

    # MPM tuning (prefork for mod_php, event for FPM)
    if using_fpm:
        print("\n  Using PHP-FPM -> enabling mpm_event for better concurrency", flush=True)
        run(ssh, "a2dismod mpm_prefork 2>/dev/null; a2enmod mpm_event proxy_fcgi 2>/dev/null; true")
        run(ssh, f"""cat > /etc/apache2/mods-available/mpm_event.conf << 'MPMEOF'
<IfModule mpm_event_module>
    StartServers             2
    MinSpareThreads         25
    MaxSpareThreads         75
    ThreadLimit             64
    ThreadsPerChild         25
    MaxRequestWorkers      {min(150, cpus * 25)}
    MaxConnectionsPerChild  10000
    ServerLimit              6
</IfModule>
MPMEOF
""")
    else:
        print("\n  Using mod_php -> tuning mpm_prefork", flush=True)
        run(ssh, f"""cat > /etc/apache2/mods-available/mpm_prefork.conf << 'MPMEOF'
<IfModule mpm_prefork_module>
    StartServers             4
    MinSpareServers          4
    MaxSpareServers         12
    MaxRequestWorkers       {min(100, max_children)}
    MaxConnectionsPerChild  5000
</IfModule>
MPMEOF
""")

    # Test Apache config
    code, _, _ = run(ssh, "apachectl configtest 2>&1")
    if code != 0:
        print("  WARNING: Apache config test failed! Rolling back...", flush=True)
        run(ssh, "a2disconf tayseer-performance 2>/dev/null; true")

    # =============================================
    # PHASE 4: MariaDB Tuning
    # =============================================
    phase_header(4, "MariaDB Performance Tuning")

    # Calculate buffer pool size: ~25% of total RAM for shared server
    innodb_pool = min(total_ram_mb // 4, 2048)
    innodb_pool = max(innodb_pool, 256)

    mariadb_tuning = f"""
# === Tayseer MariaDB Performance Config ===
# Generated for {total_ram_mb}MB RAM server

[mysqld]
# -- InnoDB Engine --
innodb_buffer_pool_size = {innodb_pool}M
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_io_capacity = 200
innodb_io_capacity_max = 400
innodb_read_io_threads = 4
innodb_write_io_threads = 4

# -- Query Cache (MariaDB still supports it) --
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M
query_cache_min_res_unit = 2048

# -- Temp Tables --
tmp_table_size = 64M
max_heap_table_size = 64M

# -- Connection and Buffer --
max_connections = 100
table_open_cache = 400
table_definition_cache = 400
thread_cache_size = 16
sort_buffer_size = 2M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
join_buffer_size = 2M
bulk_insert_buffer_size = 16M

# -- Logging --
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# -- Network --
skip_name_resolve = 1
wait_timeout = 300
interactive_timeout = 300
"""

    run(ssh, f"""cat > /etc/mysql/mariadb.conf.d/99-tayseer-perf.cnf << 'DBEOF'
{mariadb_tuning}
DBEOF
echo "MariaDB perf config written"
""")

    # Create slow query log dir
    run(ssh, "mkdir -p /var/log/mysql && chown mysql:mysql /var/log/mysql")

    # Validate config before restart
    code, _, _ = run(ssh, "mysqld --validate-config 2>&1 || mariadbd --validate-config 2>&1 || echo 'Config validation not available, will try restart'")

    # =============================================
    # PHASE 5: Yii2 Application Optimization
    # =============================================
    phase_header(5, "Yii2 Application Optimization")

    for site in SITES:
        name = site.split('/')[-1]
        print(f"\n  [{name}]", flush=True)

        # Ensure production mode
        index_file = f"{site}/backend/web/index.php"
        run(ssh, f"grep -E 'YII_DEBUG|YII_ENV' {index_file} 2>/dev/null")

        # Force production mode
        run(ssh, f"sed -i \"s/define('YII_DEBUG', true)/define('YII_DEBUG', false)/\" {index_file} 2>/dev/null; true")
        run(ssh, f"sed -i \"s/define('YII_ENV', 'dev')/define('YII_ENV', 'prod')/\" {index_file} 2>/dev/null; true")

        # Optimize composer autoloader
        run(ssh, f"cd {site} && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev 2>&1 | tail -5", timeout=120)

        # Clean runtime debug data (can be huge)
        run(ssh, f"rm -rf {site}/backend/runtime/debug/* 2>/dev/null; true")
        run(ssh, f"rm -rf {site}/backend/runtime/logs/*.log 2>/dev/null; true")
        run(ssh, f"rm -rf {site}/frontend/runtime/debug/* 2>/dev/null; true")
        run(ssh, f"rm -rf {site}/frontend/runtime/logs/*.log 2>/dev/null; true")

        # Clean published assets (force republish with fresh cache busting)
        run(ssh, f"rm -rf {site}/backend/web/assets/[0-9a-f]* 2>/dev/null; true")
        run(ssh, f"rm -rf {site}/frontend/web/assets/[0-9a-f]* 2>/dev/null; true")

        # Flush Yii2 cache
        run(ssh, f"cd {site} && php yii cache/flush-all 2>&1")

        # Fix permissions
        run(ssh, f"chown -R www-data:www-data {site}/backend/runtime {site}/frontend/runtime {site}/backend/web/assets {site}/frontend/web/assets 2>/dev/null; true")
        run(ssh, f"chmod -R 775 {site}/backend/runtime {site}/frontend/runtime {site}/backend/web/assets {site}/frontend/web/assets 2>/dev/null; true")

    # =============================================
    # PHASE 6: System Kernel Tuning
    # =============================================
    phase_header(6, "System & Kernel Optimization")

    run(ssh, """cat > /etc/sysctl.d/99-tayseer-perf.conf << 'SYSEOF'
# === Tayseer Network & System Tuning ===

# Swap usage - prefer RAM
vm.swappiness = 10

# File descriptor limits
fs.file-max = 65535

# Network - reduce TIME_WAIT connections
net.ipv4.tcp_fin_timeout = 15
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_keepalive_time = 600
net.ipv4.tcp_keepalive_intvl = 30
net.ipv4.tcp_keepalive_probes = 5

# Network buffers
net.core.somaxconn = 1024
net.core.netdev_max_backlog = 5000
net.ipv4.tcp_max_syn_backlog = 1024

# Memory overcommit (safe for web servers)
vm.overcommit_memory = 1
vm.dirty_ratio = 20
vm.dirty_background_ratio = 5
SYSEOF
""")
    run(ssh, "sysctl --system 2>&1 | tail -5")

    # Increase open file limits for Apache/PHP-FPM
    run(ssh, """grep -q 'www-data' /etc/security/limits.conf || cat >> /etc/security/limits.conf << 'LIMEOF'
www-data soft nofile 65535
www-data hard nofile 65535
root soft nofile 65535
root hard nofile 65535
LIMEOF
""")

    # =============================================
    # PHASE 7: Cleanup
    # =============================================
    phase_header(7, "Cleanup & Disk Space")

    # Clean apt cache
    run(ssh, "apt-get autoremove -y 2>&1 | tail -3")
    run(ssh, "apt-get clean 2>&1")

    # Clean old logs
    run(ssh, "find /var/log -name '*.gz' -mtime +30 -delete 2>/dev/null; true")
    run(ssh, "find /var/log -name '*.1' -mtime +7 -delete 2>/dev/null; true")

    # Clean journal logs older than 3 days
    run(ssh, "journalctl --vacuum-time=3d 2>&1 | tail -2")

    # Clean old PHP sessions
    run(ssh, f"find /var/lib/php/sessions/ -name 'sess_*' -mtime +1 -delete 2>/dev/null; true")

    # Show disk space recovered
    run(ssh, "df -h / | tail -1")

    # =============================================
    # PHASE 8: Restart Services
    # =============================================
    phase_header(8, "Restart Services")

    # Restart MariaDB with new config
    print("  Restarting MariaDB...", flush=True)
    code, _, err = run(ssh, "systemctl restart mariadb 2>&1")
    if code != 0:
        print(f"  WARNING: MariaDB restart failed! Checking config...", flush=True)
        run(ssh, "cat /var/log/mysql/error.log 2>/dev/null | tail -20")
        # Try removing our config and restart
        print("  Trying without query_cache (may not be available)...", flush=True)
        run(ssh, "sed -i '/query_cache/d' /etc/mysql/mariadb.conf.d/99-tayseer-perf.cnf")
        run(ssh, "systemctl restart mariadb 2>&1")

    # Restart PHP-FPM
    print("\n  Restarting PHP-FPM...", flush=True)
    run(ssh, f"systemctl restart php{php_ver}-fpm 2>/dev/null; true")

    # Restart Apache (clears OPcache)
    print("\n  Restarting Apache (clears OPcache)...", flush=True)
    code, _, _ = run(ssh, "systemctl restart apache2 2>&1")
    if code != 0:
        print("  WARNING: Apache restart failed!", flush=True)
        run(ssh, "apachectl configtest 2>&1")
        run(ssh, "journalctl -u apache2 --no-pager -n 20 2>&1")

    time.sleep(3)

    # =============================================
    # PHASE 9: Verification
    # =============================================
    phase_header(9, "Verification")

    # Service status
    for svc in ['apache2', 'mariadb', f'php{php_ver}-fpm']:
        code, out, _ = run(ssh, f"systemctl is-active {svc} 2>/dev/null")
        status = "RUNNING" if out.strip() == 'active' else "NOT RUNNING"
        print(f"    {svc}: {status}", flush=True)

    # OPcache check
    print("\n  OPcache Status:", flush=True)
    run(ssh, "php -r \"opcache_reset(); echo 'OPcache reset\\n';\" 2>/dev/null; "
            "php -r \"echo 'OPcache: '.ini_get('opcache.enable').'\\n';"
            "echo 'JIT: '.ini_get('opcache.jit').'\\n';"
            "echo 'JIT buffer: '.ini_get('opcache.jit_buffer_size').'\\n';"
            "echo 'Memory: '.ini_get('opcache.memory_consumption').'MB\\n';"
            "echo 'Max files: '.ini_get('opcache.max_accelerated_files').'\\n';"
            "echo 'Validate timestamps: '.ini_get('opcache.validate_timestamps').'\\n';\" 2>&1")

    # MariaDB check
    print("\n  MariaDB Status:", flush=True)
    run(ssh, """mysql -u root -e "
SELECT CONCAT(ROUND(@@innodb_buffer_pool_size/1048576), 'MB') AS buffer_pool,
       @@query_cache_type AS qc_type,
       CONCAT(ROUND(@@query_cache_size/1048576), 'MB') AS qc_size,
       @@skip_name_resolve AS skip_dns,
       @@slow_query_log AS slow_log;
" 2>&1""")

    # Compression check
    print("\n  Compression Check:", flush=True)
    for site_path in SITES:
        domain = site_path.split('/')[-1]
        run(ssh, f"curl -sI -H 'Accept-Encoding: gzip' https://{domain} 2>/dev/null | grep -i 'content-encoding\\|server' || echo '{domain}: No compression header (check DNS)'")

    # Response time benchmark
    print("\n  Response Time Benchmark:", flush=True)
    for site_path in SITES:
        domain = site_path.split('/')[-1]
        run(ssh, f"curl -so /dev/null -w '{domain}:\\n  DNS: %{{time_namelookup}}s\\n  Connect: %{{time_connect}}s\\n  TTFB: %{{time_starttransfer}}s\\n  Total: %{{time_total}}s\\n  Status: %{{http_code}}\\n' https://{domain} 2>/dev/null || echo '{domain}: Not reachable'")

    print(f"\n{'=' * 60}")
    print("  OPTIMIZATION COMPLETE!")
    print(f"{'=' * 60}")
    print(f"""
  Applied optimizations:
  ---------------------
  [1] PHP OPcache: 256MB cache, validate_timestamps=0
  [2] PHP JIT: tracing mode, 128MB buffer
  [3] PHP-FPM: dynamic pool, max_children={max_children}
  [4] Apache: gzip compression, browser caching (1 year)
  [5] Apache: KeepAlive On, security headers
  [6] MariaDB: buffer_pool={innodb_pool}MB, query_cache=64MB
  [7] MariaDB: slow query log enabled (>2s)
  [8] Yii2: production mode, optimized autoloader
  [9] Yii2: runtime/debug cleaned, caches flushed
  [10] System: kernel TCP/network tuning, file limits

  IMPORTANT - After every deploy:
  -------------------------------
  1. Restart Apache/PHP-FPM to clear OPcache
     systemctl restart apache2 php{php_ver}-fpm
  2. Run: cd /site && php yii cache/flush-all
""")

    ssh.close()


if __name__ == '__main__':
    main()
