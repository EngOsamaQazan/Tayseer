"""
Fix judiciary/view 500 error on jadal.aqssat.co
Root cause: missing DB columns (action_nature, action_type, request_status, etc.)
that are referenced by application code but never formally migrated.
"""

import paramiko
import os
import sys

SERVER = '31.220.82.115'
USER = 'root'
PASSWD = 'HAmAS12852'
REMOTE_ROOT = '/var/www/jadal.aqssat.co'

LOCAL_BASE = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))

FILES_TO_UPLOAD = [
    (
        'console/migrations/m260412_100000_add_missing_judiciary_columns.php',
        'console/migrations/m260412_100000_add_missing_judiciary_columns.php',
    ),
    (
        'backend/modules/judiciary/controllers/JudiciaryController.php',
        'backend/modules/judiciary/controllers/JudiciaryController.php',
    ),
    (
        'backend/modules/judiciary/views/judiciary/view.php',
        'backend/modules/judiciary/views/judiciary/view.php',
    ),
]


def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    print(f'[1/5] Connecting to {SERVER}...')
    ssh.connect(SERVER, username=USER, password=PASSWD, timeout=15)
    print('      Connected.')

    sftp = ssh.open_sftp()

    print('[2/5] Uploading files...')
    for local_rel, remote_rel in FILES_TO_UPLOAD:
        local_path = os.path.join(LOCAL_BASE, local_rel).replace('/', os.sep)
        remote_path = f'{REMOTE_ROOT}/{remote_rel}'
        print(f'      {local_rel}')
        sftp.put(local_path, remote_path)
    print('      All files uploaded.')

    print('[3/5] Running migration...')
    cmd = f'cd {REMOTE_ROOT} && php yii migrate/up --interactive=0 2>&1'
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    print(out)
    if err:
        print('STDERR:', err)

    print('[4/5] Verifying columns exist...')
    verify_sql = """
    SELECT
        (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_judiciary_actions'
         AND COLUMN_NAME = 'action_nature') AS has_action_nature,
        (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_judiciary_actions'
         AND COLUMN_NAME = 'action_type') AS has_action_type,
        (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_judiciary_customers_actions'
         AND COLUMN_NAME = 'request_status') AS has_request_status,
        (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_judiciary_defendant_stage'
         AND COLUMN_NAME = 'notification_date') AS has_notification_date,
        (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_judiciary'
         AND COLUMN_NAME = 'case_status') AS has_case_status
    """
    verify_cmd = f'cd {REMOTE_ROOT} && php yii db/query "{verify_sql}" 2>/dev/null || mysql -u root -e "USE namaa_jadal; {verify_sql}" 2>&1'
    stdin, stdout, stderr = ssh.exec_command(verify_cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace')
    print(out or '(use web browser to verify)')

    print('[5/5] Testing judiciary/view/5944...')
    test_cmd = f'cd {REMOTE_ROOT} && php yii serve-test 2>/dev/null; curl -sS -o /dev/null -w "%{{http_code}}" "http://localhost/judiciary/view/5944" 2>/dev/null || echo "Run manual test at https://jadal.aqssat.co/judiciary/view/5944"'
    stdin, stdout, stderr = ssh.exec_command(test_cmd, timeout=15)
    out = stdout.read().decode('utf-8', errors='replace')
    print(f'      {out}')

    sftp.close()
    ssh.close()
    print('\nDone. Please verify: https://jadal.aqssat.co/judiciary/view/5944')


if __name__ == '__main__':
    main()
