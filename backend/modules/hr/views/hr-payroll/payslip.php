<?php
/**
 * كشف راتب فردي (قابل للطباعة) — Individual Payslip View
 *
 * @var $payslip \backend\modules\hr\models\HrPayslip
 * @var $lines array of \backend\modules\hr\models\HrPayslipLine
 * @var $user \common\models\User
 * @var $run \backend\modules\hr\models\HrPayrollRun
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'كشف راتب — ' . Html::encode($user->name ?: $user->username);

$arabicMonths = [
    1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',
    4  => 'أبريل',  5  => 'مايو',   6  => 'يونيو',
    7  => 'يوليو',  8  => 'أغسطس',  9  => 'سبتمبر',
    10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
];

$monthName = $arabicMonths[(int) $run->period_month] ?? $run->period_month;
$periodLabel = $monthName . ' ' . $run->period_year;

// Split lines into earnings and deductions
$earnings = [];
$deductions = [];
foreach ($lines as $line) {
    if (($line->component_type ?? '') === 'earning') {
        $earnings[] = $line;
    } else {
        $deductions[] = $line;
    }
}

$totalEarnings = (float) ($payslip->total_earnings ?? 0);
$totalDeductions = (float) ($payslip->total_deductions ?? 0);
$netSalary = (float) ($payslip->net_salary ?? 0);
?>

<style>
.hr-page { padding: 20px; }

/* Print-specific */
@media print {
    .hr-page-header .hr-actions,
    .no-print { display: none !important; }
    .hr-page { padding: 0; }
    .payslip-card { box-shadow: none !important; border: 1px solid #ddd; }
}

.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.hr-page-header .hr-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Payslip card */
.payslip-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); overflow: hidden;
    max-width: 800px; margin: 0 auto;
}

