<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ButtonDropdown;
use common\helper\LoanContract;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\followUp\helper\ContractCalculations;
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
        'label' => Yii::t('app', 'Seller Name'),
        'value' => function ($model) {
            return $model->seller->name;
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'customer_name',
        'label' => Yii::t('app', 'Customer Name'),
        'format' => 'html',
        'contentOptions' => [

            'style' => 'max-width:150px; min-height:100px; overflow: auto; word-wrap: break-word;'
        ],
        'value' => function($model) {
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
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'first_installment_value',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'date_time',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'promise_to_pay_at',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'reminder',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'followed_by',
        'value' => 'followedBy.username'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => Yii::t('app', 'المستحق'),
        'label' => Yii::t('app', 'المستحق'),
        'value' => function ($model) {
            $contractCalculations = new ContractCalculations($model->id);
            return $contractCalculations->deservedAmount();
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => Yii::t('app', 'Residual Amount'),
        'value' => function ($model) {
            $judicary_contract = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $model->id])->all();

            if (!empty($judicary_contract)) {
                $all_case_cost = \backend\modules\expenses\models\Expenses::find()->where(['contract_id' => $model->id])->andWhere(['category_id' => 4])->all();
                $sum_case_cost = 0;
                foreach ($all_case_cost as $case_cost) {
                    $sum_case_cost = $sum_case_cost + $case_cost->amount;

                }
            }
            if (!empty($judicary_contract)) {
                $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $model->id])->all();

                foreach ($cost as $cost) {
                    $totle_value = $model->total_value + $sum_case_cost + $cost->lawyer_cost;
                    $model->total_value = $totle_value;
                }
            }

            $paid_amount = ContractInstallment::find()
                ->andWhere(['contract_id' => $model->id])
                ->sum('amount');

            $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
            $custamer_referance = (empty($custamer_referance)) ? 0 : $custamer_referance;


            return  ($model->total_value + $custamer_referance) - $paid_amount;


        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
    ],
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
        'template' => '{followup}',
        'buttons' => [
            'followup' => function ($url, $model, $key) {
                if (!$model->contract->is_locked($model->contract)) {
                    return Html::a(
                                    '<span class="glyphicon glyphicon-eye-open"> ' . \Yii::t('app', 'Add Follow Up') . '</span>', ['/followUp/follow-up/index', 'contract_id' => $key], [
                                'title' => 'Follow Up',
                                'data-pjax' => '0',
                                    ]
                    );
                }else{
                   return Html::a(
                                    '<span class="glyphicon glyphicon-eye-close"> متابع من قبل موظف اخر</span>',null, [
                                'title' => 'Follow Up',
                                'data-pjax' => '0',
                                    ]
                    );  
                }
            },
                ],
//                'template' => '{all}',
//                'buttons' => [
//                    'all' => function ($url, $model, $key) {
//                        return ButtonDropdown::widget([
//                                    'encodeLabel' => false, // if you're going to use html on the button label
//                                    'label' => Yii::t('app', 'Actions'),
//                                    'dropdown' => [
//                                        'encodeLabels' => false, // if you're going to use html on the items' labels
//                                        'items' => [
//                                            [
//                                                'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Update'),
//                                                'url' => ['/contracts/update', 'id' => $key],
//                                                'visible' => true,
//                                            ],
////                                            [
////                                                'label' => \Yii::t('yii', '<i class="icon-list"></i> validate'),
////                                                'url' => ['validate', 'id' => $key],
////                                                'visible' => true, // if you want to hide an item based on a condition, use this
////                                            ],
//                                            [
//                                                'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Print Contract'),
//                                                'url' => ['/contracts/print-first-page', 'id' => $key],
//                                                'visible' => true,
//                                            ],
//                                            [
//                                                'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Print Draft'),
//                                                'url' => ['/contracts/print-second-page', 'id' => $key],
//                                                'visible' => true,
//                                            ],
//                                            [
//                                                'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Add Installment'),
//                                                'url' => ['/contract-Income', 'contract_id' => $key],
//                                                'visible' => true,
//                                            ],
//                                            [
//                                                'label' => '<i class="icon-pencil5"></i>' . \Yii::t('app', 'Add Follow Up'),
//                                                'url' => ['/follow-up/index', 'contract_id' => $key],
//                                                'visible' => true,
//                                            ],
//                                        ],
//                                        'options' => [
//                                            'class' => 'dropdown-menu-right', // right dropdown
//                                        ],
//                                    ],
//                                    'options' => [
//                                        'class' => 'btn-default',
//                                        'style' => 'padding-left: 5px; padding-right: 5px;', // btn-success, btn-info, et cetera
//                                    ],
//                                    'split' => true, // if you want a split button
//                        ]);
//                    },
//                        ],
        ]];
        