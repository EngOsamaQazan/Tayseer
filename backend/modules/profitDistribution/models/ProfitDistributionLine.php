<?php

namespace backend\modules\profitDistribution\models;

use Yii;
use yii\db\ActiveRecord;
use backend\modules\shareholders\models\Shareholders;

/**
 * @property int $id
 * @property int $distribution_id
 * @property int $shareholder_id
 * @property int|null $share_count_snapshot
 * @property int|null $total_shares_snapshot
 * @property float|null $percentage
 * @property float|null $amount
 * @property string|null $payment_status
 * @property string|null $payment_date
 * @property string|null $payment_method
 * @property string|null $payment_reference
 *
 * @property ProfitDistributionModel $distribution
 * @property Shareholders $shareholder
 */
class ProfitDistributionLine extends ActiveRecord
{
    const PAYMENT_PENDING = 'معلّق';
    const PAYMENT_PAID = 'مدفوع';

    public static function tableName()
    {
        return '{{%profit_distribution_lines}}';
    }

    public function rules()
    {
        return [
            [['distribution_id', 'shareholder_id'], 'required'],
            [['distribution_id', 'shareholder_id', 'share_count_snapshot', 'total_shares_snapshot'], 'integer'],
            [['percentage', 'amount'], 'number'],
            [['payment_status'], 'in', 'range' => [self::PAYMENT_PENDING, self::PAYMENT_PAID]],
            [['payment_status'], 'default', 'value' => self::PAYMENT_PENDING],
            [['payment_method', 'payment_reference'], 'string', 'max' => 100],
            [['payment_date'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'distribution_id' => 'التوزيع',
            'shareholder_id' => 'المساهم',
            'share_count_snapshot' => 'عدد الأسهم',
            'total_shares_snapshot' => 'إجمالي الأسهم',
            'percentage' => 'النسبة %',
            'amount' => 'المبلغ المستحق',
            'payment_status' => 'حالة الدفع',
            'payment_date' => 'تاريخ الدفع',
            'payment_method' => 'طريقة الدفع',
            'payment_reference' => 'مرجع الدفع',
        ];
    }

    public function getDistribution()
    {
        return $this->hasOne(ProfitDistributionModel::class, ['id' => 'distribution_id']);
    }

    public function getShareholder()
    {
        return $this->hasOne(Shareholders::class, ['id' => 'shareholder_id']);
    }
}
