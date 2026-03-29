<?php

use yii\helpers\Url;
use yii\helpers\Html;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'sender_id',
        'label' => Yii::t('app', 'المرسل'),
        'value' => 'sender.username',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'title_html',
        'label' => Yii::t('app', 'العنوان'),
        'value' => function ($model) {
            $title = Html::encode($model->title_html);
            if (!empty($model->href)) {
                $url = Url::to([$model->href, 'notificationID' => $model->id]);
                return Html::a($title, $url);
            }
            return $title;
        },
        'format' => 'raw',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'body_html',
        'label' => Yii::t('app', 'المحتوى'),
        'value' => function ($model) {
            $body = Html::encode($model->body_html);
            if (!empty($model->href)) {
                $url = Url::to([$model->href, 'notificationID' => $model->id]);
                return Html::a($body, $url);
            }
            return $body;
        },
        'format' => 'raw',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_time',
        'label' => Yii::t('app', 'التاريخ'),
        'value' => function ($model) {
            return $model->created_time ? date('Y-m-d H:i:s', $model->created_time) : '';
        },
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'template' => '{delete}',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'deleteOptions' => [
            'title' => Yii::t('app', 'حذف'),
            'data-confirm' => false,
            'data-method' => false,
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => Yii::t('app', 'هل أنت متأكد؟'),
            'data-confirm-message' => Yii::t('app', 'هل أنت متأكد من حذف هذا العنصر؟'),
        ],
    ],
];
