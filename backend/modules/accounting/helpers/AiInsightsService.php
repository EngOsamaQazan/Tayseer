<?php

namespace backend\modules\accounting\helpers;

use Yii;
use backend\modules\accounting\models\JournalEntry;
use backend\modules\accounting\models\JournalEntryLine;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\Receivable;
use backend\modules\accounting\models\Payable;
use backend\modules\accounting\models\Budget;
use backend\modules\accounting\models\BudgetLine;
use backend\modules\accounting\models\FiscalYear;

/**
 * AiInsightsService — Hybrid intelligent analysis layer.
 * Phase 1: Rule-based local intelligence (no external API dependency).
 * Phase 2: Optional external API connector for advanced NLP/prediction.
 */
class AiInsightsService
{
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_DANGER = 'danger';
    const SEVERITY_SUCCESS = 'success';

    /**
     * Generate all insights for the accounting dashboard.
     */
    public static function generateInsights()
    {
        $insights = [];

        $insights = array_merge($insights, self::analyzeOverdueReceivables());
        $insights = array_merge($insights, self::analyzeOverduePayables());
        $insights = array_merge($insights, self::analyzeUnpostedEntries());
        $insights = array_merge($insights, self::analyzeCashFlowTrend());
        $insights = array_merge($insights, self::analyzeBudgetVariance());
        $insights = array_merge($insights, self::analyzeExpenseSpikes());
        $insights = array_merge($insights, self::analyzeRevenueGrowth());
        $insights = array_merge($insights, self::analyzeTrialBalanceHealth());

        usort($insights, function ($a, $b) {
            $order = [self::SEVERITY_DANGER => 0, self::SEVERITY_WARNING => 1, self::SEVERITY_INFO => 2, self::SEVERITY_SUCCESS => 3];
            return ($order[$a['severity']] ?? 4) - ($order[$b['severity']] ?? 4);
        });

        return $insights;
    }

    /**
     * Check for overdue receivables and concentration risk.
     */
    private static function analyzeOverdueReceivables()
    {
        $insights = [];

        $overdueCount = Receivable::find()->where(['status' => 'overdue'])->count();
        $overdueAmount = (float)(Receivable::find()->where(['status' => 'overdue'])->sum('balance') ?: 0);
        $totalBalance = (float)(Receivable::find()->where(['!=', 'status', 'paid'])->sum('balance') ?: 0);

        if ($overdueCount > 0) {
            $pct = $totalBalance > 0 ? round($overdueAmount / $totalBalance * 100) : 0;
            $severity = $pct > 50 ? self::SEVERITY_DANGER : ($pct > 25 ? self::SEVERITY_WARNING : self::SEVERITY_INFO);

            $insights[] = [
                'title' => 'ذمم مدينة متأخرة',
                'message' => "يوجد {$overdueCount} ذمة مدينة متأخرة بقيمة " . number_format($overdueAmount, 2) . " ({$pct}% من إجمالي الذمم)",
                'severity' => $severity,
                'icon' => 'fa-exclamation-triangle',
                'action' => '/accounting/accounts-receivable/aging-report',
                'action_label' => 'عرض تقرير الأعمار',
                'category' => 'الذمم المدينة',
            ];
        }

        // Concentration risk: single customer > 40% of total
        if ($totalBalance > 0) {
            $topCustomer = Receivable::find()
                ->select(['customer_id', 'total' => 'SUM(balance)'])
                ->where(['!=', 'status', 'paid'])
                ->andWhere(['IS NOT', 'customer_id', null])
                ->groupBy('customer_id')
                ->orderBy(['total' => SORT_DESC])
                ->asArray()
                ->one();

            if ($topCustomer && $topCustomer['total'] / $totalBalance > 0.40) {
                $pct = round($topCustomer['total'] / $totalBalance * 100);
                $insights[] = [
                    'title' => 'تركز مخاطر الذمم',
                    'message' => "عميل واحد يمثل {$pct}% من إجمالي الذمم المدينة. يُنصح بتنويع مصادر الإيراد.",
                    'severity' => self::SEVERITY_WARNING,
                    'icon' => 'fa-user-circle',
                    'category' => 'المخاطر',
                ];
            }
        }

        if ($overdueCount == 0 && $totalBalance > 0) {
            $insights[] = [
                'title' => 'تحصيل ممتاز',
                'message' => 'لا توجد ذمم مدينة متأخرة حالياً.',
                'severity' => self::SEVERITY_SUCCESS,
                'icon' => 'fa-check-circle',
                'category' => 'الذمم المدينة',
            ];
        }

        return $insights;
    }

