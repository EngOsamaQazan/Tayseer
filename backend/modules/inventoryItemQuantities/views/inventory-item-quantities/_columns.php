<?php
use yii\helpers\Url;

return [
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'item_id',
        'value'=>'inventoryItems.item_name',
        'contentOptions' => ['data-label' => 'Item'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'locations_id',
        'value'=>'locations.locations_name',
        'contentOptions' => ['data-label' => 'Location'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'inventorySuppliers.name',
        'contentOptions' => ['data-label' => 'Supplier'],

    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'quantity',
        'contentOptions' => ['data-label' => 'Quantity'],
    ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'created_at',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'created_by',
        'value'=>'createdBy.username',
        'contentOptions' => ['data-label' => 'Created by'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'company.name',
        'label'=>'company name',
        'contentOptions' => ['data-label' => 'Company name'],
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign'=>'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function($action, $model, $key, $index) { 
                return Url::to([$action,'id'=>$key]);
        },
        'viewOptions'=>['title'=>'View','data-bs-toggle'=>'tooltip', 'role' => 'modal-remote', 'data-pjax' => 0],
        'updateOptions'=>['title'=>'Update', 'data-bs-toggle'=>'tooltip', 'role' => 'modal-remote', 'data-pjax' => 0],
        'deleteOptions'=>['title'=>'Delete',
                          'data-confirm'=>false, 'data-method'=>false,// for overide yii data api
                          'data-request-method'=>'post',
                          'data-bs-toggle'=>'tooltip',
                          'data-confirm-title'=>'Are you sure?',
                          'data-confirm-message'=>'Are you sure want to delete this item'], 
    ],

];    
