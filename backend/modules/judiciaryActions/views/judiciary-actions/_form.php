<?php
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use backend\modules\judiciaryActions\models\JudiciaryActions;

/* @var $model JudiciaryActions */

$isNew = $model->isNewRecord;

$allActions = (new \yii\db\Query())
    ->select(['id', 'name', 'action_nature', 'parent_request_ids'])
    ->from('os_judiciary_actions')
    ->where(['or', ['is_deleted' => 0], ['is_deleted' => null]])
    ->orderBy(['name' => SORT_ASC])
    ->all();

$natureLabels = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلبات'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتب / مذكرات'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالات كتب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراءات إدارية'],
];

$grouped = [];
foreach ($allActions as $a) {
    if (!$isNew && $a['id'] == $model->id) continue;
    $n = $a['action_nature'] ?: 'process';
    $grouped[$n][] = $a;
}

$natureStyles = [
    'request'  => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب إجرائي',  'desc' => 'طلب يُقدَّم للمحكمة وينتظر قرار'],
    'document' => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب / مذكرة', 'desc' => 'كتاب أو مذكرة رسمية صادرة'],
    'process'  => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراء إداري',  'desc' => 'خطوة إدارية عامة (تجهيز، تسجيل...)'],
];

$currentParents = $model->getParentRequestIdList();

$currentChildren = [];
if (!$isNew) {
    foreach ($allActions as $a) {
        if ($a['id'] == $model->id) continue;
        $pids = !empty($a['parent_request_ids']) ? array_map('intval', explode(',', $a['parent_request_ids'])) : [];
        if (in_array($model->id, $pids)) {
            $currentChildren[] = (int)$a['id'];
        }
    }
}

$renderGroupedList = function($fieldName, $checkedIds) use ($grouped, $natureLabels) {
    $html = '';
    foreach ($natureLabels as $nk => $nl) {
        $items = $grouped[$nk] ?? [];
        if (empty($items)) continue;
        $checkedCount = 0;
        foreach ($items as $a) {
            if (in_array((int)$a['id'], $checkedIds)) $checkedCount++;
        }
        $html .= '<div class="ms-group">';
        $html .= '<div class="ms-group-header" style="background:' . $nl['bg'] . ';color:' . $nl['color'] . '">'
            . '<i class="fa ' . $nl['icon'] . '" style="font-size:10px"></i> '
            . '<span>' . $nl['label'] . '</span>'
            . '<span class="ms-group-count">' . count($items) . ($checkedCount > 0 ? ' · <b>' . $checkedCount . ' مختار</b>' : '') . '</span>'
            . '</div>';
        foreach ($items as $a) {
            $checked = in_array((int)$a['id'], $checkedIds) ? 'checked' : '';
            $html .= '<label class="ms-item" data-search-text="' . Html::encode($a['name']) . ' #' . $a['id'] . '" data-nature="' . $nk . '">'
                . '<input type="checkbox" name="' . $fieldName . '[]" value="' . $a['id'] . '" ' . $checked . '>'
                . '<i class="fa ' . $nl['icon'] . ' ms-nature-icon" style="color:' . $nl['color'] . '"></i>'
                . '<span>' . Html::encode($a['name']) . '</span>'
                . '<span class="ms-id">#' . $a['id'] . '</span>'
                . '</label>';
        }
        $html .= '</div>';
    }
    return $html;
};
?>

