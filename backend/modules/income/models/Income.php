<?php

namespace backend\modules\income\models;

use backend\modules\incomeCategory\IncomeCategory;
use backend\modules\paymentType\PaymentType;
use Yii;

/**
 * This is the model class for table "{{%Income}}".
 *
 * @property int $id
 * @property int $contract_id
 * @property string $date
 * @property int $financial_transaction_id
 * @property int $created_by
 * @property int $bank_number
 * @property int $number_row
 * @property int $document_number
 * @property int $income_status
 * @property string $notes
 * @property double $amount
 * @property string $payment_purpose
 * @property string $from_date
 * @property string $to_date
 * @property string $payment_type
 * @property Contracts $contract
 * @property Contracts $type
 */
class Income extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $income_status;
    public $amount_sum = 0;
    public $date_from;
    public $date_to;
    public $from_date;
    public $to_date;

    public $followed_by;
    public $company_id;
    public $number_row;

    public static function tableName()
    {
        return '{{%income}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payment_type'], 'required'],
            [['amount'], 'double'],
            [['contract_id', 'type', 'document_number', 'type', 'document_number', 'bank_number', 'income_status', 'number_row'], 'integer'],
            [['date', 'financial_transaction_id', 'notes', 'from_date', 'to_date'], 'safe'],
            [['notes', '_by', 'payment_type', 'payment_purpose', 'receipt_bank', 'from_date', 'to_date'], 'string'],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\contracts\models\Contracts::className(), 'targetAttribute' => ['contract_id' => 'id']],
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
            'date' => Yii::t('app', 'Date'),
            'total' => Yii::t('app', 'Total'),
            'amount' => Yii::t('app', 'Amount'),
            '_by' => Yii::t('app', 'By'),
            'type' => Yii::t('app', 'type'),
            'document_number' => Yii::t('app', 'Document Number'),
            'notes' => Yii::t('app', 'notes'),
            'income_status' => Yii::t('app', 'Income Status'),
            'company_id' => Yii::t('app', 'Company Id'),
            'followed_by' => Yii::t('app', 'Followed By'),
            'date_from' => Yii::t('app', 'Transaction Date Form'),
            'date_to' => Yii::t('app', 'Transaction Date To'),
            'from_date' => Yii::t('app', 'System Date Form'),
            'to_date' => Yii::t('app', 'System Date To'),
            'number_row' => Yii::t('app', 'Result Row Numbers'),
            'created_by' => Yii::t('app', 'Created By'),
        ];
    }

    /**
     * Gets query for [[Contract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContract()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::className(), ['id' => 'contract_id']);
    }

    /**
     * Gets query for [[Contract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreated()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    public function ammoutsDate()
    {
        return Income::find()->where(['date' => date('Y-m-d')])->sum('amount');
    }

    public function getIncomeCategory()
    {
        return $this->hasOne(\backend\modules\incomeCategory\models\IncomeCategory::className(), ['id' => 'type']);
    }

    public function getPaymentType()
    {
        return $this->hasOne(\backend\modules\paymentType\models\PaymentType::className(), ['id' => 'payment_type']);
    }
    public function getStatus()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::className(), ['id' => 'contract_id']);
    }


}
