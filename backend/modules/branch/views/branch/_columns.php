<?php

use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\branch\models\Branch;

return [
    [
        'class' => 'kartik\grid\CheckboxColumn',
        'width' => '20px',
    ],
    [
        'class' => 'kartik\grid\SerialColumn',
        'width' => '30px',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'code',
        'width' => '100px',
        'value' => function ($model) {
            return Html::tag('span', $model->code, ['class' => 'badge bg-light text-dark', 'style' => 'font-family:monospace']);
        },
        'format' => 'raw',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'value' => function ($model) {
            $icon = match($model->branch_type) {
                'hq' => 'fa-building',
                'branch' => 'fa-code-branch',
                'warehouse' => 'fa-warehouse',
                'client_site' => 'fa-map-marker-alt',
                'field_area' => 'fa-map',
                default => 'fa-map-pin',
            };
            return '<i class="fa ' . $icon . ' text-muted me-1"></i> ' . Html::encode($model->name);
        },
        'format' => 'raw',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'branch_type',
        'width' => '130px',
        'value' => function ($model) {
            return $model->getTypeLabel();
        },
        'filter' => Branch::getTypeLabels(),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'address',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'phone',
        'width' => '120px',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'manager_id',
        'label' => 'مدير الفرع',
        'value' => function ($model) {
            return $model->manager ? $model->manager->username : '—';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'is_active',
        'width' => '90px',
        'value' => function ($model) {
            return $model->is_active
                ? '<span class="badge bg-success">فعّال</span>'
                : '<span class="badge bg-secondary">معطّل</span>';
        },
        'format' => 'raw',
        'filter' => [1 => 'فعّال', 0 => 'معطّل'],
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['role' => 'modal-remote', 'title' => 'عرض', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['role' => 'modal-remote', 'title' => 'تعديل', 'data-toggle' => 'tooltip'],
        'deleteOptions' => [
            'title' => 'حذف',
            'data-confirm' => false,
            'data-method' => false,
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'تأكيد الحذف',
            'data-confirm-message' => 'هل أنت متأكد من حذف هذا الفرع؟',
        ],
    ],
];
