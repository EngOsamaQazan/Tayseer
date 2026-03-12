<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use backend\modules\hr\models\HrAttendanceLog;

$this->title = 'لوحة الحضور الموحّدة';

$statusLabels = HrAttendanceLog::getStatusLabels();
$employeeTypes = ['office' => 'مكتبي', 'field' => 'ميداني', 'sales' => 'مبيعات', 'hybrid' => 'مختلط'];

$present  = (int)($stats['present'] ?? 0);
$late     = (int)($stats['late'] ?? 0);
$absent   = (int)($stats['absent'] ?? 0);
$fieldDuty = (int)($stats['field_duty'] ?? 0);
$onLeave  = (int)($stats['on_leave'] ?? 0);
$mockDetected = (int)($stats['mock_detected'] ?? 0);
$avgLate  = (int)($stats['avg_late'] ?? 0);
$avgWork  = (int)($stats['avg_work'] ?? 0);
$total    = (int)($stats['total'] ?? 0);
?>

<?= $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'tracking',
    'tabs'  => [
        ['label' => 'سجل الحضور',    'icon' => 'fa-calendar-check-o', 'url' => ['/hr/hr-tracking-api/attendance-board']],
        ['label' => 'التتبع المباشر', 'icon' => 'fa-crosshairs',       'url' => ['/hr/hr-tracking-api/live-map']],
        ['label' => 'الورديات',       'icon' => 'fa-clock-o',          'url' => ['/hr/hr-shift/index']],
        ['label' => 'مناطق العمل',    'icon' => 'fa-map-pin',          'url' => ['/hr/hr-work-zone/index']],
    ],
]) ?>

