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

# Lines 380-410 (where _stl vars are defined)
run("modals.php lines 380-415",
    "sed -n '380,415p' /var/www/jadal.aqssat.co/backend/modules/followUp/views/follow-up/modals.php")

# ContractCalculations - getSettlementSnapshot method
run("ContractCalculations getSettlementSnapshot method",
    "grep -n 'function\\|totalDebt\\|total_value\\|totalValue\\|total_debt' /var/www/jadal.aqssat.co/backend/modules/followUp/helper/ContractCalculations.php | head -30")

# Full getSettlementSnapshot
run("Settlement section in ContractCalculations",
    "grep -A30 'function getSettlementSnapshot' /var/www/jadal.aqssat.co/backend/modules/followUp/helper/ContractCalculations.php")

# Look at $_vbStl source
run("_vbStl in modals.php",
    "grep -n '_vbStl\\|contractCalculations' /var/www/jadal.aqssat.co/backend/modules/followUp/views/follow-up/modals.php | head -10")

# Check what settlementFinancials is in panel.php
run("settlementFinancials passed to modals",
    "grep -n 'settlementFinancials\\|modals' /var/www/jadal.aqssat.co/backend/modules/followUp/views/follow-up/panel.php | head -10")

ssh.close()
