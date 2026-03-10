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

use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\contracts\models\ContractAdjustment;

$cid = 3238;
$calc = new ContractCalculations($cid);
$c = $calc->original_contract;

echo "=== Contract $cid ===\n";
echo "status: " . $c->status . "\n";
echo "total_value: " . $c->total_value . "\n";
echo "commitment_discount (field): " . $c->commitment_discount . "\n\n";

echo "=== ContractCalculations ===\n";
echo "totalDebt: " . $calc->totalDebt() . "\n";
echo "paidAmount: " . $calc->paidAmount() . "\n";
echo "totalAdjustments: " . $calc->totalAdjustments() . "\n";
echo "commitmentDiscount: " . $calc->commitmentDiscount() . "\n";
echo "remainingAmount: " . $calc->remainingAmount() . "\n";
echo "isJudiciaryPaid: " . ($calc->isJudiciaryPaid() ? 'YES' : 'NO') . "\n";
echo "isClosed: " . ($calc->isClosed() ? 'YES' : 'NO') . "\n\n";

// Check adjustments detail
echo "=== Adjustments Detail ===\n";
$adjs = ContractAdjustment::find()->where(['contract_id' => $cid, 'is_deleted' => 0])->all();
foreach ($adjs as $a) {
    echo "id={$a->id} type={$a->type} amount={$a->amount} reason={$a->reason}\n";
}
if (empty($adjs)) echo "No adjustments\n";

// Check all expenses
echo "\n=== Expenses ===\n";
$exps = Yii::$app->db->createCommand("SELECT id, amount, category_id FROM os_expenses WHERE contract_id = $cid")->queryAll();
foreach ($exps as $e) echo "id={$e['id']} amount={$e['amount']} cat={$e['category_id']}\n";
if (empty($exps)) echo "No expenses\n";

// Check judiciary
echo "\n=== Judiciary ===\n";
$juds = Yii::$app->db->createCommand("SELECT id, lawyer_cost, case_cost, is_deleted FROM os_judiciary WHERE contract_id = $cid")->queryAll();
foreach ($juds as $j) echo "id={$j['id']} lawyer={$j['lawyer_cost']} case={$j['case_cost']} deleted={$j['is_deleted']}\n";
if (empty($juds)) echo "No judiciary\n";

// Check income (paid)
echo "\n=== Income (paid) ===\n";
$totalPaid = Yii::$app->db->createCommand("SELECT COALESCE(SUM(amount),0) as total FROM os_income WHERE contract_id = $cid")->queryScalar();
echo "Total paid: $totalPaid\n";
"""

sftp = ssh.open_sftp()
with sftp.file(f'{root}/backend/web/_test_3238.php', 'w') as f:
    f.write(php_code)
sftp.close()

out, err = run(f'curl -sLk https://{site}.aqssat.co/_test_3238.php 2>&1')
print(out)

run(f'rm -f {root}/backend/web/_test_3238.php')
ssh.close()
print('Done!')
