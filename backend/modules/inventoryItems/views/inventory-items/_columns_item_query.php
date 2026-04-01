<?php
use yii\helpers\Url;

return [
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'id',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'item_name',
        'contentOptions' => ['data-label' => 'Item name'],
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'remaining_amount',
        'contentOptions' => ['data-label' => 'Remaining'],
        'value'=>function($model){
    return $model->remaining_amount;
        }
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
