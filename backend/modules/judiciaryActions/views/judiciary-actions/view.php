<?php
/**
 * عرض تفاصيل الإجراء القضائي
 */
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب إجرائي'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب / مذكرة'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة كتاب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراء إداري'],
];

$n = $model->action_nature ?: 'process';
$ns = $natureStyles[$n] ?? $natureStyles['process'];

$allNames = (new \yii\db\Query())->select(['id', 'name'])->from('os_judiciary_actions')->all();
$nameMap = [];
foreach ($allNames as $a) $nameMap[$a['id']] = $a['name'];

$usageCount = (int)(new \yii\db\Query())
    ->from('os_judiciary_customers_actions')
    ->where(['judiciary_actions_id' => $model->id, 'is_deleted' => 0])
    ->count();
?>

<style>
.ja-view { direction:rtl;font-family:'Tajawal',sans-serif;font-size:13px; }
.ja-view-header { text-align:center;padding:16px;margin-bottom:14px;border-radius:12px; }
.ja-view-header i { font-size:28px; }
.ja-view-header h3 { margin:6px 0 2px;font-weight:800;color:#1E293B; }
.ja-view-header .nature-tag { padding:3px 12px;border-radius:8px;font-size:11px;font-weight:600;color:#fff;display:inline-block; }
.ja-view-row { display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #F1F5F9;align-items:center; }
.ja-view-label { font-weight:700;color:#64748B;font-size:11px;width:120px;flex-shrink:0; }
.ja-view-val { color:#1E293B; }
.ja-rel-pill { display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;margin:2px;font-weight:500; }
</style>

<div class="ja-view">
    <div class="ja-view-header" style="background:<?= $ns['bg'] ?>;border:1px solid <?= $ns['color'] ?>30">
        <i class="fa <?= $ns['icon'] ?>" style="color:<?= $ns['color'] ?>"></i>
        <h3><?= Html::encode($model->name) ?></h3>
        <span class="nature-tag" style="background:<?= $ns['color'] ?>"><i class="fa <?= $ns['icon'] ?>"></i> <?= $ns['label'] ?></span>
    </div>

    <div class="ja-view-row">
        <div class="ja-view-label"># المعرف</div>
        <div class="ja-view-val" style="font-family:monospace;font-weight:700"><?= $model->id ?></div>
    </div>
    <div class="ja-view-row">
        <div class="ja-view-label">المرحلة</div>
        <div class="ja-view-val">
            <span style="padding:2px 8px;border-radius:6px;background:#F1F5F9;font-size:11px"><?= Html::encode($model->getActionTypeLabel()) ?></span>
        </div>
    </div>
    <div class="ja-view-row">
        <div class="ja-view-label">الاستخدام</div>
        <div class="ja-view-val">
            <?php if ($usageCount > 0): ?>
            <span style="font-weight:700;color:#16A34A"><?= $usageCount ?></span> مرة
            <?php else: ?>
            <span style="color:#94A3B8">لم يستخدم بعد</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="ja-view-row">
        <div class="ja-view-label">الحالة</div>
        <div class="ja-view-val">
            <?php if ($model->is_deleted): ?>
            <span style="padding:2px 8px;border-radius:6px;background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:600">محذوف</span>
            <?php else: ?>
            <span style="padding:2px 8px;border-radius:6px;background:#DCFCE7;color:#166534;font-size:11px;font-weight:600">فعال</span>
            <?php endif; ?>
        </div>
    </div>

    <?php $docIds = $model->getAllowedDocumentIds(); if (!empty($docIds)): ?>
    <div class="ja-view-row" style="flex-wrap:wrap">
        <div class="ja-view-label">كتب مسموحة</div>
        <div class="ja-view-val">
            <?php foreach ($docIds as $did): ?>
            <span class="ja-rel-pill" style="background:#F5F3FF;color:#7C3AED"><?= Html::encode($nameMap[$did] ?? '#'.$did) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php $stIds = $model->getAllowedStatusIds(); if (!empty($stIds)): ?>
    <div class="ja-view-row" style="flex-wrap:wrap">
        <div class="ja-view-label">حالات مسموحة</div>
        <div class="ja-view-val">
            <?php foreach ($stIds as $sid): ?>
            <span class="ja-rel-pill" style="background:#FFF7ED;color:#C2410C"><?= Html::encode($nameMap[$sid] ?? '#'.$sid) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php $prIds = $model->getParentRequestIdList(); if (!empty($prIds)): ?>
    <div class="ja-view-row" style="flex-wrap:wrap">
        <div class="ja-view-label">يتبع لـ</div>
        <div class="ja-view-val">
            <?php foreach ($prIds as $pid): ?>
            <span class="ja-rel-pill" style="background:#EFF6FF;color:#2563EB"><?= Html::encode($nameMap[$pid] ?? '#'.$pid) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
