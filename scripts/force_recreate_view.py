import sys, io, paramiko
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

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

// Use reflection to call the private createFollowUpReportView method
$controller = new \backend\modules\followUpReport\controllers\FollowUpReportController('follow-up-report', Yii::$app->getModule('followUpReport'));
$method = new ReflectionMethod($controller, 'createFollowUpReportView');
$method->setAccessible(true);
$method->invoke($controller);
echo "Main VIEW recreated\n";

$method2 = new ReflectionMethod($controller, 'createNoContactView');
$method2->setAccessible(true);
$method2->invoke($controller);
echo "NoContact VIEW recreated\n";

// Verify
$row = $db->createCommand("SELECT due_installments, due_amount, effective_installment FROM os_follow_up_report WHERE id = 6689")->queryOne();
if ($row) {
    echo "Contract 6689: due_installments=" . $row['due_installments'] . " due_amount=" . $row['due_amount'] . " effective=" . $row['effective_installment'] . "\n";
} else {
    echo "Contract 6689 not in view\n";
}

echo "DONE\n";
"""

sftp = ssh.open_sftp()
with sftp.file('/var/www/jadal.aqssat.co/backend/web/_recreate.php', 'w') as fh:
    fh.write(php_code)
sftp.close()

stdin, stdout, stderr = ssh.exec_command('cd /var/www/jadal.aqssat.co && php backend/web/_recreate.php', timeout=60)
print(stdout.read().decode('utf-8', errors='replace'))
err = stderr.read().decode('utf-8', errors='replace')
if err:
    print('STDERR:', err[:500])

ssh.exec_command('rm -f /var/www/jadal.aqssat.co/backend/web/_recreate.php')
ssh.close()
