<?php

use yii\helpers\Html;

$this->title = 'التحليل الذكي والتوصيات';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$severityConfig = [
    'danger'  => ['color' => '#dc3545', 'bg' => '#fdf0f0', 'border' => '#f5c6cb', 'label' => 'حرج', 'icon' => 'fa-times-circle'],
    'warning' => ['color' => '#856404', 'bg' => '#fff3cd', 'border' => '#ffeeba', 'label' => 'تنبيه', 'icon' => 'fa-exclamation-triangle'],
    'info'    => ['color' => '#0c5460', 'bg' => '#d1ecf1', 'border' => '#bee5eb', 'label' => 'معلومة', 'icon' => 'fa-info-circle'],
    'success' => ['color' => '#155724', 'bg' => '#d4edda', 'border' => '#c3e6cb', 'label' => 'إيجابي', 'icon' => 'fa-check-circle'],
];
?>

<!-- Summary Cards -->
<div class="row" style="margin-bottom:25px;">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid #dc3545;">
            <div class="stat-value" style="color:#dc3545; font-size:36px;"><?= $summary['danger'] ?></div>
            <div class="stat-label"><i class="fa fa-times-circle"></i> تنبيهات حرجة</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid #ffc107;">
            <div class="stat-value" style="color:#856404; font-size:36px;"><?= $summary['warning'] ?></div>
            <div class="stat-label"><i class="fa fa-exclamation-triangle"></i> تحذيرات</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid #17a2b8;">
            <div class="stat-value" style="color:#0c5460; font-size:36px;"><?= $summary['info'] ?></div>
            <div class="stat-label"><i class="fa fa-info-circle"></i> معلومات</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid #28a745;">
            <div class="stat-value" style="color:#155724; font-size:36px;"><?= $summary['success'] ?></div>
            <div class="stat-label"><i class="fa fa-check-circle"></i> مؤشرات إيجابية</div>
        </div>
    </div>
</div>

<!-- Insights List -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-lightbulb-o"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <span class="text-muted" style="font-size:12px;"><i class="fa fa-clock-o"></i> آخر تحديث: <?= date('Y-m-d H:i') ?></span>
        </div>
    </div>
    <div class="box-body">
        <?php if (empty($insights)): ?>
            <div class="text-center" style="padding:60px;">
                <i class="fa fa-thumbs-up fa-4x text-success"></i>
                <h3 style="margin-top:20px; color:#28a745;">كل شيء يبدو ممتازاً!</h3>
                <p class="text-muted">لا توجد تنبيهات أو توصيات حالياً. استمر بالعمل الرائع.</p>
            </div>
        <?php else: ?>
            <?php foreach ($insights as $insight):
                $cfg = $severityConfig[$insight['severity']] ?? $severityConfig['info'];
            ?>
            <div style="background:<?= $cfg['bg'] ?>; border:1px solid <?= $cfg['border'] ?>; border-radius:8px; padding:16px 20px; margin-bottom:12px; display:flex; align-items:flex-start; gap:15px;">
                <div style="flex-shrink:0; width:40px; height:40px; border-radius:50%; background:<?= $cfg['color'] ?>; display:flex; align-items:center; justify-content:center;">
                    <i class="fa <?= $insight['icon'] ?? $cfg['icon'] ?>" style="color:white; font-size:18px;"></i>
                </div>
                <div style="flex:1;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                        <strong style="font-size:15px; color:<?= $cfg['color'] ?>;"><?= Html::encode($insight['title']) ?></strong>
                        <span style="background:<?= $cfg['color'] ?>; color:white; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700;"><?= $cfg['label'] ?></span>
                        <?php if (isset($insight['category'])): ?>
                        <span style="background:rgba(0,0,0,0.06); padding:2px 8px; border-radius:10px; font-size:10px;"><?= Html::encode($insight['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p style="margin:0; color:<?= $cfg['color'] ?>; font-size:13px; line-height:1.6;"><?= Html::encode($insight['message']) ?></p>
                    <?php if (isset($insight['action'])): ?>
                    <div style="margin-top:8px;">
                        <?= Html::a('<i class="fa fa-arrow-left"></i> ' . ($insight['action_label'] ?? 'عرض'), [$insight['action']], [
                            'class' => 'btn btn-xs',
                            'style' => "background:{$cfg['color']}; color:white; border:none; border-radius:4px; font-size:11px;",
                        ]) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="box box-info" style="margin-top:10px;">
    <div class="box-body">
        <div class="row">
            <div class="col-md-8">
                <h4 style="margin-top:0;"><i class="fa fa-info-circle"></i> عن التحليل الذكي</h4>
                <p class="text-muted" style="margin:0; line-height:1.8;">
                    يعمل النظام بتقنية <strong>التحليل القائم على القواعد الذكية (Rule-Based AI)</strong> لفحص بياناتك المالية تلقائياً.
                    يتم تحليل الذمم المدينة والدائنة، الموازنات، التدفقات النقدية، والانحرافات لتقديم توصيات فورية.
                    <br>النظام مُصمم للتطوير المستقبلي بإضافة نماذج تعلم آلي متقدمة عبر API خارجي.
                </p>
            </div>
            <div class="col-md-4 text-center" style="padding-top:15px;">
                <div style="background:#e8f4fd; border-radius:50%; width:80px; height:80px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                    <i class="fa fa-rocket" style="font-size:35px; color:#17a2b8;"></i>
                </div>
                <p style="font-size:12px; color:#666;">Hybrid AI — Ready for API Extension</p>
            </div>
        </div>
    </div>
</div>
