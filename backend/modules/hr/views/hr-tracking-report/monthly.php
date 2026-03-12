<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'التقرير الشهري — ' . $month;
$employeeTypes = ['office' => 'مكتبي', 'field' => 'ميداني', 'sales' => 'مبيعات', 'hybrid' => 'مختلط'];

$totals = [
    'present_days' => 0, 'absent_days' => 0, 'late_days' => 0, 'leave_days' => 0,
    'total_work_min' => 0, 'total_late_min' => 0, 'total_overtime' => 0, 'mock_count' => 0,
];
foreach ($data as $row) {
    foreach ($totals as $k => &$v) $v += (int)($row[$k] ?? 0);
}
unset($v);
?>

<?= $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'reports',
    'tabs'  => [
        ['label' => 'لوحة التحليلات', 'icon' => 'fa-bar-chart',       'url' => ['/hr/hr-tracking-report/index']],
        ['label' => 'التقرير الشهري', 'icon' => 'fa-calendar',         'url' => ['/hr/hr-tracking-report/monthly']],
        ['label' => 'الانضباط',       'icon' => 'fa-star-half-o',      'url' => ['/hr/hr-tracking-report/punctuality']],
        ['label' => 'المخالفات',      'icon' => 'fa-shield',           'url' => ['/hr/hr-tracking-report/violations']],
    ],
]) ?>

<style>
.rp-page{padding:20px}
.rp-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.rp-header h1{font-size:22px;font-weight:700;color:var(--clr-primary,#800020);margin:0}
.rp-filter{background:#fff;border-radius:10px;padding:14px 20px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.rp-filter .fg{display:flex;flex-direction:column;gap:4px}
.rp-filter label{font-size:11px;font-weight:600;color:#64748b}
.rp-filter input,.rp-filter select{padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.rp-filter .btn-f{padding:7px 16px;background:var(--clr-primary,#800020);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer}
.rp-summary{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:20px}
.rp-sum-card{background:#fff;border-radius:8px;padding:12px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.rp-sum-card .val{font-size:22px;font-weight:800;color:#1e293b}
.rp-sum-card .lbl{font-size:11px;color:#94a3b8}
.rp-table-wrap{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow-x:auto}
.rp-table{width:100%;border-collapse:collapse;min-width:900px}
.rp-table th{padding:10px 12px;font-size:11px;font-weight:600;color:#64748b;background:#f8fafc;text-align:right;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.rp-table td{padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;white-space:nowrap}
.rp-table tr:hover td{background:#fdf2f4}
.rp-table tfoot td{font-weight:700;background:#f8fafc;border-top:2px solid #e2e8f0}
.badge-type{padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600}
.badge-type.office{background:#dbeafe;color:#1e40af}
.badge-type.field{background:#dcfce7;color:#166534}
.badge-type.sales{background:#fef3c7;color:#92400e}
.badge-type.hybrid{background:#f3e8ff;color:#7c3aed}
.pct-bar{width:60px;height:5px;border-radius:3px;background:#f1f5f9;display:inline-block;vertical-align:middle;margin-right:4px;overflow:hidden}
.pct-fill{height:100%;border-radius:3px}
</style>

<div class="rp-page">
    <div class="rp-header">
        <h1><i class="fa fa-calendar"></i> <?= $this->title ?></h1>
        <a href="<?= Url::to(['index']) ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-right"></i> لوحة التحكم</a>
    </div>

    <form method="get" class="rp-filter">
        <div class="fg">
            <label>الشهر</label>
            <input type="month" name="month" value="<?= Html::encode($month) ?>">
        </div>
        <div class="fg">
            <label>نوع الموظف</label>
            <select name="employee_type">
                <option value="">الكل</option>
                <?php foreach ($employeeTypes as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-f"><i class="fa fa-filter"></i> عرض</button>
    </form>

    <div class="rp-summary">
        <div class="rp-sum-card"><div class="val"><?= count($data) ?></div><div class="lbl">موظفون</div></div>
        <div class="rp-sum-card"><div class="val" style="color:#16a34a"><?= $totals['present_days'] ?></div><div class="lbl">أيام حضور</div></div>
        <div class="rp-sum-card"><div class="val" style="color:#dc2626"><?= $totals['absent_days'] ?></div><div class="lbl">أيام غياب</div></div>
        <div class="rp-sum-card"><div class="val" style="color:#f59e0b"><?= $totals['late_days'] ?></div><div class="lbl">أيام تأخير</div></div>
        <div class="rp-sum-card"><div class="val"><?= $totals['total_work_min'] > 0 ? round($totals['total_work_min']/60) : 0 ?> س</div><div class="lbl">إجمالي ساعات العمل</div></div>
        <div class="rp-sum-card"><div class="val" style="color:#f59e0b"><?= $totals['total_late_min'] ?> د</div><div class="lbl">إجمالي التأخير</div></div>
        <div class="rp-sum-card"><div class="val" style="color:#3b82f6"><?= $totals['total_overtime'] ?> د</div><div class="lbl">إجمالي الإضافي</div></div>
    </div>

    <div class="rp-table-wrap">
        <table class="rp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الموظف</th>
                    <th>النوع</th>
                    <th>حضور</th>
                    <th>غياب</th>
                    <th>تأخير</th>
                    <th>إجازة</th>
                    <th>ساعات العمل</th>
                    <th>تأخير (د)</th>
                    <th>إضافي (د)</th>
                    <th>نسبة الحضور</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="11" style="text-align:center;color:#94a3b8;padding:40px">لا توجد بيانات</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $i => $row): ?>
                    <?php
                        $pDays = (int)$row['present_days'];
                        $pct = $daysInMonth > 0 ? round(($pDays / $daysInMonth) * 100) : 0;
                        $pctColor = $pct >= 90 ? '#16a34a' : ($pct >= 70 ? '#f59e0b' : '#dc2626');
                        $workH = (int)$row['total_work_min'] > 0 ? floor($row['total_work_min']/60).':'.str_pad($row['total_work_min']%60,2,'0',STR_PAD_LEFT) : '—';
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= Html::encode($row['name']) ?></strong></td>
                        <td><span class="badge-type <?= $row['employee_type'] ?? '' ?>"><?= $employeeTypes[$row['employee_type']] ?? '—' ?></span></td>
                        <td style="color:#16a34a;font-weight:600"><?= $pDays ?></td>
                        <td style="color:#dc2626"><?= (int)$row['absent_days'] ?></td>
                        <td style="color:#f59e0b"><?= (int)$row['late_days'] ?></td>
                        <td><?= (int)$row['leave_days'] ?></td>
                        <td><?= $workH ?></td>
                        <td><?= (int)$row['total_late_min'] ?></td>
                        <td style="color:#3b82f6"><?= (int)$row['total_overtime'] ?></td>
                        <td>
                            <div class="pct-bar"><div class="pct-fill" style="width:<?= $pct ?>%;background:<?= $pctColor ?>"></div></div>
                            <span style="font-size:12px;font-weight:600;color:<?= $pctColor ?>"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($data)): ?>
            <tfoot>
                <tr>
                    <td colspan="3">الإجمالي</td>
                    <td><?= $totals['present_days'] ?></td>
                    <td><?= $totals['absent_days'] ?></td>
                    <td><?= $totals['late_days'] ?></td>
                    <td><?= $totals['leave_days'] ?></td>
                    <td><?= $totals['total_work_min'] > 0 ? round($totals['total_work_min']/60).'س' : '—' ?></td>
                    <td><?= $totals['total_late_min'] ?></td>
                    <td><?= $totals['total_overtime'] ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
