<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'تحليلات الحضور والتتبع';

$present = (int)($todayStats['present'] ?? 0);
$late    = (int)($todayStats['late_count'] ?? 0);
$absent  = (int)($todayStats['absent'] ?? 0);
$onLeave = (int)($todayStats['on_leave'] ?? 0);
$avgWork = (int)($todayStats['avg_work_min'] ?? 0);
$avgLate = (int)($todayStats['avg_late_min'] ?? 0);

echo $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'reports',
    'tabs'  => [
        ['label' => 'لوحة التحليلات', 'icon' => 'fa-bar-chart',       'url' => ['/hr/hr-tracking-report/index']],
        ['label' => 'التقرير الشهري', 'icon' => 'fa-calendar',         'url' => ['/hr/hr-tracking-report/monthly']],
        ['label' => 'الانضباط',       'icon' => 'fa-star-half-o',      'url' => ['/hr/hr-tracking-report/punctuality']],
        ['label' => 'المخالفات',      'icon' => 'fa-shield',           'url' => ['/hr/hr-tracking-report/violations']],
    ],
]);

$methodLabels = [
    'geofence_auto' => 'تلقائي (Geofence)',
    'manual' => 'يدوي',
    'wifi' => 'Wi-Fi',
    'qr' => 'QR Code',
    'biometric' => 'بصمة',
    'admin' => 'إدارة',
];
?>

