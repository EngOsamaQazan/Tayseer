<?php

use yii\helpers\Url;
use backend\modules\judiciary\models\JudiciaryCustomersActions;
use backend\modules\judiciary\models\JudiciaryActions;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'value' => 'contract_id'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'court_id',
        'value' => 'court_name'
        
    ],

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_number',
        'value' => 'judiciary_number',
        'label' => Yii::t('app', 'Judiciary Number'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'lawyer_cost',
        'value' => 'lawyer_cost',

    ],
 
    [
        'class' => '\kartik\grid\DataColumn',
         'attribute' => 'customer_name',
        'value' => 'customer_name'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_name',
        'value' => 'action_name'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'customer_date',
        'value' => 'customer_date'
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
  
];
