<?php
use yii\helpers\Url;

return [
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'id',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'user_id',
        'value'=>'user.username'
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'movement_number',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'bank_receipt_number',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'financial_value',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'receipt_image',
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign'=>'middle',
        'urlCreator' => function($action, $model, $key, $index) { 
                return Url::to([$action,'id'=>$key]);
        },
        'viewOptions'=>['title'=>'View','data-toggle'=>'tooltip'],
        'updateOptions'=>['title'=>'Update', 'data-toggle'=>'tooltip'],
        'deleteOptions'=>['title'=>'Delete',
                          'data-confirm'=>false, 'data-method'=>false,// for overide yii data api
                          'data-request-method'=>'post',
                          'data-toggle'=>'tooltip',
                          'data-confirm-title'=>'Are you sure?',
                          'data-confirm-message'=>'Are you sure want to delete this item'], 
    ],

];   