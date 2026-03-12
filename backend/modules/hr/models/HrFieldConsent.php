<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%hr_field_consent}}".
 * موافقات التتبع الميداني
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $consent_given
 * @property string|null $consent_date
 * @property string|null $ip_address
 * @property string|null $device_info
 * @property string|null $notes
 */
class HrFieldConsent extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_field_consent}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'consent_given'], 'integer'],
            [['consent_date'], 'safe'],
            [['ip_address'], 'string', 'max' => 50],
            [['device_info'], 'string', 'max' => 255],
            [['notes'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'user_id' => Yii::t('app', 'الموظف'),
            'consent_given' => Yii::t('app', 'تمت الموافقة'),
            'consent_date' => Yii::t('app', 'تاريخ الموافقة'),
            'ip_address' => Yii::t('app', 'عنوان IP'),
            'device_info' => Yii::t('app', 'معلومات الجهاز'),
            'notes' => Yii::t('app', 'ملاحظات'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }
}
