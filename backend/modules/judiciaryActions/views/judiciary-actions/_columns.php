<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة كتاب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إداري'],
];

$allActionsRaw = (new \yii\db\Query())->select(['id', 'name', 'parent_request_ids'])->from('os_judiciary_actions')
    ->where(['or', ['is_deleted' => 0], ['is_deleted' => null]])->all();
$nameMap = [];
$childrenOf = [];
foreach ($allActionsRaw as $an) {
    $nameMap[$an['id']] = $an['name'];
    if (!empty($an['parent_request_ids'])) {
        foreach (explode(',', $an['parent_request_ids']) as $pid) {
            $pid = (int)trim($pid);
            if ($pid > 0) $childrenOf[$pid][] = (int)$an['id'];
        }
    }
}

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'headerOptions' => ['style' => 'width:4%;text-align:center;padding:5px 2px'],
        'contentOptions' => ['style' => 'text-align:center;font-weight:700;color:#94A3B8;font-size:11px;padding:5px 2px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'label' => 'الإجراء',
        'format' => 'raw',
        'value' => function ($model) use ($natureStyles) {
            $n = $model->action_nature ?: 'process';
            $s = $natureStyles[$n] ?? $natureStyles['process'];
            return '<i class="fa ' . $s['icon'] . '" style="color:' . $s['color'] . ';margin-left:3px;font-size:10px"></i>'
                . '<span style="font-weight:600;color:#1E293B;font-size:12px">' . Html::encode($model->name) . '</span>';
        },
        'headerOptions' => ['style' => 'width:28%;padding:5px 4px'],
        'contentOptions' => ['style' => 'padding:5px 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_nature',
        'label' => 'النوع',
        'filter' => JudiciaryActions::getNatureList(),
        'format' => 'raw',
        'value' => function ($model) use ($natureStyles) {
            $n = $model->action_nature ?: 'process';
            $s = $natureStyles[$n] ?? $natureStyles['process'];
            return '<span class="ja-inline-cell" data-id="' . $model->id . '" data-field="action_nature" data-value="' . Html::encode($n) . '"'
                . ' style="display:inline-block;padding:1px 5px;border-radius:5px;font-size:10px;font-weight:600;'
                . 'background:' . $s['bg'] . ';color:' . $s['color'] . ';white-space:nowrap;cursor:pointer;border:1px dashed transparent;transition:all .2s"'
                . ' title="انقر للتعديل">'
                . '<i class="fa fa-pencil" style="font-size:8px;margin-left:3px;opacity:.4"></i>'
                . $s['label'] . '</span>';
        },
        'headerOptions' => ['style' => 'width:7%;padding:5px 2px;text-align:center'],
        'contentOptions' => ['style' => 'padding:5px 2px;text-align:center'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_type',
        'label' => 'المرحلة',
        'filter' => JudiciaryActions::getActionTypeList(),
        'format' => 'raw',
        'value' => function ($model) {
            $label = $model->getActionTypeLabel();
            $val = $model->action_type ?: '';
            return '<span class="ja-inline-cell" data-id="' . $model->id . '" data-field="action_type" data-value="' . Html::encode($val) . '"'
                . ' title="انقر للتعديل"'
                . ' style="font-size:10px;padding:1px 5px;border-radius:5px;background:#F1F5F9;color:#475569;white-space:nowrap;'
                . 'display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis;cursor:pointer;border:1px dashed transparent;transition:all .2s">'
                . '<i class="fa fa-pencil" style="font-size:8px;margin-left:3px;opacity:.4"></i>'
                . Html::encode($label) . '</span>';
        },
        'headerOptions' => ['style' => 'width:14%;padding:5px 2px;text-align:center'],
        'contentOptions' => ['style' => 'padding:5px 2px;text-align:center;overflow:hidden'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العلاقات',
        'format' => 'raw',
        'value' => function ($model) use ($nameMap, $childrenOf) {
            $parts = [];

            $parentIds = $model->getParentRequestIdList();
            if (!empty($parentIds)) {
                $titles = [];
                foreach ($parentIds as $id) $titles[] = $nameMap[$id] ?? '#' . $id;
                $parts[] = '<span title="آباء: ' . Html::encode(implode(', ', $titles)) . '" style="display:inline-block;padding:1px 4px;border-radius:4px;font-size:9px;background:#DCFCE7;color:#16A34A;cursor:help"><i class="fa fa-arrow-right" style="font-size:8px"></i> ' . count($parentIds) . '</span>';
            }

            $myChildren = $childrenOf[$model->id] ?? [];
            if (!empty($myChildren)) {
                $titles = [];
                foreach ($myChildren as $id) $titles[] = $nameMap[$id] ?? '#' . $id;
                $parts[] = '<span title="أبناء: ' . Html::encode(implode(', ', $titles)) . '" style="display:inline-block;padding:1px 4px;border-radius:4px;font-size:9px;background:#DBEAFE;color:#2563EB;cursor:help"><i class="fa fa-arrow-left" style="font-size:8px"></i> ' . count($myChildren) . '</span>';
            }

            return empty($parts) ? '<span style="color:#CBD5E1;font-size:11px">—</span>' : implode(' ', $parts);
        },
        'headerOptions' => ['style' => 'width:12%;padding:5px 2px;text-align:center'],
        'contentOptions' => ['style' => 'padding:4px 2px;text-align:center;white-space:nowrap'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'استخدام',
        'format' => 'raw',
        'value' => function ($model) use ($usageCounts) {
            $count = (int)($usageCounts[$model->id] ?? 0);
            if ($count === 0) return '<span style="color:#CBD5E1;font-size:11px">0</span>';
            $color = $count > 50 ? '#16A34A' : ($count > 10 ? '#2563EB' : '#94A3B8');
            $url = Url::to(['usage-details', 'id' => $model->id]);
            return '<a href="' . $url . '" role="modal-remote" data-pjax="0" title="عرض القضايا المرتبطة" '
                . 'style="font-weight:700;font-size:11px;color:' . $color . ';cursor:pointer;text-decoration:none;'
                . 'display:inline-flex;align-items:center;gap:2px;padding:2px 6px;border-radius:5px;'
                . 'background:' . ($count > 50 ? '#F0FDF4' : ($count > 10 ? '#EFF6FF' : '#F8FAFC')) . '">'
                . number_format($count) . '</a>';
        },
        'contentOptions' => ['style' => 'text-align:center;padding:5px 2px'],
        'headerOptions' => ['style' => 'width:9%;text-align:center;padding:5px 2px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => '',
        'format' => 'raw',
        'value' => function ($model) {
            $id = $model->id;
            return '<div class="ja-action-btns">'
                . Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $id], ['role' => 'modal-remote', 'title' => 'عرض', 'class' => 'ja-act ja-act-view', 'data-pjax' => 0])
                . Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $id], ['role' => 'modal-remote', 'title' => 'تعديل', 'class' => 'ja-act ja-act-edit', 'data-pjax' => 0])
                . Html::a('<i class="fa fa-trash-o"></i>', ['confirm-delete', 'id' => $id], ['role' => 'modal-remote', 'title' => 'حذف', 'class' => 'ja-act ja-act-del', 'data-confirm' => false, 'data-method' => false, 'data-pjax' => 0])
                . '</div>';
        },
        'headerOptions' => ['style' => 'width:10%;text-align:center;padding:5px 2px'],
        'contentOptions' => ['style' => 'padding:4px 2px;text-align:center'],
    ],
];
