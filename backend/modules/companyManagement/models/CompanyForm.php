<?php

namespace backend\modules\companyManagement\models;

use yii\base\Model;

class CompanyForm extends Model
{
    public $name_ar;
    public $name_en;
    public $slug;
    public $sms_sender;
    public $sms_user;
    public $sms_pass;
    public $admin_username = 'admin';
    public $admin_email;
    public $admin_password;

    public function rules()
    {
        return [
            [['name_ar', 'slug', 'admin_username', 'admin_email', 'admin_password'], 'required'],
            [['slug'], 'match', 'pattern' => '/^[a-z][a-z0-9_]*$/',
                'message' => 'المعرف يجب أن يبدأ بحرف لاتيني صغير ويحتوي فقط على أحرف وأرقام و _'],
            [['slug'], 'string', 'min' => 2, 'max' => 30],
            [['slug'], 'validateUniqueSlug'],
            [['name_ar', 'name_en'], 'string', 'max' => 255],
            [['sms_sender', 'sms_user'], 'string', 'max' => 50],
            [['sms_pass'], 'string', 'max' => 100],
            [['admin_username'], 'string', 'min' => 3, 'max' => 50],
            [['admin_email'], 'email'],
            [['admin_password'], 'string', 'min' => 6],
            [['name_en', 'sms_sender', 'sms_user', 'sms_pass'], 'default', 'value' => null],
        ];
    }

    public function validateUniqueSlug($attribute)
    {
        if (Company::find()->where(['slug' => $this->slug])->exists()) {
            $this->addError($attribute, 'هذا المعرف مستخدم بالفعل');
        }
    }

    public function attributeLabels()
    {
        return [
            'name_ar'        => 'اسم الشركة (عربي)',
            'name_en'        => 'اسم الشركة (إنجليزي)',
            'slug'           => 'المعرّف (Slug)',
            'sms_sender'     => 'اسم مرسل SMS',
            'sms_user'       => 'مستخدم SMS',
            'sms_pass'       => 'كلمة مرور SMS',
            'admin_username' => 'اسم مستخدم المدير',
            'admin_email'    => 'بريد المدير',
            'admin_password' => 'كلمة مرور المدير',
        ];
    }

    public function getDomain(): string
    {
        return $this->slug . '.aqssat.co';
    }

    public function getDbName(): string
    {
        return 'tayseer_' . $this->slug;
    }
}
