<?php
/**
 * لوحة تحكم الموارد البشرية — HR Dashboard
 * تصميم احترافي متوافق مع ثيم Jadal Burgundy
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'الموارد البشرية';

/* ── Register HR CSS ── */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/hr.css', ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]);

/* ── Safe defaults for all variables ── */
$totalEmployees       = isset($totalEmployees) ? (int) $totalEmployees : 0;
$activeEmployees      = isset($activeEmployees) ? (int) $activeEmployees : 0;
$presentToday         = isset($presentToday) ? (int) $presentToday : 0;
$onLeaveToday         = isset($onLeaveToday) ? (int) $onLeaveToday : 0;
$pendingLeaveRequests = isset($pendingLeaveRequests) ? (int) $pendingLeaveRequests : 0;
$departmentHeadcount  = isset($departmentHeadcount) ? $departmentHeadcount : [];
$attendanceRate30d    = isset($attendanceRate30d) ? $attendanceRate30d : (isset($attendanceRate) ? $attendanceRate : 0);
$expiringDocuments    = isset($expiringDocuments) ? (int) $expiringDocuments : 0;
$fieldOnDuty          = isset($fieldOnDuty) ? (int) $fieldOnDuty : (isset($fieldStaffOnDuty) ? (int) $fieldStaffOnDuty : 0);
$latestPayroll        = isset($latestPayroll) ? $latestPayroll : null;
$monthlyPayrollTotal  = isset($monthlyPayrollTotal) ? (float) $monthlyPayrollTotal : 0;
$recentHires          = isset($recentHires) ? $recentHires : [];
$birthdaysSoon        = isset($birthdaysSoon) ? $birthdaysSoon : [];

/* ── Chart data for department doughnut ── */
$deptLabels = [];
$deptData   = [];
$deptColors = ['#800020','#d4a84b','#3498db','#27ae60','#e74c3c','#9b59b6','#f39c12','#1abc9c','#e67e22','#2c3e50','#c0392b','#16a085'];
foreach ($departmentHeadcount as $dept) {
    $deptLabels[] = isset($dept['title']) ? $dept['title'] : (isset($dept['department_name']) ? $dept['department_name'] : '—');
    $deptData[]   = isset($dept['cnt']) ? (int) $dept['cnt'] : (isset($dept['headcount']) ? (int) $dept['headcount'] : 0);
}
?>

