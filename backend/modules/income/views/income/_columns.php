<?php

use yii\helpers\Url;

return [
    [
        'class' => 'kartik\grid\CheckboxColumn',
        'width' => '20px',
    ],
    [
        'class' => 'kartik\grid\SerialColumn',
        'width' => '30px',
    ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'id',
    // ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'item',
        'label' => 'Item Name',
        'value' => 'item.name'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'customer_id',
        'label' => 'customer name',
        'value' => 'customer.name'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'date',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'cheque_number',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'notes',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'total',
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
                'viewOptions' => ['role' => 'modal-remote', 'title' => 'View', 'data-bs-toggle' => 'tooltip', 'data-pjax' => 0],
                'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-bs-toggle' => 'tooltip', 'data-pjax' => 0],
                'deleteOptions' => ['role' => 'modal-remote', 'title' => 'Delete',
                    'data-confirm' => false, 'data-method' => false, // for overide yii data api
                    'data-request-method' => 'post',
                    'data-bs-toggle' => 'tooltip',
                    'data-pjax' => 0,
                    'data-confirm-title' => 'Are you sure?',
                    'data-confirm-message' => 'Are you sure want to delete this item'],
    ],
];