<style>
.ab-page{padding:20px}
.ab-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.ab-header h1{font-size:22px;font-weight:700;color:var(--clr-primary,#800020);margin:0}
.ab-header .actions{display:flex;gap:8px}

.ab-kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:20px}
.ab-kpi{background:#fff;border-radius:10px;padding:16px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.04);border-top:3px solid #e2e8f0}
.ab-kpi .val{font-size:28px;font-weight:800;color:#1e293b}
.ab-kpi .lbl{font-size:12px;color:#94a3b8;margin-top:2px}
.ab-kpi.green{border-top-color:#16a34a}
.ab-kpi.green .val{color:#16a34a}
.ab-kpi.orange{border-top-color:#f59e0b}
.ab-kpi.orange .val{color:#f59e0b}
.ab-kpi.red{border-top-color:#dc2626}
.ab-kpi.red .val{color:#dc2626}
.ab-kpi.blue{border-top-color:#3b82f6}
.ab-kpi.blue .val{color:#3b82f6}
.ab-kpi.purple{border-top-color:#8b5cf6}
.ab-kpi.purple .val{color:#8b5cf6}

.ab-filter{background:#fff;border-radius:10px;padding:14px 20px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ab-filter .fg{display:flex;flex-direction:column;gap:4px}
.ab-filter label{font-size:11px;font-weight:600;color:#64748b}
.ab-filter input,.ab-filter select{padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.ab-filter .btn-filter{padding:7px 16px;background:var(--clr-primary,#800020);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer}

.ab-table-wrap{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden}
.ab-table{width:100%;border-collapse:collapse}
.ab-table th{padding:10px 14px;font-size:12px;font-weight:600;color:#64748b;background:#f8fafc;text-align:right;border-bottom:1px solid #e2e8f0}
.ab-table td{padding:10px 14px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9}
.ab-table tr:hover td{background:#fdf2f4}
.ab-badge{padding:3px 8px;border-radius:10px;font-size:11px;font-weight:600;display:inline-block}
.ab-badge.present{background:#dcfce7;color:#166534}
.ab-badge.late{background:#fef3c7;color:#92400e}
.ab-badge.absent{background:#fee2e2;color:#991b1b}
.ab-badge.field_duty{background:#dbeafe;color:#1e40af}
.ab-badge.half_day{background:#f3e8ff;color:#7c3aed}
.ab-badge.on_leave{background:#f1f5f9;color:#475569}
.ab-badge.holiday,.ab-badge.weekend{background:#f1f5f9;color:#64748b}
.ab-method{font-size:11px;color:#94a3b8}
.ab-mock{background:#fee2e2;color:#dc2626;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700}
.ab-time{direction:ltr;font-variant-numeric:tabular-nums}
.empty-row td{text-align:center;color:#94a3b8;padding:40px!important}
</style>

<div class="ab-page">
    <div class="ab-header">
        <h1><i class="fa fa-calendar-check-o"></i> <?= $this->title ?></h1>
        <div class="actions">
            <a href="<?= Url::to(['/hr/hr-tracking-api/live-map']) ?>" class="btn btn-default btn-sm">
                <i class="fa fa-map"></i> الخريطة المباشرة
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="ab-kpi-grid">
        <div class="ab-kpi green"><div class="val"><?= $present ?></div><div class="lbl">حاضرون</div></div>
        <div class="ab-kpi orange"><div class="val"><?= $late ?></div><div class="lbl">متأخرون</div></div>
        <div class="ab-kpi red"><div class="val"><?= $absent ?></div><div class="lbl">غائبون</div></div>
        <div class="ab-kpi blue"><div class="val"><?= $fieldDuty ?></div><div class="lbl">ميدانيون</div></div>
        <div class="ab-kpi purple"><div class="val"><?= $onLeave ?></div><div class="lbl">إجازة</div></div>
        <div class="ab-kpi"><div class="val"><?= $avgLate ?><span style="font-size:14px"> د</span></div><div class="lbl">متوسط التأخير</div></div>
        <div class="ab-kpi"><div class="val"><?= $avgWork ? floor($avgWork/60).':'.str_pad($avgWork%60,2,'0',STR_PAD_LEFT) : '--' ?></div><div class="lbl">متوسط ساعات العمل</div></div>
        <?php if ($mockDetected > 0): ?>
        <div class="ab-kpi red"><div class="val"><?= $mockDetected ?></div><div class="lbl">مواقع مُزيّفة!</div></div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="get" class="ab-filter">
        <div class="fg">
            <label>التاريخ</label>
            <input type="date" name="date" value="<?= Html::encode($filterDate) ?>">
        </div>
        <div class="fg">
            <label>الحالة</label>
            <select name="status">
                <option value="">الكل</option>
                <?php foreach ($statusLabels as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
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
        <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> تصفية</button>
    </form>

    <!-- Table -->
    <div class="ab-table-wrap">
        <table class="ab-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الحالة</th>
                    <th>وقت الدخول</th>
                    <th>وقت الخروج</th>
                    <th>ساعات العمل</th>
                    <th>التأخير</th>
                    <th>طريقة الدخول</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($dataProvider->getTotalCount() === 0): ?>
                    <tr class="empty-row">
                        <td colspan="8"><i class="fa fa-inbox" style="font-size:24px;display:block;margin-bottom:8px"></i> لا توجد سجلات لهذا التاريخ</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dataProvider->getModels() as $log): ?>
                        <tr>
                            <td>
                                <strong><?= Html::encode($log->user ? $log->user->name : 'N/A') ?></strong>
                            </td>
                            <td>
                                <span class="ab-badge <?= $log->status ?>"><?= $statusLabels[$log->status] ?? $log->status ?></span>
                                <?php if ($log->is_mock_location): ?>
                                    <span class="ab-mock"><i class="fa fa-warning"></i> مُزيّف</span>
                                <?php endif; ?>
                            </td>
                            <td class="ab-time"><?= $log->clock_in_at ? date('h:i A', strtotime($log->clock_in_at)) : '—' ?></td>
                            <td class="ab-time"><?= $log->clock_out_at ? date('h:i A', strtotime($log->clock_out_at)) : '—' ?></td>
                            <td>
                                <?php if ($log->total_minutes > 0): ?>
                                    <?= floor($log->total_minutes / 60) ?>:<?= str_pad($log->total_minutes % 60, 2, '0', STR_PAD_LEFT) ?>
                                    <?php if ($log->overtime_minutes > 0): ?>
                                        <span style="color:#16a34a;font-size:11px">(+<?= $log->overtime_minutes ?> د)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log->late_minutes > 0): ?>
                                    <span style="color:#f59e0b;font-weight:600"><?= $log->late_minutes ?> د</span>
                                <?php else: ?>
                                    <span style="color:#16a34a">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $methods = [
                                    'geofence_auto' => '<i class="fa fa-map-marker" style="color:#16a34a"></i> تلقائي',
                                    'manual' => '<i class="fa fa-hand-paper-o"></i> يدوي',
                                    'wifi' => '<i class="fa fa-wifi"></i> Wi-Fi',
                                    'qr' => '<i class="fa fa-qrcode"></i> QR',
                                    'biometric' => '<i class="fa fa-fingerprint"></i> بصمة',
                                    'admin' => '<i class="fa fa-user-secret"></i> إدارة',
                                ];
                                ?>
                                <span class="ab-method"><?= $methods[$log->clock_in_method] ?? $log->clock_in_method ?></span>
                            </td>
                            <td>
                                <?php if ($log->admin_adjusted): ?>
                                    <span style="color:#8b5cf6;font-size:11px"><i class="fa fa-pencil"></i> معدّل</span>
                                <?php endif; ?>
                                <?= $log->notes ? Html::encode(mb_substr($log->notes, 0, 40)) : '' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;display:flex;justify-content:center">
        <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
    </div>
</div>
