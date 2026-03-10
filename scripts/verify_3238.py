import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

site = 'jadal'
root = f'/var/www/{site}.aqssat.co'

sftp = ssh.open_sftp()
with sftp.file('/tmp/.my_temp.cnf', 'w') as f:
    f.write("[client]\nuser=osama\npassword=O$amaDaTaBase@123\n")
sftp.close()
run("chmod 600 /tmp/.my_temp.cnf")

def q(sql):
    out, err = run(f"mysql --defaults-file=/tmp/.my_temp.cnf namaa_jadal -e \"{sql}\"")
    return out.strip() if out.strip() else f"(empty) err: {err.strip()}" if err.strip() else "(empty)"

# Trigger VIEW recreation by checking if 3238 appears
print("=== Check if contract 3238 is in the follow-up report view ===")
print(q("SELECT id FROM os_follow_up_report WHERE id = 3238"))

print("\n=== Verify expenses filter in VIEW - check expense data for 3238 ===")
print(q("SELECT id, amount, is_deleted FROM os_expenses WHERE contract_id = 3238"))

print("\n=== Manual calculation without deleted expenses ===")
print(q("SELECT 680 + COALESCE((SELECT SUM(amount) FROM os_expenses WHERE contract_id=3238 AND (is_deleted=0 OR is_deleted IS NULL)),0) + 102 as total_debt, (SELECT SUM(amount) FROM os_income WHERE contract_id=3238) as paid"))

# Verify deployed files
print("\n=== Verify expenses filter in deployed FollowUpReportController ===")
out, err = run(f"grep -n 'is_deleted.*os_expenses\\|FROM os_expenses' {root}/backend/modules/followUpReport/controllers/FollowUpReportController.php")
print(out)

print("\n=== Verify afterSoftDelete in LoanScheduling ===")
out, err = run(f"grep -n 'afterSoftDelete' {root}/backend/modules/loanScheduling/models/LoanScheduling.php")
print(out)

print("\n=== Verify applyUnifiedSearch in CustomersSearch ===")
out, err = run(f"grep -n 'applyUnifiedSearch\\|REPLACE.*name' {root}/backend/modules/customers/models/CustomersSearch.php")
print(out)

run("rm -f /tmp/.my_temp.cnf")
ssh.close()
print("\nVerification done!")
