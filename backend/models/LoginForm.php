<?php

namespace backend\models;

use dektrium\user\models\LoginForm as BaseLoginForm;

/**
 * نموذج الدخول مع رسالة خطأ بالعربية.
 */
class LoginForm extends BaseLoginForm
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        foreach ($rules as $key => $rule) {
            if (isset($rule[0]) && $rule[0] === 'password' && isset($rule[1]) && is_callable($rule[1])) {
                $rules[$key][1] = function ($attribute) {
                    if ($this->user === null || !\dektrium\user\helpers\Password::validate($this->password, $this->user->password_hash)) {
                        $this->addError($attribute, 'اسم المستخدم أو البريد الإلكتروني أو كلمة المرور غير صحيحة');
                    }
                };
                break;
            }
        }
        return $rules;
    }
}
