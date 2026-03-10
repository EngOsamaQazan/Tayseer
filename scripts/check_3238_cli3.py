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
    out, err = run(f'mysql --defaults-file=/tmp/.my_temp.cnf namaa_jadal -e "{sql}"')
    return out

print("=== Contract ===")
print(q("SELECT id, total_value, commitment_discount, status FROM os_contracts WHERE id=3238"))

print("=== Items ===")
print(q("SELECT * FROM os_items WHERE contract_id=3238 LIMIT 10"))

print("=== Income (payments) ===")
print(q("SELECT id, amount, payment_date, is_deleted FROM os_income WHERE contract_id=3238 ORDER BY id"))

print("=== Income total (active only) ===")
print(q("SELECT SUM(amount) as total_active FROM os_income WHERE contract_id=3238 AND (is_deleted=0 OR is_deleted IS NULL)"))
print("=== Income total (all) ===")
print(q("SELECT SUM(amount) as total_all FROM os_income WHERE contract_id=3238"))

print("=== Expenses ===")
print(q("SELECT id, amount, category_id, is_deleted FROM os_expenses WHERE contract_id=3238"))

print("=== Adjustments ===")
print(q("SELECT id, amount, type, reason, is_deleted FROM os_contract_adjustments WHERE contract_id=3238"))

print("=== Settlements ===")
print(q("SELECT id, total_debt, remaining_debt, monthly_installment, is_deleted, settlement_type FROM os_loan_scheduling WHERE contract_id=3238"))

print("=== Judiciary ===")
print(q("SELECT id, lawyer_cost, case_cost, is_deleted FROM os_judiciary WHERE contract_id=3238"))

# Also check how the follow-up report determines status
print("=== followUp report status check ===")
print(q("SELECT c.id, c.total_value, c.commitment_discount, c.status, COALESCE((SELECT SUM(i.amount) FROM os_income i WHERE i.contract_id=c.id AND (i.is_deleted=0 OR i.is_deleted IS NULL)),0) as paid, COALESCE((SELECT SUM(a.amount) FROM os_contract_adjustments a WHERE a.contract_id=c.id AND (a.is_deleted=0 OR a.is_deleted IS NULL)),0) as adj FROM os_contracts c WHERE c.id=3238"))

run("rm -f /tmp/.my_temp.cnf")
ssh.close()
print("Done!")
