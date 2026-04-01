import paramiko
import sys
import time
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[OK]')
    print()

proj = '/var/www/jadal.aqssat.co'

# 1. Check the actual file on the server byte by byte
run('Verify RouteAccessBehavior checkRouteAccess method',
    f'sed -n "128,150p" {proj}/backend/components/RouteAccessBehavior.php')

# 2. Verify Permissions.php has the new entries
run('Verify Permissions route map start',
    f'sed -n "623,645p" {proj}/common/helper/Permissions.php')

# 3. Add temporary debug logging to RouteAccessBehavior
run('Add debug log to RouteAccessBehavior',
    f"""php -r "
\\$file = '{proj}/backend/components/RouteAccessBehavior.php';
\\$content = file_get_contents(\\$file);
\\$old = '/* ── الطبقة 1: فحص المسار الكامل (controller/action) أولاً ثم الرجوع لمستوى المتحكم ── */';
\\$new = '/* ── الطبقة 1: فحص المسار الكامل (controller/action) أولاً ثم الرجوع لمستوى المتحكم ── */
        file_put_contents(\\'{proj}/backend/runtime/logs/route_debug.log\\', date(\\'Y-m-d H:i:s\\') . \\" | raw: {{\\$rawPathInfo}} | resolved: {{\\$resolvedRoute}} | controller: {{\\$controllerId}}\n\\", FILE_APPEND);';
if (strpos(\\$content, 'route_debug.log') === false) {
    \\$content = str_replace(\\$old, \\$new, \\$content);
    file_put_contents(\\$file, \\$content);
    echo 'Debug logging added';
} else {
    echo 'Debug logging already present';
}
" 2>&1""")

# Also add logging after the permissions check
run('Add permission result log',
    f"""php -r "
\\$file = '{proj}/backend/components/RouteAccessBehavior.php';
\\$content = file_get_contents(\\$file);
\\$old = 'if (\\$permissions === null) {{
            Yii::warning';
\\$new = 'file_put_contents(\\'{proj}/backend/runtime/logs/route_debug.log\\', date(\\'Y-m-d H:i:s\\') . \\" | fullRoutePerms: \\" . (\\$permissions === null ? \\'NULL\\' : count(\\$permissions).\\'perms\\') . \\" | controllerId: {{\\$controllerId}}\n\\", FILE_APPEND);
        if (\\$permissions === null) {{
            Yii::warning';
if (strpos(\\$content, 'fullRoutePerms') === false) {
    \\$content = str_replace(\\$old, \\$new, \\$content);
    file_put_contents(\\$file, \\$content);
    echo 'Permission result logging added';
} else {
    echo 'Permission result logging already present';
}
" 2>&1""")

# Clear logs and caches
run('Clear debug log', f'rm -f {proj}/backend/runtime/logs/route_debug.log')
run('Restart Apache', 'systemctl restart apache2 2>&1')

# 4. Now simulate the request via curl as yara (we can't authenticate, but let's at least trigger the behavior)
run('Trigger system-settings route', f'curl -sk -o /dev/null -w "%{{http_code}}" https://jadal.aqssat.co/site/system-settings 2>&1')

# 5. Wait and check the log
time.sleep(2)
run('Check route debug log', f'cat {proj}/backend/runtime/logs/route_debug.log 2>&1')

# 6. Also check the current behavior file to make sure edit worked
run('Verify behavior file after edits',
    f'sed -n "132,148p" {proj}/backend/components/RouteAccessBehavior.php')

sftp.close()
ssh.close()
print('Done!')
