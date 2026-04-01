<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use backend\models\Holiday;

return [
    ['class' => 'yii\grid\SerialColumn', 'header' => '#'],
    [
        'attribute' => 'holiday_date',
        'label' => 'تاريخ العطلة',
        'format' => ['date', 'php:Y-m-d'],
        'headerOptions' => ['class' => 'text-center'],
        'contentOptions' => ['class' => 'text-center'],
    ],
    [
        'attribute' => 'name',
        'label' => 'اسم العطلة',
        'headerOptions' => ['class' => 'text-center'],
        'contentOptions' => ['class' => 'text-right'],
    ],
    [
        'attribute' => 'year',
        'label' => 'السنة',
        'headerOptions' => ['class' => 'text-center'],
        'contentOptions' => ['class' => 'text-center'],
    ],
    [
        'attribute' => 'source',
        'label' => 'المصدر',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->source === Holiday::SOURCE_MANUAL ? 'يدوي' : 'تلقائي (API)';
        },
        'filter' => [
            Holiday::SOURCE_MANUAL => 'يدوي',
            Holiday::SOURCE_API => 'تلقائي (API)',
        ],
        'headerOptions' => ['class' => 'text-center'],
        'contentOptions' => ['class' => 'text-center'],
    ],
    [
        'class' => ActionColumn::class,
        'header' => 'الإجراءات',
        'template' => '{view} {update} {delete}',
        'urlCreator' => function ($action, $model, $key, $index, $column) {
            return Url::to([$action, 'id' => $model->id]);
        },
        'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
        'buttons' => [
            'delete' => function ($url, $model, $key) {
                return Html::a(
                    '<span class="fa fa-trash"></span>',
                    $url,
                    [
                        'title' => 'حذف',
                        'data' => [
                            'confirm' => 'هل أنت متأكد من الحذف؟',
                            'method' => 'post',
                        ],
                    ]
                );
            },
        ],
    ],
];