    /**
     * Check for overdue payables.
     */
    private static function analyzeOverduePayables()
    {
        $insights = [];

        $overdueCount = Payable::find()->where(['status' => 'overdue'])->count();
        $overdueAmount = (float)(Payable::find()->where(['status' => 'overdue'])->sum('balance') ?: 0);

        if ($overdueCount > 0) {
            $insights[] = [
                'title' => 'مستحقات متأخرة',
                'message' => "يوجد {$overdueCount} التزام متأخر بقيمة " . number_format($overdueAmount, 2) . ". تأخر السداد يؤثر على سمعة الشركة.",
                'severity' => self::SEVERITY_WARNING,
                'icon' => 'fa-clock-o',
                'action' => '/accounting/accounts-payable/aging-report',
                'action_label' => 'عرض المستحقات',
                'category' => 'الذمم الدائنة',
            ];
        }

        return $insights;
    }

    /**
     * Check for unposted journal entries.
     */
    private static function analyzeUnpostedEntries()
    {
        $insights = [];

        $draftCount = JournalEntry::find()->where(['status' => 'draft'])->count();
        if ($draftCount > 0) {
            $severity = $draftCount > 10 ? self::SEVERITY_WARNING : self::SEVERITY_INFO;
            $insights[] = [
                'title' => 'قيود غير مرحّلة',
                'message' => "يوجد {$draftCount} قيد يومي في حالة مسودة لم يتم ترحيله بعد.",
                'severity' => $severity,
                'icon' => 'fa-pencil-square-o',
                'action' => '/accounting/journal-entry/index',
                'action_label' => 'مراجعة القيود',
                'category' => 'العمليات',
            ];
        }

        return $insights;
    }

    /**
     * Analyze cash flow trend (comparing current month vs previous month).
     */
    private static function analyzeCashFlowTrend()
    {
        $insights = [];

        $currentMonth = date('Y-m');
        $prevMonth = date('Y-m', strtotime('-1 month'));

        $currentIncome = self::getMonthlyTotal($currentMonth, 'revenue');
        $prevIncome = self::getMonthlyTotal($prevMonth, 'revenue');
        $currentExpenses = self::getMonthlyTotal($currentMonth, 'expenses');
        $prevExpenses = self::getMonthlyTotal($prevMonth, 'expenses');

        if ($prevIncome > 0) {
            $growthRate = round(($currentIncome - $prevIncome) / $prevIncome * 100);
            if ($growthRate < -20) {
                $insights[] = [
                    'title' => 'انخفاض ملحوظ بالإيرادات',
                    'message' => "الإيرادات هذا الشهر أقل بـ " . abs($growthRate) . "% مقارنة بالشهر السابق.",
                    'severity' => self::SEVERITY_DANGER,
                    'icon' => 'fa-arrow-down',
                    'action' => '/accounting/financial-statements/income-statement',
                    'action_label' => 'قائمة الدخل',
                    'category' => 'الإيرادات',
                ];
            } elseif ($growthRate > 20) {
                $insights[] = [
                    'title' => 'نمو ممتاز بالإيرادات',
                    'message' => "الإيرادات هذا الشهر أعلى بـ {$growthRate}% مقارنة بالشهر السابق.",
                    'severity' => self::SEVERITY_SUCCESS,
                    'icon' => 'fa-arrow-up',
                    'category' => 'الإيرادات',
                ];
            }
        }

        $netCurrent = $currentIncome - $currentExpenses;
        if ($currentIncome > 0 || $currentExpenses > 0) {
            if ($netCurrent < 0) {
                $insights[] = [
                    'title' => 'تدفق نقدي سلبي',
                    'message' => 'المصروفات تتجاوز الإيرادات هذا الشهر بمقدار ' . number_format(abs($netCurrent), 2),
                    'severity' => self::SEVERITY_DANGER,
                    'icon' => 'fa-exclamation-circle',
                    'action' => '/accounting/financial-statements/cash-flow',
                    'action_label' => 'التدفقات النقدية',
                    'category' => 'التدفقات',
                ];
            }
        }

        return $insights;
    }

