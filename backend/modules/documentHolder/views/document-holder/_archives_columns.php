<?php

use yii\helpers\Url;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'approved_by_manager',
        'value' => 'approvedByManager.username'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'approved_by_employee',
        'value' => function ($model) {
            if (empty($model->approved_by_employee) || $model->ready == 0) {
                return 'لا';
            } else {
                return 'نعم';
            }
        }
    ],
    /* [
         'class'=>'\kartik\grid\DataColumn',
         'attribute'=>'approved_at',
     ],*/
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'reason',

    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'type',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'ready',
        'value' => function ($model) {
            return '<button type="button" class="glyphicon glyphicon-ok "  id = "ready" data-id = "' . $model->id . ' " >

</button> 
 ';
        },
        'format' => 'raw',
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'template' => "{update}",
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['title' => 'View', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['title' => 'Update', 'data-toggle' => 'tooltip'],
        'deleteOptions' => ['title' => 'Delete',
            'data-confirm' => false, 'data-method' => false,// for overide yii data api
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'Are you sure?',
            'data-confirm-message' => 'Are you sure want to delete this item'],
    ],
];