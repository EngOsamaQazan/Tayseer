<?php

use yii\helpers\Url;
use yii\helpers\Html;
use backend\models\JudiciaryRequestTemplate;

return [
    ['class' => 'yii\grid\SerialColumn', 'header' => '#'],
    [
        'attribute' => 'name',
        'label' => 'اسم القالب',
    ],
    [
        'attribute' => 'template_type',
        'label' => 'نوع القالب',
        'value' => function ($model) {
            $labels = JudiciaryRequestTemplate::getTypeLabels();
            return $labels[$model->template_type] ?? $model->template_type;
        },
        'filter' => JudiciaryRequestTemplate::getTypeLabels(),
    ],
    [
        'attribute' => 'is_combinable',
        'label' => 'قابل للدمج',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->is_combinable ? '<span class="text-success">نعم</span>' : '<span class="text-muted">لا</span>';
        },
        'filter' => [0 => 'لا', 1 => 'نعم'],
    ],
    [
        'attribute' => 'sort_order',
        'label' => 'الترتيب',
    ],
    [
        'class' => 'yii\grid\ActionColumn',
        'header' => 'الإجراءات',
        'template' => '{view} {update} {delete}',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'buttons' => [
            'view' => function ($url, $model, $key) {
                return Html::a('<i class="fa fa-eye"></i>', $url, ['title' => 'عرض']);
            },
            'update' => function ($url, $model, $key) {
                return Html::a('<i class="fa fa-pencil"></i>', $url, ['title' => 'تعديل']);
            },
            'delete' => function ($url, $model, $key) {
                return Html::a('<i class="fa fa-trash"></i>', $url, [
                    'title' => 'حذف',
                    'data-confirm' => 'هل أنت متأكد من الحذف؟',
                    'data-method' => 'post',
                ]);
            },
        ],
        'contentOptions' => ['class' => 'text-center', 'style' => 'white-space: nowrap;'],
    ],
];
