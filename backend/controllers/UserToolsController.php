<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use common\models\User;
use common\helper\Permissions;

/**
 * أدوات المستخدم من الويب (فحص حساب، إصلاح، تعيين كلمة مرور).
 * يعمل بنفس بيئة الموقع فلا يحتاج PDO في الطرفية.
 * يتطلب صلاحية «أدوات المستخدم» من إدارة الصلاحيات.
 */
class UserToolsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return \common\helper\Permissions::hasAnyPermission([Permissions::USER_TOOLS]);
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * صفحة واحدة: إدخال البريد/المستخدم + أزرار فحص / إصلاح / تعيين كلمة مرور.
     */
    public function actionIndex()
    {
        $login = trim((string) (Yii::$app->request->post('login') ?? Yii::$app->request->get('login') ?? ''));
        $password = (string) (Yii::$app->request->post('password') ?? '');
        $result = null;
        $error = null;

        if ($login !== '') {
            $user = User::find()
                ->andWhere(['or', ['email' => $login], ['username' => $login]])
                ->one();

            if (!$user) {
                $error = "المستخدم غير موجود: {$login}";
            } else {
                $result = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'confirmed_at' => $user->confirmed_at ? date('Y-m-d H:i', $user->confirmed_at) : null,
                    'blocked_at' => $user->blocked_at ? date('Y-m-d H:i', $user->blocked_at) : null,
                    'has_password' => strlen($user->password_hash) > 0,
                ];

                $action = Yii::$app->request->post('action');
                if ($action === 'fix') {
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
                        $result['fixed'] = true;
                        $result['confirmed_at'] = date('Y-m-d H:i', $user->confirmed_at);
                        $result['blocked_at'] = null;
                    } elseif (!$updated) {
                        $result['fixed'] = 'no_change';
                    }
                } elseif ($action === 'set_password' && $password !== '') {
                    $user->password_hash = Yii::$app->security->generatePasswordHash($password);
                    if ($user->save(false)) {
                        $result['password_set'] = true;
                    } else {
                        $error = 'فشل حفظ كلمة المرور.';
                    }
                }
            }
        }

        return $this->render('index', [
            'login' => $login,
            'password' => $password,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
