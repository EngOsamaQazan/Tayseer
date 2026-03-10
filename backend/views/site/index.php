<?php
/**
 * لوحة التحكم — Dashboard
 * تصميم احترافي متوافق مع ثيم Jadal Burgundy
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'لوحة التحكم';

// ─── ترجمة الحالات ───
$statusLabels = [
    'active'           => 'نشط',
    'judiciary'        => 'قضائي',
    'legal_department' => 'قانوني',
    'finished'         => 'منتهي',
    'canceled'         => 'ملغي',
    'pending'          => 'معلّق',
    'refused'          => 'مرفوض',
    'reconciliation'   => 'مصالحة',
    'settlement'       => 'تسوية',
];
$statusColors = [
    'active'           => '#28a745',
    'judiciary'        => '#dc3545',
    'legal_department' => '#fd7e14',
    'finished'         => '#6c757d',
    'canceled'         => '#adb5bd',
    'pending'          => '#ffc107',
    'refused'          => '#e83e8c',
    'reconciliation'   => '#17a2b8',
    'settlement'       => '#6f42c1',
];

// ─── بيانات الرسم البياني ───
$chartLabels = [];
$chartData   = [];
$arabicMonths = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
foreach ($incomeChart as $row) {
    $parts = explode('-', $row['month_key']);
    $monthIdx = (int)$parts[1] - 1;
    $chartLabels[] = $arabicMonths[$monthIdx] . ' ' . $parts[0];
    $chartData[]   = round((float)$row['total'], 2);
}

// ─── بيانات Donut chart للعقود ───
$donutLabels = [];
$donutData   = [];
$donutColors = [];
foreach ($contractsByStatus as $st => $cnt) {
    $donutLabels[] = isset($statusLabels[$st]) ? $statusLabels[$st] : $st;
    $donutData[]   = $cnt;
    $donutColors[] = isset($statusColors[$st]) ? $statusColors[$st] : '#999';
}
?>

<style>
/* ═══════════════════════════════════════
   Dashboard Styles
   ═══════════════════════════════════════ */