/* Company header */
.payslip-company-header {
    text-align: center; padding: 24px 20px 16px;
    border-bottom: 3px solid var(--clr-primary, #800020);
    background: linear-gradient(135deg, rgba(128,0,32,0.03), rgba(128,0,32,0.08));
}
.payslip-company-header h2 {
    font-size: 20px; font-weight: 800; color: var(--clr-primary, #800020); margin: 0 0 4px;
}
.payslip-company-header .payslip-title {
    font-size: 16px; font-weight: 700; color: var(--clr-text, #212529); margin: 8px 0 0;
}
.payslip-company-header .payslip-period {
    font-size: 13px; color: var(--clr-text-muted, #6c757d); margin-top: 4px;
}

/* Employee info */
.payslip-emp-info {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 12px; padding: 18px 24px;
    background: #f8f9fa; border-bottom: 1px solid var(--clr-border, #e0e0e0);
}
.payslip-emp-info .info-item {
    display: flex; gap: 8px; align-items: baseline;
}
.payslip-emp-info .info-label {
    font-size: 12px; font-weight: 600; color: var(--clr-text-muted, #6c757d);
    white-space: nowrap;
}
.payslip-emp-info .info-value {
    font-size: 13px; font-weight: 700; color: var(--clr-text, #212529);
}

/* Tables */
.payslip-body { padding: 20px 24px; }
.payslip-section-title {
    font-size: 14px; font-weight: 700; color: var(--clr-text, #212529);
    margin: 16px 0 8px; padding-bottom: 6px;
    border-bottom: 2px solid var(--clr-border, #e0e0e0);
}
.payslip-section-title i { margin-left: 6px; }

.payslip-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.payslip-table th {
    font-size: 12px; font-weight: 700; color: var(--clr-text-muted, #6c757d);
    padding: 8px 12px; border-bottom: 2px solid #e0e0e0;
    text-align: right; background: #fafafa;
}
.payslip-table td {
    padding: 8px 12px; font-size: 13px; border-bottom: 1px solid #f0f0f0;
}
.payslip-table .amount-col {
    text-align: left; direction: ltr; font-weight: 700;
}
.payslip-table .amount-earning { color: #28a745; }
.payslip-table .amount-deduction { color: #dc3545; }

/* Summary footer */
.payslip-summary {
    display: grid; grid-template-columns: 1fr 1fr 1fr;
    gap: 0; border-top: 3px solid var(--clr-primary, #800020);
}
.payslip-summary-item {
    text-align: center; padding: 18px 16px;
    border-left: 1px solid var(--clr-border, #e0e0e0);
}
.payslip-summary-item:last-child { border-left: none; }
.payslip-summary-item .sum-label {
    font-size: 12px; font-weight: 600; color: var(--clr-text-muted, #6c757d);
}
.payslip-summary-item .sum-value {
    font-size: 20px; font-weight: 800; direction: ltr; margin-top: 4px;
}
.payslip-summary-item .sum-value.green { color: #28a745; }
.payslip-summary-item .sum-value.red { color: #dc3545; }
.payslip-summary-item .sum-value.primary { color: var(--clr-primary, #800020); }

@media (max-width: 576px) {
    .payslip-emp-info { grid-template-columns: 1fr; }
    .payslip-summary { grid-template-columns: 1fr; }
    .payslip-summary-item { border-left: none; border-bottom: 1px solid #e0e0e0; }
}
</style>

<div class="hr-page">
    <!-- Header (non-printable) -->
    <div class="hr-page-header">
        <h1><i class="fa fa-file-text"></i> <?= Html::encode($this->title) ?></h1>
        <div class="hr-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة للمسيرة', ['view', 'id' => $run->id], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::button('<i class="fa fa-print"></i> طباعة', [
                'class' => 'btn btn-primary btn-sm',
                'onclick' => 'window.print()',
            ]) ?>
        </div>
    </div>

    <!-- Payslip Card -->
    <div class="payslip-card">
        <!-- Company Header -->
        <div class="payslip-company-header">
            <h2><?= Html::encode(Yii::$app->name ?? 'الشركة') ?></h2>
            <div class="payslip-title">كشف الراتب الشهري</div>
            <div class="payslip-period">
                <i class="fa fa-calendar"></i> <?= Html::encode($periodLabel) ?>
                &nbsp;|&nbsp;
                <i class="fa fa-barcode"></i> <?= Html::encode($run->run_code) ?>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="payslip-emp-info">
            <div class="info-item">
                <span class="info-label">اسم الموظف:</span>
                <span class="info-value"><?= Html::encode($user->name ?: $user->username) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">رقم الموظف:</span>
                <span class="info-value">#<?= Html::encode($user->id) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">القسم:</span>
                <span class="info-value"><?= Html::encode($user->department ?? '—') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">الفترة:</span>
                <span class="info-value"><?= Html::encode($periodLabel) ?></span>
            </div>
        </div>

        <!-- Payslip Body -->
        <div class="payslip-body">
            <!-- Earnings -->
            <div class="payslip-section-title">
                <i class="fa fa-plus-circle" style="color:#28a745"></i> البدلات والاستحقاقات
            </div>
            <table class="payslip-table">
                <thead>
                    <tr>
                        <th style="width:60%">البند</th>
                        <th style="width:40%;text-align:left">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($earnings)): ?>
                        <tr><td colspan="2" style="text-align:center;color:var(--clr-text-muted);padding:16px">لا توجد بدلات</td></tr>
                    <?php else: ?>
                        <?php foreach ($earnings as $line): ?>
                        <tr>
                            <td><?= Html::encode($line->description ?: 'بند #' . $line->component_id) ?></td>
                            <td class="amount-col amount-earning"><?= number_format((float) ($line->amount ?? 0), 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Deductions -->
            <div class="payslip-section-title">
                <i class="fa fa-minus-circle" style="color:#dc3545"></i> الاستقطاعات
            </div>
            <table class="payslip-table">
                <thead>
                    <tr>
                        <th style="width:60%">البند</th>
                        <th style="width:40%;text-align:left">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deductions)): ?>
                        <tr><td colspan="2" style="text-align:center;color:var(--clr-text-muted);padding:16px">لا توجد استقطاعات</td></tr>
                    <?php else: ?>
                        <?php foreach ($deductions as $line): ?>
                        <tr>
                            <td><?= Html::encode($line->description ?: 'بند #' . $line->component_id) ?></td>
                            <td class="amount-col amount-deduction"><?= number_format((float) ($line->amount ?? 0), 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary Footer -->
        <div class="payslip-summary">
            <div class="payslip-summary-item">
                <div class="sum-label">إجمالي البدلات</div>
                <div class="sum-value green"><?= number_format($totalEarnings, 2) ?></div>
            </div>
            <div class="payslip-summary-item">
                <div class="sum-label">إجمالي الاستقطاعات</div>
                <div class="sum-value red"><?= number_format($totalDeductions, 2) ?></div>
            </div>
            <div class="payslip-summary-item">
                <div class="sum-label">صافي الراتب</div>
                <div class="sum-value primary"><?= number_format($netSalary, 2) ?></div>
            </div>
        </div>
    </div>
</div>
