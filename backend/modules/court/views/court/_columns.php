<?php

use yii\helpers\Url;
use common\models\Court;
use common\components\City;
return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'contentOptions' => ['data-label' => 'الاسم'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'city',
        'contentOptions' => ['data-label' => 'المدينة'],
        'value' => function ($model) {
    $city = City::findMyCity($model->city);

            return $city ;
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'adress',
        'contentOptions' => ['data-label' => 'العنوان'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'phone_number',
        'contentOptions' => ['data-label' => 'الهاتف'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'contentOptions' => ['data-label' => 'أنشأ بواسطة'],
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['title' => 'عرض', 'data-bs-toggle' => 'tooltip'],
        'updateOptions' => ['title' => 'تعديل', 'data-bs-toggle' => 'tooltip'],
        'deleteOptions' => ['title' => 'حذف',
            'data-confirm' => false, 'data-method' => false,
            'data-request-method' => 'post',
            'data-bs-toggle' => 'tooltip',
            'data-confirm-title' => 'هل أنت متأكد؟',
            'data-confirm-message' => 'هل أنت متأكد من حذف هذا العنصر؟'],
    ],
];
