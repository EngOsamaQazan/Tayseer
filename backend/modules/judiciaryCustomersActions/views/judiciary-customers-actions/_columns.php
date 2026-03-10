<?php
/**
 * أعمدة جدول إجراءات العملاء القضائية
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
        'headerOptions' => ['style' => 'width:80px'],
        'contentOptions' => ['style' => 'font-weight:700;white-space:nowrap'],
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
        'headerOptions' => ['style' => 'width:140px'],
        'contentOptions' => ['class' => 'jca-name-cell'],
    ],

    /* الإجراء */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_actions_id',
        'label' => 'الإجراء',
        'value' => 'judiciaryActions.name',
        'headerOptions' => ['style' => 'width:110px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px'],
    ],

    /* الملاحظات */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'note',
        'label' => 'ملاحظات',
        'format' => 'text',
        'headerOptions' => ['style' => 'width:200px'],
        'contentOptions' => ['class' => 'jca-notes-cell'],
    ],

    /* المنشئ */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => 'المنشئ',
        'value' => 'createdBy.username',
        'headerOptions' => ['style' => 'width:90px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px'],
    ],

    /* المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحامي',
        'value' => fn($m) => $m->judiciary->lawyer->name ?? '—',
        'headerOptions' => ['style' => 'width:100px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px'],
    ],

    /* المحكمة */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحكمة',
        'value' => fn($m) => $m->judiciary->court->name ?? '—',
        'headerOptions' => ['style' => 'width:100px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px'],
    ],

    /* العقد */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => 'العقد',
        'format' => 'raw',
        'headerOptions' => ['style' => 'width:70px'],
        'contentOptions' => ['style' => 'font-weight:700;white-space:nowrap'],
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
        'headerOptions' => ['style' => 'width:95px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px'],
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'headerOptions' => ['style' => 'width:50px'],
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
                $viewUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/view', 'id' => $m->id]);
                $editUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $m->id, 'contractID' => $contractID]);
                $delUrl  = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/delete', 'id' => $m->id]);

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
