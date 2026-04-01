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
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'collection_id',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'month',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'amount',
    ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'created_at',
    // ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'updated_at',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'created_by',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'last_updated_by',
    ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'is_deleted',
    // ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign'=>'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function($action, $model, $key, $index) { 
                return Url::to([$action,'id'=>$key]);
        },
        'viewOptions'=>['role'=>'modal-remote','title'=>'View','data-bs-toggle'=>'tooltip','data-pjax'=>0],
        'updateOptions'=>['role'=>'modal-remote','title'=>'Update', 'data-bs-toggle'=>'tooltip','data-pjax'=>0],
        'deleteOptions'=>['role'=>'modal-remote','title'=>'Delete', 
                          'data-confirm'=>false, 'data-method'=>false,// for overide yii data api
                          'data-request-method'=>'post',
                          'data-bs-toggle'=>'tooltip',
                          'data-pjax'=>0,
                          'data-confirm-title'=>'Are you sure?',
                          'data-confirm-message'=>'Are you sure want to delete this item'], 
    ],

];   