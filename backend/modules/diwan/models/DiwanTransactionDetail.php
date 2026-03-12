<?php

namespace backend\modules\diwan\models;

use Yii;
use yii\db\Expression;

/**
 * تفاصيل معاملات الديوان (العقود في كل معاملة)
 *
 * @property int $id
 * @property int $transaction_id
 * @property string $contract_number
 * @property int|null $contract_id
 * @property int|null $created_at
 */
class DiwanTransactionDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_diwan_transaction_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_id', 'contract_number'], 'required'],
            [['transaction_id', 'contract_id', 'created_at'], 'integer'],
            [['contract_number'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'transaction_id' => 'رقم المعاملة',
            'contract_number' => 'رقم العقد',
            'contract_id' => 'ربط العقد',
            'created_at' => 'تاريخ الإنشاء',
        ];
    }

    /* ═══ العلاقات ═══ */

    public function getTransaction()
    {
        return $this->hasOne(DiwanTransaction::class, ['id' => 'transaction_id']);
    }
}
