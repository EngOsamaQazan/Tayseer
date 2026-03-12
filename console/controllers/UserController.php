<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;

/**
 * أوامر المستخدم من الكونسول (مثل إعادة تعيين كلمة المرور).
 */
class UserController extends Controller
{
    /**
     * إعادة تعيين كلمة مرور مستخدم بالبريد أو اسم المستخدم.
     * الاستخدام: php yii user/set-password <email|username> <كلمة_المرور_الجديدة>
     * مثال: php yii user/set-password abu.danial.1993@gmail.com admin123
     * مثال: php yii user/set-password admin admin123
     */
    public function actionSetPassword($login, $password)
    {
        $user = User::find()
            ->andWhere(['or', ['email' => $login], ['username' => $login]])
            ->one();

        if (!$user) {
            $this->stderr("المستخدم غير موجود: {$login}\n");
            return ExitCode::DATAERR;
        }

        $hash = Yii::$app->security->generatePasswordHash($password);
        $user->password_hash = $hash;
        if ($user->save(false)) {
            $this->stdout("تم تعيين كلمة المرور بنجاح للمستخدم: {$user->username} ({$user->email})\n");
            return ExitCode::OK;
        }

        $this->stderr("فشل حفظ كلمة المرور.\n");
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * فحص حالة حساب مستخدم (موجود؟ محظور؟ مؤكد؟).
     * الاستخدام: php yii user/check-account <email|username>
     */
    public function actionCheckAccount($login)
    {
        $user = User::find()
            ->andWhere(['or', ['email' => $login], ['username' => $login]])
            ->one();

        if (!$user) {
            $this->stderr("المستخدم غير موجود: {$login}\n");
            return ExitCode::DATAERR;
        }

        $this->stdout("المستخدم: id={$user->id} | username={$user->username} | email={$user->email}\n");
        $this->stdout("  confirmed_at: " . ($user->confirmed_at ? date('Y-m-d H:i', $user->confirmed_at) : 'null (غير مؤكد)') . "\n");
        $this->stdout("  blocked_at:   " . ($user->blocked_at ? date('Y-m-d H:i', $user->blocked_at) . ' (محظور)' : 'null') . "\n");
        $this->stdout("  password_hash: " . (!empty($user->password_hash) ? 'موجود' : 'فارغ') . "\n");

        if (!$user->confirmed_at) {
            $this->stdout("\nتحذير: الحساب غير مؤكد — قد يمنع تسجيل الدخول. استخدم: php yii user/fix-account {$login}\n");
        }
        if ($user->blocked_at) {
            $this->stdout("\nتحذير: الحساب محظور. استخدم: php yii user/fix-account {$login}\n");
        }
        return ExitCode::OK;
    }

    /**
     * إصلاح حساب: تأكيد البريد وإلغاء الحظر.
     * الاستخدام: php yii user/fix-account <email|username>
     */
    public function actionFixAccount($login)
    {
        $user = User::find()
            ->andWhere(['or', ['email' => $login], ['username' => $login]])
            ->one();

        if (!$user) {
            $this->stderr("المستخدم غير موجود: {$login}\n");
            return ExitCode::DATAERR;
        }

        $updated = false;
        if (!$user->confirmed_at) {
            $user->confirmed_at = time();
            $updated = true;
        }
        if ($user->blocked_at) {
            $user->blocked_at = null;
            $updated = true;
        }
        if ($updated && $user->save(false)) {
            $this->stdout("تم إصلاح الحساب: {$user->username} ({$user->email})\n");
            return ExitCode::OK;
        }
        if (!$updated) {
            $this->stdout("الحساب سليم ولا يحتاج إصلاحاً.\n");
            return ExitCode::OK;
        }
        $this->stderr("فشل حفظ التعديلات.\n");
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
