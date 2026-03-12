<?php
/**
 * عرض تفاصيل صنف — مع الأرقام التسلسلية المرتبطة
 */
use yii\helpers\Html;
use yii\widgets\DetailView;
use backend\modules\inventoryItems\models\InventorySerialNumber;

/* @var $model backend\modules\inventoryItems\models\InventoryItems */

$serials = InventorySerialNumber::find()
    ->where(['item_id' => $model->id])
    ->orderBy(['id' => SORT_DESC])
    ->all();

$serialStats = [
    'total'     => count($serials),
    'available' => 0,
    'sold'      => 0,
    'reserved'  => 0,
];
foreach ($serials as $s) {
    if (isset($serialStats[$s->status])) $serialStats[$s->status]++;
}
?>

<style>
.inventory-items-view table.detail-view th { font-weight: 700; color: #1e293b; background: #f1f5f9; font-size: 13px; }
.inventory-items-view table.detail-view td { font-size: 13.5px; color: #1e293b; }
.item-view-serials { margin-top: 18px; border-top: 2px solid #cbd5e1; padding-top: 14px; }
.item-view-serials h4 { font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 10px; }
.sn-mini-stats { display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
.sn-mini-stat { padding: 4px 12px; border-radius: 16px; font-size: 12px; font-weight: 700; }
.sn-mini-stat--total { background: #dbeafe; color: #1e40af; }
.sn-mini-stat--available { background: #dcfce7; color: #166534; }
.sn-mini-stat--sold { background: #ede9fe; color: #5b21b6; }
.sn-mini-stat--reserved { background: #fef3c7; color: #92400e; }
.sn-list { max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
.sn-list-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
.sn-list-item:last-child { border-bottom: none; }
.sn-list-item:hover { background: #f1f5f9; }
.sn-list-serial { font-family: monospace; font-weight: 700; direction: ltr; color: #0f172a; flex: 1; font-size: 13px; }
.sn-list-badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.sn-list-badge--available { background: #dcfce7; color: #166534; }
.sn-list-badge--reserved { background: #fef3c7; color: #92400e; }
.sn-list-badge--sold { background: #ede9fe; color: #5b21b6; }
.sn-list-badge--returned { background: #dbeafe; color: #1e40af; }
.sn-list-badge--defective { background: #fee2e2; color: #991b1b; }
</style>

<div class="inventory-items-view">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'item_name',
            'item_barcode',
            [
                'attribute' => 'category',
                'value' => $model->category ?: '—',
            ],
            [
                'attribute' => 'unit_price',
                'value' => $model->unit_price ? number_format($model->unit_price, 2) : '—',
            ],
            [
                'attribute' => 'unit',
                'value' => $model->unit ?: 'قطعة',
            ],
            [
                'attribute' => 'supplier_id',
                'value' => $model->supplier ? $model->supplier->name : '—',
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => '<span class="inv-badge ' . $model->getStatusCssClass() . '">' . Html::encode($model->getStatusLabel()) . '</span>',
            ],
            [
                'attribute' => 'created_by',
                'value' => $model->createdBy ? $model->createdBy->username : '—',
            ],
            [
                'attribute' => 'created_at',
                'value' => $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—',
            ],
        ],
    ]) ?>

    <?php if ($serialStats['total'] > 0): ?>
    <div class="item-view-serials">
        <h4><i class="fa fa-barcode"></i> الأرقام التسلسلية المرتبطة</h4>

        <div class="sn-mini-stats">
            <span class="sn-mini-stat sn-mini-stat--total"><i class="fa fa-barcode"></i> <?= $serialStats['total'] ?> إجمالي</span>
            <?php if ($serialStats['available']): ?>
                <span class="sn-mini-stat sn-mini-stat--available"><i class="fa fa-check"></i> <?= $serialStats['available'] ?> متاح</span>
            <?php endif ?>
            <?php if ($serialStats['sold']): ?>
                <span class="sn-mini-stat sn-mini-stat--sold"><i class="fa fa-shopping-cart"></i> <?= $serialStats['sold'] ?> مباع</span>
            <?php endif ?>
            <?php if ($serialStats['reserved']): ?>
                <span class="sn-mini-stat sn-mini-stat--reserved"><i class="fa fa-clock-o"></i> <?= $serialStats['reserved'] ?> محجوز</span>
            <?php endif ?>
        </div>

        <div class="sn-list">
            <?php foreach ($serials as $serial): ?>
            <div class="sn-list-item">
                <span class="sn-list-serial"><?= Html::encode($serial->serial_number) ?></span>
                <span class="sn-list-badge sn-list-badge--<?= $serial->status ?>">
                    <?= Html::encode($serial->getStatusLabel()) ?>
                </span>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

</div>
