<?php
use yii\helpers\Url;

return [
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'item_id',
        'value'=>'inventoryItems.item_name'
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'locations_id',
        'value'=>'locations.locations_name'
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'inventorySuppliers.name',

    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'quantity',
    ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'created_at',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'created_by',
        'value'=>'createdBy.username'
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'company.name',
        'label'=>'company name'
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