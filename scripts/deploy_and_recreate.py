import sys, io, os, paramiko
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
print('Connecting...')
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

# Upload file
sftp = ssh.open_sftp()
local = r'c:\Users\PC\Desktop\Tayseer\backend\modules\followUpReport\controllers\FollowUpReportController.php'
remote = '/var/www/jadal.aqssat.co/backend/modules/followUpReport/controllers/FollowUpReportController.php'
sftp.put(local, remote)
print('Uploaded controller')
sftp.close()

# Flush cache + OPcache
stdin, stdout, stderr = ssh.exec_command('cd /var/www/jadal.aqssat.co && php yii cache/flush-all', timeout=30)
print(stdout.read().decode('utf-8', errors='replace'))

sftp2 = ssh.open_sftp()
with sftp2.file('/var/www/jadal.aqssat.co/backend/web/_opcache_reset.php', 'w') as fh:
    fh.write('<?php opcache_reset(); echo "OPcache reset OK";')
sftp2.close()
stdin, stdout, stderr = ssh.exec_command('curl -sLk https://jadal.aqssat.co/_opcache_reset.php 2>&1', timeout=15)
print('OPcache:', stdout.read().decode('utf-8', errors='replace'))
ssh.exec_command('rm -f /var/www/jadal.aqssat.co/backend/web/_opcache_reset.php')

# Recreate VIEWs
php_recreate = r"""<?php
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

$controller = new \backend\modules\followUpReport\controllers\FollowUpReportController('follow-up-report', Yii::$app->getModule('followUpReport'));

$m1 = new ReflectionMethod($controller, 'createFollowUpReportView');
$m1->setAccessible(true);
$m1->invoke($controller);
echo "Main VIEW recreated\n";

$m2 = new ReflectionMethod($controller, 'createNoContactView');
$m2->setAccessible(true);
$m2->invoke($controller);
echo "NoContact VIEW recreated\n";

// Verify contract 6689
$row = $db->createCommand("SELECT due_installments, due_amount, effective_installment FROM os_follow_up_report WHERE id = 6689")->queryOne();
if ($row) {
    echo "6689: due_installments=" . $row['due_installments'] . " due_amount=" . $row['due_amount'] . " effective=" . $row['effective_installment'] . "\n";
}

// Verify contract 6169
$row2 = $db->createCommand("SELECT due_installments, due_amount, effective_installment FROM os_follow_up_report WHERE id = 6169")->queryOne();
if ($row2) {
    echo "6169: due_installments=" . $row2['due_installments'] . " due_amount=" . $row2['due_amount'] . " effective=" . $row2['effective_installment'] . "\n";
}

echo "DONE\n";
"""

sftp3 = ssh.open_sftp()
with sftp3.file('/var/www/jadal.aqssat.co/backend/web/_recreate.php', 'w') as fh:
    fh.write(php_recreate)
sftp3.close()

stdin, stdout, stderr = ssh.exec_command('cd /var/www/jadal.aqssat.co && php backend/web/_recreate.php', timeout=60)
print(stdout.read().decode('utf-8', errors='replace'))
err = stderr.read().decode('utf-8', errors='replace')
if err:
    print('STDERR:', err[:500])

ssh.exec_command('rm -f /var/www/jadal.aqssat.co/backend/web/_recreate.php')
ssh.close()
print('Deploy complete!')
