<?php
/**
 * تفاصيل مسيرة الرواتب — Payroll Run Detail View
 *
 * @var $model \backend\modules\hr\models\HrPayrollRun
 * @var $payslips array of \backend\modules\hr\models\HrPayslip
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'مسيرة الرواتب — ' . Html::encode($model->run_code);

$arabicMonths = [
    1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',
    4  => 'أبريل',  5  => 'مايو',   6  => 'يونيو',
    7  => 'يوليو',  8  => 'أغسطس',  9  => 'سبتمبر',
    10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
];

$statusMap = [
    'draft'      => ['label' => 'مسودة',   'color' => '#6c757d'],
    'preview'    => ['label' => 'محسوبة',  'color' => '#17a2b8'],
    'calculated' => ['label' => 'محسوبة',  'color' => '#17a2b8'],
    'reviewed'   => ['label' => 'مراجعة',  'color' => '#fd7e14'],
    'approved'   => ['label' => 'معتمدة',  'color' => '#28a745'],
    'paid'       => ['label' => 'مدفوعة',  'color' => '#20c997'],
    'cancelled'  => ['label' => 'ملغاة',   'color' => '#dc3545'],
];

$st = $model->status ?? 'draft';
$statusInfo = $statusMap[$st] ?? ['label' => $st, 'color' => '#999'];
$monthName = $arabicMonths[(int) $model->period_month] ?? $model->period_month;

// Calculate totals from payslips
$totalGross = 0;
$totalDeductions = 0;
$totalNet = 0;
foreach ($payslips as $ps) {
    $totalGross += (float) ($ps->total_earnings ?? 0);
    $totalDeductions += (float) ($ps->total_deductions ?? 0);
    $totalNet += (float) ($ps->net_salary ?? 0);
}
?>

<style>
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.hr-page-header .hr-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Run info card */
.hr-run-info {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); padding: 24px;
    margin-bottom: 24px;
}
.hr-run-info-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}
.hr-run-info-item .info-label {
    font-size: 12px; font-weight: 600; color: var(--clr-text-muted, #6c757d);
    margin-bottom: 4px;
}
.hr-run-info-item .info-value {
    font-size: 18px; font-weight: 800; color: var(--clr-text, #212529);
}
.hr-run-info-item .info-value.amount {
    direction: ltr; text-align: right; color: var(--clr-primary, #800020);
}

/* Totals bar */
.hr-totals-bar {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 14px; margin-bottom: 24px;
}
.hr-total-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); padding: 18px; text-align: center;
    border-top: 4px solid var(--total-color, #800020);
}
.hr-total-card .total-label {
    font-size: 12px; font-weight: 600; color: var(--clr-text-muted, #6c757d);
}
.hr-total-card .total-value {
    font-size: 22px; font-weight: 800; direction: ltr;
}

/* Status badge */
.pay-status-badge {
    display: inline-block; padding: 4px 14px; border-radius: 20px;
    font-size: 13px; font-weight: 600; color: #fff;
}

/* Payslips table */
.hr-payslips-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); overflow: hidden;
}
.hr-payslips-card .card-header-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid var(--clr-border, #e0e0e0);
}
.hr-payslips-card .card-header-bar h3 {
    margin: 0; font-size: 15px; font-weight: 700; color: var(--clr-text, #212529);
}

.hr-ps-table { width: 100%; border-collapse: collapse; }
.hr-ps-table th {
    font-size: 12px; font-weight: 700; color: var(--clr-text-muted, #6c757d);
    padding: 10px 14px; border-bottom: 2px solid var(--clr-border, #e0e0e0);
    text-align: right; background: #f8f9fa; white-space: nowrap;
}
.hr-ps-table td {
    padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}
.hr-ps-table tr:hover td { background: var(--clr-primary-50, #fdf0f3); cursor: pointer; }
.hr-ps-table .amount { font-weight: 700; direction: ltr; text-align: left; }
.hr-ps-table .amount-green { color: #28a745; }
.hr-ps-table .amount-red { color: #dc3545; }
.hr-ps-table .amount-primary { color: var(--clr-primary, #800020); }

@media (max-width: 768px) {
    .hr-totals-bar { grid-template-columns: 1fr; }
    .hr-run-info-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="hr-page">
    <!-- Header -->
    <div class="hr-page-header">
        <h1>
            <i class="fa fa-file-text-o"></i> <?= Html::encode($this->title) ?>
            <span class="pay-status-badge" style="background:<?= $statusInfo['color'] ?>;font-size:14px;vertical-align:middle;margin-right:8px">
                <?= $statusInfo['label'] ?>
            </span>
        </h1>
        <div class="hr-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'btn btn-default btn-sm']) ?>

            <?php if (in_array($st, ['draft', 'calculated'])): ?>
                <?= Html::a('<i class="fa fa-sliders"></i> عمولات وتعديلات', ['adjustments', 'id' => $model->id], [
                    'class' => 'btn btn-warning btn-sm',
                    'title' => 'إضافة عمولات أو تعديلات شهرية قبل الحساب',
                ]) ?>
                <?= Html::a('<i class="fa fa-calculator"></i> حساب', ['calculate', 'id' => $model->id], [
                    'class' => 'btn btn-info btn-sm',
                    'data-method' => 'post',
                    'data-confirm' => 'هل تريد حساب/إعادة حساب الرواتب لهذه المسيرة؟',
                ]) ?>
            <?php endif; ?>

            <?php if ($st === 'calculated'): ?>
                <?= Html::a('<i class="fa fa-check-circle"></i> اعتماد', ['approve', 'id' => $model->id], [
                    'class' => 'btn btn-success btn-sm',
                    'data-method' => 'post',
                    'data-confirm' => 'هل أنت متأكد من اعتماد هذه المسيرة؟ لا يمكن التراجع عن هذا الإجراء.',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Run Info Card -->
    <div class="hr-run-info">
        <div class="hr-run-info-grid">
            <div class="hr-run-info-item">
                <div class="info-label"><i class="fa fa-barcode"></i> رمز المسيرة</div>
                <div class="info-value"><?= Html::encode($model->run_code) ?></div>
            </div>
            <div class="hr-run-info-item">
                <div class="info-label"><i class="fa fa-calendar"></i> الفترة</div>
                <div class="info-value"><?= Html::encode($monthName . ' ' . $model->period_year) ?></div>
            </div>
            <div class="hr-run-info-item">
                <div class="info-label"><i class="fa fa-flag"></i> الحالة</div>
                <div class="info-value">
                    <span class="pay-status-badge" style="background:<?= $statusInfo['color'] ?>"><?= $statusInfo['label'] ?></span>
                </div>
            </div>
            <div class="hr-run-info-item">
                <div class="info-label"><i class="fa fa-users"></i> عدد الموظفين</div>
                <div class="info-value"><?= number_format((int) ($model->total_employees ?? count($payslips))) ?></div>
            </div>
            <?php if ($model->approved_at): ?>
            <div class="hr-run-info-item">
                <div class="info-label"><i class="fa fa-check"></i> تاريخ الاعتماد</div>
                <div class="info-value" style="font-size:14px"><?= date('Y-m-d H:i', $model->approved_at) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($model->notes): ?>
            <div class="hr-run-info-item" style="grid-column: span 2">
                <div class="info-label"><i class="fa fa-sticky-note"></i> ملاحظات</div>
                <div class="info-value" style="font-size:14px;font-weight:400"><?= Html::encode($model->notes) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Totals Bar -->
    <div class="hr-totals-bar">
        <div class="hr-total-card" style="--total-color:#28a745">
            <div class="total-label">إجمالي البدلات</div>
            <div class="total-value amount-green"><?= number_format($totalGross, 2) ?></div>
        </div>
        <div class="hr-total-card" style="--total-color:#dc3545">
            <div class="total-label">إجمالي الاستقطاعات</div>
            <div class="total-value amount-red"><?= number_format($totalDeductions, 2) ?></div>
        </div>
        <div class="hr-total-card" style="--total-color:#800020">
            <div class="total-label">صافي الرواتب</div>
            <div class="total-value amount-primary"><?= number_format($totalNet, 2) ?></div>
        </div>
    </div>

    <!-- Payslips Table -->
    <div class="hr-payslips-card">
        <div class="card-header-bar">
            <h3><i class="fa fa-list"></i> كشوف الرواتب</h3>
            <span style="font-size:12px;color:var(--clr-text-muted)"><?= count($payslips) ?> كشف</span>
        </div>

        <div style="overflow-x:auto">
            <table class="hr-ps-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th style="text-align:left">الراتب الأساسي</th>
                        <th style="text-align:left">البدلات</th>
                        <th style="text-align:left">الاستقطاعات</th>
                        <th style="text-align:left">الصافي</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payslips)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:30px;color:var(--clr-text-muted)">
                                <i class="fa fa-info-circle"></i> لا توجد كشوف رواتب بعد. قم بحساب المسيرة أولاً.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $idx = 0; foreach ($payslips as $ps):
                            $idx++;
                            $user = $ps->user;
                            $empName = $user ? ($user->name ?: $user->username) : '—';
                            $gross = (float) ($ps->total_earnings ?? 0);
                            $deductions = (float) ($ps->total_deductions ?? 0);
                            $net = (float) ($ps->net_salary ?? 0);
                            $psStatus = $ps->status ?? 'draft';
                            $psStatusInfo = $statusMap[$psStatus] ?? ['label' => $psStatus, 'color' => '#999'];
                        ?>
                        <tr onclick="window.location.href='<?= Url::to(['payslip', 'id' => $ps->id]) ?>'" title="عرض كشف الراتب">
                            <td style="color:var(--clr-text-muted)"><?= $idx ?></td>
                            <td><strong><?= Html::encode($empName) ?></strong></td>
                            <td class="amount"><?= number_format($gross, 2) ?></td>
                            <td class="amount amount-green"><?= number_format($gross, 2) ?></td>
                            <td class="amount amount-red"><?= number_format($deductions, 2) ?></td>
                            <td class="amount amount-primary" style="font-size:14px"><?= number_format($net, 2) ?></td>
                            <td>
                                <span class="pay-status-badge" style="background:<?= $psStatusInfo['color'] ?>;font-size:11px;padding:2px 10px">
                                    <?= $psStatusInfo['label'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