    /**
     * Analyze budget variance alerts.
     */
    private static function analyzeBudgetVariance()
    {
        $insights = [];

        $activeBudget = Budget::find()
            ->where(['status' => Budget::STATUS_APPROVED])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$activeBudget) return $insights;

        $lines = BudgetLine::find()
            ->with(['account'])
            ->where(['budget_id' => $activeBudget->id])
            ->all();

        $overBudgetItems = [];
        foreach ($lines as $line) {
            if ($line->annual_total <= 0) continue;

            $actual = JournalEntryLine::find()
                ->joinWith('journalEntry')
                ->where([
                    '{{%journal_entry_lines}}.account_id' => $line->account_id,
                    '{{%journal_entries}}.status' => 'posted',
                    '{{%journal_entries}}.fiscal_year_id' => $activeBudget->fiscal_year_id,
                ])
                ->sum('{{%journal_entry_lines}}.debit') ?: 0;

            $usage = round($actual / $line->annual_total * 100);
            if ($usage > 90) {
                $overBudgetItems[] = [
                    'account' => $line->account ? $line->account->name_ar : '',
                    'usage' => $usage,
                    'budgeted' => $line->annual_total,
                    'actual' => $actual,
                ];
            }
        }

        if (!empty($overBudgetItems)) {
            $count = count($overBudgetItems);
            $names = implode('، ', array_slice(array_column($overBudgetItems, 'account'), 0, 3));
            $insights[] = [
                'title' => 'تجاوز الموازنة',
                'message' => "{$count} بند تجاوز 90% من الموازنة المخصصة: {$names}",
                'severity' => self::SEVERITY_DANGER,
                'icon' => 'fa-pie-chart',
                'action' => '/accounting/budget/variance?id=' . $activeBudget->id,
                'action_label' => 'تقرير الانحراف',
                'category' => 'الموازنة',
            ];
        }

