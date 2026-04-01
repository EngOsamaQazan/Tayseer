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

# Beginning of modals.php (where vars should be initialized)
run("modals.php lines 1-50",
    "sed -n '1,50p' /var/www/jadal.aqssat.co/backend/modules/followUp/views/follow-up/modals.php")

# Search for ALL _stl variables in modals.php
run("All _stl variables in modals.php",
    "grep -n '_stl' /var/www/jadal.aqssat.co/backend/modules/followUp/views/follow-up/modals.php")

# Check ContractCalculations class for settlement methods
run("ContractCalculations getSettlementSnapshot",
    "grep -n 'function getSettlement\\|_stlTotalDebt\\|stlTotalDebt\\|totalDebt\\|stl_total_debt' /var/www/jadal.aqssat.co/backend/modules/followUp/models/ContractCalculations.php 2>/dev/null || grep -rn 'ContractCalculations' /var/www/jadal.aqssat.co/backend/modules/followUp/models/ 2>/dev/null | head -5")

# Find ContractCalculations class
run("Find ContractCalculations",
    "find /var/www/jadal.aqssat.co -name 'ContractCalculations.php' -not -path '*/vendor/*' 2>/dev/null")

# Check what settlementFinancials contains 
run("settlementFinancials in controller",
    "grep -n 'settlementFinancials\\|getSettlementSnapshot\\|_stl' /var/www/jadal.aqssat.co/backend/modules/followUp/controllers/FollowUpController.php")

ssh.close()
