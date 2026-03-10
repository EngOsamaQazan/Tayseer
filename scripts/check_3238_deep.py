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

php_code = r"""<?php
opcache_reset();
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../backend/config/main.php',
    require __DIR__ . '/../../backend/config/main-local.php'
);
new yii\web\Application($config);

$db = Yii::$app->db;
$cid = 3238;

echo "=== Contract Record ===\n";
$c = $db->createCommand("SELECT id, total_value, commitment_discount, status, monthly_installment_value, first_installment_date FROM os_contracts WHERE id = $cid")->queryOne();
print_r($c);

echo "\n=== Items in contract ===\n";
$items = $db->createCommand("SELECT id, item_name, amount, total, quantity FROM os_items WHERE contract_id = $cid")->queryAll();
$itemsTotal = 0;
foreach ($items as $i) {
    echo "id={$i['id']} item={$i['item_name']} amount={$i['amount']} total={$i['total']} qty={$i['quantity']}\n";
    $itemsTotal += (float)$i['total'];
}
echo "Items total: $itemsTotal\n";

echo "\n=== Income (payments) detail ===\n";
$incomes = $db->createCommand("SELECT id, amount, payment_date, payment_value, is_deleted FROM os_income WHERE contract_id = $cid ORDER BY id")->queryAll();
$totalActive = 0;
$totalAll = 0;
foreach ($incomes as $i) {
    $del = $i['is_deleted'] ? 'DELETED' : 'active';
    echo "id={$i['id']} amount={$i['amount']} pay_val={$i['payment_value']} date={$i['payment_date']} {$del}\n";
    $totalAll += (float)$i['amount'];
    if (!$i['is_deleted']) $totalActive += (float)$i['amount'];
}
echo "Total all: $totalAll, Total active: $totalActive\n";

echo "\n=== Expenses detail ===\n";
$exps = $db->createCommand("SELECT id, amount, category_id, is_deleted, description FROM os_expenses WHERE contract_id = $cid")->queryAll();
foreach ($exps as $e) {
    $del = $e['is_deleted'] ? 'DELETED' : 'active';
    echo "id={$e['id']} amount={$e['amount']} cat={$e['category_id']} {$del} desc={$e['description']}\n";
}

echo "\n=== Adjustments detail ===\n";
$adjs = $db->createCommand("SELECT id, amount, type, reason, is_deleted FROM os_contract_adjustments WHERE contract_id = $cid")->queryAll();
foreach ($adjs as $a) {
    $del = $a['is_deleted'] ? 'DELETED' : 'active';
    echo "id={$a['id']} amount={$a['amount']} type={$a['type']} reason={$a['reason']} {$del}\n";
}
if (empty($adjs)) echo "No adjustments\n";

echo "\n=== Settlements (loan_scheduling) ===\n";
$loans = $db->createCommand("SELECT id, total_debt, remaining_debt, monthly_installment, first_installment_date, is_deleted, settlement_type FROM os_loan_scheduling WHERE contract_id = $cid")->queryAll();
foreach ($loans as $l) {
    $del = $l['is_deleted'] ? 'DELETED' : 'active';
    echo "id={$l['id']} total_debt={$l['total_debt']} remaining={$l['remaining_debt']} monthly={$l['monthly_installment']} type={$l['settlement_type']} {$del}\n";
}
if (empty($loans)) echo "No settlements\n";

echo "\n=== Judiciary ===\n";
$juds = $db->createCommand("SELECT id, lawyer_cost, case_cost, is_deleted FROM os_judiciary WHERE contract_id = $cid")->queryAll();
foreach ($juds as $j) {
    $del = $j['is_deleted'] ? 'DELETED' : 'active';
    echo "id={$j['id']} lawyer={$j['lawyer_cost']} case={$j['case_cost']} {$del}\n";
}
"""

sftp = ssh.open_sftp()
with sftp.file(f'{root}/backend/web/_test_3238d.php', 'w') as f:
    f.write(php_code)
sftp.close()

out, err = run(f'curl -sLk https://{site}.aqssat.co/_test_3238d.php 2>&1')
print(out)
if err:
    print("STDERR:", err)

run(f'rm -f {root}/backend/web/_test_3238d.php')
ssh.close()
print('Done!')
