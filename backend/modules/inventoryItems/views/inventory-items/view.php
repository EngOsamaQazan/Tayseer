<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  Inventory Item — Pro Detail View
 *  Tayseer ERP — نظام تيسير
 *  Replaces yii\widgets\DetailView with .inv-detail-pro layout
 * ═══════════════════════════════════════════════════════════════
 */
use yii\helpers\Html;
use backend\modules\inventoryItems\models\InventorySerialNumber;

/** @var $this yii\web\View */
/** @var $model backend\modules\inventoryItems\models\InventoryItems */

$baseUrl = Yii::$app->request->baseUrl;
$this->registerCssFile($baseUrl . '/css/inv-items-pro.css?v=3');

$serials = InventorySerialNumber::find()
    ->where(['item_id' => $model->id])
    ->orderBy(['id' => SORT_DESC])
    ->all();

$serialStats = [
    'total'     => count($serials),
    'available' => 0,
    'sold'      => 0,
    'reserved'  => 0,
    'returned'  => 0,
    'defective' => 0,
];
foreach ($serials as $s) {
    if (isset($serialStats[$s->status])) $serialStats[$s->status]++;
}

$stock = $model->getTotalStock();
$min   = (int) $model->min_stock_level;
$value = $stock * (float) $model->unit_price;

$stockLevel = 'ok';
if ($stock <= 0)            $stockLevel = 'out';
elseif ($min > 0 && $stock < $min) $stockLevel = 'low';

$statusBadgeClass = [
    'approved' => 'inv-pro-badge--success',
    'pending'  => 'inv-pro-badge--warning',
    'rejected' => 'inv-pro-badge--danger',
    'draft'    => '',
][$model->status] ?? '';

$statusIcon = [
    'approved' => 'fa-check-circle',
    'pending'  => 'fa-clock-o',
    'rejected' => 'fa-times-circle',
    'draft'    => 'fa-pencil',
][$model->status] ?? 'fa-question';

$turnover = method_exists($model, 'getTurnover') ? $model->getTurnover() : null;
?>

