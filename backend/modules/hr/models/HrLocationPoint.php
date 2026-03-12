<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%hr_location_point}}".
 * نقاط الموقع الجغرافي
 *
 * @property int $id
 * @property int $session_id
 * @property string|null $lat
 * @property string|null $lng
 * @property float|null $accuracy
 * @property float|null $speed
 * @property string|null $captured_at
 * @property int|null $created_at
 */
class HrLocationPoint extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_location_point}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['session_id'], 'required'],
            [['session_id', 'created_at'], 'integer'],
            [['lat', 'lng'], 'string', 'max' => 30],
            [['accuracy', 'speed'], 'number'],
            [['captured_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'session_id' => Yii::t('app', 'الجلسة'),
            'lat' => Yii::t('app', 'خط العرض'),
            'lng' => Yii::t('app', 'خط الطول'),
            'accuracy' => Yii::t('app', 'الدقة'),
            'speed' => Yii::t('app', 'السرعة'),
            'captured_at' => Yii::t('app', 'وقت الالتقاط'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSession()
    {
        return $this->hasOne(HrFieldSession::class, ['id' => 'session_id']);
    }
}
