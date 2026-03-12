<?php

namespace backend\modules\leaveRequest\models;
use common\models\Model;
use common\models\User;
use Yii;

/**
 * This is the model class for table "{{%leave_request}}".
 *
 * @property int $id
 * @property string|null $reason
 * @property string $start_at
 * @property string $end_at
 * @property int|null $attachment
 * @property int $leave_policy
 * @property int|null $action_by
 * @property int $created_by
 * @property int $created_at
 * @property int $proved_at
 * @property int|null $updated_at
 * @property string $status
 * @property User $approvedBy
 * @property User $createdBy
 */
class LeaveRequest extends Model
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%leave_request}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['start_at', 'end_at', 'leave_policy'], 'required'],
            [['start_at', 'end_at'], 'safe'],
            [['status'], 'string'],
            [['attachment', 'leave_policy', 'action_by', 'created_by', 'created_at', 'updated_at', 'proved_at'], 'integer'],
            [['reason'], 'string', 'max' => 255],
            ['start_at', 'compare', 'compareAttribute' => 'end_at', 'operator' => '<=', 'enableClientValidation' => false],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['action_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['action_by' => 'id']],
            [['leave_policy'], 'exist', 'skipOnError' => true, 'targetClass' => LeavePolicy::class, 'targetAttribute' => ['leave_policy' => 'id']],
            ['start_at', 'compare', 'compareAttribute' => 'end_at', 'operator' => '<=', 'enableClientValidation' => false],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'reason' => Yii::t('app', 'Reason'),
            'start_at' => Yii::t('app', 'Start At'),
            'end_at' => Yii::t('app', 'End At'),
            'attachment' => Yii::t('app', 'Attachment'),
            'leave_policy' => Yii::t('app', 'Leave Policy'),
            'action_by' => Yii::t('app', 'Action By'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'status' => Yii::t('app', 'Status'),
            'proved_at' => Yii::t('app', 'proved At'),
        ];
    }

    /**
     * Gets query for [[ApprovedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionBy()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'action_by']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public function getLeavePolicy()
    {
        return $this->hasOne(\backend\modules\leavePolicy\models\LeavePolicy::class, ['id' => 'leave_policy']);
    }

}
