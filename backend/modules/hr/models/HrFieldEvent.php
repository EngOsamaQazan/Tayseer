<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "{{%hr_field_event}}".
 * أحداث العمل الميداني
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $session_id
 * @property int|null $task_id
 * @property string|null $event_type
 * @property string|null $event_time
 * @property string|null $lat
 * @property string|null $lng
 * @property string|null $photo_path
 * @property string|null $description
 * @property string|null $metadata
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrFieldEvent extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_field_event}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
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
            [['user_id', 'session_id', 'task_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['event_type'], 'string', 'max' => 50],
            [['event_time'], 'safe'],
            [['lat', 'lng'], 'string', 'max' => 30],
            [['photo_path'], 'string', 'max' => 500],
            [['description', 'metadata'], 'string'],
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
            'session_id' => Yii::t('app', 'الجلسة'),
            'task_id' => Yii::t('app', 'المهمة'),
            'event_type' => Yii::t('app', 'نوع الحدث'),
            'event_time' => Yii::t('app', 'وقت الحدث'),
            'lat' => Yii::t('app', 'خط العرض'),
            'lng' => Yii::t('app', 'خط الطول'),
            'photo_path' => Yii::t('app', 'مسار الصورة'),
            'description' => Yii::t('app', 'الوصف'),
            'metadata' => Yii::t('app', 'بيانات إضافية'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'updated_by' => Yii::t('app', 'عُدّل بواسطة'),
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
    public function getSession()
    {
        return $this->hasOne(HrFieldSession::class, ['id' => 'session_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(HrFieldTask::class, ['id' => 'task_id']);
    }
}
