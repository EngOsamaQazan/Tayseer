<?php

namespace backend\modules\followUp\models;

use Yii;
use DateTime;
use backend\modules\customers\models\ContractsCustomers;
/**
 * This is the model class for table "{{%follow_up}}".
 *
 * @property int $id
 * @property int $contract_id
 * @property string $date_time
 * @property string $connection_response
 * @property string|null $notes
 * @property string|null $feeling
 * @property int $created_by
 * @property int|null $connection_goal
 * @property int|null $number_row
 * @property string|null $reminder
 * @property string|null $promise_to_pay_at
 * @property Contracts $contract
 * @property User $createdBy
 */
class FollowUp extends \yii\db\ActiveRecord
{
    public $number_row;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%follow_up}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
            return [
                [['contract_id', 'created_by', 'feeling', 'connection_goal','reminder'], 'required'],
                [['contract_id', 'created_by', 'connection_goal','number_row'], 'integer'],
                [['date_time', 'reminder', 'promise_to_pay_at',], 'safe'],
                [['notes', 'feeling'], 'string'],
                [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => \common\models\User::class, 'targetAttribute' => ['created_by' => 'id']],
                [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\contracts\models\Contracts::class, 'targetAttribute' => ['contract_id' => 'id']],
            ];

    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'contract_id' => Yii::t('app', 'Contract ID'),
            'date_time' => Yii::t('app', 'Date Time'),
            'notes' => Yii::t('app', 'Notes'),
            'feeling' => Yii::t('app', 'Feeling'),
            'created_by' => Yii::t('app', 'Created By'),
            'connection_goal' => Yii::t('app', 'Connection Goal'),
            'reminder' => Yii::t('app', 'Reminder'),
            'promise_to_pay_at' => Yii::t('app', 'Promise To Pay At'),
        ];
    }

    /**
     * Gets query for [[Contract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContract()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::class, ['id' => 'contract_id']);
    }

    public function getNext()
    {
        $next = \backend\modules\followUpReport\models\FollowUpReport::find()->select('*')->orderBy('date_time asc')->limit(1)->one();
        return $next;
    }

    public function getNextContractID($contract_id)
    {

        return \backend\modules\contracts\models\Contracts::find()
            ->select(['id'])
            ->where(['>', 'id', $contract_id])
            ->andWhere(['<>', 'status', 'finished'])
            ->orderBy(['id' => SORT_ASC])
            ->createCommand()
            ->queryScalar();
    }
    public function getNextContractIDForManager($contract_id)
    {

        return \backend\modules\contracts\models\Contracts::find()
            ->select(['id'])
            ->where(['>', 'id', $contract_id])
            ->orderBy(['id' => SORT_ASC])
            ->createCommand()
            ->queryScalar();
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


}
