<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\inventoryInvoices\models\InventoryInvoices;
use common\helper\Permissions;

return [
    [
        'class' => '\kartik\grid\SerialColumn',
        'width' => '40px',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => 'رقم الأمر',
        'vAlign' => 'middle',
        'width' => '80px',
        'format' => 'raw',
        'value' => function ($model) {
            return '<strong style="color:#0369a1">#' . $model->id . '</strong>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'branch_id',
        'label' => 'موقع التخزين',
        'vAlign' => 'middle',
        'value' => function ($model) {
            return $model->stockLocation ? $model->stockLocation->locations_name : '—';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'suppliers_id',
        'label' => 'المورد',
        'vAlign' => 'middle',
        'value' => function ($model) {
            return $model->suppliers ? $model->suppliers->name : '—';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'company_id',
        'label' => 'الشركة',
        'vAlign' => 'middle',
        'value' => function ($model) {
            return $model->company ? $model->company->name : '—';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'type',
        'label' => 'نوع الدفع',
        'vAlign' => 'middle',
        'width' => '100px',
        'format' => 'raw',
        'value' => function ($model) {
            $classes = [0 => 'po-type--cash', 1 => 'po-type--credit', 2 => 'po-type--mixed'];
            $cls = $classes[$model->type] ?? 'po-type--cash';
            return '<span class="po-type ' . $cls . '">' . $model->getTypeLabel() . '</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'total_amount',
        'label' => 'المبلغ',
        'vAlign' => 'middle',
        'width' => '110px',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->total_amount ? '<strong>' . number_format($model->total_amount, 2) . '</strong>' : '—';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'date',
        'label' => 'التاريخ',
        'vAlign' => 'middle',
        'width' => '110px',
        'value' => function ($model) {
            return $model->date ?: ($model->created_at ? date('Y-m-d', $model->created_at) : '—');
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => 'بواسطة',
        'vAlign' => 'middle',
        'value' => function ($model) {
            return $model->createdBy ? $model->createdBy->username : '—';
        },
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'header' => 'إجراءات',
        'dropdown' => false,
        'vAlign' => 'middle',
        'width' => '120px',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'visibleButtons' => [
            'view' => true,
            'update' => Permissions::can(Permissions::INVINV_UPDATE),
            'delete' => Permissions::can(Permissions::INVINV_DELETE),
        ],
        'viewOptions' => ['title' => 'عرض', 'data-toggle' => 'tooltip', 'class' => 'btn btn-xs btn-default', 'role' => 'modal-remote'],
        'updateOptions' => ['title' => 'تعديل', 'data-toggle' => 'tooltip', 'class' => 'btn btn-xs btn-info'],
        'deleteOptions' => [
            'title' => 'حذف', 'data-confirm' => false, 'data-method' => false,
            'data-request-method' => 'post', 'data-toggle' => 'tooltip',
            'data-confirm-title' => 'تأكيد الحذف', 'data-confirm-message' => 'هل أنت متأكد من حذف أمر الشراء هذا؟ سيتم تعديل كميات المخزون تلقائياً.',
            'class' => 'btn btn-xs btn-danger',
        ],
    ],
];