<style>
.rp-page{padding:20px}
.rp-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.rp-header h1{font-size:22px;font-weight:700;color:var(--clr-primary,#800020);margin:0}
.rp-links{display:flex;gap:8px;flex-wrap:wrap}
.rp-links a{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;transition:all .2s}
.rp-links a:hover{background:var(--clr-primary,#800020);color:#fff}

.rp-kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.rp-kpi{background:#fff;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.04);border-right:4px solid #e2e8f0}
.rp-kpi .val{font-size:32px;font-weight:800}
.rp-kpi .lbl{font-size:12px;color:#94a3b8;margin-top:4px}
.rp-kpi .sub{font-size:11px;color:#64748b;margin-top:2px}
.rp-kpi.green{border-right-color:#16a34a}.rp-kpi.green .val{color:#16a34a}
.rp-kpi.orange{border-right-color:#f59e0b}.rp-kpi.orange .val{color:#f59e0b}
.rp-kpi.red{border-right-color:#dc2626}.rp-kpi.red .val{color:#dc2626}
.rp-kpi.blue{border-right-color:#3b82f6}.rp-kpi.blue .val{color:#3b82f6}
.rp-kpi.purple{border-right-color:#8b5cf6}.rp-kpi.purple .val{color:#8b5cf6}

.rp-row{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px}
@media(max-width:992px){.rp-row{grid-template-columns:1fr}}
.rp-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.rp-card h3{font-size:15px;font-weight:700;color:#1e293b;margin:0 0 16px;display:flex;align-items:center;gap:8px}
.rp-card h3 i{color:var(--clr-primary,#800020)}

.chart-container{position:relative;height:280px}
canvas{width:100%!important}

.rp-table{width:100%;border-collapse:collapse}
.rp-table th{padding:8px 12px;font-size:11px;font-weight:600;color:#64748b;background:#f8fafc;text-align:right;border-bottom:1px solid #e2e8f0}
.rp-table td{padding:8px 12px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9}
.rp-table tr:hover td{background:#fdf2f4}

.score-bar{height:6px;border-radius:3px;background:#f1f5f9;overflow:hidden;width:80px;display:inline-block;vertical-align:middle;margin-right:6px}
.score-fill{height:100%;border-radius:3px}

.method-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9}
.method-row:last-child{border-bottom:none}
.method-name{flex:1;font-size:13px;color:#475569}
.method-bar{flex:2;height:20px;border-radius:4px;background:#f1f5f9;overflow:hidden;position:relative}
.method-fill{height:100%;border-radius:4px;background:var(--clr-primary,#800020);transition:width .5s}
.method-count{min-width:40px;text-align:left;font-size:13px;font-weight:600;color:#1e293b}
</style>

<div class="rp-page">
    <div class="rp-header">
        <h1><i class="fa fa-bar-chart"></i> <?= $this->title ?></h1>
        <div class="rp-links">
            <a href="<?= Url::to(['monthly']) ?>"><i class="fa fa-calendar"></i> التقرير الشهري</a>
            <a href="<?= Url::to(['punctuality']) ?>"><i class="fa fa-clock-o"></i> الانضباط</a>
            <a href="<?= Url::to(['violations']) ?>"><i class="fa fa-exclamation-triangle"></i> المخالفات</a>
            <a href="<?= Url::to(['/hr/hr-tracking-api/live-map']) ?>"><i class="fa fa-map"></i> الخريطة</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="rp-kpi-grid">
        <div class="rp-kpi green">
            <div class="val"><?= $present ?></div>
            <div class="lbl">حاضرون اليوم</div>
        </div>
        <div class="rp-kpi orange">
            <div class="val"><?= $late ?></div>
            <div class="lbl">متأخرون</div>
            <?php if ($avgLate): ?><div class="sub">متوسط <?= $avgLate ?> د</div><?php endif; ?>
        </div>
        <div class="rp-kpi red">
            <div class="val"><?= $absent ?></div>
            <div class="lbl">غائبون</div>
        </div>
        <div class="rp-kpi blue">
            <div class="val"><?= $avgWork ? floor($avgWork/60).':'.str_pad($avgWork%60,2,'0',STR_PAD_LEFT) : '--' ?></div>
            <div class="lbl">متوسط ساعات العمل</div>
        </div>
        <div class="rp-kpi purple">
            <div class="val"><?= $avgWorkMinutes ? floor($avgWorkMinutes/60).':'.str_pad($avgWorkMinutes%60,2,'0',STR_PAD_LEFT) : '--' ?></div>
            <div class="lbl">متوسط الشهر</div>
        </div>
        <?php if ($mockCount > 0): ?>
        <div class="rp-kpi red">
            <div class="val"><?= $mockCount ?></div>
            <div class="lbl">مواقع مُزيّفة!</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Charts Row -->
    <div class="rp-row">
        <div class="rp-card">
            <h3><i class="fa fa-line-chart"></i> اتجاه الحضور الشهري</h3>
            <div class="chart-container">
                <div id="trendChart"></div>
            </div>
        </div>
        <div class="rp-card">
            <h3><i class="fa fa-pie-chart"></i> طريقة تسجيل الدخول</h3>
            <?php
            $totalMethods = array_sum(array_column($methodBreakdown, 'cnt'));
            foreach ($methodBreakdown as $m):
                $pct = $totalMethods > 0 ? round(($m['cnt'] / $totalMethods) * 100) : 0;
            ?>
            <div class="method-row">
                <span class="method-name"><?= $methodLabels[$m['clock_in_method']] ?? $m['clock_in_method'] ?></span>
                <div class="method-bar">
                    <div class="method-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="method-count"><?= $m['cnt'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Late Employees -->
    <div class="rp-row" style="grid-template-columns:1fr">
        <div class="rp-card">
            <h3><i class="fa fa-clock-o"></i> أكثر الموظفين تأخراً هذا الشهر</h3>
            <?php if (empty($topLateEmployees)): ?>
                <div style="text-align:center;color:#94a3b8;padding:30px">لا توجد بيانات تأخير</div>
            <?php else: ?>
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>أيام التأخير</th>
                        <th>إجمالي التأخير</th>
                        <th>متوسط التأخير</th>
                        <th>مؤشر الانضباط</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topLateEmployees as $i => $emp): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= Html::encode($emp['name']) ?></strong></td>
                        <td><?= $emp['late_days'] ?> يوم</td>
                        <td style="color:#f59e0b;font-weight:600"><?= $emp['total_late'] ?> د</td>
                        <td><?= $emp['avg_late'] ?> د</td>
                        <td>
                            <?php
                            $score = max(0, 100 - (int)$emp['total_late']);
                            $color = $score >= 80 ? '#16a34a' : ($score >= 50 ? '#f59e0b' : '#dc2626');
                            ?>
                            <div class="score-bar"><div class="score-fill" style="width:<?= $score ?>%;background:<?= $color ?>"></div></div>
                            <span style="font-size:12px;color:<?= $color ?>;font-weight:600"><?= $score ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof ApexCharts === 'undefined') return;
    var trendData = <?= json_encode($monthlyTrend) ?>;
    if (trendData.length > 0) {
        var labels = trendData.map(function(d){ return d.attendance_date.substring(5); });
        var el = document.getElementById('trendChart');
        if (el) {
            new ApexCharts(el, {
                chart: { type: 'area', height: 280, fontFamily: 'Noto Kufi Arabic, Cairo, sans-serif',
                    toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
                    animations: { enabled: true, easing: 'easeinout', speed: 800 }
                },
                series: [
                    { name: 'حاضرون', data: trendData.map(function(d){ return parseInt(d.present); }) },
                    { name: 'متأخرون', data: trendData.map(function(d){ return parseInt(d.late); }) },
                    { name: 'غائبون', data: trendData.map(function(d){ return parseInt(d.absent); }) }
                ],
                xaxis: { categories: labels, labels: { style: { fontFamily: 'Noto Kufi Arabic', fontSize: '11px' } } },
                yaxis: { labels: { style: { fontFamily: 'Noto Kufi Arabic' } } },
                colors: ['#16a34a', '#f59e0b', '#dc2626'],
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05, stops: [0, 90, 100] } },
                stroke: { curve: 'smooth', width: 2.5 },
                dataLabels: { enabled: false },
                legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'Noto Kufi Arabic', fontSize: '12px' },
                grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
                tooltip: { style: { fontFamily: 'Noto Kufi Arabic' } }
            }).render();
        }
    }
});
</script>
