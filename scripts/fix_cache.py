import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

for site in ['jadal', 'namaa']:
    root = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')

    # Create an OPcache reset + test script via web
    php_code = """<?php
opcache_reset();
echo "opcache_reset: done\\n";

// Bootstrap Yii
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
$config = yii\\helpers\\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../backend/config/main.php',
    require __DIR__ . '/../../backend/config/main-local.php'
);
new yii\\web\\Application($config);

$c = backend\\modules\\contracts\\models\\Contracts::findOne(6437);
if (!$c) { echo "Contract 6437 not found\\n"; exit; }
echo "status: " . $c->status . "\\n";

$calc = new backend\\modules\\followUp\\helper\\ContractCalculations(6437);
echo "totalDebt: " . $calc->totalDebt() . "\\n";
echo "paidAmount: " . $calc->paidAmount() . "\\n";
echo "totalAdjustments: " . $calc->totalAdjustments() . "\\n";
echo "commitmentDiscount: " . $calc->commitmentDiscount() . "\\n";
echo "remainingAmount: " . $calc->remainingAmount() . "\\n";
echo "isJudiciaryPaid: " . ($c->isJudiciaryPaid() ? "YES" : "NO") . "\\n";
"""

    # Write the test file
    sftp = ssh.open_sftp()
    with sftp.file(f'{root}/backend/web/_test_cache.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    # Run via curl (through Apache so OPcache is in the right context)
    out, err = run(f'curl -sLk https://{site}.aqssat.co/_test_cache.php 2>&1')
    print(out)

    # Cleanup
    run(f'rm -f {root}/backend/web/_test_cache.php')

    # Also flush Yii cache
    out, _ = run(f'cd {root} && php yii cache/flush-all 2>&1')
    print(f'yii cache: {out.strip()}')

# Restart Apache
out, err = run('systemctl restart apache2')
print(f'\nApache: {err.strip() or "restarted"}')

ssh.close()
print('\nDone!')
