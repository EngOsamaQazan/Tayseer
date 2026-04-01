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
        print('[NONE]')
    print()

# TODAY ONLY - 500 errors per project
run("JADAL 500s today (28/Mar/2026)",
    "grep '28/Mar/2026' /var/log/apache2/jadal.aqssat.co-access.log 2>/dev/null | grep '\" 500 ' | wc -l")

run("JADAL 500s today - details",
    "grep '28/Mar/2026' /var/log/apache2/jadal.aqssat.co-access.log 2>/dev/null | grep '\" 500 ' | awk '{print $7}' | sort | uniq -c | sort -rn")

run("NAMAA 500s today",
    "grep '28/Mar/2026' /var/log/apache2/namaa.aqssat.co-access.log 2>/dev/null | grep '\" 500 ' | wc -l")

run("WATAR 500s today",
    "grep '28/Mar/2026' /var/log/apache2/watar-access.log 2>/dev/null | grep '\" 500 ' | wc -l")

run("old.jadal 500s today",
    "grep '28/Mar/2026' /var/log/apache2/old.jadal.aqssat.co-access.log 2>/dev/null | grep '\" 500 ' | wc -l")

run("old.namaa 500s today",
    "grep '28/Mar/2026' /var/log/apache2/old.namaa.aqssat.co-access.log 2>/dev/null | grep '\" 500 ' | wc -l")

run("FAHRAS 500s today",
    "grep '28/Mar/2026' /var/log/apache2/fahras-access.log 2>/dev/null | grep '\" 500 ' | wc -l")

# Check old.jadal Yii2 errors today
run("old.jadal app.log today",
    "grep '2026-03-28' /var/www/old.jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

# Check old.namaa Yii2 errors today
run("old.namaa app.log today",
    "grep '2026-03-28' /var/www/old.namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

# Check WATAR Yii2 errors today
run("watar app.log today",
    "grep '2026-03-28' /var/www/watar.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

# Check NAMAA Yii2 errors today
run("namaa app.log today",
    "grep '2026-03-28' /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

# Check FAHRAS Yii2 errors today
run("fahras app.log today",
    "grep '2026-03-28' /var/www/fahras.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

# Check Fail2Ban - is brute-forcer 112.118.57.75 banned?
run("Fail2Ban SSH ban status",
    "fail2ban-client status sshd 2>/dev/null | tail -5")

# How many SSH brute force attempts today?
run("SSH brute force count today",
    "journalctl --since '2026-03-28 00:00:00' -u sshd --no-pager 2>/dev/null | grep -c 'maximum authentication\\|Failed password\\|Invalid user' 2>/dev/null")

ssh.close()
