<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\inventoryItems\models\InventoryItems;
use common\helper\Permissions;

return [
    [
        'class' => '\kartik\grid\SerialColumn',
        'width' => '40px',
        'contentOptions' => ['data-label' => '#'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'item_name',
        'label' => 'اسم الصنف',
        'vAlign' => 'middle',
        'headerOptions' => ['style' => 'min-width:140px'],
        'contentOptions' => ['data-label' => 'اسم الصنف'],
        'format' => 'raw',
        'value' => function ($model) {
            $name = Html::encode($model->item_name);
            $cat = $model->category ? '<br><small style="color:#94a3b8">' . Html::encode($model->category) . '</small>' : '';
            return '<strong>' . $name . '</strong>' . $cat;
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'item_barcode',
        'label' => 'الباركود',
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => 'الباركود', 'style' => 'direction:ltr; font-family:monospace; font-weight:600'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المخزون',
        'vAlign' => 'middle',
        'width' => '100px',
        'contentOptions' => ['data-label' => 'المخزون'],
        'format' => 'raw',
        'value' => function ($model) {
            $stock = $model->getTotalStock();
            if ($stock <= 0) {
                return '<span class="inv-stock-tag inv-stock-zero"><i class="fa fa-minus-circle"></i> 0</span>';
            }
            if ($model->min_stock_level > 0 && $stock < $model->min_stock_level) {
                return '<span class="inv-stock-tag inv-stock-low"><i class="fa fa-exclamation-triangle"></i> ' . $stock . '</span>';
            }
            return '<span class="inv-stock-tag inv-stock-ok"><i class="fa fa-check"></i> ' . $stock . '</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'unit_price',
        'label' => 'السعر',
        'vAlign' => 'middle',
        'width' => '90px',
        'contentOptions' => ['data-label' => 'السعر'],
        'format' => 'raw',
        'value' => function ($model) {
            return $model->unit_price ? number_format($model->unit_price, 2) : '<span style="color:#cbd5e1">—</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
        'label' => 'الحالة',
        'vAlign' => 'middle',
        'width' => '130px',
        'contentOptions' => ['data-label' => 'الحالة'],
        'filter' => InventoryItems::getStatusList(),
        'format' => 'raw',
        'value' => function ($model) {
            $icons = [
                'draft'    => 'fa-pencil',
                'pending'  => 'fa-clock-o',
                'approved' => 'fa-check-circle',
                'rejected' => 'fa-times-circle',
            ];
            $icon = $icons[$model->status] ?? 'fa-question';
            return '<span class="inv-badge ' . $model->getStatusCssClass() . '">'
                 . '<i class="fa ' . $icon . '"></i> '
                 . Html::encode($model->getStatusLabel())
                 . '</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => 'أنشئ بواسطة',
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => 'أنشئ بواسطة'],
        'value' => 'createdBy.username',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'التاريخ',
        'attribute' => 'created_at',
        'vAlign' => 'middle',
        'contentOptions' => ['data-label' => 'التاريخ'],
        'format' => 'raw',
        'value' => function ($model) {
            return $model->created_at ? date('Y-m-d', $model->created_at) : '-';
        },
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'header' => 'إجراءات',
        'template' => '{approve} {reject} {view} {update} {delete}',
        'dropdown' => false,
        'vAlign' => 'middle',
        'width' => '200px',
        'contentOptions' => ['data-label' => ''],
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'buttons' => [
            'approve' => function ($url, $model) {
                if ($model->status !== 'pending') return '';
                if (!Permissions::can(Permissions::INVITEM_UPDATE)) return '';
                return '<button class="inv-approve-btn" data-id="' . $model->id . '" title="اعتماد"><i class="fa fa-check"></i> اعتماد</button>';
            },
            'reject' => function ($url, $model) {
                if ($model->status !== 'pending') return '';
                if (!Permissions::can(Permissions::INVITEM_UPDATE)) return '';
                return '<button class="inv-reject-btn" data-id="' . $model->id . '" title="رفض"><i class="fa fa-times"></i> رفض</button>';
            },
        ],
        'visibleButtons' => [
            'view' => true,
            'update' => Permissions::can(Permissions::INVITEM_UPDATE),
            'delete' => Permissions::can(Permissions::INVITEM_DELETE),
        ],
        'viewOptions' => ['title' => 'عرض', 'data-bs-toggle' => 'tooltip', 'class' => 'btn btn-xs btn-secondary', 'role' => 'modal-remote', 'data-pjax' => 0],
        'updateOptions' => ['title' => 'تعديل', 'data-bs-toggle' => 'tooltip', 'class' => 'btn btn-xs btn-info', 'role' => 'modal-remote', 'data-pjax' => 0],
        'deleteOptions' => [
            'title' => 'حذف', 'data-confirm' => false, 'data-method' => false,
            'data-request-method' => 'post', 'data-bs-toggle' => 'tooltip',
            'data-confirm-title' => 'تأكيد الحذف', 'data-confirm-message' => 'هل أنت متأكد من حذف هذا الصنف؟',
            'class' => 'btn btn-xs btn-danger',
        ],
    ],
];
