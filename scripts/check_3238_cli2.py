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

queries = [
    "SELECT id, total_value, commitment_discount, status, monthly_installment_value FROM os_contracts WHERE id=3238;",
    "SELECT id, item_name, amount, total, quantity FROM os_items WHERE contract_id=3238;",
    "SELECT id, amount, payment_date, is_deleted FROM os_income WHERE contract_id=3238 ORDER BY id;",
    "SELECT id, amount, category_id, is_deleted, description FROM os_expenses WHERE contract_id=3238;",
    "SELECT id, amount, type, reason, is_deleted FROM os_contract_adjustments WHERE contract_id=3238;",
    "SELECT id, total_debt, remaining_debt, monthly_installment, is_deleted, settlement_type FROM os_loan_scheduling WHERE contract_id=3238;",
    "SELECT id, lawyer_cost, case_cost, is_deleted FROM os_judiciary WHERE contract_id=3238;",
]

# Write a SQL file and execute it
all_sql = "\n".join(queries)

sftp = ssh.open_sftp()
with sftp.file('/tmp/check_3238.sql', 'w') as f:
    f.write(all_sql)
sftp.close()

# Use .my.cnf or pass credentials via env
# First, let's get the credentials properly
out, err = run(f"php -r \"\\$c = require '{root}/common/config/main-local.php'; echo \\$c['components']['db']['username'].'|'.\\$c['components']['db']['password'].'|'; preg_match('/dbname=([^;]+)/', \\$c['components']['db']['dsn'], \\$m); echo \\$m[1];\"")
print("Creds:", out)
parts = out.strip().split('|')
db_user = parts[0] if len(parts) > 0 else ''
db_pass = parts[1] if len(parts) > 1 else ''
db_name = parts[2] if len(parts) > 2 else ''
print(f"User={db_user}, DB={db_name}")

# Write temp my.cnf
sftp = ssh.open_sftp()
with sftp.file('/tmp/.my_temp.cnf', 'w') as f:
    f.write(f"[client]\nuser={db_user}\npassword={db_pass}\n")
sftp.close()

run("chmod 600 /tmp/.my_temp.cnf")

out, err = run(f"mysql --defaults-file=/tmp/.my_temp.cnf {db_name} < /tmp/check_3238.sql")
print(out)
if err:
    print("ERR:", err)

run("rm -f /tmp/.my_temp.cnf /tmp/check_3238.sql")
ssh.close()
print("Done!")
