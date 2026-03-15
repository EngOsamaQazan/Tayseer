<?php

use yii\helpers\Url;
use yii\helpers\Html;
use backend\models\JudiciaryAuthority;

return [
    [
        'class' => 'yii\grid\SerialColumn',
    ],
    [
        'class' => 'yii\grid\DataColumn',
        'attribute' => 'name',
    ],
    [
        'class' => 'yii\grid\DataColumn',
        'attribute' => 'authority_type',
        'value' => function ($model) {
            $list = JudiciaryAuthority::getTypeList();
            return $list[$model->authority_type] ?? $model->authority_type;
        },
        'filter' => JudiciaryAuthority::getTypeList(),
    ],
    [
        'class' => 'yii\grid\DataColumn',
        'attribute' => 'notes',
        'value' => function ($model) {
            return $model->notes ? \yii\helpers\StringHelper::truncate($model->notes, 50) : '—';
        },
    ],
    [
        'class' => 'yii\grid\ActionColumn',
        'template' => '{view} {update} {delete}',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'buttons' => [
            'view' => function ($url, $model, $key) {
                return Html::a('<i class="fa fa-eye"></i>', $url, [
                    'title' => 'عرض',
                    'data-pjax' => '0',
                    'class' => 'btn btn-sm btn-outline-secondary',
                ]);
            },
            'update' => function ($url, $model, $key) {
                return Html::a('<i class="fa fa-pencil"></i>', $url, [
                    'title' => 'تعديل',
                    'data-pjax' => '0',
                    'class' => 'btn btn-sm btn-outline-primary',
                ]);
            },
            'delete' => function ($url, $model, $key) {
                return Html::a('<i class="fa fa-trash"></i>', $url, [
                    'title' => 'حذف',
                    'data-confirm' => 'هل أنت متأكد من حذف هذا العنصر؟',
                    'data-method' => 'post',
                    'data-pjax' => '0',
                    'class' => 'btn btn-sm btn-outline-danger',
                ]);
            },
        ],
    ],
];
