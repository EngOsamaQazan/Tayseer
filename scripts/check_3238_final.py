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
    if err.strip():
        return f"(output: {out.strip()}) (err: {err.strip()})"
    return out.strip() if out.strip() else "(empty)"

print("=== os_income columns ===")
print(q("DESCRIBE os_income"))

print("\n=== All income for 3238 ===")
print(q("SELECT * FROM os_income WHERE contract_id=3238"))

print("\n=== os_items check ===")
print(q("DESCRIBE os_items"))

print("\n=== Items for 3238 ===")
print(q("SELECT * FROM os_items WHERE contract_id=3238"))

print("\n=== contracts_customers for 3238 ===")
print(q("SELECT * FROM os_contracts_customers WHERE contract_id=3238"))

print("\n=== Customer data ===")
# Get customer IDs for contract 3238 and show names
print(q("SELECT cc.customer_id, c.name FROM os_contracts_customers cc JOIN os_customers c ON c.id = cc.customer_id WHERE cc.contract_id=3238"))

# Check if the first test script result was using ContractInstallment correctly
print("\n=== ContractInstallment SUM vs os_income SUM ===")
print(q("SELECT SUM(amount) as income_total FROM os_income WHERE contract_id=3238"))

# Check what the actual deployed ContractCalculations code looks like
print("\n=== Deployed remainingAmount method ===")
out, err = run(f"grep -A5 'function remainingAmount' {root}/backend/modules/followUp/helper/ContractCalculations.php")
print(out)

print("\n=== Deployed paidAmount method ===")
out, err = run(f"grep -A10 'function paidAmount' {root}/backend/modules/followUp/helper/ContractCalculations.php")
print(out)

print("\n=== Deployed getContractTotal method ===")
out, err = run(f"grep -A5 'function getContractTotal' {root}/backend/modules/followUp/helper/ContractCalculations.php")
print(out)

run("rm -f /tmp/.my_temp.cnf")
ssh.close()
print("\nDone!")
