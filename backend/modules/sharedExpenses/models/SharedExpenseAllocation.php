<?php

namespace backend\modules\sharedExpenses\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\User;
use backend\modules\companies\models\Companies;

/**
 * @property int $id
 * @property string $name
 * @property float $total_amount
 * @property string $allocation_method
 * @property string $allocation_date
 * @property string|null $period_from
 * @property string|null $period_to
 * @property string|null $notes
 * @property string $status
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $approved_by
 * @property int|null $approved_at
 *
 * @property SharedExpenseLine[] $lines
 * @property User $createdByUser
 */
class SharedExpenseAllocation extends ActiveRecord
{
    const METHOD_CONTRACTS = 'عدد_العقود';
    const METHOD_DEBT = 'صافي_الدين';
    const METHOD_MANUAL = 'يدوي';
    const METHOD_EQUAL = 'بالتساوي';

    const STATUS_DRAFT = 'مسودة';
    const STATUS_APPROVED = 'معتمد';

    public static function tableName()
    {
        return '{{%shared_expense_allocations}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
                'value' => time(),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name', 'total_amount', 'allocation_method', 'allocation_date'], 'required'],
            [['total_amount'], 'number', 'min' => 0.01],
            [['allocation_method'], 'in', 'range' => array_keys(self::getAllocationMethods())],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_APPROVED]],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['created_by', 'created_at', 'approved_by', 'approved_at'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['notes'], 'string'],
            [['allocation_date', 'period_from', 'period_to'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'name' => 'اسم التوزيع',
            'total_amount' => 'المبلغ الإجمالي',
            'allocation_method' => 'طريقة التوزيع',
            'allocation_date' => 'تاريخ التوزيع',
            'period_from' => 'الفترة من',
            'period_to' => 'الفترة إلى',
            'notes' => 'ملاحظات',
            'status' => 'الحالة',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'approved_by' => 'اعتمد بواسطة',
            'approved_at' => 'تاريخ الاعتماد',
        ];
    }

    public function getLines()
    {
        return $this->hasMany(SharedExpenseLine::class, ['allocation_id' => 'id']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getApprovedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'approved_by']);
    }

    public static function getAllocationMethods()
    {
        return [
            self::METHOD_CONTRACTS => 'عدد العقود',
            self::METHOD_DEBT => 'صافي الدين',
            self::METHOD_MANUAL => 'يدوي',
            self::METHOD_EQUAL => 'بالتساوي',
        ];
    }

    /**
     * @return array ['lines' => [...], 'total_metric' => float]
     */
    public function calculateAllocation()
    {
        $companies = Companies::find()->all();
        if (empty($companies)) {
            return ['lines' => [], 'total_metric' => 0];
        }

        $totalAmount = (float) $this->total_amount;
        $lines = [];

        switch ($this->allocation_method) {
            case self::METHOD_CONTRACTS:
                $lines = $this->calculateByContracts($companies, $totalAmount);
                break;
            case self::METHOD_DEBT:
                $lines = $this->calculateByDebt($companies, $totalAmount);
                break;
            case self::METHOD_EQUAL:
                $lines = $this->calculateEqual($companies, $totalAmount);
                break;
            case self::METHOD_MANUAL:
                $lines = $this->calculateManual($companies);
                break;
        }

        return $lines;
    }

    protected function calculateByContracts($companies, $totalAmount)
    {
        $contractCounts = (new \yii\db\Query())
            ->select(['company_id', 'cnt' => 'COUNT(*)'])
            ->from('os_contracts')
            ->where(['is_deleted' => 0])
            ->groupBy('company_id')
            ->indexBy('company_id')
            ->column();

        $totalContracts = array_sum($contractCounts);
        $lines = [];

        foreach ($companies as $company) {
            $count = isset($contractCounts[$company->id]) ? (int) $contractCounts[$company->id] : 0;
            $pct = $totalContracts > 0 ? round(($count / $totalContracts) * 100, 2) : 0;
            $amount = $totalContracts > 0 ? round(($count / $totalContracts) * $totalAmount, 2) : 0;

            $lines[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'metric_value' => $count,
                'percentage' => $pct,
                'allocated_amount' => $amount,
            ];
        }

        return $lines;
    }

    protected function calculateByDebt($companies, $totalAmount)
    {
        $debtSums = (new \yii\db\Query())
            ->select(['company_id', 'total' => 'SUM(total_value)'])
            ->from('os_contracts')
            ->where(['is_deleted' => 0])
            ->groupBy('company_id')
            ->indexBy('company_id')
            ->column();

        $totalDebt = array_sum($debtSums);
        $lines = [];

        foreach ($companies as $company) {
            $debt = isset($debtSums[$company->id]) ? (float) $debtSums[$company->id] : 0;
            $pct = $totalDebt > 0 ? round(($debt / $totalDebt) * 100, 2) : 0;
            $amount = $totalDebt > 0 ? round(($debt / $totalDebt) * $totalAmount, 2) : 0;

            $lines[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'metric_value' => $debt,
                'percentage' => $pct,
                'allocated_amount' => $amount,
            ];
        }

        return $lines;
    }

    protected function calculateEqual($companies, $totalAmount)
    {
        $count = count($companies);
        $pct = $count > 0 ? round(100 / $count, 2) : 0;
        $amount = $count > 0 ? round($totalAmount / $count, 2) : 0;
        $lines = [];

        foreach ($companies as $company) {
            $lines[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'metric_value' => 1,
                'percentage' => $pct,
                'allocated_amount' => $amount,
            ];
        }

        return $lines;
    }

    protected function calculateManual($companies)
    {
        $lines = [];
        foreach ($companies as $company) {
            $lines[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'metric_value' => 0,
                'percentage' => 0,
                'allocated_amount' => 0,
            ];
        }
        return $lines;
    }
}