<div class="inv-detail-pro">

    <div class="inv-detail-hero">
        <div class="inv-detail-hero-avatar">
            <i class="fa fa-cube"></i>
        </div>
        <div class="inv-detail-hero-main">
            <h2 class="inv-detail-hero-title"><?= Html::encode($model->item_name) ?></h2>
            <div class="inv-detail-hero-meta">
                <span style="font-family:Courier New,monospace;direction:ltr;font-weight:700">
                    <i class="fa fa-barcode"></i> <?= Html::encode($model->item_barcode) ?>
                </span>
                <span class="inv-pro-badge <?= $statusBadgeClass ?>">
                    <i class="fa <?= $statusIcon ?>"></i> <?= Html::encode($model->getStatusLabel()) ?>
                </span>
                <?php if ($model->category): ?>
                    <span class="inv-pro-badge inv-pro-badge--info">
                        <i class="fa fa-folder-o"></i> <?= Html::encode($model->category) ?>
                    </span>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="inv-detail-grid">
        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-archive"></i> المخزون المتوفر</span>
            <span class="inv-detail-row-val" style="color:<?= $stockLevel === 'out' ? '#b91c1c' : ($stockLevel === 'low' ? '#b45309' : '#15803d') ?>">
                <?= number_format($stock) ?>
                <?php if ($model->unit): ?>
                    <small style="color:#94a3b8;font-weight:600;font-size:11.5px"><?= Html::encode($model->unit) ?></small>
                <?php endif ?>
                <?php if ($stockLevel !== 'ok'): ?>
                    <span class="inv-pro-badge <?= $stockLevel === 'out' ? 'inv-pro-badge--danger' : 'inv-pro-badge--warning' ?>" style="margin-inline-start:6px">
                        <?= $stockLevel === 'out' ? 'نافد' : 'تحت الحد' ?>
                    </span>
                <?php endif ?>
            </span>
        </div>

        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-exclamation-triangle"></i> الحد الأدنى</span>
            <span class="inv-detail-row-val">
                <?= $min > 0 ? number_format($min) : '<span style="color:#94a3b8">لم يُحدّد</span>' ?>
            </span>
        </div>

        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-tag"></i> سعر الوحدة</span>
            <span class="inv-detail-row-val">
                <?= $model->unit_price ? number_format($model->unit_price, 2) . ' <small style="color:#94a3b8;font-weight:600">د.أ</small>' : '<span style="color:#94a3b8">—</span>' ?>
            </span>
        </div>

        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-money"></i> قيمة المخزون</span>
            <span class="inv-detail-row-val" style="color:#6d28d9">
                <?= $value > 0 ? number_format($value, 2) . ' <small style="color:#94a3b8;font-weight:600">د.أ</small>' : '<span style="color:#94a3b8">—</span>' ?>
            </span>
        </div>

        <?php if ($turnover !== null): ?>
        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-refresh"></i> معدّل الدوران</span>
            <span class="inv-detail-row-val">
                <?php
                $tColor = $turnover >= 4 ? '#15803d' : ($turnover >= 1 ? '#b45309' : '#b91c1c');
                $tLevel = $turnover >= 4 ? 'صحي' : ($turnover >= 1 ? 'بطيء' : 'راكد');
                ?>
                <span style="color:<?= $tColor ?>"><?= number_format($turnover, 1) ?>×/سنة</span>
                <span class="inv-pro-badge" style="margin-inline-start:6px;background:<?= $tColor ?>;color:#fff;border-color:<?= $tColor ?>">
                    <?= $tLevel ?>
                </span>
            </span>
        </div>
        <?php endif ?>

        <?php if ($model->supplier): ?>
        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-truck"></i> المورد</span>
            <span class="inv-detail-row-val"><?= Html::encode($model->supplier->name) ?></span>
        </div>
        <?php endif ?>

        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-user"></i> أنشئ بواسطة</span>
            <span class="inv-detail-row-val">
                <?= $model->createdBy ? Html::encode($model->createdBy->username) : '<span style="color:#94a3b8">—</span>' ?>
            </span>
        </div>

        <div class="inv-detail-row">
            <span class="inv-detail-row-key"><i class="fa fa-calendar"></i> تاريخ الإنشاء</span>
            <span class="inv-detail-row-val" style="font-variant-numeric:tabular-nums">
                <?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—' ?>
            </span>
        </div>

        <?php if ($model->description): ?>
        <div class="inv-detail-row inv-detail-row--full">
            <span class="inv-detail-row-key"><i class="fa fa-align-right"></i> الوصف</span>
            <span class="inv-detail-row-val" style="font-weight:500;line-height:1.6">
                <?= nl2br(Html::encode($model->description)) ?>
            </span>
        </div>
        <?php endif ?>
    </div>

    <?php if ($serialStats['total'] > 0): ?>
    <div class="inv-form-section">
        <h4 class="inv-form-section-title">
            <i class="fa fa-barcode"></i> الأرقام التسلسلية المرتبطة
        </h4>

        <div style="display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap">
            <span class="inv-pro-badge inv-pro-badge--info">
                <i class="fa fa-list"></i> الإجمالي <?= $serialStats['total'] ?>
            </span>
            <?php if ($serialStats['available']): ?>
                <span class="inv-pro-badge inv-pro-badge--success">
                    <i class="fa fa-check"></i> متاح <?= $serialStats['available'] ?>
                </span>
            <?php endif ?>
            <?php if ($serialStats['sold']): ?>
                <span class="inv-pro-badge inv-pro-badge--purple">
                    <i class="fa fa-shopping-cart"></i> مباع <?= $serialStats['sold'] ?>
                </span>
            <?php endif ?>
            <?php if ($serialStats['reserved']): ?>
                <span class="inv-pro-badge inv-pro-badge--warning">
                    <i class="fa fa-clock-o"></i> محجوز <?= $serialStats['reserved'] ?>
                </span>
            <?php endif ?>
            <?php if ($serialStats['defective']): ?>
                <span class="inv-pro-badge inv-pro-badge--danger">
                    <i class="fa fa-warning"></i> تالف <?= $serialStats['defective'] ?>
                </span>
            <?php endif ?>
        </div>

        <div style="max-height:240px;overflow-y:auto;border:1px solid var(--inv-pro-border);border-radius:9px;background:#fff">
            <?php foreach ($serials as $serial):
                $sBadge = [
                    'available' => 'inv-pro-badge--success',
                    'reserved'  => 'inv-pro-badge--warning',
                    'sold'      => 'inv-pro-badge--purple',
                    'returned'  => 'inv-pro-badge--info',
                    'defective' => 'inv-pro-badge--danger',
                ][$serial->status] ?? '';
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:9px 13px;border-bottom:1px solid var(--inv-pro-border);font-size:13px">
                <span style="font-family:Courier New,monospace;font-weight:700;direction:ltr;color:var(--inv-pro-text-1);flex:1;font-size:12.5px">
                    <?= Html::encode($serial->serial_number) ?>
                </span>
                <span class="inv-pro-badge <?= $sBadge ?>">
                    <?= Html::encode($serial->getStatusLabel()) ?>
                </span>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

</div>
