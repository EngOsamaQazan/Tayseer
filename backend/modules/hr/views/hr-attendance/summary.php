<?php
/**
 * ملخص الحضور الشهري — Monthly Attendance Summary
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ملخص الحضور الشهري';

$arabicMonths = [
    1  => 'يناير',
    2  => 'فبراير',
    3  => 'مارس',
    4  => 'أبريل',
    5  => 'مايو',
    6  => 'يونيو',
    7  => 'يوليو',
    8  => 'أغسطس',
    9  => 'سبتمبر',
    10 => 'أكتوبر',
    11 => 'نوفمبر',
    12 => 'ديسمبر',
];
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

/* Filter bar */
.hr-filter-bar {
    display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
    margin-bottom: 20px; padding: 16px 20px;
    background: var(--clr-surface, #fff); border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm);
}
.hr-filter-bar .filter-group { display: flex; flex-direction: column; gap: 4px; }
.hr-filter-bar .filter-group label {
    font-size: 12px; font-weight: 600; color: var(--clr-text-muted, #6c757d);
}

/* Summary table */
.hr-summary-table-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); overflow: hidden;
}
.hr-summary-table-card .card-header-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid var(--clr-border, #e0e0e0);
}
.hr-summary-table-card .card-header-bar h3 {
    margin: 0; font-size: 15px; font-weight: 700; color: var(--clr-text, #212529);
}

.hr-att-table { width: 100%; border-collapse: collapse; }
.hr-att-table th {
    font-size: 12px; font-weight: 700; color: var(--clr-text-muted, #6c757d);
    padding: 10px 14px; border-bottom: 2px solid var(--clr-border, #e0e0e0);
    text-align: right; background: #f8f9fa; white-space: nowrap;
}
.hr-att-table td {
    padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}
.hr-att-table tr:hover td { background: var(--clr-primary-50, #fdf0f3); }
.hr-att-table .num-cell { text-align: center; font-weight: 700; }
.hr-att-table .warn-cell { color: #dc3545; font-weight: 700; text-align: center; }
.hr-att-table .good-cell { color: #28a745; font-weight: 700; text-align: center; }

/* Absence rate coloring */
.row-high-absence { background: rgba(220, 53, 69, 0.06) !important; }
.row-medium-absence { background: rgba(253, 126, 20, 0.06) !important; }
</style>

<div class="hr-page">
    <!-- Header -->
    <div class="hr-page-header">
        <h1><i class="fa fa-calendar-check-o"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fa fa-arrow-right"></i> لوحة الحضور', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="get" action="<?= Url::to(['summary']) ?>">
        <div class="hr-filter-bar">
            <div class="filter-group">
                <label>الشهر</label>
                <?= Html::dropDownList('month', $month, $arabicMonths, [
                    'class' => 'form-control',
                    'style' => 'width:150px',
                ]) ?>
            </div>

            <div class="filter-group">
                <label>السنة</label>
                <?= Html::dropDownList('year', $year, array_combine(
                    range(date('Y') - 3, date('Y') + 1),
                    range(date('Y') - 3, date('Y') + 1)
                ), [
                    'class' => 'form-control',
                    'style' => 'width:120px',
                ]) ?>
            </div>

            <div class="filter-group">
                <label>القسم</label>
                <?= Html::dropDownList('department', $departmentId, $departments, [
                    'class' => 'form-control',
                    'prompt' => '— جميع الأقسام —',
                    'style' => 'width:180px',
                ]) ?>
            </div>

            <div class="filter-group">
                <?= Html::submitButton('<i class="fa fa-search"></i> عرض', [
                    'class' => 'btn btn-primary btn-sm',
                ]) ?>
            </div>
        </div>
    </form>

    <!-- Summary Table -->
    <div class="hr-summary-table-card">
        <div class="card-header-bar">
            <h3>
                <i class="fa fa-table"></i>
                ملخص الحضور — <?= Html::encode($arabicMonths[(int) $month] ?? $month) ?> <?= Html::encode($year) ?>
            </h3>
            <span style="font-size:12px;color:var(--clr-text-muted)"><?= count($summary) ?> موظف</span>
        </div>

        <div style="overflow-x:auto">
            <table class="hr-att-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>القسم</th>
                        <th style="text-align:center">أيام العمل</th>
                        <th style="text-align:center">حضور</th>
                        <th style="text-align:center">غياب</th>
                        <th style="text-align:center">تأخر</th>
                        <th style="text-align:center">إجازة</th>
                        <th style="text-align:center">تأخير (دقيقة)</th>
                        <th style="text-align:center">ساعات إضافية</th>
                        <th style="text-align:center">إجمالي الساعات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary)): ?>
                        <tr>
                            <td colspan="11" style="text-align:center;padding:30px;color:var(--clr-text-muted)">
                                <i class="fa fa-info-circle"></i> لا توجد بيانات لهذه الفترة
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $idx = 0; foreach ($summary as $row):
                            $idx++;
                            $presentDays = (int) ($row['present_days'] ?? 0);
                            $absentDays = (int) ($row['absent_days'] ?? 0);
                            $lateDays = (int) ($row['late_days'] ?? 0);
                            $leaveDays = (int) ($row['leave_days'] ?? 0);
                            $totalRecords = (int) ($row['total_records'] ?? 0);
                            $totalLateMinutes = (int) ($row['total_late_minutes'] ?? 0);
                            $totalOvertime = round((float) ($row['total_overtime'] ?? 0), 1);
                            $totalHours = round((float) ($row['total_hours'] ?? 0), 1);

                            // Absence rate for row coloring
                            $absenceRate = $totalRecords > 0 ? ($absentDays / max($totalRecords, 1)) * 100 : 0;
                            $rowClass = '';
                            if ($absenceRate >= 20) {
                                $rowClass = 'row-high-absence';
                            } elseif ($absenceRate >= 10) {
                                $rowClass = 'row-medium-absence';
                            }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td style="color:var(--clr-text-muted)"><?= $idx ?></td>
                            <td>
                                <strong><?= Html::encode($row['name'] ?: $row['username']) ?></strong>
                            </td>
                            <td><?= Html::encode($row['department_name'] ?? '—') ?></td>
                            <td class="num-cell"><?= $totalRecords ?></td>
                            <td class="good-cell"><?= $presentDays ?></td>
                            <td class="<?= $absentDays > 0 ? 'warn-cell' : 'num-cell' ?>"><?= $absentDays ?></td>
                            <td class="num-cell"><?= $lateDays ?></td>
                            <td class="num-cell" style="color:#17a2b8"><?= $leaveDays ?></td>
                            <td class="<?= $totalLateMinutes > 0 ? 'warn-cell' : 'num-cell' ?>"><?= $totalLateMinutes ?></td>
                            <td class="num-cell" style="color:#6f42c1"><?= $totalOvertime ?></td>
                            <td class="num-cell"><?= $totalHours ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
