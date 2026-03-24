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
        'contentOptions' => ['style' => 'font-weight:700;white-space:nowrap', 'data-label' => 'القضية'],
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
        'contentOptions' => ['class' => 'jca-name-cell', 'data-label' => 'المحكوم عليه'],
    ],

    /* الإجراء */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_actions_id',
        'label' => 'الإجراء',
        'value' => 'judiciaryActions.name',
        'headerOptions' => ['style' => 'width:110px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px', 'data-label' => 'الإجراء'],
    ],

    /* الملاحظات */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'note',
        'label' => 'ملاحظات',
        'format' => 'text',
        'headerOptions' => ['style' => 'width:200px'],
        'contentOptions' => ['class' => 'jca-notes-cell', 'data-label' => 'ملاحظات'],
    ],

    /* المنشئ */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => 'المنشئ',
        'value' => 'createdBy.username',
        'headerOptions' => ['style' => 'width:90px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px', 'data-label' => 'المنشئ'],
    ],

    /* المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحامي',
        'value' => fn($m) => $m->judiciary->lawyer->name ?? '—',
        'headerOptions' => ['style' => 'width:100px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px', 'data-label' => 'المحامي'],
    ],

    /* المحكمة */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحكمة',
        'value' => fn($m) => $m->judiciary->court->name ?? '—',
        'headerOptions' => ['style' => 'width:100px'],
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px', 'data-label' => 'المحكمة'],
    ],

    /* العقد */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => 'العقد',
        'format' => 'raw',
        'headerOptions' => ['style' => 'width:70px'],
        'contentOptions' => ['style' => 'font-weight:700;white-space:nowrap', 'data-label' => 'العقد'],
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
        'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px', 'data-label' => 'التاريخ'],
    ],

    /* حالة الإجراء */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'request_status',
        'label' => 'الحالة',
        'format' => 'raw',
        'headerOptions' => ['style' => 'width:90px'],
        'contentOptions' => ['style' => 'white-space:nowrap;text-align:center', 'data-label' => 'الحالة'],
        'value' => function ($m) {
            $label = $m->getRequestStatusLabel();
            if (!$label) return '<span style="color:#94A3B8">—</span>';
            $color = $m->getRequestStatusColor();
            $bgMap = [
                'printed'   => '#F3F4F6',
                'submitted' => '#EFF6FF',
                'pending'   => '#FFFBEB',
                'approved'  => '#ECFDF5',
                'rejected'  => '#FEF2F2',
            ];
            $bg = $bgMap[$m->request_status] ?? '#F1F5F9';
            return '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;background:'
                . $bg . ';color:' . $color . '">' . Html::encode($label) . '</span>';
        },
        'filter' => [
            'printed'   => 'مطبوع',
            'submitted' => 'مُقدَّم',
            'pending'   => 'معلق',
            'approved'  => 'موافقة',
            'rejected'  => 'مرفوض',
        ],
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'headerOptions' => ['style' => 'width:50px'],
        'contentOptions' => ['style' => 'width:50px;text-align:center;overflow:visible;position:relative', 'data-label' => ''],
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
