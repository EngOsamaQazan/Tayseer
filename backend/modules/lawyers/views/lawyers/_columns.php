<?php

use yii\helpers\Url;
use backend\modules\lawyers\models\Lawyers;

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'label' => 'الاسم',
        'contentOptions' => ['data-label' => 'الاسم'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'النوع',
        'contentOptions' => ['data-label' => 'النوع'],
        'format' => 'raw',
        'value' => function ($model) {
            if ($model->representative_type === Lawyers::REP_TYPE_LAWYER) {
                return '<span class="lw-badge lw-badge--lawyer"><i class="fa fa-gavel"></i> وكيل محامي</span>';
            }
            return '<span class="lw-badge lw-badge--delegate"><i class="fa fa-user"></i> مفوض</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'phone_number',
        'label' => 'الهاتف',
        'contentOptions' => ['data-label' => 'الهاتف'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
        'label' => 'الحالة',
        'contentOptions' => ['data-label' => 'الحالة'],
        'format' => 'raw',
        'value' => function ($model) {
            return ($model->status == 0)
                ? '<span style="color:#28a745;font-weight:600"><i class="fa fa-check-circle"></i> نشط</span>'
                : '<span style="color:#94a3b8"><i class="fa fa-minus-circle"></i> غير نشط</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'التوقيع',
        'contentOptions' => ['data-label' => 'التوقيع'],
        'format' => 'raw',
        'value' => function ($model) {
            if ($model->representative_type === Lawyers::REP_TYPE_LAWYER && $model->signature_image) {
                return '<span class="lw-sig-ok"><i class="fa fa-check-circle"></i> مرفق</span>';
            }
            if ($model->representative_type === Lawyers::REP_TYPE_LAWYER) {
                return '<span class="lw-sig-no"><i class="fa fa-minus-circle"></i> غير مرفق</span>';
            }
            return '<span class="lw-sig-no">—</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => 'أنشأ بواسطة',
        'contentOptions' => ['data-label' => 'أنشأ بواسطة'],
        'value' => 'createdBy.username',
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['title' => 'عرض', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['title' => 'تعديل', 'data-toggle' => 'tooltip'],
        'deleteOptions' => [
            'title' => 'حذف',
            'data-confirm' => false,
            'data-method' => false,
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'هل أنت متأكد؟',
            'data-confirm-message' => 'هل أنت متأكد من حذف هذا العنصر؟',
        ],
    ],
];
