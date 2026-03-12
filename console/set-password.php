<?php
/**
 * سكربت لتعيين كلمة مرور مستخدم (بدون تحميل وحدة dektrium لتجنب تحذيرات PHP).
 * التشغيل: php console/set-password.php <email_or_username> <new_password>
 * مثال: php console/set-password.php abu.danial.1993@gmail.com admin123
 * مثال: php console/set-password.php admin admin123
 */
if (php_sapi_name() !== 'cli') {
    die('CLI only.');
}

$login = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$login || !$password) {
    fwrite(STDERR, "الاستخدام: php console/set-password.php <البريد_أو_اسم_المستخدم> <كلمة_المرور_الجديدة>\n");
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../common/config/main.php',
    file_exists(__DIR__ . '/../common/config/main-local.php') ? require __DIR__ . '/../common/config/main-local.php' : []
);

new yii\console\Application([
    'id' => 'set-password',
    'basePath' => dirname(__DIR__),
    'components' => [
        'db' => $config['components']['db'],
        'security' => ['class' => 'yii\base\Security'],
    ],
]);

$db = Yii::$app->db;
$row = $db->createCommand(
    'SELECT id, username, email FROM {{%user}} WHERE email = :login OR username = :login2 LIMIT 1',
    [':login' => $login, ':login2' => $login]
)->queryOne();

if (!$row) {
    fwrite(STDERR, "المستخدم غير موجود: {$login}\n");
    exit(1);
}

$hash = Yii::$app->security->generatePasswordHash($password);
$db->createCommand()->update('{{%user}}', ['password_hash' => $hash], ['id' => $row['id']])->execute();

echo "تم تعيين كلمة المرور بنجاح للمستخدم: {$row['username']} ({$row['email']})\n";
exit(0);
