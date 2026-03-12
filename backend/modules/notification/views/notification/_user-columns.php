<?php

use yii\helpers\Url;

return [
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'id',
    // ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'sender_id',
        'value' => 'sender.username'

    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'title_html',
        'value' => function ($model) {
            return \yii\helpers\Html::a($model->title_html, URL::to([$model->href . '&notificationID=' . $model->id]));
        },
        'format' => 'raw',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'body_html',
        'value' => function ($model) {
            return \yii\helpers\Html::a($model->body_html, URL::to([$model->href . '&notificationID=' . $model->id]));
        },
        'format' => 'raw',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_time',
        'value' => function ($model) {
            return date("Y-m-d H:m:s", $model->created_time);
        },

    ],
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'href',
        // ],
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'is_unread',
        // ],
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'is_hidden',
        // ],
    ['class' => 'kartik\grid\ActionColumn',
    'dropdown' => false,
    'vAlign' => 'middle',
    'template' => '{delete}',
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