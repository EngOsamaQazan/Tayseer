<?php

use yii\helpers\Url;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'contentOptions' => ['data-label' => 'الاسم'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'address',
        'contentOptions' => ['data-label' => 'العنوان'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'phone_number',
        'contentOptions' => ['data-label' => 'الهاتف'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
        'contentOptions' => ['data-label' => 'الحالة'],
        'value' => function($model) {
            return ($model->status == 0) ? Yii::t('app', 'Active') : Yii::t('app', 'None Active');
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'contentOptions' => ['data-label' => 'أنشأ بواسطة'],
        'value' => 'createdBy.username'
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['title' => 'عرض', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['title' => 'تعديل', 'data-toggle' => 'tooltip'],
        'deleteOptions' => ['title' => 'حذف',
            'data-confirm' => false, 'data-method' => false,
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'هل أنت متأكد؟',
            'data-confirm-message' => 'هل أنت متأكد من حذف هذا العنصر؟'],
    ],
];
