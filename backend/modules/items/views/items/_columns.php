<?php
use yii\helpers\Url;

return [
    [
        'class' => 'kartik\grid\CheckboxColumn',
        'width' => '20px',
        'contentOptions' => ['data-label' => ''],
    ],
    [
        'class' => 'kartik\grid\SerialColumn',
        'width' => '30px',
        'contentOptions' => ['data-label' => '#'],
    ],
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'id',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'name',
        'contentOptions' => ['data-label' => 'Name'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'cost',
        'contentOptions' => ['data-label' => 'Cost'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'price',
        'contentOptions' => ['data-label' => 'Price'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'invoice_number',
        'contentOptions' => ['data-label' => 'Invoice number'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'notes',
        'contentOptions' => ['data-label' => 'Notes'],
    ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'is_sold',
    // ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'sold_to',
    // ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign'=>'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function($action, $model, $key, $index) { 
                return Url::to([$action,'id'=>$key]);
        },
        'viewOptions'=>['role'=>'modal-remote','title'=>'View','data-bs-toggle'=>'tooltip', 'data-pjax' => 0],
        'updateOptions'=>['role'=>'modal-remote','title'=>'Update', 'data-bs-toggle'=>'tooltip', 'data-pjax' => 0],
        'deleteOptions'=>['role'=>'modal-remote','title'=>'Delete', 
                          'data-confirm'=>false, 'data-method'=>false,// for overide yii data api
                          'data-request-method'=>'post',
                          'data-bs-toggle'=>'tooltip',
                          'data-confirm-title'=>'Are you sure?',
                          'data-confirm-message'=>'Are you sure want to delete this item', 'data-pjax' => 0], 
    ],

];    
