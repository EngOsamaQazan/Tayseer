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
        'attribute'=>'number',
        'contentOptions' => ['data-label' => 'Number'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'single_price',
        'contentOptions' => ['data-label' => 'Single price'],
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
        'contentOptions' => ['data-label' => 'Created by'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'last_updated_by',
        'contentOptions' => ['data-label' => 'Last updated by'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'is_deleted',
        'contentOptions' => ['data-label' => 'Is deleted'],
    ],
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