<style>
.jaf-def { direction:rtl;font-family:'Tajawal',sans-serif;font-size:13px;color:#1E293B; }
.jaf-def *,.jaf-def *:before,.jaf-def *:after { box-sizing:border-box; }

.jaf-def .sec { margin-bottom:14px;padding:14px 16px;background:#FAFBFC;border-radius:10px;border:1px solid #E2E8F0; }
.jaf-def .sec-title { font-size:13px;font-weight:700;color:#334155;margin-bottom:10px;display:flex;align-items:center;gap:6px; }

.jaf-def .nature-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:8px; }
.jaf-def .nature-card {
    display:flex;align-items:center;gap:10px;padding:10px 14px;
    border-radius:10px;border:2px solid #E2E8F0;background:#fff;
    cursor:pointer;transition:all .2s;
}
.jaf-def .nature-card:hover { border-color:#93C5FD;background:#F0F9FF; }
.jaf-def .nature-card.selected { box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.jaf-def .nature-card-icon {
    width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    font-size:16px;flex-shrink:0;
}
.jaf-def .nature-card-name { font-weight:700;font-size:13px; }
.jaf-def .nature-card-desc { font-size:10px;color:#94A3B8;margin-top:1px; }

.jaf-def .fi { width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:13px;outline:none;transition:border .2s;background:#fff;font-family:inherit; }
.jaf-def .fi:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.08); }
.jaf-def .fl { font-size:11px;font-weight:600;color:#64748B;margin-bottom:4px;display:block; }

.jaf-def .ms-wrap { border:1px solid #E2E8F0;border-radius:8px;background:#fff;overflow:hidden; }
.jaf-def .ms-search { width:100%;padding:7px 10px;border:none;border-bottom:1px solid #E2E8F0;font-size:12px;outline:none;font-family:inherit;direction:rtl;background:#FAFBFC; }
.jaf-def .ms-search:focus { background:#fff;border-bottom-color:#3B82F6; }
.jaf-def .ms-list { max-height:220px;overflow-y:auto; }
.jaf-def .ms-group-header {
    display:flex;align-items:center;gap:5px;padding:5px 10px;font-size:11px;font-weight:700;
    position:sticky;top:0;z-index:1;border-bottom:1px solid #E2E8F0;
}
.jaf-def .ms-group-count { margin-right:auto;font-size:10px;font-weight:400;opacity:.7; }
.jaf-def .ms-item {
    display:flex;align-items:center;gap:8px;padding:6px 10px;
    border-bottom:1px solid #F1F5F9;font-size:12px;cursor:pointer;transition:background .15s;
}
.jaf-def .ms-item:last-child { border-bottom:none; }
.jaf-def .ms-item:hover { background:#F8FAFC; }
.jaf-def .ms-item input[type=checkbox] { accent-color:#3B82F6; }
.jaf-def .ms-item .ms-id { font-size:10px;color:#94A3B8;margin-right:auto;font-family:monospace; }
.jaf-def .ms-item .ms-nature-icon { width:14px;text-align:center;font-size:11px; }

.jaf-def .rel-cols { display:flex;gap:12px;flex-wrap:wrap; }
.jaf-def .rel-col { flex:1;min-width:220px; }
.jaf-def .rel-col-title { font-size:12px;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:5px; }
</style>

<div class="jaf-def">
<?php $form = ActiveForm::begin(['id' => 'ja-def-form']); ?>

<div class="sec">
    <div class="sec-title"><i class="fa fa-pencil" style="color:#3B82F6"></i> المعلومات الأساسية</div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <div style="flex:2;min-width:200px">
            <label class="fl">اسم الإجراء *</label>
            <?= Html::activeTextInput($model, 'name', ['class' => 'fi', 'placeholder' => 'مثال: طلب حسم راتب', 'autofocus' => true]) ?>
        </div>
        <div style="flex:1;min-width:160px">
            <label class="fl">المرحلة القضائية</label>
            <?= Html::activeDropDownList($model, 'action_type', JudiciaryActions::getActionTypeList(), [
                'class' => 'fi',
                'prompt' => '— اختر المرحلة —',
            ]) ?>
        </div>
    </div>
</div>

<div class="sec">
    <div class="sec-title"><i class="fa fa-tags" style="color:#8B5CF6"></i> طبيعة الإجراء</div>
    <?= Html::activeHiddenInput($model, 'action_nature', ['id' => 'ja-nature-input']) ?>
    <div class="nature-grid">
        <?php foreach ($natureStyles as $nk => $ns): ?>
        <div class="nature-card <?= $model->action_nature === $nk ? 'selected' : '' ?>"
             data-nature="<?= $nk ?>"
             style="<?= $model->action_nature === $nk ? 'border-color:'.$ns['color'].';background:'.$ns['bg'] : '' ?>"
             onclick="JADef.selectNature('<?= $nk ?>')">
            <div class="nature-card-icon" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>">
                <i class="fa <?= $ns['icon'] ?>"></i>
            </div>
            <div>
                <div class="nature-card-name" style="color:<?= $ns['color'] ?>"><?= $ns['label'] ?></div>
                <div class="nature-card-desc"><?= $ns['desc'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="sec">
    <div class="sec-title" style="justify-content:space-between">
        <span><i class="fa fa-link" style="color:#3B82F6"></i> الربط مع إجراءات أخرى</span>
        <button type="button" onclick="JADef.clearAll()" style="border:none;background:#FEE2E2;color:#DC2626;font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;cursor:pointer;font-family:inherit;transition:all .2s" onmouseover="this.style.background='#FECACA'" onmouseout="this.style.background='#FEE2E2'"><i class="fa fa-times" style="margin-left:4px;font-size:10px"></i>إلغاء جميع التبعيات</button>
    </div>
    <div class="rel-cols">
        <div class="rel-col">
            <div class="rel-col-title" style="color:#16A34A"><i class="fa fa-arrow-right"></i> إجراءات تسبقه (آباء)</div>
            <div class="ms-wrap">
                <input type="text" class="ms-search" placeholder="ابحث..." oninput="JADef.filterList(this)">
                <div class="ms-list">
                    <?= $renderGroupedList('rel_parent_ids', $currentParents) ?>
                </div>
            </div>
        </div>
        <div class="rel-col">
            <div class="rel-col-title" style="color:#2563EB"><i class="fa fa-arrow-left"></i> إجراءات تلحقه (أبناء)</div>
            <div class="ms-wrap">
                <input type="text" class="ms-search" placeholder="ابحث..." oninput="JADef.filterList(this)">
                <div class="ms-list">
                    <?= $renderGroupedList('rel_child_ids', $currentChildren) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!Yii::$app->request->isAjax): ?>
<div style="padding-top:10px">
    <?= Html::submitButton(
        $isNew ? '<i class="fa fa-plus"></i> إضافة الإجراء' : '<i class="fa fa-save"></i> حفظ التعديلات',
        ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg', 'style' => 'border-radius:10px;font-size:14px;padding:10px 30px']
    ) ?>
</div>
<?php endif; ?>

<?php ActiveForm::end(); ?>
</div>

<script>
var JADef = (function() {
    var natureColors = <?= Json::encode(array_map(function($s) { return ['color'=>$s['color'],'bg'=>$s['bg']]; }, $natureStyles)) ?>;

    function selectNature(nature) {
        $('.nature-card').removeClass('selected').css({borderColor:'#E2E8F0',background:'#fff'});
        var $card = $('.nature-card[data-nature="' + nature + '"]');
        var c = natureColors[nature];
        $card.addClass('selected').css({borderColor:c.color,background:c.bg});
        $('#ja-nature-input').val(nature);
    }

    $(document).ready(function() {
        var currentNature = $('#ja-nature-input').val();
        if (currentNature) selectNature(currentNature);
    });

    function filterList(input) {
        var q = input.value.trim().toLowerCase();
        var wrap = input.nextElementSibling;
        var items = wrap.querySelectorAll('.ms-item');
        var groups = wrap.querySelectorAll('.ms-group');
        for (var i = 0; i < items.length; i++) {
            var text = (items[i].dataset.searchText || '').toLowerCase();
            items[i].style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
        }
        for (var g = 0; g < groups.length; g++) {
            var visible = groups[g].querySelectorAll('.ms-item:not([style*="display: none"])');
            groups[g].style.display = (!q || visible.length > 0) ? '' : 'none';
        }
    }

    function clearAll() {
        $('input[name="rel_parent_ids[]"], input[name="rel_child_ids[]"]').prop('checked', false);
    }

    return { selectNature: selectNature, filterList: filterList, clearAll: clearAll };
})();
</script>
