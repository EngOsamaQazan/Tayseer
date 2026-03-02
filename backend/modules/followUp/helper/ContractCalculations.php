<?php

namespace backend\modules\followUp\helper;

use Yii;
use DateTime;
use common\helper\LoanContract;
use backend\modules\expenses\models\Expenses;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\contracts\models\Contracts;
use backend\modules\contracts\models\ContractAdjustment;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\loanScheduling\models\LoanScheduling;
use yii\helpers\ArrayHelper;

class ContractCalculations
{
    public $contract_id, $judicary_contract, $contract_model, $modelf;

    /**
     * العقد الأصلي بدون أي تعديلات من التسويات
     * يُستخدم في "الحسابات الأصلية" فقط
     */
    public $original_contract;

    /** @var LoanScheduling|null آخر تسوية فعّالة */
    public $latestSettlement;

    public function __construct($contract_id)
    {
        $this->modelf = new LoanContract;
        $this->contract_id = $contract_id;
        $this->judicary_contract = Judiciary::find()->where([
            'contract_id' => $contract_id,
            'is_deleted' => 0
        ])->all();

        // العقد بعد دمج بيانات التسوية (للتوافق مع الكود القديم)
        $this->contract_model = $this->modelf->findContract($contract_id);

        // العقد الأصلي — مباشرة من قاعدة البيانات بدون تعديلات
        $this->original_contract = Contracts::findOne($contract_id);

        // آخر تسوية فعّالة
        $this->latestSettlement = LoanScheduling::find()
            ->where(['contract_id' => $contract_id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    /* ═══════════════════════════════════════════════════
     *  الحسابات الأصلية — لا تنظر للتسويات نهائياً
     *  تستخدم original_contract (البيانات الحقيقية)
     * ═══════════════════════════════════════════════════ */

    /**
     * المبلغ الأصلي للعقد
     */
    public function getContractTotal(): float
    {
        return (float)($this->original_contract->total_value ?? 0);
    }

    /**
     * مجموع كل مصاريف Outcome على العقد (كل التصنيفات)
     */
    public function allExpenses(): float
    {
        return (float)(Expenses::find()
            ->where(['contract_id' => $this->contract_id])
            ->sum('amount') ?? 0);
    }

    /**
     * مجموع أتعاب المحاماة من القضايا
     */
    public function allLawyerCosts(): float
    {
        $total = 0;
        if (!empty($this->judicary_contract)) {
            foreach ($this->judicary_contract as $j) {
                $total += (float)($j->lawyer_cost ?? 0);
            }
        }
        return $total;
    }

    /**
     * مجموع رسوم القضية فقط (category_id=4)
     */
    public function caseCost(): float
    {
        return (float)(Expenses::find()
            ->where(['contract_id' => $this->contract_id, 'category_id' => 4])
            ->sum('amount') ?? 0);
    }

    /**
     * مجموع التسويات / الخصومات المسجّلة على العقد
     */
    public function totalAdjustments(): float
    {
        return (float)(ContractAdjustment::find()
            ->where(['contract_id' => $this->contract_id, 'is_deleted' => 0])
            ->sum('amount') ?? 0);
    }

    /**
     * المبلغ الإجمالي (الخام) = أصلي + كل Outcome + أتعاب محاماة
     * لا يطرح الخصومات — يُستخدم للعرض كـ "إجمالي العقد"
     */
    public function totalDebt(): float
    {
        return $this->getContractTotal() + $this->allExpenses() + $this->allLawyerCosts();
    }

    /**
     * المدفوع — مجموع كل Income على العقد (كل المدفوعات بلا استثناء)
     */
    public function paidAmount($without_loan_condtion = false): float
    {
        $paid = ContractInstallment::find()
            ->andWhere(['contract_id' => $this->contract_id])
            ->sum('amount');
        return (float)($paid ?? 0);
    }

    /**
     * خصم الالتزام المسجل على العقد عند الإنشاء
     * لا يُطرح من المتبقي — للعرض فقط
     */
    public function commitmentDiscount(): float
    {
        return (float)($this->original_contract->commitment_discount ?? 0);
    }

    /**
     * المتبقي = الإجمالي − المدفوع − الخصومات (التسويات اللاحقة فقط)
     */
    public function remainingAmount(): float
    {
        return max(0, round($this->totalDebt() - $this->paidAmount() - $this->totalAdjustments(), 2));
    }

    /**
     * عدد الأشهر من أول قسط (أصلي) حتى اليوم
     */
    public function timeInterval(): int
    {
        $firstDate = $this->original_contract->first_installment_date ?? null;
        if (empty($firstDate)) return 0;
        $d1 = new DateTime($firstDate);
        $d2 = new DateTime(date('Y-m-d'));
        $interval = $d2->diff($d1);
        return $interval->y * 12 + $interval->m;
    }

    /**
     * عدد الأشهر من تاريخ التسوية حتى اليوم
     */
    public function settlementTimeInterval(): int
    {
        if (!$this->latestSettlement) return 0;
        $firstDate = $this->latestSettlement->first_installment_date ?? null;
        if (empty($firstDate)) return 0;
        $d1 = new DateTime($firstDate);
        $d2 = new DateTime(date('Y-m-d'));
        if ($d2 < $d1) return 0;
        $interval = $d2->diff($d1);
        return $interval->y * 12 + $interval->m;
    }

    /**
     * المبلغ المستحق حتى اليوم
     * قضائي بدون تسوية → كامل الدين مستحق فوراً
     * عقد عليه تسوية → حساب حسب أقساط التسوية
     * عقد عادي → حساب حسب أقساط العقد الأصلي
     */
    public function amountShouldBePaid(): float
    {
        $netTotal = $this->totalDebt() - $this->totalAdjustments();

        if ($this->hasJdicary() && !$this->latestSettlement) {
            return $netTotal;
        }

        if ($this->latestSettlement) {
            $months = $this->settlementTimeInterval() + 1;
            $monthly = (float)($this->latestSettlement->monthly_installment ?? 0);
            return min($months * $monthly, $netTotal);
        }

        $months = $this->timeInterval() + 1;
        $monthly = (float)($this->original_contract->monthly_installment_value ?? 0);
        return min($months * $monthly, $netTotal);
    }

    /**
     * المتأخر = المستحق حتى اليوم - المدفوع
     */
    public function deservedAmount(): float
    {
        if ($this->hasJdicary() && !$this->latestSettlement) {
            return max(0, round($this->totalDebt() - $this->totalAdjustments() - $this->paidAmount(), 2));
        }

        $firstDate = $this->latestSettlement
            ? ($this->latestSettlement->first_installment_date ?? null)
            : ($this->original_contract->first_installment_date ?? null);

        if (empty($firstDate) || date('Y-m-d') < $firstDate) {
            return 0;
        }
        return max(0, round($this->amountShouldBePaid() - $this->paidAmount(), 2));
    }

    /**
     * القسط الفعّال (تسوية أو أصلي)
     */
    public function effectiveInstallment(): float
    {
        if ($this->latestSettlement) {
            return (float)($this->latestSettlement->monthly_installment ?? 0);
        }
        return (float)($this->original_contract->monthly_installment_value ?? 0);
    }

    /* ═══════════════════════════════════════════════════
     *  بيانات التسوية
     * ═══════════════════════════════════════════════════ */

    public function hasSettlement(): bool
    {
        return $this->latestSettlement !== null;
    }

    public function settlementTotalDebt(): float
    {
        return (float)($this->latestSettlement->total_debt ?? 0);
    }

    public function settlementFirstPayment(): float
    {
        return (float)($this->latestSettlement->first_payment ?? 0);
    }

    public function settlementInstallment(): float
    {
        return (float)($this->latestSettlement->monthly_installment ?? 0);
    }

    public function settlementInstallmentsCount(): int
    {
        return (int)($this->latestSettlement->installments_count ?? 0);
    }

    public function settlementType(): string
    {
        return $this->latestSettlement->settlement_type ?? 'monthly';
    }

    public function settlementPaidAfter(): float
    {
        if (!$this->latestSettlement || !$this->latestSettlement->first_installment_date) {
            return 0;
        }
        return (float)(ContractInstallment::find()
            ->where(['contract_id' => $this->contract_id])
            ->andWhere(['>=', 'date', $this->latestSettlement->first_installment_date])
            ->sum('amount') ?? 0);
    }

    public function settlementRemaining(): float
    {
        return max(0, $this->settlementTotalDebt() - $this->settlementPaidAfter());
    }

    public function settlementRemainingInstallments(): int
    {
        $inst = $this->settlementInstallment();
        $rem = $this->settlementRemaining();
        return ($inst > 0 && $rem > 0) ? (int)ceil($rem / $inst) : 0;
    }

    public function settlementFirstDate(): ?string
    {
        return $this->latestSettlement->first_installment_date ?? null;
    }

    public function settlementNextDate(): ?string
    {
        return $this->latestSettlement->new_installment_date ?? null;
    }

    /* ═══════════════════════════════════════════════════
     *  مؤشرات حسابية
     * ═══════════════════════════════════════════════════ */

    public function overdueInstallments(): int
    {
        $monthly = $this->effectiveInstallment();
        $overdue = $this->deservedAmount();
        return ($monthly > 0 && $overdue > 0) ? (int)ceil($overdue / $monthly) : 0;
    }

    public function remainingInstallments(): int
    {
        $monthly = $this->effectiveInstallment();
        $remaining = $this->remainingAmount();
        return ($monthly > 0 && $remaining > 0) ? (int)ceil($remaining / $monthly) : 0;
    }

    public function complianceRate(): int
    {
        $shouldPaid = $this->amountShouldBePaid();
        return ($shouldPaid > 0) ? min(100, (int)round(($this->paidAmount() / $shouldPaid) * 100)) : 100;
    }

    /* ═══════════════════════════════════════════════════
     *  حالة العقد
     * ═══════════════════════════════════════════════════ */

    public function hasJdicary(): bool
    {
        return !empty($this->judicary_contract);
    }

    public function isJudiciaryPaid(): bool
    {
        $status = $this->original_contract->status ?? '';
        return $status === 'judiciary' && $this->remainingAmount() <= 0;
    }

    public function isClosed(): bool
    {
        $status = $this->original_contract->status ?? '';
        if (in_array($status, ['finished', 'canceled'])) return true;
        return $this->isJudiciaryPaid();
    }

    /* ═══════════════════════════════════════════════════
     *  اللقطات المالية
     * ═══════════════════════════════════════════════════ */

    public function getFinancialSnapshot(): array
    {
        return [
            'total' => $this->totalDebt(),
            'paid' => $this->paidAmount(),
            'remaining' => $this->remainingAmount(),
            'overdue' => $this->deservedAmount(),
            'should_paid' => $this->amountShouldBePaid(),
            'overdue_installments' => $this->overdueInstallments(),
            'remaining_installments' => $this->remainingInstallments(),
            'compliance_rate' => $this->complianceRate(),
            'has_judiciary' => $this->hasJdicary(),
            'lawyer_costs' => $this->allLawyerCosts(),
            'case_costs' => $this->caseCost(),
            'contract_value' => $this->getContractTotal(),
            'total_adjustments' => $this->totalAdjustments(),
            'commitment_discount' => $this->commitmentDiscount(),
        ];
    }

    public function getSettlementSnapshot(): ?array
    {
        if (!$this->hasSettlement()) return null;

        return [
            'total_debt' => $this->settlementTotalDebt(),
            'first_payment' => $this->settlementFirstPayment(),
            'installment' => $this->settlementInstallment(),
            'installments_count' => $this->settlementInstallmentsCount(),
            'type' => $this->settlementType(),
            'type_label' => $this->settlementType() === 'weekly' ? 'أسبوعي' : 'شهري',
            'paid_after' => $this->settlementPaidAfter(),
            'remaining' => $this->settlementRemaining(),
            'remaining_installments' => $this->settlementRemainingInstallments(),
            'first_date' => $this->settlementFirstDate(),
            'next_date' => $this->settlementNextDate(),
        ];
    }

    /* ═══════════════════════════════════════════════════
     *  تحميل دفعي لصفحات القوائم
     * ═══════════════════════════════════════════════════ */

    public static function batchPreload(array $contractIds): array
    {
        if (empty($contractIds)) return [];
        $db = Yii::$app->db;
        $idList = implode(',', array_map('intval', $contractIds));

        $contractMap = ArrayHelper::index(
            $db->createCommand("SELECT id, total_value, status FROM os_contracts WHERE id IN ($idList)")->queryAll(),
            'id'
        );

        $expMap = ArrayHelper::map(
            $db->createCommand("SELECT contract_id, COALESCE(SUM(amount),0) as total FROM os_expenses WHERE contract_id IN ($idList) AND (is_deleted = 0 OR is_deleted IS NULL) GROUP BY contract_id")->queryAll(),
            'contract_id', 'total'
        );

        $lawyerMap = ArrayHelper::map(
            $db->createCommand("SELECT contract_id, COALESCE(SUM(lawyer_cost),0) as total FROM os_judiciary WHERE contract_id IN ($idList) AND (is_deleted=0 OR is_deleted IS NULL) GROUP BY contract_id")->queryAll(),
            'contract_id', 'total'
        );

        $paidMap = ArrayHelper::map(
            $db->createCommand("SELECT contract_id, COALESCE(SUM(amount),0) as total FROM os_income WHERE contract_id IN ($idList) GROUP BY contract_id")->queryAll(),
            'contract_id', 'total'
        );

        $adjMap = ArrayHelper::map(
            $db->createCommand("SELECT contract_id, COALESCE(SUM(amount),0) as total FROM os_contract_adjustments WHERE contract_id IN ($idList) AND is_deleted=0 GROUP BY contract_id")->queryAll(),
            'contract_id', 'total'
        );

        $result = [];
        foreach ($contractIds as $cid) {
            $c = $contractMap[$cid] ?? null;
            if (!$c) continue;
            $totalDebt = (float)$c['total_value'] + (float)($expMap[$cid] ?? 0) + (float)($lawyerMap[$cid] ?? 0);
            $paidAmt = (float)($paidMap[$cid] ?? 0);
            $adjAmt = (float)($adjMap[$cid] ?? 0);
            $remaining = max(0, round($totalDebt - $paidAmt - $adjAmt, 2));

            $result[$cid] = [
                'totalDebt' => $totalDebt,
                'paid' => $paidAmt,
                'adjustments' => $adjAmt,
                'remaining' => $remaining,
                'isJudiciaryPaid' => ($c['status'] === 'judiciary') && ($remaining <= 0),
            ];
        }
        return $result;
    }

    /* ═══════════════════════════════════════════════════
     *  دوال مساعدة (للتوافق مع الكود القديم)
     * ═══════════════════════════════════════════════════ */

    /** @deprecated use totalDebt() */
    public function totalCosts(): float
    {
        return $this->totalDebt();
    }

    /** @deprecated use totalDebt() */
    public function getContractTotalWithlawyerAndCaseCost(): float
    {
        return $this->totalDebt();
    }

    /** @deprecated use remainingAmount() */
    public function calculationRemainingAmount(): float
    {
        return $this->remainingAmount();
    }

    /** مصاريف مرجعية (category_id=19) */
    public function customerReferance(): float
    {
        return (float)(Expenses::find()
            ->where(['contract_id' => $this->contract_id, 'category_id' => 19])
            ->sum('amount') ?? 0);
    }

    /** أتعاب محاماة (أول قضية) — للتوافق القديم */
    public function lawyerCost()
    {
        if (!empty($this->judicary_contract)) {
            $cost = Judiciary::find()
                ->where(['contract_id' => $this->contract_id])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            return $cost ? (float)$cost->lawyer_cost : 0;
        }
        return 0;
    }

    /** المبلغ التنفيذي */
    public function getExecutedAmount(): float
    {
        $paid = max(0, $this->paidAmount());
        return ($this->original_contract->total_value - $paid) + $this->allLawyerCosts();
    }
}
