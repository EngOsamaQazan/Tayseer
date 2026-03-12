<?php
/**
 * أعمدة جدول العملاء — V2
 * Custom dropdown menus (no BS3 ButtonDropdown)
 * All modal-remote links have data-pjax="0"
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\helper\Permissions;
use backend\helpers\NameHelper;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'contentOptions' => ['style' => 'width:50px', 'data-label' => '#'],
    ],

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'label' => 'الاسم',
        'format' => 'raw',
        'value' => fn($m) => Permissions::can(Permissions::CUST_UPDATE)
            ? Html::a(Html::encode(NameHelper::short($m->name)), ['update', 'id' => $m->id], ['class' => 'text-burgundy', 'style' => 'font-weight:600', 'title' => $m->name, 'data-pjax' => 0])
            : Html::encode(NameHelper::short($m->name)),
        'contentOptions' => fn($m) => ['title' => $m->name, 'data-label' => 'الاسم'],
    ],

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'primary_phone_number',
        'label' => 'الهاتف',
        'format' => 'raw',
        'value' => function ($model) {
            return '<span dir="ltr">' . Html::encode(\backend\helpers\PhoneHelper::toLocal($model->primary_phone_number)) . '</span>';
        },
        'contentOptions' => ['style' => 'direction:ltr;text-align:right;font-family:monospace', 'data-label' => 'الهاتف'],
    ],

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id_number',
        'label' => 'الرقم الوطني',
        'contentOptions' => ['style' => 'font-family:monospace', 'data-label' => 'الوطني'],
    ],

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
                ? '<span class="label label-danger" style="padding:3px 10px;border-radius:10px;font-size:11px">نعم</span>'
                : '<span class="label label-success" style="padding:3px 10px;border-radius:10px;font-size:11px">لا</span>';
        },
        'contentOptions' => ['style' => 'text-align:center;width:70px', 'data-label' => 'مشتكى عليه'],
    ],

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
                    '<span class="label label-info" style="padding:2px 8px;border-radius:8px;font-size:11px">' . $cid . '</span>',
                    ['/followUp/follow-up/index', 'contract_id' => $cid],
                    ['data-pjax' => '0', 'title' => "متابعة العقد $cid"]
                );
            }
            return implode(' ', $links);
        },
        'contentOptions' => ['data-label' => 'العقود'],
    ],

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'job_title',
        'label' => 'الوظيفة',
        'value' => 'jobs.name',
        'contentOptions' => ['data-label' => 'الوظيفة'],
    ],

    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:60px;text-align:center;overflow:visible', 'data-label' => ''],
        'header' => 'إجراءات',
        'template' => '{all}',
        'buttons' => [
            'all' => function ($url, $m) {
                $items = '';

                if (Permissions::can(Permissions::CUST_UPDATE)) {
                    $items .= '<a href="' . Url::to(['update', 'id' => $m->id]) . '" data-pjax="0"><i class="fa fa-pencil" style="color:#3b82f6"></i> تعديل</a>';
                }

                $items .= '<a href="' . Url::to(['view', 'id' => $m->id]) . '" role="modal-remote" data-pjax="0"><i class="fa fa-eye" style="color:#0891b2"></i> عرض</a>';

                $items .= '<a href="' . Url::to(['/contracts/contracts/create', 'id' => $m->id]) . '" data-pjax="0"><i class="fa fa-file-text-o" style="color:#059669"></i> إضافة عقد</a>';

                if (Permissions::can(Permissions::CUST_UPDATE)) {
                    $items .= '<div class="cust-act-sep"></div>';
                    $items .= '<a href="' . Url::to(['update-contact', 'id' => $m->id]) . '" role="modal-remote" data-pjax="0"><i class="fa fa-phone" style="color:#d97706"></i> تحديث اتصال</a>';
                }

                if (Permissions::can(Permissions::CUST_DELETE)) {
                    $items .= '<div class="cust-act-sep"></div>';
                    $items .= '<a href="' . Url::to(['delete', 'id' => $m->id]) . '" data-method="post" data-confirm="هل أنت متأكد من حذف هذا العميل؟" data-pjax="0"><i class="fa fa-trash" style="color:#dc2626"></i> حذف</a>';
                }

                return '<div class="cust-act-wrap">'
                    . '<button class="cust-act-trigger" type="button"><i class="fa fa-ellipsis-v"></i></button>'
                    . '<div class="cust-act-menu">' . $items . '</div>'
                    . '</div>';
            },
        ],
    ],
];
