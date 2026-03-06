<?php
/**
 * أعمدة جدول إجراءات العملاء القضائية - بناء من الصفر
 */
use yii\helpers\Url;
use yii\helpers\Html;

return [
    /* رقم القضية */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_id',
        'label' => 'القضية',
        'format' => 'raw',
        'value' => function ($m) {
            $jud = $m->judiciary;
            $label = $jud ? ($jud->judiciary_number . '/' . $jud->year) : '#' . $m->judiciary_id;
            return Html::a($label, ['/judiciary/judiciary/update', 'id' => $m->judiciary_id, 'contract_id' => $jud->contract_id ?? 0], ['class' => 'text-burgundy', 'style' => 'font-weight:600']);
        },
    ],

    /* العميل */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحكوم عليه',
        'value' => 'customers.name',
    ],

    /* الإجراء */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_actions_id',
        'label' => 'الإجراء',
        'value' => 'judiciaryActions.name',
    ],

    /* الملاحظات */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'note',
        'label' => 'ملاحظات',
        'contentOptions' => ['style' => 'max-width:200px;word-wrap:break-word;direction:rtl'],
    ],

    /* المنشئ */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => 'المنشئ',
        'value' => 'createdBy.username',
    ],

    /* المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحامي',
        'value' => fn($m) => $m->judiciary->lawyer->name ?? '—',
    ],

    /* المحكمة */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحكمة',
        'value' => fn($m) => $m->judiciary->court->name ?? '—',
    ],

    /* العقد */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => 'العقد',
        'format' => 'raw',
        'value' => function ($m) {
            $cid = $m->judiciary->contract_id ?? null;
            return $cid ? Html::a($cid, ['/followUp/follow-up/index', 'contract_id' => $cid], ['class' => 'text-burgundy']) : '—';
        },
    ],

    /* تاريخ الإجراء */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_date',
        'label' => 'تاريخ الإجراء',
        'contentOptions' => ['style' => 'white-space:nowrap'],
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:50px;text-align:center;overflow:visible;position:relative'],
        'header' => '',
        'template' => '{all}',
        'buttons' => [
            'all' => function($url, $m) {
                $contractID = 0;
                if ($m->judiciary_id) {
                    $jud = $m->judiciary;
                    if ($jud) $contractID = $jud->contract_id;
                }
                $viewUrl = Url::to(['view', 'id' => $m->id]);
                $editUrl = Url::to(['update-followup-judicary-custamer-action', 'id' => $m->id, 'contractID' => $contractID]);
                $delUrl  = Url::to(['delete', 'id' => $m->id]);

                return '<div class="jca-act-wrap">'
                    . '<button type="button" class="jca-act-trigger"><i class="fa fa-ellipsis-v"></i></button>'
                    . '<div class="jca-act-menu">'
                    .   '<a href="' . $viewUrl . '" role="modal-remote"><i class="fa fa-eye text-info"></i> عرض</a>'
                    .   '<a href="' . $editUrl . '" role="modal-remote"><i class="fa fa-pencil text-primary"></i> تعديل</a>'
                    .   '<div class="jca-act-divider"></div>'
                    .   '<a href="' . $delUrl . '" role="modal-remote" data-request-method="post" data-confirm-title="تأكيد الحذف" data-confirm-message="هل أنت متأكد من حذف هذا الإجراء؟"><i class="fa fa-trash text-danger"></i> حذف</a>'
                    . '</div>'
                    . '</div>';
            },
        ],
    ],
];
