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

# Direct SQL queries via CLI
queries = [
    "SELECT id, total_value, commitment_discount, status, monthly_installment_value FROM os_contracts WHERE id=3238",
    "SELECT id, item_name, amount, total, quantity FROM os_items WHERE contract_id=3238",
    "SELECT id, amount, payment_date, is_deleted FROM os_income WHERE contract_id=3238 ORDER BY id",
    "SELECT id, amount, category_id, is_deleted, description FROM os_expenses WHERE contract_id=3238",
    "SELECT id, amount, type, reason, is_deleted FROM os_contract_adjustments WHERE contract_id=3238",
    "SELECT id, total_debt, remaining_debt, monthly_installment, is_deleted, settlement_type FROM os_loan_scheduling WHERE contract_id=3238",
    "SELECT id, lawyer_cost, case_cost, is_deleted FROM os_judiciary WHERE contract_id=3238",
]

# Get DB credentials
out, err = run(f"grep 'dsn\\|username\\|password' {root}/common/config/main-local.php")
print("=== DB Config ===")
print(out)

# Parse DB name from DSN
import re
dsn_match = re.search(r"dbname=(\w+)", out)
db_name = dsn_match.group(1) if dsn_match else None
user_match = re.search(r"'username'\s*=>\s*'(\w+)'", out)
pass_match = re.search(r"'password'\s*=>\s*'([^']*)'", out)
db_user = user_match.group(1) if user_match else 'root'
db_pass = pass_match.group(1) if pass_match else ''
print(f"DB: {db_name}, User: {db_user}")

for q in queries:
    print(f"\n--- {q[:60]}... ---")
    cmd = f'mysql -u{db_user} -p"{db_pass}" {db_name} -e "{q}" 2>/dev/null'
    out, err = run(cmd)
    print(out if out else "(empty)")

ssh.close()
print('\nDone!')
