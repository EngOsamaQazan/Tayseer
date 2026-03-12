<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%user_leave_policy}}".
 *
 * @property int $user_id
 * @property int $leave_policy_id
 *
 * @property LeavePolicy $leavePolicy
 * @property User $user
 */
class UserLeavePolicy extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_leave_policy}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'leave_policy_id'], 'required'],
            [['user_id', 'leave_policy_id'], 'integer'],
            [['leave_policy_id'], 'exist', 'skipOnError' => true, 'targetClass' => LeavePolicy::className(), 'targetAttribute' => ['leave_policy_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('app', 'User ID'),
            'leave_policy_id' => Yii::t('app', 'Leave Policy ID'),
        ];
    }

    /**
     * Gets query for [[LeavePolicy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeavePolicy()
    {
        return $this->hasOne(LeavePolicy::className(), ['id' => 'leave_policy_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
