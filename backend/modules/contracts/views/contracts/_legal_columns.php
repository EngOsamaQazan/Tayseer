<?php

use yii\helpers\Url;
use yii\bootstrap\ButtonDropdown;
use common\helper\LoanContract;
use common\helper\Permissions;
use backend\helpers\NameHelper;
return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'seller_id',
        'value' => function ($model) {
            return NameHelper::short($model->seller->name);
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'customer_name',
        'label' => Yii::t('app', 'Customer Name'),
        'value' => function ($model) {
            return join(', ', array_map(fn($c) => NameHelper::short($c->name), $model->customers));
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'Date_of_sale',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'total_value',
        'value' => function ($model) {
            $totle_value = new LoanContract();
            $totle_value = $totle_value->findContract($model->id);
            if( $totle_value->status == 'judiciary'){
                if ($totle_value->is_loan == 1) {
                    $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $totle_value->id])->where(['>=', 'created_at', $totle_value->created_at])->orderBy(['contract_id' => SORT_DESC])->one();

                } else {
                    $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $totle_value->id])->orderBy(['contract_id' => SORT_DESC])->one();
                }
                if(!empty($cost)){
                    $totle_value = $totle_value->total_value + $cost->case_cost + $cost->lawyer_cost;
                    return $totle_value;
                }
                return $totle_value->total_value;

            }
            return $totle_value->total_value;


        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'first_installment_value',
    ],

    // [
// 'class'=>'\kartik\grid\DataColumn',
// 'attribute'=>'first_installment_date',
// ],
// [
// 'class'=>'\kartik\grid\DataColumn',
// 'attribute'=>'monthly_installment_value',
// ],
// [
// 'class'=>'\kartik\grid\DataColumn',
// 'attribute'=>'notes',
// ],
// [
// 'class'=>'\kartik\grid\DataColumn',
// 'attribute'=>'updated_at',
// ],
// [
// 'class'=>'\kartik\grid\DataColumn',
// 'attribute'=>'is_deleted',
// ],
    ['class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:10%;'],
        'header' => Yii::t('app', 'Actions'),
        'template' => '{all}',
        'buttons' => [
            'all' => function ($url, $model, $key) {
                if (Yii::$app->user->can(Permissions::MANAGER)) {
                    return ButtonDropdown::widget([
                        'encodeLabel' => false, // if you're going to use html on the button label
                        'label' => Yii::t('app', 'Actions'),
                        'dropdown' => [
                            'encodeLabels' => false, // if you're going to use html on the items' labels
                            'items' => [
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Update'),
                                    'url' => ['update', 'id' => $key],
                                    'visible' => true,
                                ],
//                                            [
//                                                'label' => \Yii::t('yii', '<i class="icon-list"></i> validate'),
//                                                'url' => ['validate', 'id' => $key],
//                                                'visible' => true, // if you want to hide an item based on a condition, use this
//                                            ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Print'),
                                    'url' => ['print-preview', 'id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Add Installment'),
                                    'url' => ['/contractInstallment/contract-installment/index', 'contract_id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Add Follow Up'),
                                    'url' => ['/followUp/follow-up/index', 'contract_id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Loan Scheduling'),
                                    'url' => ['/loanScheduling/loan-scheduling/create', 'contract_id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Create Judiciary'),
                                    'url' => ['/judiciary/judiciary/create', 'contract_id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<button type="button" class="btn btn-primary finish" data-id = ' . $key . ' data-toggle="modal" data-target="#exampleModalCenter">
انهاء العقد
</button>',
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<button type="button" class="btn btn-primary cancel" data-id = ' . $key . ' data-toggle="modal" data-target="#123">
الغاء العقد
</button>',
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
                                    'url' => ['update', 'id' => $key],
                                    'visible' => true,
                                ],
//                                            [
//                                                'label' => \Yii::t('yii', '<i class="icon-list"></i> validate'),
//                                                'url' => ['validate', 'id' => $key],
//                                                'visible' => true, // if you want to hide an item based on a condition, use this
//                                            ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Print'),
                                    'url' => ['print-preview', 'id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Add Installment'),
                                    'url' => ['/contractInstallment/contract-installment/index', 'contract_id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Add Follow Up'),
                                    'url' => ['/followUp/follow-up/index', 'contract_id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Loan Scheduling'),
                                    'url' => ['/loan-scheduling/loan-scheduling/create', 'contract_id' => $key],
                                    'visible' => true,
                                ],
                                [
                                    'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Create Judiciary'),
                                    'url' => ['/judiciary/judiciary/create', 'contract_id' => $key],
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
    ]];
