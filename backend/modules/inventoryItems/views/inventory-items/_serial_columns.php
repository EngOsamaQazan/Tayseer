<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\inventoryItems\models\InventorySerialNumber;

return [
    [
        'class' => '\kartik\grid\SerialColumn',
        'width' => '40px',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'serial_number',
        'label' => 'الرقم التسلسلي',
        'vAlign' => 'middle',
        'headerOptions' => ['style' => 'min-width:180px'],
        'format' => 'raw',
        'value' => function ($model) {
            return '<span class="sn-serial-cell">' . Html::encode($model->serial_number) . '</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'item_name',
        'label' => 'الصنف',
        'vAlign' => 'middle',
        'headerOptions' => ['style' => 'min-width:140px'],
        'format' => 'raw',
        'value' => function ($model) {
            if ($model->item) {
                $name = Html::encode($model->item->item_name);
                $barcode = '<br><small style="color:#94a3b8;direction:ltr;font-family:monospace">' . Html::encode($model->item->item_barcode) . '</small>';
                return '<strong>' . $name . '</strong>' . $barcode;
            }
            return '<span style="color:#cbd5e1">—</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
        'label' => 'الحالة',
        'vAlign' => 'middle',
        'width' => '150px',
        'filter' => InventorySerialNumber::getStatusList(),
        'format' => 'raw',
        'value' => function ($model) {
            $statuses = InventorySerialNumber::getStatusList();
            $options = '';
            foreach ($statuses as $key => $label) {
                $selected = ($key === $model->status) ? ' selected' : '';
                $options .= "<option value=\"{$key}\"{$selected}>{$label}</option>";
            }
            return '<select class="sn-status-select form-control input-sm" data-id="' . $model->id . '" data-original="' . $model->status . '" style="font-size:12px;font-weight:700;padding:2px 6px;border-radius:8px">'
                . $options . '</select>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'supplier_id',
        'label' => 'المورد',
        'vAlign' => 'middle',
        'value' => function ($model) {
            return $model->supplier ? $model->supplier->name : '—';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'location_id',
        'label' => 'الموقع',
        'vAlign' => 'middle',
        'value' => function ($model) {
            return $model->location ? $model->location->locations_name : '—';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'note',
        'label' => 'ملاحظات',
        'vAlign' => 'middle',
        'format' => 'raw',
        'value' => function ($model) {
            $note = (string)($model->note ?? '');
            return $note !== '' ? '<span title="' . Html::encode($note) . '">' . Html::encode(mb_substr($note, 0, 30)) . (mb_strlen($note) > 30 ? '...' : '') . '</span>' : '<span style="color:#cbd5e1">—</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'التاريخ',
        'attribute' => 'created_at',
        'vAlign' => 'middle',
        'width' => '100px',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->created_at ? date('Y-m-d', $model->created_at) : '—';
        },
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'header' => 'إجراءات',
        'template' => '{serial-view} {serial-update} {serial-delete}',
        'dropdown' => false,
        'vAlign' => 'middle',
        'width' => '150px',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'buttons' => [
            'serial-view' => function ($url, $model) {
                return Html::a('<i class="fa fa-eye"></i>', $url, [
                    'class' => 'btn btn-xs btn-default', 'title' => 'عرض',
                    'role' => 'modal-remote', 'data-toggle' => 'tooltip',
                ]);
            },
            'serial-update' => function ($url, $model) {
                return Html::a('<i class="fa fa-pencil"></i>', $url, [
                    'class' => 'btn btn-xs btn-info', 'title' => 'تعديل',
                    'role' => 'modal-remote', 'data-toggle' => 'tooltip',
                ]);
            },
            'serial-delete' => function ($url, $model) {
                return Html::a('<i class="fa fa-trash"></i>', $url, [
                    'class' => 'btn btn-xs btn-danger', 'title' => 'حذف',
                    'data-confirm' => false, 'data-method' => false,
                    'data-request-method' => 'post', 'data-toggle' => 'tooltip',
                    'data-confirm-title' => 'تأكيد الحذف',
                    'data-confirm-message' => 'هل أنت متأكد من حذف هذا الرقم التسلسلي؟',
                ]);
            },
        ],
    ],
];
