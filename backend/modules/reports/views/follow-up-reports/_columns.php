<?php
use yii\helpers\Url;

return [
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'id',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'contract_id',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'date_time',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'notes',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'feeling',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'createdBy.username',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'reminder',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'promise_to_pay_at',
    ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'connection_goal',
    // ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'reminder',
    // ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'promise_to_pay_at',
    // ],

];   