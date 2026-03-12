<?php
/**
 * كشف حساب الموظف السنوي — Employee Annual Statement
 *
 * @var $user \common\models\User
 * @var $year int
 * @var $payslips array
 * @var $lines array
 * @var $yearlyTotals array
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'كشف حساب — ' . Html::encode($user->name ?: $user->username) . ' — ' . $year;

$arabicMonths = [
    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
    5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
];
?>

<style>
.stmt-page { padding: 20px; }

@media print {
    .stmt-actions, .stmt-year-selector, .no-print { display: none !important; }
    .stmt-page { padding: 0; }
    .stmt-card { box-shadow: none !important; border: 1px solid #ddd; }
}

.stmt-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
}
.stmt-header h1 { font-size: 22px; font-weight: 700; color: #800020; margin: 0; }
.stmt-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.stmt-year-selector {
    display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
}
.stmt-year-selector select {
    padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 14px; font-weight: 600;
}

/* Employee info bar */
.stmt-emp-bar {
    background: linear-gradient(135deg, rgba(128,0,32,0.04), rgba(128,0,32,0.08));
    border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 24px;
    margin-bottom: 20px; display: flex; justify-content: space-between;
    flex-wrap: wrap; gap: 16px;
}
.stmt-emp-bar .emp-info strong { color: #1e293b; font-size: 18px; }
.stmt-emp-bar .emp-info span { color: #64748b; font-size: 13px; display: block; margin-top: 4px; }

/* Yearly Summary Cards */
.stmt-summary {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 14px; margin-bottom: 24px;
}
.stmt-sum-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 16px; text-align: center;
}
.stmt-sum-card .sum-label { font-size: 11px; font-weight: 600; color: #64748b; }
.stmt-sum-card .sum-value { font-size: 22px; font-weight: 800; margin-top: 4px; direction: ltr; }
.stmt-sum-card .sum-value.green { color: #166534; }
.stmt-sum-card .sum-value.red { color: #dc3545; }
.stmt-sum-card .sum-value.primary { color: #800020; }
.stmt-sum-card .sum-value.blue { color: #1d4ed8; }

/* Monthly Table */
.stmt-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    margin-bottom: 20px;
}
.stmt-card-header {
    padding: 14px 20px; border-bottom: 2px solid #e2e8f0;
    background: #f8fafc; font-weight: 700; font-size: 15px; color: #1e293b;
}
.stmt-card-header i { margin-left: 6px; color: #800020; }

.stmt-table { width: 100%; border-collapse: collapse; }
.stmt-table thead th {
    background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;
    padding: 10px 14px; border-bottom: 2px solid #e2e8f0; text-align: right;
}
.stmt-table tbody td {
    padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.stmt-table tbody tr:hover { background: #fefce8; }
.stmt-table .amount { font-weight: 700; direction: ltr; text-align: left; }
.stmt-table .amount-green { color: #28a745; }
.stmt-table .amount-red { color: #dc3545; }
.stmt-table .amount-primary { color: #800020; }
.stmt-table tfoot td {
    background: #fef2f2; font-weight: 700; padding: 12px 14px;
    border-top: 2px solid #e2e8f0;
}

/* Detail expandable */
.stmt-detail-toggle {
    cursor: pointer; color: #1d4ed8; font-size: 12px; font-weight: 600;
}
.stmt-detail-toggle:hover { text-decoration: underline; }
.stmt-detail-row { display: none; }
.stmt-detail-row.active { display: table-row; }
.stmt-detail-cell {
    background: #f8fafc; padding: 12px 20px !important;
}
.stmt-detail-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.stmt-detail-table td { padding: 4px 10px; border-bottom: 1px solid #f1f5f9; }
.stmt-detail-table .earning { color: #166534; }
.stmt-detail-table .deduction { color: #dc3545; }

.stmt-empty {
    text-align: center; padding: 48px 20px; color: #94a3b8;
}
.stmt-empty i { font-size: 40px; display: block; margin-bottom: 12px; opacity: 0.4; }

@media (max-width: 768px) {
    .stmt-summary { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="stmt-page">
    <!-- Header -->
    <div class="stmt-header">
        <h1><i class="fa fa-file-text"></i> <?= Html::encode($this->title) ?></h1>
        <div class="stmt-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة لسجل الموظفين', ['/hr/hr-employee/index'], [
                'class' => 'btn btn-default btn-sm', 'style' => 'border-radius:8px',
            ]) ?>
            <?= Html::button('<i class="fa fa-print"></i> طباعة', [
                'class' => 'btn btn-sm',
                'style' => 'background:#800020;color:#fff;border-radius:8px',
                'onclick' => 'window.print()',
            ]) ?>
        </div>
    </div>

    <!-- Year Selector -->
    <div class="stmt-year-selector no-print">
        <label style="font-weight:600;color:#334155">السنة:</label>
        <select onchange="window.location.href='<?= Url::to(['statement', 'id' => $user->id]) ?>&year='+this.value">
            <?php for ($y = (int)date('Y') + 1; $y >= 2020; $y--): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- Employee Info -->
    <div class="stmt-emp-bar">
        <div class="emp-info">
            <strong><?= Html::encode($user->name ?: $user->username) ?></strong>
            <span>#<?= $user->id ?> | <?= Html::encode($user->email ?: '—') ?></span>
        </div>
        <div class="emp-info" style="text-align:left">
            <strong><?= $year ?></strong>
            <span>كشف حساب سنوي — <?= count($payslips) ?> شهر</span>
        </div>
    </div>

    <!-- Yearly Summary -->
    <div class="stmt-summary">
        <div class="stmt-sum-card">
            <div class="sum-label">إجمالي الراتب الأساسي</div>
            <div class="sum-value blue"><?= number_format($yearlyTotals['total_basic'], 2) ?></div>
        </div>
        <div class="stmt-sum-card">
            <div class="sum-label">إجمالي الاستحقاقات</div>
            <div class="sum-value green"><?= number_format($yearlyTotals['total_earnings'], 2) ?></div>
        </div>
        <div class="stmt-sum-card">
            <div class="sum-label">إجمالي الاستقطاعات</div>
            <div class="sum-value red"><?= number_format($yearlyTotals['total_deductions'], 2) ?></div>
        </div>
        <div class="stmt-sum-card">
            <div class="sum-label">إجمالي الصافي</div>
            <div class="sum-value primary"><?= number_format($yearlyTotals['total_net'], 2) ?></div>
        </div>
    </div>

    <!-- Monthly Breakdown Table -->
    <div class="stmt-card">
        <div class="stmt-card-header">
            <i class="fa fa-table"></i> كشف الحساب الشهري — <?= $year ?>
        </div>

        <?php if (empty($payslips)): ?>
            <div class="stmt-empty">
                <i class="fa fa-file-text"></i>
                <p>لا توجد مسيرات رواتب لهذا الموظف في سنة <?= $year ?>.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="stmt-table">
                    <thead>
                        <tr>
                            <th>الشهر</th>
                            <th style="text-align:left">الراتب الأساسي</th>
                            <th style="text-align:left">الاستحقاقات</th>
                            <th style="text-align:left">الاستقطاعات</th>
                            <th style="text-align:left">الصافي</th>
                            <th>أيام العمل</th>
                            <th>غياب</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payslips as $ps):
                            $psId = $ps['payslip_id'];
                            $month = (int)$ps['period_month'];
                            $monthLabel = $arabicMonths[$month] ?? $month;
                            $psLines = $lines[$psId] ?? [];
                        ?>
                        <tr>
                            <td><strong><?= Html::encode($monthLabel) ?></strong></td>
                            <td class="amount"><?= number_format((float)$ps['basic_salary'], 2) ?></td>
                            <td class="amount amount-green"><?= number_format((float)$ps['total_earnings'], 2) ?></td>
                            <td class="amount amount-red"><?= number_format((float)$ps['total_deductions'], 2) ?></td>
                            <td class="amount amount-primary" style="font-size:14px"><?= number_format((float)$ps['net_salary'], 2) ?></td>
                            <td style="text-align:center"><?= (int)($ps['present_days'] ?? 0) ?>/<?= (int)($ps['working_days'] ?? 0) ?></td>
                            <td style="text-align:center">
                                <?php $absent = (int)($ps['absent_days'] ?? 0); ?>
                                <?php if ($absent > 0): ?>
                                    <span style="color:#dc3545;font-weight:700"><?= $absent ?></span>
                                <?php else: ?>
                                    <span style="color:#94a3b8">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($psLines)): ?>
                                    <span class="stmt-detail-toggle" onclick="toggleDetail(<?= $psId ?>)">
                                        <i class="fa fa-chevron-down"></i> تفاصيل
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($psLines)): ?>
                        <tr class="stmt-detail-row" id="detail-<?= $psId ?>">
                            <td colspan="8" class="stmt-detail-cell">
                                <table class="stmt-detail-table">
                                    <?php foreach ($psLines as $line): ?>
                                    <tr>
                                        <td style="width:60%"><?= Html::encode($line['description']) ?></td>
                                        <td style="width:20%;text-align:center">
                                            <?php if ($line['component_type'] === 'earning'): ?>
                                                <span class="label label-success" style="font-size:10px">استحقاق</span>
                                            <?php else: ?>
                                                <span class="label label-danger" style="font-size:10px">خصم</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="width:20%;text-align:left;font-weight:700;direction:ltr" class="<?= $line['component_type'] === 'earning' ? 'earning' : 'deduction' ?>">
                                            <?= number_format((float)$line['amount'], 2) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>إجمالي <?= $year ?></strong></td>
                            <td class="amount"><?= number_format($yearlyTotals['total_basic'], 2) ?></td>
                            <td class="amount amount-green"><?= number_format($yearlyTotals['total_earnings'], 2) ?></td>
                            <td class="amount amount-red"><?= number_format($yearlyTotals['total_deductions'], 2) ?></td>
                            <td class="amount amount-primary" style="font-size:15px"><?= number_format($yearlyTotals['total_net'], 2) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleDetail(psId) {
    var row = document.getElementById('detail-' + psId);
    if (row) {
        row.classList.toggle('active');
    }
}
</script>