.db-page { padding: 20px; }
.db-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.db-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.db-header .db-date {
    font-size: 13px; color: var(--clr-text-muted, #6c757d);
    background: var(--clr-surface, #fff); padding: 6px 16px;
    border-radius: var(--radius-sm, 6px); box-shadow: var(--shadow-sm);
}

/* KPI Grid */
.db-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px; margin-bottom: 24px;
}
.db-kpi {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    padding: 20px;
    box-shadow: var(--shadow-sm);
    display: flex; align-items: flex-start; gap: 14px;
    transition: var(--transition);
    border-right: 4px solid var(--kpi-color, var(--clr-primary, #800020));
    position: relative; overflow: hidden;
}
.db-kpi:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.db-kpi-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff; flex-shrink: 0;
    background: var(--kpi-color, var(--clr-primary, #800020));
}
.db-kpi-body { flex: 1; min-width: 0; }
.db-kpi-label { font-size: 12px; color: var(--clr-text-muted); margin-bottom: 2px; }
.db-kpi-value { font-size: 24px; font-weight: 800; color: var(--clr-text); line-height: 1.2; }
.db-kpi-sub { font-size: 11px; color: var(--clr-text-muted); margin-top: 4px; }

/* Two-column layout */
.db-grid-2 {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px; margin-bottom: 24px;
}
@media (max-width: 1200px) { .db-grid-2 { grid-template-columns: 1fr; } }

.db-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.db-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid var(--clr-border, #e0e0e0);
}
.db-card-header h3 {
    margin: 0; font-size: 15px; font-weight: 700; color: var(--clr-text);
}
.db-card-header .db-badge {
    font-size: 11px; padding: 3px 10px; border-radius: 20px;
    background: var(--clr-primary-50, #fdf0f3); color: var(--clr-primary, #800020);
    font-weight: 600;
}
.db-card-body { padding: 16px 20px; }

/* Chart container */
.db-chart-wrap { position: relative; height: 280px; }

/* Tables */
.db-table { width: 100%; border-collapse: collapse; }
.db-table th {
    font-size: 11px; font-weight: 600; color: var(--clr-text-muted);
    text-transform: uppercase; padding: 8px 12px;
    border-bottom: 2px solid var(--clr-border);
    text-align: right;
}
.db-table td {
    padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}
.db-table tr:hover td { background: var(--clr-primary-50, #fdf0f3); }
.db-table .db-amount { font-weight: 700; color: var(--clr-success, #28a745); direction: ltr; text-align: left; }
.db-table .db-link { color: var(--clr-primary); font-weight: 600; text-decoration: none; }
.db-table .db-link:hover { text-decoration: underline; }

/* Status pill */
.db-status-pill {
    display: inline-block; padding: 2px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; color: #fff;
}

/* Top collectors bar */
.db-collector { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.db-collector-name { font-size: 13px; font-weight: 600; min-width: 120px; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-collector-bar-wrap { flex: 1; height: 24px; background: #f0f0f0; border-radius: 12px; overflow: hidden; }
.db-collector-bar {
    height: 100%; border-radius: 12px; display: flex; align-items: center;
    padding: 0 10px; font-size: 11px; font-weight: 700; color: #fff;
    transition: width 1s ease;
}
.db-collector-amount { font-size: 12px; font-weight: 700; color: var(--clr-text); min-width: 80px; text-align: left; direction: ltr; }

/* Contract status grid */
.db-status-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px; margin-top: 12px;
}
.db-status-item {
    display: flex; align-items: center; gap: 8px;
    padding: 10px; border-radius: var(--radius-sm); background: #f8f9fa;
}
.db-status-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.db-status-item-label { font-size: 12px; color: var(--clr-text-muted); }
.db-status-item-val { font-size: 16px; font-weight: 800; color: var(--clr-text); margin-right: auto; }

/* ── Mobile Responsive ── */
@media (max-width: 575.98px) {
    .db-page { padding: 10px 0; }
    .db-header h1 { font-size: 17px; }
    .db-kpi { padding: 14px; gap: 10px; }
    .db-kpi-icon { width: 38px; height: 38px; font-size: 16px; border-radius: 10px; }
    .db-kpi-value { font-size: 18px; }
    .db-kpi-label { font-size: 11px; }
    .db-card-header { padding: 10px 14px; }
    .db-card-body { padding: 12px 14px; }
    .db-chart-wrap { height: 200px; }
    .db-table th { font-size: 10px; padding: 6px 8px; }
    .db-table td { font-size: 12px; padding: 8px; }
    .db-collector-name { min-width: 80px; font-size: 11px; }
    .db-collector-bar-wrap { height: 20px; }
    .db-collector-amount { font-size: 11px; min-width: 60px; }
}
@media (max-width: 379.98px) {
    .db-kpi-grid { grid-template-columns: 1fr; gap: 10px; }
    .db-status-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="db-page">
    <!-- Header -->
    <div class="db-header">
        <h1><i class="fa fa-tachometer"></i> لوحة التحكم</h1>
        <div class="db-date">
            <i class="fa fa-calendar"></i>
            <?= date('l، d F Y') ?>
        </div>
    </div>

    <!-- KPI Cards (auto-animated by AOS via tayseer-modern.js) -->
    <div class="db-kpi-grid">
        <div class="db-kpi" style="--kpi-color:#800020" data-tippy-content="إجمالي قيمة العقود: <?= number_format($totalContractValue, 0) ?> د.أ">
            <div class="db-kpi-icon"><i class="fa fa-file-text"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">إجمالي العقود</div>
                <div class="db-kpi-value"><?= number_format($totalContracts) ?></div>
                <div class="db-kpi-sub">قيمة: <?= number_format($totalContractValue, 0) ?> د.أ</div>
            </div>
        </div>

        <div class="db-kpi" style="--kpi-color:#28a745" data-tippy-content="إيرادات <?= date('F Y') ?>">
            <div class="db-kpi-icon"><i class="fa fa-money"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">إيرادات الشهر</div>
                <div class="db-kpi-value"><?= number_format($monthlyIncome, 0) ?></div>
                <div class="db-kpi-sub">د.أ — <?= date('F Y') ?></div>
            </div>
        </div>

        <div class="db-kpi" style="--kpi-color:#17a2b8">
            <div class="db-kpi-icon"><i class="fa fa-line-chart"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">إيرادات السنة</div>
                <div class="db-kpi-value"><?= number_format($yearlyIncome, 0) ?></div>
                <div class="db-kpi-sub">د.أ — <?= date('Y') ?></div>
            </div>
        </div>

        <div class="db-kpi" style="--kpi-color:#dc3545" data-tippy-content="صافي الشهر: <?= number_format($monthlyIncome - $monthlyExpenses, 0) ?> د.أ">
            <div class="db-kpi-icon"><i class="fa fa-minus-circle"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">مصاريف الشهر</div>
                <div class="db-kpi-value"><?= number_format($monthlyExpenses, 0) ?></div>
                <div class="db-kpi-sub">صافي: <?= number_format($monthlyIncome - $monthlyExpenses, 0) ?> د.أ</div>
            </div>
        </div>

        <div class="db-kpi" style="--kpi-color:#6f42c1">
            <div class="db-kpi-icon"><i class="fa fa-users"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">العملاء</div>
                <div class="db-kpi-value"><?= number_format($totalCustomers) ?></div>
            </div>
        </div>

        <div class="db-kpi" style="--kpi-color:#fd7e14">
            <div class="db-kpi-icon"><i class="fa fa-gavel"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">القضايا</div>
                <div class="db-kpi-value"><?= number_format($totalCases) ?></div>
            </div>
        </div>

        <div class="db-kpi" style="--kpi-color:#c8a04a">
            <div class="db-kpi-icon"><i class="fa fa-handshake-o"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">التسويات</div>
                <div class="db-kpi-value"><?= number_format($totalSettlements) ?></div>
            </div>
        </div>

        <div class="db-kpi" style="--kpi-color:#28a745">
            <div class="db-kpi-icon"><i class="fa fa-check-circle"></i></div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">عقود نشطة</div>
                <div class="db-kpi-value"><?= number_format(isset($contractsByStatus['active']) ? $contractsByStatus['active'] : 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Row (ApexCharts) -->
    <div class="db-grid-2">
        <!-- Income Area Chart -->
        <div class="db-card">
            <div class="db-card-header">
                <h3><i class="fa fa-area-chart"></i> الإيرادات — آخر 12 شهر</h3>
            </div>
            <div class="db-card-body">
                <div id="apexIncomeChart" style="height:280px"></div>
            </div>
        </div>

        <!-- Contract Status Donut -->
        <div class="db-card">
            <div class="db-card-header">
                <h3><i class="fa fa-pie-chart"></i> توزيع العقود</h3>
                <span class="db-badge"><?= number_format($totalContracts) ?> عقد</span>
            </div>
            <div class="db-card-body">
                <div id="apexDonutChart" style="height:220px"></div>
                <div class="db-status-grid">
                    <?php foreach ($contractsByStatus as $st => $cnt): ?>
                    <div class="db-status-item">
                        <span class="db-status-dot" style="background:<?= isset($statusColors[$st]) ? $statusColors[$st] : '#999' ?>"></span>
                        <div>
                            <div class="db-status-item-label"><?= isset($statusLabels[$st]) ? $statusLabels[$st] : $st ?></div>
                        </div>
                        <div class="db-status-item-val"><?= number_format($cnt) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="db-grid-2">
        <!-- Recent Payments -->
        <div class="db-card">
            <div class="db-card-header">
                <h3><i class="fa fa-money"></i> آخر الدفعات</h3>
                <a href="<?= Url::to(['/income/income/index']) ?>" class="db-badge" style="text-decoration:none">عرض الكل</a>
            </div>
            <div class="db-card-body" style="padding:0">
                <table class="db-table">
                    <thead><tr>
                        <th>#</th><th>العميل</th><th>التاريخ</th><th>المبلغ</th><th>العقد</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentPayments as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= Html::encode($p['customer_name'] ?: '—') ?></td>
                        <td><?= $p['date'] ?></td>
                        <td class="db-amount"><?= number_format($p['amount'], 2) ?></td>
                        <td>
                            <?php if ($p['contract_id']): ?>
                            <a href="<?= Url::to(['/followUp/follow-up/panel', 'id' => $p['contract_id']]) ?>" class="db-link">#<?= $p['contract_id'] ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentPayments)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--clr-text-muted)">لا توجد دفعات</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Collectors -->
        <div class="db-card">
            <div class="db-card-header">
                <h3><i class="fa fa-trophy"></i> أفضل المحصّلين — هذا الشهر</h3>
            </div>
            <div class="db-card-body">
                <?php
                $maxCollected = 0;
                foreach ($topCollectors as $tc) { if ((float)$tc['collected'] > $maxCollected) $maxCollected = (float)$tc['collected']; }
                $barColors = ['#800020','#9a1a3a','#b03050','#c75070','#dd8098','#17a2b8','#28a745','#6f42c1','#fd7e14','#c8a04a'];
                $idx = 0;
                foreach ($topCollectors as $tc):
                    $pct = $maxCollected > 0 ? round(((float)$tc['collected'] / $maxCollected) * 100) : 0;
                    $color = $barColors[$idx % count($barColors)];
                    $idx++;
                ?>
                <div class="db-collector">
                    <div class="db-collector-name"><?= Html::encode($tc['emp_name'] ?: 'غير محدد') ?></div>
                    <div class="db-collector-bar-wrap">
                        <div class="db-collector-bar" style="width:<?= $pct ?>%;background:<?= $color ?>"><?= $pct ?>%</div>
                    </div>
                    <div class="db-collector-amount"><?= number_format($tc['collected'], 0) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($topCollectors)): ?>
                <div style="text-align:center;padding:20px;color:var(--clr-text-muted)">لا توجد بيانات</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Contracts -->
    <div class="db-card" style="margin-bottom:24px">
        <div class="db-card-header">
            <h3><i class="fa fa-file-text"></i> آخر العقود</h3>
            <a href="<?= Url::to(['/contracts/contracts/index']) ?>" class="db-badge" style="text-decoration:none">عرض الكل</a>
        </div>
        <div class="db-card-body" style="padding:0">
            <table class="db-table">
                <thead><tr>
                    <th>#</th><th>العميل</th><th>التاريخ</th><th>القيمة الإجمالية</th><th>القسط الشهري</th><th>الحالة</th>
                </tr></thead>
                <tbody>
                <?php foreach ($recentContracts as $ct): ?>
                <tr>
                    <td><a href="<?= Url::to(['/followUp/follow-up/panel', 'id' => $ct['id']]) ?>" class="db-link">#<?= $ct['id'] ?></a></td>
                    <td><?= Html::encode($ct['customer_name'] ?: '—') ?></td>
                    <td><?= $ct['Date_of_sale'] ?></td>
                    <td class="db-amount"><?= number_format($ct['total_value'], 2) ?></td>
                    <td style="direction:ltr;text-align:left"><?= number_format($ct['monthly_installment_value'], 2) ?></td>
                    <td>
                        <span class="db-status-pill" style="background:<?= isset($statusColors[$ct['status']]) ? $statusColors[$ct['status']] : '#999' ?>">
                            <?= isset($statusLabels[$ct['status']]) ? $statusLabels[$ct['status']] : $ct['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ApexCharts Dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof ApexCharts === 'undefined') return;

    // ─── Income Area Chart (ApexCharts) ───
    var incomeEl = document.getElementById('apexIncomeChart');
    if (incomeEl) {
        new ApexCharts(incomeEl, {
            chart: {
                type: 'area', height: 280, fontFamily: 'Noto Kufi Arabic, Cairo, sans-serif',
                toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
                animations: { enabled: true, easing: 'easeinout', speed: 800, animateGradually: { enabled: true, delay: 150 } },
                dropShadow: { enabled: true, top: 3, left: 0, blur: 6, opacity: 0.15, color: '#800020' }
            },
            series: [{ name: 'الإيرادات (د.أ)', data: <?= json_encode($chartData) ?> }],
            xaxis: {
                categories: <?= json_encode($chartLabels) ?>,
                labels: { style: { fontFamily: 'Noto Kufi Arabic', fontSize: '11px' } }
            },
            yaxis: {
                labels: {
                    formatter: function(v) { return v.toLocaleString(); },
                    style: { fontFamily: 'Noto Kufi Arabic', fontSize: '11px' }
                }
            },
            colors: ['#800020'],
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] }
            },
            stroke: { curve: 'smooth', width: 3 },
            dataLabels: { enabled: false },
            markers: { size: 4, colors: ['#800020'], strokeWidth: 2, hover: { size: 7 } },
            tooltip: {
                y: { formatter: function(v) { return v.toLocaleString() + ' د.أ'; } },
                style: { fontFamily: 'Noto Kufi Arabic' }
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 }
        }).render();
    }

    // ─── Donut Chart (ApexCharts) ───
    var donutEl = document.getElementById('apexDonutChart');
    if (donutEl) {
        new ApexCharts(donutEl, {
            chart: {
                type: 'donut', height: 220, fontFamily: 'Noto Kufi Arabic, Cairo, sans-serif',
                animations: { enabled: true, easing: 'easeinout', speed: 1000, animateGradually: { enabled: true, delay: 100 } }
            },
            series: <?= json_encode($donutData) ?>,
            labels: <?= json_encode($donutLabels) ?>,
            colors: <?= json_encode($donutColors) ?>,
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true, label: 'الإجمالي',
                                formatter: function(w) { return w.globals.seriesTotals.reduce(function(a,b){return a+b;}, 0).toLocaleString(); },
                                style: { fontFamily: 'Noto Kufi Arabic', fontWeight: 700 }
                            }
                        }
                    }
                }
            },
            legend: { show: false },
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ['#fff'] },
            tooltip: {
                y: { formatter: function(v) { return v.toLocaleString() + ' عقد'; } },
                style: { fontFamily: 'Noto Kufi Arabic' }
            }
        }).render();
    }
});
</script>