        return $insights;
    }

    /**
     * Detect unusual expense spikes.
     */
    private static function analyzeExpenseSpikes()
    {
        $insights = [];

        $currentMonth = date('Y-m');
        $currentExpenses = self::getMonthlyTotal($currentMonth, 'expenses');

        // Average of last 3 months
        $avg = 0;
        for ($i = 1; $i <= 3; $i++) {
            $m = date('Y-m', strtotime("-{$i} month"));
            $avg += self::getMonthlyTotal($m, 'expenses');
        }
        $avg = $avg / 3;

        if ($avg > 0 && $currentExpenses > $avg * 1.5) {
            $spike = round(($currentExpenses - $avg) / $avg * 100);
            $insights[] = [
                'title' => 'ارتفاع غير عادي بالمصروفات',
                'message' => "مصروفات هذا الشهر أعلى بـ {$spike}% من متوسط الأشهر الثلاثة الماضية. تحقق من المصروفات الاستثنائية.",
                'severity' => self::SEVERITY_WARNING,
                'icon' => 'fa-bolt',
                'action' => '/accounting/financial-statements/income-statement',
                'action_label' => 'مراجعة المصروفات',
                'category' => 'المصروفات',
            ];
        }

        return $insights;
    }

    /**
     * Analyze revenue growth trajectory.
     */
    private static function analyzeRevenueGrowth()
    {
        $insights = [];

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-{$i} month"));
            $months[$m] = self::getMonthlyTotal($m, 'revenue');
        }

        $values = array_values($months);
        $nonZero = array_filter($values);
        if (count($nonZero) >= 3) {
            $trend = 0;
            for ($i = 1; $i < count($values); $i++) {
                if ($values[$i - 1] > 0) {
                    $trend += ($values[$i] - $values[$i - 1]) / $values[$i - 1];
                }
            }
            $avgTrend = round($trend / (count($values) - 1) * 100);

            if ($avgTrend > 10) {
                $insights[] = [
                    'title' => 'اتجاه إيرادات تصاعدي',
                    'message' => "الإيرادات في اتجاه تصاعدي بمتوسط نمو {$avgTrend}% شهرياً خلال آخر 6 أشهر.",
                    'severity' => self::SEVERITY_SUCCESS,
                    'icon' => 'fa-line-chart',
                    'category' => 'الإيرادات',
                ];
            } elseif ($avgTrend < -10) {
                $insights[] = [
                    'title' => 'اتجاه إيرادات تنازلي',
                    'message' => "الإيرادات في اتجاه تنازلي بمتوسط " . abs($avgTrend) . "% شهرياً. يُنصح بمراجعة استراتيجية الإيراد.",
                    'severity' => self::SEVERITY_WARNING,
                    'icon' => 'fa-line-chart',
                    'category' => 'الإيرادات',
                ];
            }
        }

        return $insights;
    }

    /**
     * Check trial balance health.
     */
    private static function analyzeTrialBalanceHealth()
    {
        $insights = [];

        $totalDebit = (float)(JournalEntryLine::find()
            ->joinWith('journalEntry')
            ->where(['{{%journal_entries}}.status' => 'posted'])
            ->sum('{{%journal_entry_lines}}.debit') ?: 0);

        $totalCredit = (float)(JournalEntryLine::find()
            ->joinWith('journalEntry')
            ->where(['{{%journal_entries}}.status' => 'posted'])
            ->sum('{{%journal_entry_lines}}.credit') ?: 0);

        if ($totalDebit > 0 || $totalCredit > 0) {
            $diff = abs($totalDebit - $totalCredit);
            if ($diff > 0.01) {
                $insights[] = [
                    'title' => 'ميزان المراجعة غير متوازن',
                    'message' => 'يوجد فرق بقيمة ' . number_format($diff, 2) . ' في ميزان المراجعة. يجب مراجعة القيود.',
                    'severity' => self::SEVERITY_DANGER,
                    'icon' => 'fa-balance-scale',
                    'action' => '/accounting/financial-statements/trial-balance',
                    'action_label' => 'ميزان المراجعة',
                    'category' => 'الدقة المحاسبية',
                ];
            } else {
                $insights[] = [
                    'title' => 'ميزان المراجعة سليم',
                    'message' => 'ميزان المراجعة متوازن تماماً. إجمالي الحركات: ' . number_format($totalDebit, 2),
                    'severity' => self::SEVERITY_SUCCESS,
                    'icon' => 'fa-check',
                    'category' => 'الدقة المحاسبية',
                ];
            }
        }

        return $insights;
    }

    /**
     * Get total for a specific month by account type.
     */
    private static function getMonthlyTotal($yearMonth, $accountType)
    {
        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = JournalEntryLine::find()
            ->joinWith(['journalEntry', 'account'])
            ->where([
                '{{%journal_entries}}.status' => 'posted',
                '{{%accounts}}.type' => $accountType,
            ])
            ->andWhere(['>=', '{{%journal_entries}}.entry_date', $startDate])
            ->andWhere(['<=', '{{%journal_entries}}.entry_date', $endDate]);

        if ($accountType === Account::TYPE_REVENUE) {
            return (float)($query->sum('{{%journal_entry_lines}}.credit') ?: 0)
                 - (float)($query->sum('{{%journal_entry_lines}}.debit') ?: 0);
        } else {
            return (float)($query->sum('{{%journal_entry_lines}}.debit') ?: 0)
                 - (float)($query->sum('{{%journal_entry_lines}}.credit') ?: 0);
        }
    }
}
