<?php
/**
 * عرض تفاصيل إجراء عميل قضائي — تصميم OCP
 */
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;

/* @var $model backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions */

$actionDef = JudiciaryActions::findOne($model->judiciary_actions_id);
$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب إجرائي'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب / مذكرة'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة كتاب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراء إداري'],
];

$nature = $actionDef ? ($actionDef->action_nature ?: 'process') : 'process';
$ns = $natureStyles[$nature] ?? $natureStyles['process'];

$statusLabels = ['pending' => ['معلق', '#F59E0B', '#FFFBEB'], 'approved' => ['موافقة', '#10B981', '#ECFDF5'], 'rejected' => ['مرفوض', '#EF4444', '#FEF2F2']];
?>

<style>
.jcav { font-family:'Tajawal',sans-serif;direction:rtl;font-size:13px;color:#1E293B; }
.jcav-header { text-align:center;padding:14px;margin-bottom:12px;border-radius:10px; }
.jcav-header i { font-size:24px; }
.jcav-header h3 { margin:4px 0 2px;font-weight:800;font-size:15px;color:#1E293B; }
.jcav-nature-tag { padding:2px 10px;border-radius:8px;font-size:10px;font-weight:600;color:#fff;display:inline-block; }
.jcav-row { display:flex;gap:8px;padding:6px 0;border-bottom:1px solid #F1F5F9;align-items:center; }
.jcav-label { font-weight:700;color:#64748B;font-size:11px;width:100px;flex-shrink:0; }
.jcav-val { color:#1E293B;font-size:13px; }
.jcav-status { padding:2px 10px;border-radius:8px;font-size:11px;font-weight:600;display:inline-block; }
</style>

<div class="jcav">
    <!-- Header -->
    <div class="jcav-header" style="background:<?= $ns['bg'] ?>;border:1px solid <?= $ns['color'] ?>30">
        <i class="fa <?= $ns['icon'] ?>" style="color:<?= $ns['color'] ?>"></i>
        <h3><?= $actionDef ? Html::encode($actionDef->name) : '#' . $model->judiciary_actions_id ?></h3>
        <span class="jcav-nature-tag" style="background:<?= $ns['color'] ?>"><?= $ns['label'] ?></span>
    </div>

    <div class="jcav-row">
        <div class="jcav-label"># المعرف</div>
        <div class="jcav-val" style="font-family:monospace;font-weight:700"><?= $model->id ?></div>
    </div>

    <div class="jcav-row">
        <div class="jcav-label">القضية</div>
        <div class="jcav-val">
            <?php
            $jud = $model->judiciary;
            echo $jud ? Html::encode($jud->judiciary_number . '/' . $jud->year) : '#' . $model->judiciary_id;
            ?>
        </div>
    </div>

    <div class="jcav-row">
        <div class="jcav-label">العميل</div>
        <div class="jcav-val">
            <?php
            $cust = $model->customers;
            echo $cust ? Html::encode($cust->name) : '#' . $model->customers_id;
            ?>
        </div>
    </div>

    <div class="jcav-row">
        <div class="jcav-label">تاريخ الإجراء</div>
        <div class="jcav-val"><?= $model->action_date ?: '<span style="color:#94A3B8">—</span>' ?></div>
    </div>

    <?php if ($nature === 'request' && $model->request_status): ?>
    <div class="jcav-row">
        <div class="jcav-label">حالة الطلب</div>
        <div class="jcav-val">
            <?php $sl = $statusLabels[$model->request_status] ?? ['—', '#94A3B8', '#F1F5F9']; ?>
            <span class="jcav-status" style="background:<?= $sl[2] ?>;color:<?= $sl[1] ?>"><?= $sl[0] ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($model->request_target): ?>
    <div class="jcav-row">
        <div class="jcav-label">جهة الطلب</div>
        <div class="jcav-val">
            <?php
            $targets = ['judge' => 'القاضي', 'accounting' => 'المحاسبة', 'other' => 'أخرى'];
            echo $targets[$model->request_target] ?? $model->request_target;
            ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($model->amount > 0): ?>
    <div class="jcav-row">
        <div class="jcav-label">المبلغ</div>
        <div class="jcav-val" style="font-weight:700;color:#059669"><?= number_format($model->amount, 2) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($model->parent_id): ?>
    <div class="jcav-row">
        <div class="jcav-label">يتبع لـ</div>
        <div class="jcav-val">
            <?php
            $parent = \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::findOne($model->parent_id);
            if ($parent) {
                $pAction = JudiciaryActions::findOne($parent->judiciary_actions_id);
                echo Html::encode(($pAction ? $pAction->name : '#' . $parent->judiciary_actions_id) . ($parent->action_date ? ' · ' . substr($parent->action_date, 0, 10) : ''));
            } else {
                echo '#' . $model->parent_id;
            }
            ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($model->decision_text): ?>
    <div class="jcav-row" style="flex-direction:column;align-items:stretch">
        <div class="jcav-label">نص القرار</div>
        <div class="jcav-val" style="padding:6px 10px;background:#F8FAFC;border-radius:6px;margin-top:4px"><?= nl2br(Html::encode($model->decision_text)) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($model->note): ?>
    <div class="jcav-row" style="flex-direction:column;align-items:stretch">
        <div class="jcav-label">ملاحظات</div>
        <div class="jcav-val" style="padding:6px 10px;background:#F8FAFC;border-radius:6px;margin-top:4px"><?= nl2br(Html::encode($model->note)) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($model->image): ?>
    <div class="jcav-row" style="flex-direction:column;align-items:stretch">
        <div class="jcav-label">المرفق</div>
        <div class="jcav-val" style="margin-top:4px">
            <?php
            <?php $imgUrl = \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::resolveImageUrl($model->image); ?>
            <?php $ext = strtolower(pathinfo($model->image, PATHINFO_EXTENSION)); ?>
            <?php if ($ext === 'pdf'): ?>
                <a href="<?= $imgUrl ?>" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#FEF2F2;border-radius:8px;color:#DC2626;text-decoration:none">
                    <i class="fa fa-file-pdf-o" style="font-size:18px"></i> عرض الملف
                </a>
            <?php else: ?>
                <img src="<?= $imgUrl ?>" style="max-width:200px;border-radius:8px;border:1px solid #E2E8F0" alt="">
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="jcav-row">
        <div class="jcav-label">المنشئ</div>
        <div class="jcav-val">
            <?php $creator = $model->createdBy; echo $creator ? Html::encode($creator->username) : '#' . $model->created_by; ?>
            <span style="color:#94A3B8;font-size:10px;margin-right:4px"><?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '' ?></span>
        </div>
    </div>

    <!-- Child actions (documents/statuses under this action) -->
    <?php
    $children = \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::find()
        ->where(['parent_id' => $model->id, 'is_deleted' => 0])
        ->orderBy(['action_date' => SORT_ASC])
        ->all();
    if (!empty($children)):
    ?>
    <div style="margin-top:12px;padding-top:10px;border-top:2px solid #E2E8F0">
        <div style="font-weight:700;color:#475569;font-size:12px;margin-bottom:8px"><i class="fa fa-sitemap"></i> الإجراءات المتفرعة (<?= count($children) ?>)</div>
        <?php foreach ($children as $child):
            $childDef = JudiciaryActions::findOne($child->judiciary_actions_id);
            $cn = $childDef ? ($childDef->action_nature ?: 'process') : 'process';
            $cns = $natureStyles[$cn] ?? $natureStyles['process'];
        ?>
        <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:<?= $cns['bg'] ?>;border-radius:8px;margin-bottom:4px;border:1px solid <?= $cns['color'] ?>20">
            <i class="fa <?= $cns['icon'] ?>" style="color:<?= $cns['color'] ?>;font-size:12px"></i>
            <span style="font-weight:600;font-size:12px"><?= $childDef ? Html::encode($childDef->name) : '#' . $child->judiciary_actions_id ?></span>
            <span style="font-size:10px;color:#64748B"><?= $child->action_date ? substr($child->action_date, 0, 10) : '' ?></span>
            <?php if ($child->request_status):
                $csl = $statusLabels[$child->request_status] ?? ['—', '#94A3B8', '#F1F5F9'];
            ?>
            <span class="jcav-status" style="background:<?= $csl[2] ?>;color:<?= $csl[1] ?>;font-size:9px;padding:1px 6px"><?= $csl[0] ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
