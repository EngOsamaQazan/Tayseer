<?php
use yii\helpers\Url;

return [

        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'id',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'name',
    ],

    [
        'class' => 'kartik\grid\ActionColumn',
        'contentOptions' => ['data-label' => ''],
        'dropdown' => false,
        'vAlign'=>'middle',
        'template'=>'{delete}{update}',
        'urlCreator' => function($action, $model, $key, $index) { 
                return Url::to([$action,'id'=>$key]);
        },
        'viewOptions'=>['title'=>'View','data-bs-toggle'=>'tooltip'],
        'updateOptions'=>['title'=>'Update', 'data-bs-toggle'=>'tooltip'],
        'deleteOptions'=>['title'=>'Delete',
                          'data-confirm'=>false, 'data-method'=>false,// for overide yii data api
                          'data-request-method'=>'post',
                          'data-bs-toggle'=>'tooltip',
                          'data-confirm-title'=>'Are you sure?',
                          'data-confirm-message'=>'Are you sure want to delete this item'], 
    ],

];   