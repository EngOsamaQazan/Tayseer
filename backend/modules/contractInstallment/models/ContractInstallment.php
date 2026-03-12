<?php

namespace backend\modules\contractInstallment\models;

use Yii;

/**
 * This is the model class for table "os_installment".
 *
 * @property int $id
 * @property int $contract_id
 * @property string $date
 * @property float $amount
 * @property int|null $created_by
 * @property string $payment_type
 * @property string|null $_by
 * @property string|null $receipt_bank
 * @property string|null $payment_purpose
 * @property Contracts $contract
 * @property int $type
 * @property User $createdBy
 */
class ContractInstallment extends \yii\db\ActiveRecord
{


    const PAYMENT_PURPOSES = [
        'fine' => 'غرامة',
        'monthly_payment' => 'دفعة شهرية',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_income';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_id', 'date', 'amount', 'type','payment_type'], 'required'],
            [['contract_id', 'created_by'], 'integer'],
            [['date'], 'safe'],
            [['receipt_bank'], 'required',
                'when' => function ($model) {
                    return $model->payment_type == 'bank_receipt';
                },
                'whenClient' => "function (attribute, value) { 
                    return $('#contractinstallment-payment_type').val() == 'bank_receipt'; 
                }"
            ],
            [['amount'], 'number'],
            [['payment_type', '_by', 'receipt_bank', 'payment_purpose'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'contract_id' =>  Yii::t('app', 'Contract ID'),
            'date' =>  Yii::t('app', 'Date'),
            'amount' =>  Yii::t('app', 'Amount'),
            'created_by' =>  Yii::t('app', 'Created By'),
            'payment_type' =>  Yii::t('app', 'Payment Type'),
            '_by' =>  Yii::t('app', 'By'),
            'receipt_bank' =>  Yii::t('app', 'Receipt Bank'),
            'payment_purpose' =>  Yii::t('app', 'Payment Purpose'),
            'type' => Yii::t('app', 'type'),
        ];
    }

    public function getContract(){
        return \backend\modules\contracts\models\Contracts::findOne($this->contract_id);
    }

    public function getCreatedBy(){
        return $this->hasOne(\common\models\User::className(),['id','created_by']);
    }
    public function getIncomeCategory()
    {
        return $this->hasOne(\backend\modules\incomeCategory\models\IncomeCategory::className(), ['id' => 'type']);
    }

    public function beforeSave($insert)
    {
        if ($insert){
            $this->created_by = Yii::$app->user->identity->id;
        }

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->contract_id) {
            \backend\modules\contracts\models\Contracts::refreshContractStatus($this->contract_id);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        if ($this->contract_id) {
            \backend\modules\contracts\models\Contracts::refreshContractStatus($this->contract_id);
        }
    }
}
