import paramiko
import os

LOCAL_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

FILES = [
    'backend/modules/customers/controllers/CustomersController.php',
    'backend/modules/customers/models/CustomersSearch.php',
    'backend/modules/customers/views/customers/_search.php',
    'backend/modules/customers/views/customers/index.php',
    'backend/modules/followUpReport/models/FollowUpReportSearch.php',
    'backend/modules/followUpReport/views/follow-up-report/index.php',
    'backend/modules/judiciary/controllers/JudiciaryController.php',
    'backend/modules/judiciary/models/JudiciarySearch.php',
    'backend/modules/judiciary/views/judiciary/_columns.php',
    'backend/modules/judiciary/views/judiciary/_form.php',
    'backend/modules/judiciary/views/judiciary/batch_actions.php',
    'backend/modules/judiciary/views/judiciary/index.php',
    'backend/modules/judiciaryActions/views/judiciary-actions/_columns.php',
    'backend/modules/judiciaryActions/views/judiciary-actions/_confirm_delete.php',
    'backend/modules/judiciaryActions/views/judiciary-actions/_form.php',
    'backend/modules/judiciaryActions/views/judiciary-actions/index.php',
    'backend/modules/judiciaryActions/controllers/JudiciaryActionsController.php',
    'backend/modules/judiciaryActions/models/JudiciaryActions.php',
    'backend/modules/followUp/views/follow-up/panel.php',
    'backend/modules/followUp/views/follow-up/panel/_ai_suggestions.php',
    'backend/web/css/ocp.css',
    'backend/modules/followUp/helper/ContractCalculations.php',
    'backend/modules/followUp/views/follow-up/partial/tabs/payments.php',
    'backend/modules/followUp/controllers/FollowUpController.php',
    'backend/modules/followUp/views/follow-up/panel/_financial.php',
    'backend/modules/contracts/controllers/ContractsController.php',
    'backend/modules/contracts/views/contracts/index.php',
]

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)
sftp = ssh.open_sftp()

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=120)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

for site in ['namaa', 'jadal']:
    remote_root = f'/var/www/{site}.aqssat.co'
    print(f'\n=== Deploying to {site} ===')
    for f in FILES:
        local_path = os.path.join(LOCAL_ROOT, f.replace('/', os.sep))
        remote_path = f'{remote_root}/{f}'
        try:
            sftp.put(local_path, remote_path)
            print(f'  OK: {f}')
        except Exception as e:
            print(f'  FAIL: {f} -> {e}')

    out, err = run(f'cd {remote_root} && php yii cache/flush-all 2>&1')
    print(f'  Cache: {out.strip()}')

print('\n=== Restarting Apache ===')
out, err = run('systemctl restart apache2')
print(f'Apache: {err.strip() or "restarted"}')

sftp.close()
ssh.close()
print('\nDone!')
