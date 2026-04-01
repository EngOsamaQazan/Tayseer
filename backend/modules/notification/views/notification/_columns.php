<?php

use yii\helpers\Url;
use backend\modules\notification\models\Notification;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'sender_id',
        'value' => 'sender.username',
        'label' => Yii::t('app', 'المرسل'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'recipient_id',
        'value' => 'recipient.username',
        'label' => Yii::t('app', 'المستلم'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'type_of_notification',
        'label' => Yii::t('app', 'النوع'),
        'value' => function ($model) {
            return Notification::getTypeLabel($model->type_of_notification);
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'title_html',
        'label' => Yii::t('app', 'العنوان'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'is_unread',
        'label' => Yii::t('app', 'الحالة'),
        'value' => function ($model) {
            return (int)$model->is_unread === 1 ? 'غير مقروء' : 'مقروء';
        },
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
            'data-bs-toggle' => 'tooltip',
            'data-confirm-title' => Yii::t('app', 'هل أنت متأكد؟'),
            'data-confirm-message' => Yii::t('app', 'هل أنت متأكد من حذف هذا العنصر؟'),
        ],
    ],
];
