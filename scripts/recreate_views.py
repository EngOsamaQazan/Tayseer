import sys, io, paramiko
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

# Run a PHP script to recreate the views by calling the controller method
php_code = r"""<?php
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';
$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);
$app = new yii\web\Application($config);

$db = Yii::$app->db;

// Test contract 6689
$row = $db->createCommand("SELECT id, total_value, monthly_installment_value, first_installment_date FROM os_contracts WHERE id = 6689")->queryOne();
echo "Contract 6689: total=" . $row['total_value'] . " installment=" . $row['monthly_installment_value'] . " first_date=" . $row['first_installment_date'] . "\n";

$paid = $db->createCommand("SELECT COALESCE(SUM(amount),0) FROM os_income WHERE contract_id = 6689")->queryScalar();
echo "Paid: $paid\n";

$expenses = $db->createCommand("SELECT COALESCE(SUM(amount),0) FROM os_expenses WHERE contract_id = 6689 AND (is_deleted=0 OR is_deleted IS NULL)")->queryScalar();
echo "Expenses: $expenses\n";

$adj = $db->createCommand("SELECT COALESCE(SUM(amount),0) FROM os_contract_adjustments WHERE contract_id = 6689 AND is_deleted=0")->queryScalar();
echo "Adjustments: $adj\n";

$lawyer = $db->createCommand("SELECT COALESCE(SUM(lawyer_cost),0) FROM os_judiciary WHERE contract_id = 6689 AND is_deleted=0")->queryScalar();
echo "Lawyer: $lawyer\n";

$remaining = $row['total_value'] + $expenses + $lawyer - $adj - $paid;
echo "Remaining: $remaining\n";

$maxInst = ceil(max(0, $remaining) / max($row['monthly_installment_value'], 1));
echo "Max installments (capped): $maxInst\n";

// Check current VIEW value
$viewRow = $db->createCommand("SELECT due_installments, due_amount, effective_installment FROM os_follow_up_report WHERE id = 6689")->queryOne();
if ($viewRow) {
    echo "VIEW due_installments=" . $viewRow['due_installments'] . " due_amount=" . $viewRow['due_amount'] . " effective_installment=" . $viewRow['effective_installment'] . "\n";
} else {
    echo "Contract 6689 not in follow_up_report view\n";
}

echo "DONE\n";
"""

sftp = ssh.open_sftp()
with sftp.file('/var/www/jadal.aqssat.co/backend/web/_check_view.php', 'w') as fh:
    fh.write(php_code)
sftp.close()

stdin, stdout, stderr = ssh.exec_command('cd /var/www/jadal.aqssat.co && php backend/web/_check_view.php', timeout=30)
print(stdout.read().decode('utf-8', errors='replace'))
err = stderr.read().decode('utf-8', errors='replace')
if err:
    print('STDERR:', err[:500])

ssh.exec_command('rm -f /var/www/jadal.aqssat.co/backend/web/_check_view.php')
ssh.close()
