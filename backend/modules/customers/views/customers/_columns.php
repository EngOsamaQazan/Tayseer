<?php
/**
 * أعمدة جدول العملاء
 * تعريفات الأعمدة مع تحسين الأداء (لا N+1)
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\ButtonDropdown;
use common\helper\Permissions;
use backend\helpers\NameHelper;

return [
    /* رقم العميل */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'contentOptions' => ['style' => 'width:50px', 'data-label' => '#'],
    ],

    /* الاسم */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'label' => 'الاسم',
        'format' => 'raw',
        'value' => fn($m) => Permissions::can(Permissions::CUST_UPDATE)
            ? Html::a(Html::encode(NameHelper::short($m->name)), ['update', 'id' => $m->id], ['class' => 'text-burgundy', 'style' => 'font-weight:600', 'title' => $m->name])
            : Html::encode(NameHelper::short($m->name)),
        'contentOptions' => fn($m) => ['title' => $m->name, 'data-label' => 'الاسم'],
    ],

    /* الهاتف */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'primary_phone_number',
        'label' => 'الهاتف',
        'format' => 'raw',
        'value' => function ($model) {
            return '<span dir="ltr">' . \yii\helpers\Html::encode(\backend\helpers\PhoneHelper::toLocal($model->primary_phone_number)) . '</span>';
        },
        'contentOptions' => ['style' => 'direction:ltr;text-align:right;font-family:monospace', 'data-label' => 'الهاتف'],
    ],

    /* الرقم الوطني */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id_number',
        'label' => 'الرقم الوطني',
        'contentOptions' => ['style' => 'font-family:monospace', 'data-label' => 'الوطني'],
    ],

    /* مشتكى عليه - استعلام EXISTS للأداء */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'مشتكى عليه',
        'format' => 'raw',
        'value' => function ($m) {
            static $cache = [];
            if (!isset($cache[$m->id])) {
                $cache[$m->id] = \backend\modules\judiciary\models\Judiciary::find()
                    ->innerJoin('os_contracts_customers cc', 'os_judiciary.contract_id = cc.contract_id')
                    ->where(['cc.customer_id' => $m->id])
                    ->exists();
            }
            return $cache[$m->id]
                ? '<span class="label label-danger">نعم</span>'
                : '<span class="label label-success">لا</span>';
        },
        'contentOptions' => ['style' => 'text-align:center;width:70px', 'data-label' => 'مشتكى عليه'],
    ],

    /* العقود */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العقود',
        'format' => 'raw',
        'value' => function ($m) {
            $contracts = \backend\modules\customers\models\ContractsCustomers::find()
                ->select('contract_id')
                ->where(['customer_id' => $m->id])
                ->column();
            if (empty($contracts)) return '<span class="text-muted">—</span>';
            $links = [];
            foreach ($contracts as $cid) {
                $links[] = Html::a(
                    '<span class="label label-info">' . $cid . '</span>',
                    ['/followUp/follow-up/index', 'contract_id' => $cid],
                    ['data-pjax' => '0', 'title' => "متابعة العقد $cid"]
                );
            }
            return implode(' ', $links);
        },
        'contentOptions' => ['data-label' => 'العقود'],
    ],

    /* الوظيفة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'job_title',
        'label' => 'الوظيفة',
        'value' => 'jobs.name',
        'contentOptions' => ['data-label' => 'الوظيفة'],
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:100px;text-align:center', 'data-label' => ''],
        'header' => 'إجراءات',
        'template' => '{all}',
        'buttons' => [
            'all' => fn($url, $m) => ButtonDropdown::widget([
                'encodeLabel' => false,
                'label' => '<i class="fa fa-ellipsis-v"></i>',
                'dropdown' => [
                    'encodeLabels' => false,
                    'items' => array_filter([
                        Permissions::can(Permissions::CUST_UPDATE) ? ['label' => '<i class="fa fa-pencil text-primary"></i> تعديل', 'url' => ['update', 'id' => $m->id]] : null,
                        ['label' => '<i class="fa fa-eye text-info"></i> عرض', 'url' => ['view', 'id' => $m->id], 'linkOptions' => ['role' => 'modal-remote']],
                        ['label' => '<i class="fa fa-file-text-o text-success"></i> إضافة عقد', 'url' => ['/contracts/contracts/create', 'id' => $m->id]],
                        '<li class="divider"></li>',
                        Permissions::can(Permissions::CUST_UPDATE) ? ['label' => '<i class="fa fa-phone text-warning"></i> تحديث اتصال', 'url' => ['update-contact', 'id' => $m->id], 'linkOptions' => ['role' => 'modal-remote']] : null,
                        Permissions::can(Permissions::CUST_DELETE) ? '<li class="divider"></li>' : null,
                        Permissions::can(Permissions::CUST_DELETE) ? ['label' => '<i class="fa fa-trash text-danger"></i> حذف', 'url' => ['delete', 'id' => $m->id], 'linkOptions' => ['data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد من حذف هذا العميل؟']]] : null,
                    ]),
                    'options' => ['class' => 'dropdown-menu-left'],
                ],
                'options' => ['class' => 'btn-default btn-xs'],
                'split' => false,
            ]),
        ],
    ],
];
