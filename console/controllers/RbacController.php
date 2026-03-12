<?php
namespace console\controllers;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;
use yii\db\Exception;

class RbacController extends Controller
{
    /** دور مدير النظام في هذا المشروع */
    const ROLE_SYSTEM_ADMIN = 'مدير النظام';
    public function actionInit()
    {
        $auth = Yii::$app->authManager;
        try {
            $auth->removeAll();


            $createPost = $auth->createPermission('admin');
            $createPost->description = '';
            $auth->add($createPost);


            $author = $auth->createRole('author');
            $auth->add($author);
            $auth->addChild($author, $createPost);


            $auth->assign($author, 2);
        }catch (Exception $e){
            throw new $e;
    }


    }
    public function actionAssgin(){
        $auth = Yii::$app->authManager;
        try {
            $user = new User();
            $user->username = $this->username;
            $user->email = $this->email;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->save(false);

            $authorRole = $auth->getRole('author');
            $auth->assign($authorRole, $user->getId());

        }catch (Exception $e){
            throw new $e;
        }
    }

    /**
     * التحقق من مستخدم بالبريد وتعيين دور «مدير النظام» له إن لم يكن معيّناً.
     * الاستخدام: php yii rbac/ensure-system-admin osamaqazan89@gmail.com
     * @param string $email البريد الإلكتروني للمستخدم
     * @return int
     */
    public function actionEnsureSystemAdmin($email)
    {
        $email = trim($email);
        if ($email === '') {
            $this->stderr("يجب تمرير البريد الإلكتروني. مثال: php yii rbac/ensure-system-admin user@example.com\n");
            return ExitCode::DATAERR;
        }

        $user = User::find()->andWhere(['email' => $email])->one();
        if (!$user) {
            $this->stderr("المستخدم غير موجود بالبريد: {$email}\n");
            return ExitCode::DATAERR;
        }

        $auth = Yii::$app->authManager;
        $roles = array_keys($auth->getRolesByUser($user->id));
        $hasRole = in_array(self::ROLE_SYSTEM_ADMIN, $roles, true);

        if ($hasRole) {
            $this->stdout("المستخدم (id={$user->id}, {$user->email}) لديه بالفعل دور «" . self::ROLE_SYSTEM_ADMIN . "».\n");
            return ExitCode::OK;
        }

        $role = $auth->getRole(self::ROLE_SYSTEM_ADMIN);
        if (!$role) {
            $this->stderr("دور «" . self::ROLE_SYSTEM_ADMIN . "» غير موجود. نفّذ أولاً من واجهة إدارة الصلاحيات: إنشاء الأدوار الافتراضية، ثم أعد تشغيل هذا الأمر.\n");
            return ExitCode::UNAVAILABLE;
        }

        $auth->assign($role, $user->id);
        $auth->invalidateCache();
        $this->stdout("تم تعيين دور «" . self::ROLE_SYSTEM_ADMIN . "» للمستخدم (id={$user->id}, {$user->email}).\n");
        return ExitCode::OK;
    }
}
