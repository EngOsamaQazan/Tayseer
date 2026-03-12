<?php

use yii\helpers\Url;

return [
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'id',
    // ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'locations_name',
        'label' => Yii::t('app', 'Location Name'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'company_id',
        'value' => 'company.name',
        'label' => Yii::t('app', 'Company'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'value' => 'createdBy.username',
        'label' => Yii::t('app', 'Created By'),
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
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'last_update_by',
        'value'=>'updateBy.username',
        'label' => Yii::t('app', 'Last Update BY'),
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
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