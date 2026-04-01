<?php
/**
 * أعمدة جدول العقود
 * أعمدة محسنة مع أداء أفضل
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap5\ButtonDropdown;
use common\helper\LoanContract;
use common\helper\Permissions;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\followUp\helper\ContractCalculations;

/* ألوان الحالات */
$statusColors = [
    'active' => 'success', 'judiciary' => 'danger',
    'legal_department' => 'info', 'settlement' => 'primary', 'finished' => 'default',
    'canceled' => 'default',
];
$statusLabels = [
    'active' => 'نشط', 'judiciary' => 'قضاء',
    'legal_department' => 'قانوني', 'settlement' => 'تسوية', 'finished' => 'منتهي',
    'canceled' => 'ملغي',
];

$isManager = Yii::$app->user->can(Permissions::MANAGER);

/* كاش المستخدمين للمتابعين */
$allUsers = $isManager ? ArrayHelper::map(\common\models\User::find()->asArray()->all(), 'id', 'username') : [];

return [
    /* # */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'contentOptions' => ['style' => 'width:45px'],
    ],

    /* البائع */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'seller_id',
        'label' => 'البائع',
        'value' => fn($m) => $m->seller->name ?? '—',
    ],

    /* العميل */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العميل',
        'format' => 'raw',
        'value' => function ($m) {
            $names = ArrayHelper::map($m->customers, 'id', 'name');
            return implode('، ', $names) ?: '—';
        },
    ],

    /* المستحق */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المستحق',
        'format' => ['decimal', 0],
        'value' => fn($m) => (new ContractCalculations($m->id))->deservedAmount(),
        'contentOptions' => ['style' => 'font-weight:600;color:#800020'],
    ],

    /* تاريخ البيع */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'Date_of_sale',
        'label' => 'التاريخ',
        'contentOptions' => ['style' => 'white-space:nowrap'],
    ],

    /* الدفعة الأولى */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'first_installment_value',
        'label' => 'الدفعة الأولى',
        'format' => ['decimal', 0],
        'contentOptions' => ['style' => 'white-space:nowrap'],
    ],

    /* القيمة الإجمالية */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'total_value',
        'label' => 'الإجمالي',
        'format' => ['decimal', 0],
        'value' => function ($m) {
            $total = $m->total_value;
            if ($m->status === 'judiciary') {
                $jud = \backend\modules\judiciary\models\Judiciary::find()
                    ->where(['contract_id' => $m->id])
                    ->orderBy(['id' => SORT_DESC])
                    ->one();
                if ($jud) $total += $jud->case_cost + $jud->lawyer_cost;
            }
            return $total;
        },
    ],

    /* الحالة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
        'label' => 'الحالة',
        'format' => 'raw',
        'value' => function ($m) use ($statusColors, $statusLabels) {
            $color = $statusColors[$m->status] ?? 'default';
            $label = $statusLabels[$m->status] ?? $m->status;
            $badgeMap = [
                'success' => 'badge bg-success',
                'danger' => 'badge bg-danger',
                'warning' => 'badge bg-warning text-dark',
                'info' => 'badge bg-info',
                'primary' => 'badge bg-primary',
                'default' => 'badge bg-secondary',
            ];
            $cls = $badgeMap[$color] ?? 'badge bg-secondary';
            return '<span class="' . $cls . '">' . $label . '</span>';
        },
        'contentOptions' => ['style' => 'text-align:center'],
    ],

    /* المتبقي — يُقرأ من vw_contract_balance */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المتبقي',
        'format' => ['decimal', 0],
        'value' => function ($m) {
            $b = ContractCalculations::fromView($m->id);
            return $b ? $b['remaining'] : 0;
        },
        'contentOptions' => ['style' => 'font-weight:600'],
    ],

    /* المتابع */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'followed_by',
        'label' => 'المتابع',
        'format' => 'raw',
        'value' => function ($m) use ($isManager, $allUsers) {
            if ($isManager) {
                return Html::dropDownList('followedBy', $m->followed_by, $allUsers, [
                    'class' => 'form-control input-sm followUpUser',
                    'data-contract-id' => $m->id,
                    'prompt' => '-- المتابع --',
                    'style' => 'min-width:100px',
                ]);
            }
            return $allUsers[$m->followed_by] ?? ($m->followedBy->username ?? '—');
        },
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:90px;text-align:center'],
        'header' => 'إجراءات',
        'template' => '{all}',
        'buttons' => [
            'all' => function ($url, $m, $key) use ($isManager) {
                $items = [];

                if (Permissions::can(Permissions::CONT_UPDATE)) {
                    $items[] = ['label' => '<i class="fa fa-pencil text-primary"></i> تعديل', 'url' => ['update', 'id' => $key]];
                }
                $items[] = ['label' => '<i class="fa fa-print text-info"></i> طباعة العقد والكمبيالات', 'url' => ['print-preview', 'id' => $key]];
                $items[] = '<li><hr class="dropdown-divider"></li>';
                $items[] = ['label' => '<i class="fa fa-money text-success"></i> الدفعات', 'url' => ['/contractInstallment/contract-installment/index', 'contract_id' => $key]];
                $items[] = ['label' => '<i class="fa fa-comments text-primary"></i> المتابعة', 'url' => ['/followUp/follow-up/index', 'contract_id' => $key]];
                $items[] = ['label' => '<i class="fa fa-calendar text-info"></i> جدولة', 'url' => ['/loanScheduling/loan-scheduling/create', 'contract_id' => $key]];

                if ($m->status === 'judiciary') {
                    $items[] = ['label' => '<i class="fa fa-gavel text-danger"></i> تحصيل', 'url' => ['/collection/collection/create', 'contract_id' => $key]];
                }

                if ($isManager) {
                    $items[] = '<li><hr class="dropdown-divider"></li>';
                    if (Permissions::can(Permissions::CONT_UPDATE)) {
                        $items[] = ['label' => '<i class="fa fa-check-circle text-success"></i> إنهاء', 'url' => '#', 'linkOptions' => ['class' => 'yeas-finish', 'data-url' => Url::to(['finish', 'id' => $key])]];
                        $items[] = ['label' => '<i class="fa fa-ban text-danger"></i> إلغاء', 'url' => '#', 'linkOptions' => ['class' => 'yeas-cancel', 'data-url' => Url::to(['cancel', 'id' => $key])]];
                    }
                    if (Permissions::can(Permissions::CONT_DELETE)) {
                        $items[] = ['label' => '<i class="fa fa-trash text-danger"></i> حذف', 'url' => ['delete', 'id' => $key], 'linkOptions' => [
                            'data-confirm' => 'هل أنت متأكد من حذف هذا العقد؟',
                            'data-method' => 'post',
                        ]];
                    }
                }

                return ButtonDropdown::widget([
                    'encodeLabel' => false,
                    'label' => '<i class="fa fa-cogs"></i>',
                    'dropdown' => ['encodeLabels' => false, 'items' => $items, 'options' => ['class' => 'dropdown-menu-right']],
                    'options' => ['class' => 'btn-secondary btn-xs'],
                    'split' => false,
                ]);
            },
        ],
    ],
];
