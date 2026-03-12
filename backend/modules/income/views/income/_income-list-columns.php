<?php

use yii\helpers\Url;
use yii\bootstrap\ButtonDropdown;
use yii\helpers\Html;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => '_by',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'value' => function ($model) {
            return Html::a($model->contract_id, Url::to(['/contracts/update', 'id' => $model->contract_id]), ['data-pjax' => 0, 'target' => '_blank']);
        },
        'format' => 'raw',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'date',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'amount',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created.username',

        'label' => Yii::t('app', 'Created By'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'paymentType.name',
        'label' => Yii::t('app', 'Payment Type'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'type',
        'value' => 'incomeCategory.name',
        'label' => Yii::t('app', 'Type'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'notes',
        'label' => Yii::t('app', 'Notes'),
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'document_number',
        'label' => Yii::t('app', 'Document Number'),
    ],
    ['class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:10%;'],
        'header' => Yii::t('app', 'Actions'),
        'template' => '{all}',
        'buttons' => [
            'all' => function ($url, $model, $key) {
                if (!empty($model->financial_transaction_id)) {
                    return ButtonDropdown::widget([
                        'encodeLabel' => false, // if you're going to use html on the button label
                        'label' => Yii::t('app', 'Actions'),
                        'dropdown' => [
                            'encodeLabels' => false, // if you're going to use html on the items' labels
                            'items' => [
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Update'),
                                    'url' => ['update-income', 'id' => $key],
                                    'visible' => true,
                                ],
//
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Delete'),
                                    'url' => ['delete', 'id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Back to financial transaction'),
                                    'url' => ['income/back-to-financial-transaction', 'id' => $model->id, 'financial' => $model->financial_transaction_id],
                                    'visible' => true,
                                ],
                            ],
                            'options' => [
                                'class' => 'dropdown-menu-right', // right dropdown
                            ],
                        ],
                        'options' => [
                            'class' => 'btn-default',
                            'style' => 'padding-left: 5px; padding-right: 5px;', // btn-success, btn-info, et cetera
                        ],
                        'split' => true, // if you want a split button
                    ]);
                } else {
                    return ButtonDropdown::widget([
                        'encodeLabel' => false, // if you're going to use html on the button label
                        'label' => Yii::t('app', 'Actions'),
                        'dropdown' => [
                            'encodeLabels' => false, // if you're going to use html on the items' labels
                            'items' => [
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Update'),
                                    'url' => ['update-income', 'id' => $key],
                                    'visible' => true,
                                ],
//
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Delete'),
                                    'url' => ['delete', 'id' => $key],
                                    'visible' => true,
                                ],

                            ],
                            'options' => [
                                'class' => 'dropdown-menu-right', // right dropdown
                            ],
                        ],
                        'options' => [
                            'class' => 'btn-default',
                            'style' => 'padding-left: 5px; padding-right: 5px;', // btn-success, btn-info, et cetera
                        ],
                        'split' => true, // if you want a split button
                    ]);
                }
            },
        ],
    ]
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'_by',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'receipt_bank',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'payment_purpose',
    // ],
//    [
//        'class' => 'yii\grid\ActionColumn',
//        'contentOptions' => ['style' => 'width:10%;'],
//        'header' => Yii::t('app', 'Actions'),
//        'template' => '{all}',
//        'buttons' => [
//            'all' => function ($url, $model, $key) {
//                return ButtonDropdown::widget([
//                    'encodeLabel' => false, // if you're going to use html on the button label
//                    'label' => Yii::t('app', 'Actions'),
//                    'dropdown' => [
//                        'encodeLabels' => false, // if you're going to use html on the items' labels
//                        'items' => [
//                            [
//                                'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Update'),
//                                'url' => ['/Income/Income/update', 'id' => $model->id],
//                                'visible' => true,
//                            ],
//                            [
//                                'label' => '<i class="icon-bin"></i>' . \Yii::t('app', 'Delete'),
//                                'linkOptions' => [
//                                    'data' => [
//                                        'method' => 'post',
//                                        'confirm' => '<i class="icon-bin"></i>' . \Yii::t('yii', 'are you sure you want to delete ?'),
//                                    ],
//                                ],
//                                'url' => ['delete', 'id' => $model->id],
//                                'visible' => true, // same as above
//                            ],
//                        ],
//                        'options' => [
//                            'class' => 'dropdown-menu-right', // right dropdown
//                        ],
//                    ],
//                    'options' => [
//                        'class' => 'btn-default',
//                        'style' => 'padding-left: 5px; padding-right: 5px;', // btn-success, btn-info, et cetera
//                    ],
//                    'split' => true, // if you want a split button
//                ]);
//            },
//        ],
////            [
////                'class' => 'kartik\grid\ActionColumn',
////                'dropdown' => true,
////                'vAlign' => 'middle',
////                'urlCreator' => function($action, $model, $model->id, $index) {
////                    return Url::to([$action, 'id' => $model->id]);
////                },
////                        'viewOptions' => ['role' => 'modal-remote', 'title' => 'View', 'data-toggle' => 'tooltip'],
////                        'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
////                        'deleteOptions' => ['role' => 'modal-remote', 'title' => 'Delete',
////                            'data-confirm' => false, 'data-method' => false, // for overide yii data api
////                            'data-request-method' => 'post',
////                            'data-toggle' => 'tooltip',
////                            'data-confirm-title' => 'Are you sure?',
////                            'data-confirm-message' => 'Are you sure want to delete this item'],
////                    ]
//    ],

];

