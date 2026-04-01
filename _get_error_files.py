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

# 1. modals.php around line 439
run("modals.php lines 420-460",
    "sed -n '420,460p' /var/www/jadal.aqssat.co/backend/modules/followUp/views/follow-up/modals.php")

# 2. panel.php around line 544 (where modals.php is rendered)
run("panel.php lines 530-560",
    "sed -n '530,560p' /var/www/jadal.aqssat.co/backend/modules/followUp/views/follow-up/panel.php")

# 3. FollowUpController.php around line 912 (actionPanel)
run("FollowUpController.php lines 880-950",
    "sed -n '880,950p' /var/www/jadal.aqssat.co/backend/modules/followUp/controllers/FollowUpController.php")

# 4. Check where $_stlTotalDebt is defined/used in the codebase
run("grep _stlTotalDebt in followUp module",
    "grep -rn '_stlTotalDebt' /var/www/jadal.aqssat.co/backend/modules/followUp/ 2>/dev/null")

# 5. Also check errors on other projects today
run("NAMAA errors today",
    "grep '2026-03-28' /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

run("WATAR errors today",
    "grep '2026-03-28' /var/www/watar.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

run("FAHRAS errors today",
    "grep '2026-03-28' /var/www/fahras.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

run("OLD-JADAL errors today",
    "grep '2026-03-28' /var/www/old.jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

run("OLD-NAMAA errors today",
    "grep '2026-03-28' /var/www/old.namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i 'error' | grep -v 'HttpException:404' | head -10")

ssh.close()
