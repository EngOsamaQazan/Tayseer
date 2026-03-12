<?php

namespace backend\modules\attendance\models;

use Yii;

/**
 * This is the model class for table "{{%attendance}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int $location_id
 * @property string $check_in_time
 * @property string $check_out_time
 * @property int $manual_checked_in_by
 * @property int $manual_checked_out_by
 * @property string $is_manual_actions
 *
 * @property Location $location
 * @property User $user
 */
class Attendance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%attendance}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'location_id', 'check_in_time', 'check_out_time'], 'required'],
            [['user_id', 'location_id', 'manual_checked_in_by', 'manual_checked_out_by'], 'integer'],
            [['check_in_time', 'check_out_time'], 'safe'],
            [['is_manual_actions'], 'string'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::className(), 'targetAttribute' => ['location_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'location_id' => Yii::t('app', 'Location ID'),
            'check_in_time' => Yii::t('app', 'Check In Time'),
            'check_out_time' => Yii::t('app', 'Check Out Time'),
            'manual_checked_in_by' => Yii::t('app', 'Manual Checked In By'),
            'manual_checked_out_by' => Yii::t('app', 'Manual Checked Out By'),
            'is_manual_actions' => Yii::t('app', 'Is Manual Actions'),
        ];
    }

    /**
     * Gets query for [[Location]].
     *
     * @return \yii\db\ActiveQuery|LocationQuery
     */
    public function getLocation()
    {
        return $this->hasOne(Location::className(), ['id' => 'location_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery|UserQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     * @return AttendanceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AttendanceQuery(get_called_class());
    }
}
