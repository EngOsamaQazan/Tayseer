<?php

namespace backend\modules\sharedExpenses\models;

use Yii;
use yii\db\ActiveRecord;
use backend\modules\companies\models\Companies;

/**
 * @property int $id
 * @property int $allocation_id
 * @property int $company_id
 * @property float|null $metric_value
 * @property float|null $percentage
 * @property float|null $allocated_amount
 *
 * @property SharedExpenseAllocation $allocation
 * @property Companies $company
 */
class SharedExpenseLine extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%shared_expense_lines}}';
    }

    public function rules()
    {
        return [
            [['allocation_id', 'company_id'], 'required'],
            [['allocation_id', 'company_id'], 'integer'],
            [['metric_value', 'allocated_amount'], 'number'],
            [['percentage'], 'number', 'max' => 100, 'min' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'allocation_id' => 'التوزيع',
            'company_id' => 'المحفظة',
            'metric_value' => 'القيمة المرجعية',
            'percentage' => 'النسبة %',
            'allocated_amount' => 'المبلغ الموزّع',
        ];
    }

    public function getAllocation()
    {
        return $this->hasOne(SharedExpenseAllocation::class, ['id' => 'allocation_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(Companies::class, ['id' => 'company_id']);
    }
}
