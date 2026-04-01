import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('NONE')
    print()

# 500 errors from access logs
run("JADAL - HTTP 500 errors",
    "grep '\" 500 ' /var/log/apache2/jadal.aqssat.co-access.log 2>/dev/null | tail -20")

run("NAMAA - HTTP 500 errors",
    "grep '\" 500 ' /var/log/apache2/namaa.aqssat.co-access.log 2>/dev/null | tail -20")

run("WATAR - HTTP 500 errors",
    "grep '\" 500 ' /var/log/apache2/watar-access.log 2>/dev/null | tail -20")

run("API-JADAL - HTTP 500 errors",
    "grep '\" 500 ' /var/log/apache2/api-jadal-access.log 2>/dev/null | tail -20")

run("API-NAMAA - HTTP 500 errors",
    "grep '\" 500 ' /var/log/apache2/api-namaa-access.log 2>/dev/null | tail -20")

run("FAHRAS - HTTP 500 errors",
    "grep '\" 500 ' /var/log/apache2/fahras-access.log 2>/dev/null | tail -20")

# 502, 503, 504 errors
run("ALL - HTTP 502/503/504 errors",
    "grep -E '\" (502|503|504) ' /var/log/apache2/*access.log 2>/dev/null | tail -20")

# 403 Forbidden errors (real ones, not bot scans)
run("ALL - HTTP 403 errors (non-bot)",
    "grep '\" 403 ' /var/log/apache2/*access.log 2>/dev/null | grep -v 'bot\\|crawl\\|spider\\|scan' | tail -15")

# PHP Fatal errors from Apache error logs
run("ALL - PHP Fatal errors",
    "grep -i 'PHP Fatal' /var/log/apache2/*error.log 2>/dev/null | grep -v 'old\\.' | tail -20")

# PHP Exceptions
run("ALL - PHP Exceptions (non-404)",
    "grep -i 'exception' /var/log/apache2/*error.log 2>/dev/null | grep -v 'NotFoundHttpException\\|old\\.' | tail -20")

# Count errors per project
run("Error counts per status code - JADAL",
    "awk '{print $9}' /var/log/apache2/jadal.aqssat.co-access.log 2>/dev/null | sort | uniq -c | sort -rn | head -10")

run("Error counts per status code - NAMAA",
    "awk '{print $9}' /var/log/apache2/namaa.aqssat.co-access.log 2>/dev/null | sort | uniq -c | sort -rn | head -10")

run("Error counts per status code - WATAR",
    "awk '{print $9}' /var/log/apache2/watar-access.log 2>/dev/null | sort | uniq -c | sort -rn | head -10")

run("Error counts per status code - API-JADAL",
    "awk '{print $9}' /var/log/apache2/api-jadal-access.log 2>/dev/null | sort | uniq -c | sort -rn | head -10")

ssh.close()
