<?php

use yii\helpers\Html;

/**
 * @var array $financials
 * @var array|null $settlementFinancials
 */

$total = $financials['total'] ?? 0;
$paid = $financials['paid'] ?? 0;
$remaining = $financials['remaining'] ?? 0;
$overdue = $financials['overdue'] ?? 0;
$shouldPaid = $financials['should_paid'] ?? 0;
$overdueInstallments = $financials['overdue_installments'] ?? 0;
$remainingInstallments = $financials['remaining_installments'] ?? 0;
$complianceRate = $financials['compliance_rate'] ?? 0;
$monthlyInstallment = $financials['monthly_installment'] ?? 0;
$paidRatio = $total > 0 ? round(($paid / $total) * 100) : 0;
$overdueRatio = $total > 0 ? round(($overdue / $total) * 100) : 0;
$shouldPaidRatio = $total > 0 ? round(($shouldPaid / $total) * 100) : 0;

$hasSettlement = !empty($settlementFinancials);

$hasJudiciary = !empty($financials['has_judiciary']);
$lawyerCosts = $financials['lawyer_costs'] ?? 0;
$caseCosts = $financials['case_costs'] ?? 0;
$contractValue = $financials['contract_value'] ?? 0;
$allExpenses = $total - $contractValue - $lawyerCosts;
$totalAdjustments = $financials['total_adjustments'] ?? 0;
$commitmentDiscount = $financials['commitment_discount'] ?? 0;
?>

