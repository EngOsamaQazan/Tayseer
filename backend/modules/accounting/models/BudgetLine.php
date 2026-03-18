<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $budget_id
 * @property int $account_id
 * @property int|null $cost_center_id
 * @property float $period_1 ... period_12
 * @property float $annual_total
 * @property string|null $notes
 *
 * @property Budget $budget
 * @property Account $account
 * @property CostCenter $costCenter
 */
class BudgetLine extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%budget_lines}}';
    }

    public function rules()
    {
        $rules = [
            [['budget_id', 'account_id'], 'required'],
            [['budget_id', 'account_id', 'cost_center_id'], 'integer'],
            [['notes'], 'string', 'max' => 500],
            [['annual_total'], 'number', 'min' => 0],
            [['budget_id'], 'exist', 'skipOnError' => true, 'targetClass' => Budget::class, 'targetAttribute' => ['budget_id' => 'id']],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => Account::class, 'targetAttribute' => ['account_id' => 'id']],
        ];

        for ($i = 1; $i <= 12; $i++) {
            $rules[] = [["period_{$i}"], 'number', 'min' => 0];
            $rules[] = [["period_{$i}"], 'default', 'value' => 0];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        $labels = [
            'id' => 'م',
            'budget_id' => 'الموازنة',
            'account_id' => 'الحساب',
            'cost_center_id' => 'مركز التكلفة',
            'annual_total' => 'الإجمالي السنوي',
            'notes' => 'ملاحظات',
        ];
        $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        for ($i = 1; $i <= 12; $i++) {
            $labels["period_{$i}"] = $months[$i - 1];
        }
        return $labels;
    }

    public function getBudget()
    {
        return $this->hasOne(Budget::class, ['id' => 'budget_id']);
    }

    public function getAccount()
    {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function getCostCenter()
    {
        return $this->hasOne(CostCenter::class, ['id' => 'cost_center_id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->annual_total = 0;
        for ($i = 1; $i <= 12; $i++) {
            $this->annual_total += (float)$this->{"period_{$i}"};
        }
        return true;
    }

    /**
     * Distribute an annual amount evenly across 12 periods.
     */
    public function distributeEvenly($totalAmount)
    {
        $monthly = round($totalAmount / 12, 2);
        $remainder = round($totalAmount - ($monthly * 12), 2);
        for ($i = 1; $i <= 12; $i++) {
            $this->{"period_{$i}"} = $monthly;
        }
        $this->period_12 += $remainder;
        $this->annual_total = $totalAmount;
    }
}
