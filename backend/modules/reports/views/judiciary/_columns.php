<?php

use yii\helpers\Url;
use \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;
use \backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\helpers\NameHelper;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => Yii::t('app', 'Contract ID')
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'court_id',
        'value' => 'court.name'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_number',
        'label' => Yii::t('app', 'Judiciary Number'),
        'value' => function ($model) {
            return $model->judiciary_number . '/' . $model->year;
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'lawyer_cost',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'lawyer_id',
        'value' => function ($model) {
            return $model->lawyer ? NameHelper::short($model->lawyer->name) : '';
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'customer_name',
        'contentOptions' => ['class' => 'text-wrap'],
        'format' => 'raw',
        'label' => Yii::t('app', 'Customer Name'),
        'value' => function ($model) {
            $names_array = [];

            foreach ($model->customersAndGuarantor as $value) {
                $db = Yii::$app->db;
                $judicaryAction = $db->cache(function ($db) use ($value) {

                    return JudiciaryActions::find()->innerJoinWith('judiciaryCustomersActions')->where(['customers_id' => $value->id])->orderBy(['action_date' => SORT_DESC])->one();
                });

                if (!empty($judicaryAction)) {
                    array_push($names_array, NameHelper::short($value->name) . '(' . $judicaryAction->name . ')<br>');
                } else {
                    array_push($names_array, NameHelper::short($value->name) . '(لا إجرائات متبعه)<br>');
                }
            }
            return join('', $names_array);
        },
    ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'created_at',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'updated_at',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'created_by',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'last_update_by',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'is_deleted',
    // ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key, 'contract_id' => $model->contract_id]);
        },
        'viewOptions' => ['title' => 'View', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['title' => 'Update', 'data-toggle' => 'tooltip'],
        'deleteOptions' => ['title' => 'Delete',
            'data-confirm' => false, 'data-method' => false, // for overide yii data api
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'Are you sure?',
            'data-confirm-message' => 'Are you sure want to delete this item'],
    ],
];
