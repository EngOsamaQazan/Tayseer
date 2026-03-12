<?php

namespace backend\modules\capitalTransactions\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use backend\modules\companies\models\Companies;

/**
 * @property int $id
 * @property int $company_id
 * @property string $transaction_type
 * @property string $amount
 * @property string $transaction_date
 * @property string|null $balance_after
 * @property string|null $payment_method
 * @property string|null $reference_number
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $created_at
 *
 * @property Companies $company
 */
class CapitalTransactions extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%capital_transactions}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => time(),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['company_id', 'transaction_type', 'amount', 'transaction_date'], 'required'],
            [['company_id', 'created_by', 'created_at'], 'integer'],
            [['amount', 'balance_after'], 'number'],
            [['transaction_date'], 'safe'],
            [['notes'], 'string'],
            [['transaction_type'], 'in', 'range' => ['إيداع', 'سحب', 'إعادة_رأس_مال']],
            [['payment_method', 'reference_number'], 'string', 'max' => 100],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Companies::class, 'targetAttribute' => ['company_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'company_id' => 'المحفظة',
            'transaction_type' => 'نوع العملية',
            'amount' => 'المبلغ',
            'transaction_date' => 'التاريخ',
            'balance_after' => 'الرصيد بعد العملية',
            'payment_method' => 'طريقة الدفع',
            'reference_number' => 'رقم المرجع',
            'notes' => 'ملاحظات',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
        ];
    }

    public function getCompany()
    {
        return $this->hasOne(Companies::class, ['id' => 'company_id']);
    }

    public static function getTransactionTypes()
    {
        return [
            'إيداع' => 'إيداع',
            'سحب' => 'سحب',
            'إعادة_رأس_مال' => 'إعادة رأس مال',
        ];
    }
}
