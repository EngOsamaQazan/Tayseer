<?php
use yii\helpers\Url;
use backend\helpers\NameHelper;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'customer_id',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'customer_name',
        'value' => function ($model) {
            $name = is_object($model) ? ($model->customer_name ?? '') : ($model['customer_name'] ?? '');
            return NameHelper::short($name);
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'court_name',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_id',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_actions_name',
    ],
];
