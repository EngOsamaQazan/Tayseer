<?php

use yii\helpers\Url;
use backend\modules\department\models\Department;

return [
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'id',
    // ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'year',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'leave_type',
        'value' => function ($model) {
            $name = \common\models\LeaveTypes::find()->where(['id' => $model->leave_type])->one();
            return $name->title;
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'total_days',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'description',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'department',
        'value' => function ($model) {
            $name = Department::find()->where(['id' => $model->department])->one();
            return $name->title;
        }
    ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'designation',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'location',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'gender',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'marital_status',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'status',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'created_by',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'created_at',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'updated_at',
    // ],
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