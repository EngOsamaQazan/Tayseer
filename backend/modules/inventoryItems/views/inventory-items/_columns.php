<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  Inventory Items — Table Mode Columns (Pro)
 *  Tayseer ERP — نظام تيسير
 * ═══════════════════════════════════════════════════════════════
 */
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\inventoryItems\models\InventoryItems;
use common\helper\Permissions;

return [
    [
        'class' => '\kartik\grid\CheckboxColumn',
        'width' => '38px',
        'rowSelectedClass' => 'success',
        'contentOptions' => ['data-label' => 'تحديد'],
    ],
    [
        'class' => '\kartik\grid\SerialColumn',
        'width' => '40px',
        'contentOptions' => ['data-label' => '#'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'item_name',
        'label' => 'الصنف',
        'vAlign' => 'middle',
        'headerOptions' => ['style' => 'min-width:180px'],
        'contentOptions' => ['data-label' => 'الصنف'],
        'format' => 'raw',
        'value' => function ($model) {
            $name = Html::encode($model->item_name);
            $cat = $model->category
                ? '<br><span style="display:inline-block;padding:1px 7px;border-radius:4px;background:#e0f2fe;color:#075985;font-size:11px;font-weight:700;margin-top:2px">' . Html::encode($model->category) . '</span>'
                : '';
            return '<strong style="color:#0f172a">' . $name . '</strong>' . $cat;
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'item_barcode',
        'label' => 'الباركود',
        'vAlign' => 'middle',
        'contentOptions' => [
            'data-label' => 'الباركود',
            'style' => 'direction:ltr; font-family:Courier New,monospace; font-weight:600; font-size:12.5px',
        ],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المخزون',
        'vAlign' => 'middle',
        'width' => '180px',
        'contentOptions' => ['data-label' => 'المخزون'],
        'format' => 'raw',
        'value' => function ($model) {
            $stock = $model->getTotalStock();
            $min   = (int) $model->min_stock_level;

            $level = 'ok';
            if ($stock <= 0)            $level = 'out';
            elseif ($min > 0 && $stock < $min) $level = 'low';

            $cap = $min > 0 ? max($min * 2, $min + 1) : max($stock, 100);
            $percent = $cap > 0 ? min(100, max(0, ($stock / $cap) * 100)) : 0;

            $colors = [
                'ok'  => 'linear-gradient(90deg, #15803d 0%, #22c55e 100%)',
                'low' => 'linear-gradient(90deg, #d97706 0%, #f59e0b 100%)',
                'out' => '#b91c1c',
            ];
            $textColor = ['ok' => '#15803d', 'low' => '#b45309', 'out' => '#b91c1c'][$level];

            $unit = $model->unit ? ' <small style="color:#94a3b8;font-weight:600">' . Html::encode($model->unit) . '</small>' : '';

            $hint = '';
            if ($min > 0) {
                $hint = '<div style="font-size:10.5px;color:#94a3b8;margin-top:2px">حد أدنى: ' . number_format($min) . '</div>';
            }

            return '
                <div style="min-width:140px">
                    <div style="display:flex;justify-content:space-between;align-items:center;font-weight:800;color:' . $textColor . ';font-variant-numeric:tabular-nums">
                        <span>' . number_format($stock) . $unit . '</span>
                    </div>
                    <div style="height:5px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin-top:4px">
                        <div style="height:100%;background:' . $colors[$level] . ';width:' . number_format($percent, 1) . '%;border-radius:999px"></div>
                    </div>
                    ' . $hint . '
                </div>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'unit_price',
        'label' => 'السعر',
        'vAlign' => 'middle',
        'width' => '100px',
        'contentOptions' => ['data-label' => 'السعر'],
        'format' => 'raw',
        'value' => function ($model) {
            if (!$model->unit_price) return '<span style="color:#cbd5e1">—</span>';
            return '<span style="font-weight:700;font-variant-numeric:tabular-nums">' . number_format($model->unit_price, 2) . '</span> <small style="color:#94a3b8">د.أ</small>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'القيمة',
        'vAlign' => 'middle',
        'width' => '110px',
        'contentOptions' => ['data-label' => 'قيمة المخزون'],
        'format' => 'raw',
        'value' => function ($model) {
            $value = $model->getTotalStock() * (float) $model->unit_price;
            if ($value <= 0) return '<span style="color:#cbd5e1">—</span>';
            return '<span style="font-weight:800;color:#6d28d9;font-variant-numeric:tabular-nums">' . number_format($value, 0) . '</span> <small style="color:#94a3b8">د.أ</small>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الدوران',
        'vAlign' => 'middle',
        'width' => '80px',
        'contentOptions' => ['data-label' => 'معدل الدوران'],
        'format' => 'raw',
        'value' => function ($model) {
            $turnover = method_exists($model, 'getTurnover') ? $model->getTurnover() : null;
            if ($turnover === null) return '<span style="color:#cbd5e1" title="بيانات غير كافية">—</span>';

            if ($turnover >= 4)      { $bg = '#dcfce7'; $color = '#15803d'; $level = 'صحي'; }
            elseif ($turnover >= 1)  { $bg = '#fef3c7'; $color = '#b45309'; $level = 'بطيء'; }
            else                     { $bg = '#fee2e2'; $color = '#b91c1c'; $level = 'راكد'; }

            return '<span style="display:inline-flex;gap:3px;align-items:center;padding:2px 8px;border-radius:999px;background:' . $bg . ';color:' . $color . ';font-weight:700;font-size:11.5px" title="' . $level . ' — ' . number_format($turnover, 1) . ' مرة/سنة">'
                . '<i class="fa fa-refresh"></i> ' . number_format($turnover, 1) . '×</span>';
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
            $bgs = [
                'draft'    => ['#f1f5f9', '#475569'],
                'pending'  => ['#fef3c7', '#b45309'],
                'approved' => ['#dcfce7', '#15803d'],
                'rejected' => ['#fee2e2', '#b91c1c'],
            ];
            $b = $bgs[$model->status] ?? ['#f1f5f9', '#475569'];
            $icon = $icons[$model->status] ?? 'fa-question';
            return '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;background:' . $b[0] . ';color:' . $b[1] . ';font-size:11.5px;font-weight:700">'
                 . '<i class="fa ' . $icon . '"></i> '
                 . Html::encode($model->getStatusLabel())
                 . '</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'التاريخ',
        'attribute' => 'created_at',
        'vAlign' => 'middle',
        'width' => '110px',
        'contentOptions' => ['data-label' => 'التاريخ'],
        'format' => 'raw',
        'value' => function ($model) {
            if (!$model->created_at) return '<span style="color:#cbd5e1">—</span>';
            $date = date('Y-m-d', $model->created_at);
            $diff = time() - (int) $model->created_at;
            $rel  = '';
            if ($diff < 86400)              $rel = 'اليوم';
            elseif ($diff < 86400 * 7)      $rel = floor($diff / 86400) . ' يوم';
            elseif ($diff < 86400 * 30)     $rel = floor($diff / 86400 / 7) . ' أسبوع';
            return '<div style="font-size:12px;color:#475569">' . $date . '</div>'
                 . ($rel ? '<div style="font-size:10.5px;color:#94a3b8">' . $rel . '</div>' : '');
        },
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'header' => 'إجراءات',
        'template' => '{approve} {reject} {view} {update} {delete}',
        'dropdown' => false,
        'vAlign' => 'middle',
        'width' => '200px',
        'contentOptions' => ['data-label' => '', 'style' => 'white-space:nowrap'],
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'buttons' => [
            'approve' => function ($url, $model) {
                if ($model->status !== 'pending') return '';
                if (!Permissions::can(Permissions::INVITEM_UPDATE)) return '';
                return '<button class="btn btn-xs inv-approve-btn" data-id="' . $model->id . '" title="اعتماد" style="background:#15803d;color:#fff;border:none;padding:3px 9px;border-radius:5px;font-weight:700;font-size:11px;margin-inline-end:3px"><i class="fa fa-check"></i> اعتماد</button>';
            },
            'reject' => function ($url, $model) {
                if ($model->status !== 'pending') return '';
                if (!Permissions::can(Permissions::INVITEM_UPDATE)) return '';
                return '<button class="btn btn-xs inv-reject-btn" data-id="' . $model->id . '" title="رفض" style="background:#b91c1c;color:#fff;border:none;padding:3px 9px;border-radius:5px;font-weight:700;font-size:11px;margin-inline-end:3px"><i class="fa fa-times"></i> رفض</button>';
            },
        ],
        'visibleButtons' => [
            'view'   => true,
            'update' => Permissions::can(Permissions::INVITEM_UPDATE),
            'delete' => Permissions::can(Permissions::INVITEM_DELETE),
        ],
        'viewOptions'   => ['title' => 'عرض', 'data-bs-toggle' => 'tooltip', 'class' => 'btn btn-xs btn-secondary', 'role' => 'modal-remote', 'data-pjax' => 0],
        'updateOptions' => ['title' => 'تعديل', 'data-bs-toggle' => 'tooltip', 'class' => 'btn btn-xs btn-info', 'role' => 'modal-remote', 'data-pjax' => 0],
        'deleteOptions' => [
            'title' => 'حذف',
            'data-confirm' => false,
            'data-method' => false,
            'data-request-method' => 'post',
            'data-bs-toggle' => 'tooltip',
            'data-confirm-title' => 'تأكيد الحذف',
            'data-confirm-message' => 'هل أنت متأكد من حذف هذا الصنف؟',
            'class' => 'btn btn-xs btn-danger',
        ],
    ],
];
