<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%hr_audit_log}}".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $action
 * @property string|null $model_class
 * @property int|null $model_id
 * @property string|null $old_values
 * @property string|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $created_at
 */
class HrAuditLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_audit_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'model_id', 'created_at'], 'integer'],
            [['action'], 'string', 'max' => 50],
            [['model_class'], 'string', 'max' => 255],
            [['old_values', 'new_values'], 'string'],
            [['ip_address'], 'string', 'max' => 50],
            [['user_agent'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'المستخدم'),
            'action' => Yii::t('app', 'الاجراء'),
            'model_class' => Yii::t('app', 'نوع السجل'),
            'model_id' => Yii::t('app', 'معرف السجل'),
            'old_values' => Yii::t('app', 'القيم القديمة'),
            'new_values' => Yii::t('app', 'القيم الجديدة'),
            'ip_address' => Yii::t('app', 'عنوان IP'),
            'user_agent' => Yii::t('app', 'وكيل المستخدم'),
            'created_at' => Yii::t('app', 'تاريخ الانشاء'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->created_at)) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }
}