<div class="hr-page">

    <!-- ═══════════════════════════════════════
         Header
         ═══════════════════════════════════════ -->
    <div class="hr-header">
        <h1><i class="fa fa-users"></i> الموارد البشرية</h1>
        <div class="hr-date-badge">
            <i class="fa fa-calendar"></i>
            <?= date('l، d F Y') ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Primary KPI Cards (4)
         ═══════════════════════════════════════ -->
    <div class="hr-kpi-grid hr-kpi-grid--4">
        <!-- إجمالي الموظفين -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-primary)">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">إجمالي الموظفين</span>
                <span class="hr-kpi-card__value"><?= number_format($totalEmployees) ?></span>
            </div>
        </div>

        <!-- الحاضرون اليوم -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-success)">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">الحاضرون اليوم</span>
                <span class="hr-kpi-card__value"><?= number_format($presentToday) ?></span>
            </div>
        </div>

        <!-- في إجازة -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-warning)">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-plane"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">في إجازة</span>
                <span class="hr-kpi-card__value"><?= number_format($onLeaveToday) ?></span>
            </div>
        </div>

        <!-- طلبات معلّقة -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-danger)">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-hourglass-half"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">طلبات معلّقة</span>
                <span class="hr-kpi-card__value"><?= number_format($pendingLeaveRequests) ?></span>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Secondary KPI Cards (4)
         ═══════════════════════════════════════ -->
    <div class="hr-kpi-grid hr-kpi-grid--4">
        <!-- الموظفون النشطون -->
        <div class="hr-kpi-card hr-kpi-card--secondary" style="--kpi-accent: var(--hr-info)">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-user-circle"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">الموظفون النشطون</span>
                <span class="hr-kpi-card__value"><?= number_format($activeEmployees) ?></span>
                <?php if ($totalEmployees > 0): ?>
                <span class="hr-kpi-card__sub"><?= round(($activeEmployees / $totalEmployees) * 100) ?>% من الإجمالي</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- معدل الحضور (30 يوم) -->
        <div class="hr-kpi-card hr-kpi-card--secondary" style="--kpi-accent: var(--hr-success)">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-bar-chart"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">معدل الحضور (30 يوم)</span>
                <span class="hr-kpi-card__value"><?= number_format($attendanceRate30d, 1) ?>%</span>
            </div>
        </div>

        <!-- مستندات قاربت الانتهاء -->
        <div class="hr-kpi-card hr-kpi-card--secondary" style="--kpi-accent: var(--hr-warning)">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-file-text-o"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">مستندات قاربت الانتهاء</span>
                <span class="hr-kpi-card__value"><?= number_format($expiringDocuments) ?></span>
                <span class="hr-kpi-card__sub">خلال 30 يوماً</span>
            </div>
        </div>

        <!-- ميداني في مهمة -->
        <div class="hr-kpi-card hr-kpi-card--secondary" style="--kpi-accent: #9b59b6">
            <div class="hr-kpi-card__icon">
                <i class="fa fa-map-marker"></i>
            </div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">ميداني في مهمة</span>
                <span class="hr-kpi-card__value"><?= number_format($fieldOnDuty) ?></span>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Charts Row (2 columns)
         ═══════════════════════════════════════ -->
    <div class="hr-grid-2">
        <!-- Doughnut: Department Headcount -->
        <div class="hr-card">
            <div class="hr-card__header">
                <h3><i class="fa fa-pie-chart"></i> توزيع الموظفين حسب القسم</h3>
                <span class="hr-badge hr-badge--primary"><?= number_format($totalEmployees) ?> موظف</span>
            </div>
            <div class="hr-card__body">
                <?php if (!empty($deptData)): ?>
                <div class="hr-chart-container" style="height: 260px;">
                    <div id="hrDeptChart"></div>
                </div>
                <!-- Legend under chart -->
                <div class="hr-chart-legend">
                    <?php foreach ($departmentHeadcount as $i => $dept):
                        $name = isset($dept['title']) ? $dept['title'] : (isset($dept['department_name']) ? $dept['department_name'] : '—');
                        $count = isset($dept['cnt']) ? (int) $dept['cnt'] : (isset($dept['headcount']) ? (int) $dept['headcount'] : 0);
                        $color = $deptColors[$i % count($deptColors)];
                    ?>
                    <div class="hr-chart-legend__item">
                        <span class="hr-chart-legend__dot" style="background: <?= $color ?>"></span>
                        <span class="hr-chart-legend__label"><?= Html::encode($name) ?></span>
                        <span class="hr-chart-legend__value"><?= number_format($count) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="hr-empty-state">
                    <i class="fa fa-pie-chart"></i>
                    <p>لا توجد بيانات أقسام</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Latest Payroll Summary -->
        <div class="hr-card">
            <div class="hr-card__header">
                <h3><i class="fa fa-money"></i> ملخص آخر مسيرة رواتب</h3>
            </div>
            <div class="hr-card__body">
                <?php if ($latestPayroll !== null): ?>
                <div class="hr-payroll-summary">
                    <div class="hr-payroll-summary__row">
                        <span class="hr-payroll-summary__label">الفترة</span>
                        <span class="hr-payroll-summary__value">
                            <?= Html::encode(isset($latestPayroll->period) ? $latestPayroll->period : (isset($latestPayroll->month) ? $latestPayroll->month . '/' . $latestPayroll->year : '—')) ?>
                        </span>
                    </div>
                    <div class="hr-payroll-summary__row">
                        <span class="hr-payroll-summary__label">الحالة</span>
                        <span class="hr-payroll-summary__value">
                            <?php
                            $prStatus = isset($latestPayroll->status) ? $latestPayroll->status : 'unknown';
                            $prStatusMap = ['draft' => 'مسودة', 'approved' => 'معتمد', 'paid' => 'مدفوع', 'unknown' => '—'];
                            $prStatusClass = ['draft' => 'hr-badge--warning', 'approved' => 'hr-badge--info', 'paid' => 'hr-badge--success'];
                            ?>
                            <span class="hr-badge <?= isset($prStatusClass[$prStatus]) ? $prStatusClass[$prStatus] : 'hr-badge--default' ?>">
                                <?= isset($prStatusMap[$prStatus]) ? $prStatusMap[$prStatus] : $prStatus ?>
                            </span>
                        </span>
                    </div>
                    <div class="hr-payroll-summary__row">
                        <span class="hr-payroll-summary__label">عدد الموظفين</span>
                        <span class="hr-payroll-summary__value"><?= isset($latestPayroll->employee_count) ? number_format($latestPayroll->employee_count) : '—' ?></span>
                    </div>
                    <div class="hr-payroll-summary__total">
                        <span class="hr-payroll-summary__label">إجمالي الرواتب</span>
                        <span class="hr-payroll-summary__amount">
                            <?= isset($latestPayroll->total_amount) ? number_format($latestPayroll->total_amount, 2) : number_format($monthlyPayrollTotal, 2) ?> د.أ
                        </span>
                    </div>
                </div>
                <?php elseif ($monthlyPayrollTotal > 0): ?>
                <!-- Fallback: show total only -->
                <div class="hr-payroll-summary">
                    <div class="hr-payroll-summary__total">
                        <span class="hr-payroll-summary__label">إجمالي آخر مسيرة</span>
                        <span class="hr-payroll-summary__amount"><?= number_format($monthlyPayrollTotal, 2) ?> د.أ</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="hr-empty-state">
                    <i class="fa fa-money"></i>
                    <p>لم يتم تشغيل مسيرة رواتب بعد</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Quick Actions
         ═══════════════════════════════════════ -->
    <div class="hr-card" style="margin-bottom: 24px;">
        <div class="hr-card__header">
            <h3><i class="fa fa-bolt"></i> إجراءات سريعة</h3>
        </div>
        <div class="hr-card__body">
            <div class="hr-quick-actions">
                <a href="<?= Url::to(['/hr/hr-employee/create']) ?>" class="hr-btn hr-btn--primary">
                    <i class="fa fa-user-plus"></i>
                    إضافة مستخدم
                </a>
                <a href="<?= Url::to(['/hr/hr-attendance/create']) ?>" class="hr-btn hr-btn--success">
                    <i class="fa fa-clock-o"></i>
                    تسجيل حضور
                </a>
                <a href="<?= Url::to(['/hr/hr-payroll/run']) ?>" class="hr-btn hr-btn--accent">
                    <i class="fa fa-calculator"></i>
                    تشغيل مسيرة رواتب
                </a>
                <a href="<?= Url::to(['/hr/hr-field/map']) ?>" class="hr-btn hr-btn--info">
                    <i class="fa fa-map"></i>
                    عرض الخريطة
                </a>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Bottom Tables Row
         ═══════════════════════════════════════ -->
    <div class="hr-grid-2">
        <!-- Upcoming Birthdays -->
        <div class="hr-card">
            <div class="hr-card__header">
                <h3><i class="fa fa-birthday-cake"></i> أعياد ميلاد قادمة</h3>
                <span class="hr-badge hr-badge--accent"><?= count($birthdaysSoon) ?></span>
            </div>
            <div class="hr-card__body" style="padding: 0;">
                <?php if (!empty($birthdaysSoon)): ?>
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>القسم</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($birthdaysSoon as $bday): ?>
                        <tr>
                            <td>
                                <div class="hr-table__employee">
                                    <span class="hr-table__avatar" style="background: <?= $deptColors[array_rand($deptColors)] ?>">
                                        <?= mb_substr(Html::encode(isset($bday['name']) ? $bday['name'] : '—'), 0, 1) ?>
                                    </span>
                                    <?= Html::encode(isset($bday['name']) ? $bday['name'] : '—') ?>
                                </div>
                            </td>
                            <td><?= Html::encode(isset($bday['department']) ? $bday['department'] : '—') ?></td>
                            <td>
                                <span class="hr-badge hr-badge--info">
                                    <i class="fa fa-gift"></i>
                                    <?= Html::encode(isset($bday['date']) ? $bday['date'] : (isset($bday['birthday']) ? $bday['birthday'] : '—')) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="hr-empty-state">
                    <i class="fa fa-birthday-cake"></i>
                    <p>لا توجد أعياد ميلاد قادمة</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Hires -->
        <div class="hr-card">
            <div class="hr-card__header">
                <h3><i class="fa fa-user-plus"></i> آخر التعيينات</h3>
                <span class="hr-badge hr-badge--success"><?= count($recentHires) ?></span>
            </div>
            <div class="hr-card__body" style="padding: 0;">
                <?php if (!empty($recentHires)): ?>
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>المسمّى الوظيفي</th>
                            <th>تاريخ التعيين</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentHires as $hire): ?>
                        <tr>
                            <td>
                                <div class="hr-table__employee">
                                    <span class="hr-table__avatar" style="background: <?= $deptColors[array_rand($deptColors)] ?>">
                                        <?= mb_substr(Html::encode(isset($hire['name']) ? $hire['name'] : '—'), 0, 1) ?>
                                    </span>
                                    <?= Html::encode(isset($hire['name']) ? $hire['name'] : '—') ?>
                                </div>
                            </td>
                            <td><?= Html::encode(isset($hire['position']) ? $hire['position'] : (isset($hire['job_title']) ? $hire['job_title'] : '—')) ?></td>
                            <td><?= Html::encode(isset($hire['hire_date']) ? $hire['hire_date'] : (isset($hire['created_at']) ? $hire['created_at'] : '—')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="hr-empty-state">
                    <i class="fa fa-user-plus"></i>
                    <p>لا توجد تعيينات حديثة</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ApexCharts Department Doughnut -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    var deptLabels = <?= json_encode($deptLabels, JSON_UNESCAPED_UNICODE) ?>;
    var deptData   = <?= json_encode($deptData) ?>;
    var deptColors = <?= json_encode(array_slice($deptColors, 0, max(count($deptData), 1))) ?>;

    var chartEl = document.getElementById('hrDeptChart');
    if (chartEl && deptData.length > 0) {
        new ApexCharts(chartEl, {
            chart: {
                type: 'donut', height: 260,
                fontFamily: 'Noto Kufi Arabic, Cairo, sans-serif',
                animations: { enabled: true, easing: 'easeinout', speed: 1000, animateGradually: { enabled: true, delay: 100 } }
            },
            series: deptData,
            labels: deptLabels,
            colors: deptColors,
            plotOptions: {
                pie: {
                    donut: {
                        size: '62%',
                        labels: {
                            show: true,
                            total: {
                                show: true, label: 'الإجمالي',
                                formatter: function(w) { return w.globals.seriesTotals.reduce(function(a,b){return a+b;}, 0).toLocaleString(); },
                                style: { fontFamily: 'Noto Kufi Arabic', fontWeight: 700, fontSize: '16px' }
                            }
                        }
                    }
                }
            },
            legend: { show: false },
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ['#fff'] },
            tooltip: {
                y: {
                    formatter: function(v, opts) {
                        var total = opts.globals.seriesTotals.reduce(function(a,b){return a+b;},0);
                        var pct = total > 0 ? Math.round((v / total) * 100) : 0;
                        return v.toLocaleString() + ' (' + pct + '%)';
                    }
                },
                style: { fontFamily: 'Noto Kufi Arabic' }
            }
        }).render();
    }
});
</script>
