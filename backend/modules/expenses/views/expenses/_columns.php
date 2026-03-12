<?php
/**
 * تعريف أعمدة جدول المصاريف
 * ============================
 * 
 * @return array مصفوفة تعريف الأعمدة
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\ButtonDropdown;
use common\helper\Permissions;

return [
    /* === التصنيف === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'category_id',
        'label' => Yii::t('app', 'التصنيف'),
        'value' => 'category.name',
    ],

    /* === المنشئ === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => Yii::t('app', 'بواسطة'),
        'value' => 'createdBy.username',
    ],

    /* === الوصف === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'description',
        'label' => Yii::t('app', 'الوصف'),
        'contentOptions' => ['style' => 'max-width: 200px; overflow: hidden; text-overflow: ellipsis;'],
    ],

    /* === المبلغ === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'amount',
        'label' => Yii::t('app', 'المبلغ'),
        'format' => 'decimal',
    ],

    /* === رقم المستلم === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'receiver_number',
        'label' => Yii::t('app', 'رقم المستلم'),
    ],

    /* === تاريخ المصروف === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'expenses_date',
        'label' => Yii::t('app', 'التاريخ'),
    ],

    /* === الملاحظات === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'notes',
        'label' => Yii::t('app', 'ملاحظات'),
    ],

    /* === رقم المستند === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'document_number',
        'label' => Yii::t('app', 'رقم المستند'),
    ],

    /* === رقم العقد === */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => Yii::t('app', 'العقد'),
    ],

    /* === الإجراءات === */
    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width: 120px; text-align: center;'],
        'header' => Yii::t('app', 'الإجراءات'),
        'template' => '{all}',
        'buttons' => [
            'all' => function ($url, $model, $key) {
                $u = Yii::$app->user;
                $items = [];

                /* عرض — متاح لكل من يملك صلاحية المصاريف */
                $items[] = [
                    'label' => '<i class="fa fa-eye text-info"></i> ' . Yii::t('app', 'عرض'),
                    'url' => ['view', 'id' => $key],
                ];

                /* تعديل — fallback للصلاحية الأساسية */
                if ($u->can(Permissions::EXP_EDIT) || $u->can(Permissions::EXPENSES)) {
                    $items[] = [
                        'label' => '<i class="fa fa-pencil text-primary"></i> ' . Yii::t('app', 'تعديل'),
                        'url' => ['update', 'id' => $key],
                    ];
                }

                /* حذف — fallback للصلاحية الأساسية */
                if ($u->can(Permissions::EXP_DELETE) || $u->can(Permissions::EXPENSES)) {
                    $items[] = [
                        'label' => '<i class="fa fa-trash text-danger"></i> ' . Yii::t('app', 'حذف'),
                        'url' => ['delete', 'id' => $key],
                        'linkOptions' => ['data' => ['method' => 'post', 'confirm' => Yii::t('app', 'هل أنت متأكد؟')]],
                    ];
                }

                /* إرجاع للحركات المالية — fallback للصلاحية الأساسية */
                if (!empty($model->financial_transaction_id) && ($u->can(Permissions::EXP_REVERT) || $u->can(Permissions::EXPENSES))) {
                    $items[] = [
                        'label' => '<i class="fa fa-undo text-warning"></i> ' . Yii::t('app', 'إرجاع للحركات المالية'),
                        'url' => ['back-to-financial-transaction', 'id' => $key, 'financial' => $model->financial_transaction_id],
                    ];
                }

                return ButtonDropdown::widget([
                    'encodeLabel' => false,
                    'label' => '<i class="fa fa-cogs"></i> ' . Yii::t('app', 'خيارات'),
                    'dropdown' => [
                        'encodeLabels' => false,
                        'items' => $items,
                        'options' => ['class' => 'dropdown-menu-right'],
                    ],
                    'options' => ['class' => 'btn-default btn-sm'],
                    'split' => true,
                ]);
            },
        ],
    ],
];