<style>
.ocp-fin-divider{display:flex;align-items:center;gap:10px;margin:18px 0 14px;color:#800020;font-size:12px;font-weight:700}
.ocp-fin-divider::before,.ocp-fin-divider::after{content:'';flex:1;height:2px;background:linear-gradient(90deg,#800020 0%,transparent 100%)}
.ocp-fin-divider::after{background:linear-gradient(90deg,transparent 0%,#800020 100%)}
.ocp-fin-divider i{font-size:14px}
.ocp-stl-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px}
.ocp-stl-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;text-align:center}
.ocp-stl-item__value{font-size:16px;font-weight:700;color:#1e293b}
.ocp-stl-item__label{font-size:11px;color:#64748b;margin-top:2px}
.ocp-stl-item--primary{border-color:#800020;background:linear-gradient(135deg,#fdf2f4,#fff)}
.ocp-stl-item--primary .ocp-stl-item__value{color:#800020}
.ocp-stl-item--success .ocp-stl-item__value{color:#059669}
.ocp-stl-item--info .ocp-stl-item__value{color:#0284c7}
.ocp-stl-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
.ocp-debt-breakdown{margin-top:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;font-size:13px}
.ocp-debt-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;color:#334155}
.ocp-debt-row+.ocp-debt-row{border-top:1px dashed #e2e8f0}
.ocp-debt-row--total{border-top:2px solid #800020 !important;margin-top:4px;padding-top:8px;font-weight:800;color:#800020}
.ocp-debt-row i{margin-left:6px;width:16px;text-align:center}
</style>

<!-- ═══ القسم الأول: الحسابات الأصلية ═══ -->
<div class="ocp-section">
    <div class="ocp-section-title">
        <i class="fa fa-money"></i>
        الحسابات الأصلية
    </div>

    <div class="ocp-financial">
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--total"><i class="fa fa-file-text-o"></i></div>
            <div class="ocp-fin-card__value"><?= number_format($total) ?></div>
            <div class="ocp-fin-card__label">إجمالي العقد</div>
            <div class="ocp-fin-card__bar"><div class="ocp-fin-card__bar-fill" style="width:100%;background:var(--ocp-primary)"></div></div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon" style="background:#EDE7F6;color:#5E35B1"><i class="fa fa-credit-card"></i></div>
            <div class="ocp-fin-card__value" style="color:#5E35B1"><?= number_format($monthlyInstallment) ?></div>
            <div class="ocp-fin-card__label">القسط الشهري</div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--paid"><i class="fa fa-check-circle"></i></div>
            <div class="ocp-fin-card__value ocp-text-success"><?= number_format($paid) ?></div>
            <div class="ocp-fin-card__label">المدفوع</div>
            <div class="ocp-fin-card__bar"><div class="ocp-fin-card__bar-fill" style="width:<?= $paidRatio ?>%;background:var(--ocp-success)"></div></div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon" style="background:#FFF8E1;color:#F57F17"><i class="fa fa-clock-o"></i></div>
            <div class="ocp-fin-card__value" style="color:#F57F17"><?= number_format($shouldPaid) ?></div>
            <div class="ocp-fin-card__label">المستحق حتى الآن</div>
            <div class="ocp-fin-card__bar"><div class="ocp-fin-card__bar-fill" style="width:<?= $shouldPaidRatio ?>%;background:#F57F17"></div></div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--remain"><i class="fa fa-hourglass-half"></i></div>
            <div class="ocp-fin-card__value"><?= number_format($remaining) ?></div>
            <div class="ocp-fin-card__label">المتبقي</div>
            <div class="ocp-fin-card__bar"><div class="ocp-fin-card__bar-fill" style="width:<?= (100 - $paidRatio) ?>%;background:var(--ocp-info)"></div></div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--overdue"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="ocp-fin-card__value ocp-text-danger"><?= number_format($overdue) ?></div>
            <div class="ocp-fin-card__label">المتأخر</div>
            <div class="ocp-fin-card__bar"><div class="ocp-fin-card__bar-fill" style="width:<?= $overdueRatio ?>%;background:var(--ocp-danger)"></div></div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--count"><i class="fa fa-calendar-times-o"></i></div>
            <div class="ocp-fin-card__value ocp-text-danger"><?= $overdueInstallments ?></div>
            <div class="ocp-fin-card__label">أقساط متأخرة</div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--count"><i class="fa fa-calendar"></i></div>
            <div class="ocp-fin-card__value"><?= $remainingInstallments ?></div>
            <div class="ocp-fin-card__label">أقساط متبقية</div>
        </div>

        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon" style="background:<?= $complianceRate >= 70 ? 'var(--ocp-success-bg)' : ($complianceRate >= 40 ? 'var(--ocp-warning-bg)' : 'var(--ocp-danger-bg)') ?>;color:<?= $complianceRate >= 70 ? 'var(--ocp-success)' : ($complianceRate >= 40 ? 'var(--ocp-warning)' : 'var(--ocp-danger)') ?>">
                <i class="fa fa-pie-chart"></i>
            </div>
            <div class="ocp-fin-card__value" style="color:<?= $complianceRate >= 70 ? 'var(--ocp-success)' : ($complianceRate >= 40 ? 'var(--ocp-warning)' : 'var(--ocp-danger)') ?>"><?= $complianceRate ?>%</div>
            <div class="ocp-fin-card__label">نسبة الالتزام</div>
            <div class="ocp-fin-card__bar"><div class="ocp-fin-card__bar-fill" style="width:<?= $complianceRate ?>%;background:<?= $complianceRate >= 70 ? 'var(--ocp-success)' : ($complianceRate >= 40 ? 'var(--ocp-warning)' : 'var(--ocp-danger)') ?>"></div></div>
        </div>
    </div>

    <?php $showBreakdown = ($hasJudiciary && ($lawyerCosts > 0 || $caseCosts > 0 || $allExpenses > 0)) || $totalAdjustments > 0 || $commitmentDiscount > 0; ?>
    <?php if ($showBreakdown): ?>
    <div class="ocp-debt-breakdown">
        <div class="ocp-debt-row">
            <span><i class="fa fa-file-text-o" style="color:#075985"></i> أصل العقد</span>
            <span style="font-weight:700"><?= number_format($contractValue) ?></span>
        </div>
        <?php if ($lawyerCosts > 0): ?>
        <div class="ocp-debt-row">
            <span><i class="fa fa-gavel" style="color:#92400e"></i> أتعاب المحاماة</span>
            <span style="font-weight:700;color:#92400e"><?= number_format($lawyerCosts) ?></span>
        </div>
        <?php endif ?>
        <?php if ($caseCosts > 0): ?>
        <div class="ocp-debt-row">
            <span><i class="fa fa-balance-scale" style="color:#7c3aed"></i> رسوم القضية</span>
            <span style="font-weight:700;color:#7c3aed"><?= number_format($caseCosts) ?></span>
        </div>
        <?php endif ?>
        <?php if ($allExpenses - $caseCosts > 0): ?>
        <div class="ocp-debt-row">
            <span><i class="fa fa-money" style="color:#64748b"></i> مصاريف أخرى</span>
            <span style="font-weight:700"><?= number_format($allExpenses - $caseCosts) ?></span>
        </div>
        <?php endif ?>
        <div class="ocp-debt-row ocp-debt-row--total">
            <span><i class="fa fa-calculator"></i> الإجمالي</span>
            <span><?= number_format($total) ?></span>
        </div>
        <?php if ($commitmentDiscount > 0): ?>
        <div class="ocp-debt-row" style="color:#6d28d9">
            <span><i class="fa fa-tag" style="color:#6d28d9"></i> خصم الالتزام</span>
            <span style="font-weight:700"><?= number_format($commitmentDiscount) ?></span>
        </div>
        <?php endif ?>
        <?php if ($totalAdjustments > 0): ?>
        <div class="ocp-debt-row" style="color:#dc2626">
            <span><i class="fa fa-minus-circle" style="color:#dc2626"></i> الخصومات</span>
            <span style="font-weight:700">-<?= number_format($totalAdjustments) ?></span>
        </div>
        <div class="ocp-debt-row" style="font-weight:700;color:#059669;border-top:1px solid #e2e8f0;padding-top:6px">
            <span><i class="fa fa-check-circle" style="color:#059669"></i> الصافي بعد الخصم</span>
            <span><?= number_format($total - $totalAdjustments) ?></span>
        </div>
        <?php endif ?>
    </div>
    <?php endif ?>
</div>

<?php if ($hasSettlement): ?>
<!-- ═══ فاصل بصري ═══ -->
<div class="ocp-fin-divider">
    <i class="fa fa-balance-scale"></i>
    بعد التسوية
</div>

<!-- ═══ القسم الثاني: حسابات ما بعد التسوية ═══ -->
<div class="ocp-section" style="margin-top:0">
    <?php
    $stl = $settlementFinancials;
    $stlPaidRatio = $stl['total_debt'] > 0 ? round(($stl['paid_after'] / $stl['total_debt']) * 100) : 0;
    ?>
    <div class="ocp-stl-grid">
        <div class="ocp-stl-item ocp-stl-item--primary">
            <div class="ocp-stl-item__value"><?= number_format($stl['total_debt']) ?></div>
            <div class="ocp-stl-item__label">إجمالي دين التسوية</div>
        </div>
        <div class="ocp-stl-item ocp-stl-item--success">
            <div class="ocp-stl-item__value"><?= number_format($stl['paid_after']) ?></div>
            <div class="ocp-stl-item__label">المدفوع بعد التسوية</div>
        </div>
        <div class="ocp-stl-item">
            <div class="ocp-stl-item__value"><?= number_format($stl['remaining']) ?></div>
            <div class="ocp-stl-item__label">المتبقي</div>
        </div>
        <?php if ($stl['first_payment'] > 0): ?>
        <div class="ocp-stl-item">
            <div class="ocp-stl-item__value"><?= number_format($stl['first_payment']) ?></div>
            <div class="ocp-stl-item__label">الدفعة الأولى</div>
        </div>
        <?php endif ?>
        <div class="ocp-stl-item">
            <div class="ocp-stl-item__value"><?= number_format($stl['installment']) ?></div>
            <div class="ocp-stl-item__label">القسط <span class="ocp-stl-badge" style="background:#f0f4ff;color:#4338ca"><?= $stl['type_label'] ?></span></div>
        </div>
        <div class="ocp-stl-item">
            <div class="ocp-stl-item__value"><?= $stl['remaining_installments'] ?></div>
            <div class="ocp-stl-item__label">أقساط متبقية</div>
        </div>
        <div class="ocp-stl-item ocp-stl-item--info">
            <div class="ocp-stl-item__value" style="font-size:13px"><?= $stl['first_date'] ?: '—' ?></div>
            <div class="ocp-stl-item__label">تاريخ الدفعة الأولى</div>
        </div>
        <div class="ocp-stl-item ocp-stl-item--info">
            <div class="ocp-stl-item__value" style="font-size:13px"><?= $stl['next_date'] ?: '—' ?></div>
            <div class="ocp-stl-item__label">تاريخ القسط الجديد</div>
        </div>
    </div>
    <?php if ($stl['total_debt'] > 0): ?>
    <div style="margin-top:10px;background:#f0f0f0;border-radius:6px;height:8px;overflow:hidden">
        <div style="width:<?= $stlPaidRatio ?>%;height:100%;background:linear-gradient(90deg,#059669,#10b981);border-radius:6px;transition:width .3s"></div>
    </div>
    <div style="font-size:11px;color:#64748b;text-align:center;margin-top:4px">تقدم السداد: <?= $stlPaidRatio ?>%</div>
    <?php endif ?>
</div>
<?php endif ?>
