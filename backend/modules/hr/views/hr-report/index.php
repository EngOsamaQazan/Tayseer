<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  تقارير الموارد البشرية — HR Reports Hub
 *  ──────────────────────────────────────
 *  شبكة بطاقات التقارير المتاحة مع أيقونات ووصف مختصر
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */

$this->title = 'تقارير الموارد البشرية';

/* ─── Register HR CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── Report cards configuration ─── */
$reports = [
    [
        'title'       => 'تحليلات الحضور',
        'description' => 'لوحة تحكم شاملة لتحليل بيانات الحضور والتأخير والغياب مع رسوم بيانية',
        'icon'        => 'fa-bar-chart',
        'link'        => Url::to(['/hr/hr-tracking-report/index']),
        'color'       => '#27ae60',
    ],
    [
        'title'       => 'التقرير الشهري',
        'description' => 'ملخص الحضور الشهري لكل موظف مع إجمالي أيام الحضور والغياب والتأخير',
        'icon'        => 'fa-calendar-check-o',
        'link'        => Url::to(['/hr/hr-tracking-report/monthly']),
        'color'       => '#3498db',
    ],
    [
        'title'       => 'تقرير الانضباط',
        'description' => 'نسبة انضباط كل موظف بناءً على الالتزام بمواعيد الحضور والانصراف',
        'icon'        => 'fa-star-half-o',
        'link'        => Url::to(['/hr/hr-tracking-report/punctuality']),
        'color'       => '#e67e22',
    ],
    [
        'title'       => 'المخالفات الأمنية',
        'description' => 'رصد استخدام المواقع الوهمية والخروج من نطاق العمل المحدد',
        'icon'        => 'fa-shield',
        'link'        => Url::to(['/hr/hr-tracking-report/violations']),
        'color'       => '#e74c3c',
    ],
    [
        'title'       => 'التتبع المباشر',
        'description' => 'خريطة مباشرة لتتبع مواقع الموظفين الميدانيين في الوقت الفعلي',
        'icon'        => 'fa-map-marker',
        'link'        => Url::to(['/hr/hr-tracking-api/live-map']),
        'color'       => '#9b59b6',
    ],
    [
        'title'       => 'سجل الحضور الموحّد',
        'description' => 'عرض تفصيلي لجميع سجلات الحضور والانصراف مع فلاتر متقدمة',
        'icon'        => 'fa-list-alt',
        'link'        => Url::to(['/hr/hr-tracking-api/attendance-board']),
        'color'       => '#2c3e50',
    ],
    [
        'title'       => 'تقرير الرواتب',
        'description' => 'ملخص مسيرات الرواتب والبدلات والخصومات الشهرية',
        'icon'        => 'fa-money',
        'link'        => Url::to(['/hr/hr-payroll/index']),
        'color'       => '#800020',
    ],
    [
        'title'       => 'تقرير الإجازات',
        'description' => 'أرصدة الإجازات المستحقة والمستخدمة وطلبات الإجازة',
        'icon'        => 'fa-calendar',
        'link'        => Url::to(['/hr/hr-leave/index']),
        'color'       => '#f39c12',
    ],
];
?>

<style>
/* ═══════════════════════════════════════
   Reports Hub — Card Grid Styles
   ═══════════════════════════════════════ */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
@media (max-width: 1100px) {
    .reports-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .reports-grid { grid-template-columns: 1fr; }
}

.report-card {
    background: var(--hr-card-bg, #fff);
    border-radius: var(--hr-radius-md, 10px);
    box-shadow: var(--hr-shadow-sm);
    padding: 28px 24px;
    display: flex; flex-direction: column; align-items: flex-start;
    gap: 14px;
    transition: var(--hr-transition);
    border-right: 4px solid var(--report-color, var(--hr-primary, #800020));
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
}
.report-card::after {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(135deg, transparent 80%, rgba(128,0,32,0.03));
    pointer-events: none;
}
.report-card:hover {
    box-shadow: var(--hr-shadow-hover);
    transform: translateY(-3px);
    text-decoration: none;
    color: inherit;
}
.report-card:active {
    transform: translateY(-1px);
}

.report-card__icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: #fff;
    background: var(--report-color, var(--hr-primary, #800020));
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    flex-shrink: 0;
}
.report-card__title {
    font-size: 16px; font-weight: 700;
    color: var(--hr-text, #2c3e50);
    margin: 0; line-height: 1.4;
}
.report-card__desc {
    font-size: 13px; color: var(--hr-text-light, #7f8c8d);
    line-height: 1.6; margin: 0;
}
.report-card__arrow {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 600;
    color: var(--report-color, var(--hr-primary, #800020));
    margin-top: auto;
    transition: var(--hr-transition-fast, all 0.15s ease);
}
.report-card:hover .report-card__arrow {
    gap: 10px;
}
.report-card__arrow i {
    font-size: 12px;
    transition: transform 0.2s ease;
}
.report-card:hover .report-card__arrow i {
    transform: translateX(-4px);
}
</style>

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  العنوان                               ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-header">
        <h1><i class="fa fa-bar-chart"></i> <?= Html::encode($this->title) ?></h1>
        <div class="hr-date-badge">
            <i class="fa fa-calendar"></i>
            <?= date('Y-m-d') ?>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  شبكة بطاقات التقارير                  ║
         ╚═══════════════════════════════════════╝ -->
    <div class="reports-grid">
        <?php foreach ($reports as $report): ?>
        <a href="<?= $report['link'] ?>" class="report-card" style="--report-color: <?= $report['color'] ?>">
            <div class="report-card__icon">
                <i class="fa <?= $report['icon'] ?>"></i>
            </div>
            <h3 class="report-card__title"><?= Html::encode($report['title']) ?></h3>
            <p class="report-card__desc"><?= Html::encode($report['description']) ?></p>
            <span class="report-card__arrow">
                عرض التقرير <i class="fa fa-arrow-left"></i>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

</div><!-- /.hr-page -->
