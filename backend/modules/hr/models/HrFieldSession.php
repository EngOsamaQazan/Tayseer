<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "{{%hr_field_session}}".
 * جلسات العمل الميداني
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $start_lat
 * @property string|null $start_lng
 * @property string|null $end_lat
 * @property string|null $end_lng
 * @property string|null $status
 * @property float|null $total_distance_km
 * @property string|null $device_info
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 */
class HrFieldSession extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_field_session}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['start_time', 'end_time'], 'safe'],
            [['start_lat', 'start_lng', 'end_lat', 'end_lng'], 'string', 'max' => 30],
            [['status'], 'string', 'max' => 30],
            [['total_distance_km'], 'number'],
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
            'start_time' => Yii::t('app', 'وقت البدء'),
            'end_time' => Yii::t('app', 'وقت الانتهاء'),
            'start_lat' => Yii::t('app', 'خط عرض البداية'),
            'start_lng' => Yii::t('app', 'خط طول البداية'),
            'end_lat' => Yii::t('app', 'خط عرض النهاية'),
            'end_lng' => Yii::t('app', 'خط طول النهاية'),
            'status' => Yii::t('app', 'الحالة'),
            'total_distance_km' => Yii::t('app', 'المسافة الإجمالية (كم)'),
            'device_info' => Yii::t('app', 'معلومات الجهاز'),
            'notes' => Yii::t('app', 'ملاحظات'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLocationPoints()
    {
        return $this->hasMany(HrLocationPoint::class, ['session_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(HrFieldEvent::class, ['session_id' => 'id']);
    }
}
