<?php

namespace backend\modules\companyManagement\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Company extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%companies}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['slug', 'name_ar', 'domain', 'db_name'], 'required'],
            [['slug'], 'string', 'max' => 50],
            [['slug'], 'match', 'pattern' => '/^[a-z][a-z0-9_]*$/',
                'message' => 'المعرف يجب أن يبدأ بحرف لاتيني صغير ويحتوي فقط على أحرف وأرقام و _'],
            [['slug', 'domain'], 'unique'],
            [['name_ar', 'name_en'], 'string', 'max' => 255],
            [['domain'], 'string', 'max' => 255],
            [['db_name'], 'string', 'max' => 100],
            [['server_ip'], 'string', 'max' => 45],
            [['sms_sender', 'sms_user'], 'string', 'max' => 50],
            [['sms_pass'], 'string', 'max' => 100],
            [['og_title'], 'string', 'max' => 255],
            [['og_description'], 'string', 'max' => 500],
            [['og_image'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => ['pending', 'dns_ready', 'provisioned', 'active', 'disabled']],
            [['provision_log'], 'string'],
            [['provisioned_at', 'created_at', 'updated_at'], 'integer'],
            [['name_en', 'sms_sender', 'sms_user', 'sms_pass', 'og_title', 'og_description', 'og_image', 'provision_log'], 'default', 'value' => null],
            [['server_ip'], 'default', 'value' => '31.220.82.115'],
            [['status'], 'default', 'value' => 'pending'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => '#',
            'slug'           => 'المعرّف',
            'name_ar'        => 'اسم الشركة (عربي)',
            'name_en'        => 'اسم الشركة (إنجليزي)',
            'domain'         => 'النطاق',
            'db_name'        => 'قاعدة البيانات',
            'server_ip'      => 'عنوان IP للخادم',
            'sms_sender'     => 'اسم مرسل SMS',
            'sms_user'       => 'مستخدم SMS',
            'sms_pass'       => 'كلمة مرور SMS',
            'og_title'       => 'عنوان OG',
            'og_description' => 'وصف OG',
            'og_image'       => 'صورة OG',
            'status'         => 'الحالة',
            'provision_log'  => 'سجل التجهيز',
            'provisioned_at' => 'تاريخ التجهيز',
            'created_at'     => 'تاريخ الإنشاء',
            'updated_at'     => 'تاريخ التحديث',
        ];
    }

    public function getStatusLabel(): string
    {
        $labels = [
            'pending'     => 'قيد الانتظار',
            'dns_ready'   => 'DNS جاهز',
            'provisioned' => 'تم التجهيز',
            'active'      => 'نشط',
            'disabled'    => 'معطّل',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClass(): string
    {
        $classes = [
            'pending'     => 'bg-label-warning',
            'dns_ready'   => 'bg-label-info',
            'provisioned' => 'bg-label-primary',
            'active'      => 'bg-label-success',
            'disabled'    => 'bg-label-danger',
        ];
        return $classes[$this->status] ?? 'bg-label-secondary';
    }

    public function appendLog(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] {$message}";
        $this->provision_log = $this->provision_log
            ? $this->provision_log . "\n" . $entry
            : $entry;
    }
}
