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
        'attribute'=>'status.status',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'date',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'amount',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'createdBy.username',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'payment_type',
    'value'=>'paymentType.name'
    ],
  [
        'class'=>'\kartik\grid\DataColumn',
         'attribute'=>'_by',
    ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'receipt_bank',
    // ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'payment_purpose',
    // ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'financial_transaction_id',
    // ],
 [
         'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'type',
     'value'=>'incomeCategory.name'
  ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'notes',
    // ],
    // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'document_number',
    // ],


];   