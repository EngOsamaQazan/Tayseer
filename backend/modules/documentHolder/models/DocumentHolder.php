<?php

namespace backend\modules\documentHolder\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "os_document_holder".
 *
 * @property int $id
 * @property int $created_by
 * @property int $updated_by
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $approved_by_manager
 * @property int|null $approved_by_employee
 * @property string|null $approved_at
 * @property string|null $reason
 * @property int|null $ready
 * @property int $contract_id
 * @property int $manager_approved
 * @property string $status
 * @property string $type
 */
class DocumentHolder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_document_holder';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            [
                'class' => TimestampBehavior::className(),
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
            [['contract_id', 'status', 'type'], 'required'],
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'approved_by_manager', 'approved_by_employee', 'contract_id', 'manager_approved'], 'integer'],
            [['approved_at'], 'safe'],
            [['reason'], 'string'],
            [['status', 'type'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'approved_by_manager' => Yii::t('app', 'Approved By Manager'),
            'approved_by_employee' => Yii::t('app', 'Approved By Employee'),
            'approved_at' => Yii::t('app', 'Approved At'),
            'reason' => Yii::t('app', 'Reason'),
            'contract_id' => Yii::t('app', 'Contract ID'),
            'status' => Yii::t('app', 'Status'),
            'type' => Yii::t('app', 'Type'),
            'manager_approved' => Yii::t('app', 'manager approved'),
        ];
    }

    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'updated_by']);
    }

    public function getApprovedByManager()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'approved_by_manager']);
    }
}
