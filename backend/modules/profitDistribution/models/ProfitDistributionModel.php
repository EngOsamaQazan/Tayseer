<?php

namespace backend\modules\profitDistribution\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\User;
use backend\modules\companies\models\Companies;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $distribution_type
 * @property string $period_from
 * @property string $period_to
 * @property float|null $total_revenue
 * @property float|null $direct_expenses
 * @property float|null $shared_expenses
 * @property float|null $net_profit
 * @property float|null $investor_share_pct
 * @property float|null $investor_amount
 * @property float|null $parent_amount
 * @property float|null $distribution_amount
 * @property string|null $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $approved_by
 * @property int|null $approved_at
 *
 * @property Companies $company
 * @property ProfitDistributionLine[] $lines
 * @property User $createdByUser
 */
class ProfitDistributionModel extends ActiveRecord
{
    const TYPE_SHAREHOLDERS = 'مساهمين';
    const TYPE_PORTFOLIO = 'محفظة';

    const STATUS_DRAFT = 'مسودة';
    const STATUS_APPROVED = 'معتمد';
    const STATUS_DISTRIBUTED = 'موزّع';

    public static function tableName()
    {
        return '{{%profit_distributions}}';
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
            [['distribution_type', 'period_from', 'period_to'], 'required'],
            [['company_id', 'created_by', 'created_at', 'approved_by', 'approved_at'], 'integer'],
            [['total_revenue', 'direct_expenses', 'shared_expenses', 'net_profit',
              'investor_share_pct', 'investor_amount', 'parent_amount', 'distribution_amount'], 'number'],
            [['distribution_type'], 'in', 'range' => [self::TYPE_SHAREHOLDERS, self::TYPE_PORTFOLIO]],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_APPROVED, self::STATUS_DISTRIBUTED]],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['notes'], 'string'],
            [['period_from', 'period_to'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'company_id' => 'المحفظة / الشركة',
            'distribution_type' => 'نوع التوزيع',
            'period_from' => 'الفترة من',
            'period_to' => 'الفترة إلى',
            'total_revenue' => 'إجمالي الإيرادات',
            'direct_expenses' => 'المصاريف المباشرة',
            'shared_expenses' => 'حصة المصاريف المشتركة',
            'net_profit' => 'صافي الربح',
            'investor_share_pct' => 'نسبة المُستثمر %',
            'investor_amount' => 'حصة المُستثمر',
            'parent_amount' => 'حصة الشركة الأم',
            'distribution_amount' => 'المبلغ الموزّع',
            'status' => 'الحالة',
            'notes' => 'ملاحظات',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'approved_by' => 'اعتمد بواسطة',
            'approved_at' => 'تاريخ الاعتماد',
        ];
    }

    public function getCompany()
    {
        return $this->hasOne(Companies::class, ['id' => 'company_id']);
    }

    public function getLines()
    {
        return $this->hasMany(ProfitDistributionLine::class, ['distribution_id' => 'id']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getApprovedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'approved_by']);
    }

    /**
     * @return array{total_revenue: float, direct_expenses: float, shared_expenses: float,
     *               net_profit: float, investor_pct: float, parent_pct: float,
     *               investor_amount: float, parent_amount: float}
     */
    public function calculatePortfolioProfit($companyId, $periodFrom, $periodTo)
    {
        $revenue = (float) (new \yii\db\Query())
            ->from('os_financial_transaction')
            ->where([
                'company_id' => $companyId,
                'type' => 1,
                'is_deleted' => 0,
            ])
            ->andWhere(['between', 'date', $periodFrom, $periodTo])
            ->sum('amount') ?: 0;

        $directExpenses = (float) (new \yii\db\Query())
            ->from('os_financial_transaction')
            ->where([
                'company_id' => $companyId,
                'type' => 2,
                'is_deleted' => 0,
            ])
            ->andWhere(['between', 'date', $periodFrom, $periodTo])
            ->sum('amount') ?: 0;

        $sharedExp = (float) (new \yii\db\Query())
            ->select(['SUM(sel.allocated_amount)'])
            ->from('{{%shared_expense_lines}} sel')
            ->innerJoin('{{%shared_expense_allocations}} sea', 'sea.id = sel.allocation_id')
            ->where(['sel.company_id' => $companyId])
            ->andWhere(['or',
                ['between', 'sea.period_from', $periodFrom, $periodTo],
                ['between', 'sea.period_to', $periodFrom, $periodTo],
                ['and', ['<=', 'sea.period_from', $periodFrom], ['>=', 'sea.period_to', $periodTo]],
            ])
            ->scalar() ?: 0;

        $netProfit = $revenue - $directExpenses - $sharedExp;

        $company = Companies::findOne($companyId);
        $investorPct = $company ? (float) $company->profit_share_ratio : 0;
        $parentPct = $company ? (float) $company->parent_share_ratio : 0;

        if ($investorPct + $parentPct == 0) {
            $investorPct = 50;
            $parentPct = 50;
        }

        $investorAmount = round($netProfit * ($investorPct / 100), 2);
        $parentAmount = round($netProfit * ($parentPct / 100), 2);

        return [
            'total_revenue' => $revenue,
            'direct_expenses' => $directExpenses,
            'shared_expenses' => $sharedExp,
            'net_profit' => $netProfit,
            'investor_pct' => $investorPct,
            'parent_pct' => $parentPct,
            'investor_amount' => $investorAmount,
            'parent_amount' => $parentAmount,
        ];
    }
}
