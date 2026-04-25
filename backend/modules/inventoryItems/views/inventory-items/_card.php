<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  Inventory Item Card — Professional Template
 *  Tayseer ERP — نظام تيسير
 *  Standards: ISO 9241-110/125/171, WCAG 2.2 AA
 *  ─────────────────────────────────────────────────────────────
 *  Variables:
 *    $model       : InventoryItems
 *    $stock       : int total stock
 *    $turnover    : float|null  (X times/year, null if unavailable)
 *    $canUpdate   : bool
 *    $canDelete   : bool
 * ═══════════════════════════════════════════════════════════════
 */
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\inventoryItems\models\InventoryItems;

/** @var \backend\modules\inventoryItems\models\InventoryItems $model */
/** @var int $stock */
/** @var float|null $turnover */
/** @var bool $canUpdate */
/** @var bool $canDelete */

$status = $model->status;
$min    = (int) $model->min_stock_level;
$price  = (float) $model->unit_price;
$value  = $stock * $price;

/* تحديد مستوى المخزون */
$stockLevel = 'ok';
if ($stock <= 0)            $stockLevel = 'out';
elseif ($min > 0 && $stock < $min) $stockLevel = 'low';

/* نسبة الشريط: لو فيه min، نقيس مقابله؛ لو لا، نستخدم cap منطقي */
$cap     = $min > 0 ? max($min * 2, $min + 1) : max($stock, 100);
$percent = $cap > 0 ? min(100, max(0, ($stock / $cap) * 100)) : 0;

/* تصنيف معدل الدوران */
$turnoverLevel = null;
if ($turnover !== null) {
    if      ($turnover >= 4)   $turnoverLevel = 'healthy';
    elseif  ($turnover >= 1)   $turnoverLevel = 'slow';
    else                       $turnoverLevel = 'stale';
}

/* خرائط visual */
$stripeMap = [
    'approved' => 'var(--inv-success)',
    'pending'  => 'var(--inv-warning)',
    'rejected' => 'var(--inv-danger)',
    'draft'    => 'var(--inv-text-3)',
];
$stripeColor = $stripeMap[$status] ?? 'var(--inv-info)';
if ($stockLevel === 'out')      $stripeColor = 'var(--inv-danger)';
elseif ($stockLevel === 'low')  $stripeColor = '#d97706';

$cardClasses = ['inv-card'];
if ($stockLevel === 'low')          $cardClasses[] = 'inv-card--low';
if ($stockLevel === 'out')          $cardClasses[] = 'inv-card--out';
if ($status === 'rejected')         $cardClasses[] = 'inv-card--rejected';

$statusIcons = [
    'draft'    => 'fa-pencil',
    'pending'  => 'fa-clock-o',
    'approved' => 'fa-check-circle',
    'rejected' => 'fa-times-circle',
];

$daysAgo = '';
if ($model->updated_at) {
    $diff = time() - (int) $model->updated_at;
    if ($diff < 60)              $daysAgo = 'الآن';
    elseif ($diff < 3600)        $daysAgo = floor($diff / 60) . ' د';
    elseif ($diff < 86400)       $daysAgo = floor($diff / 3600) . ' س';
    elseif ($diff < 86400 * 30)  $daysAgo = floor($diff / 86400) . ' يوم';
    else                         $daysAgo = date('Y-m-d', $model->updated_at);
}

$ariaLabel = sprintf(
    'الصنف %s، باركود %s، المخزون %d قطعة، الحالة %s',
    $model->item_name,
    $model->item_barcode,
    $stock,
    $model->getStatusLabel()
);
?>

<article class="<?= implode(' ', $cardClasses) ?>"
         data-inv-card-id="<?= $model->id ?>"
         role="article"
         aria-label="<?= Html::encode($ariaLabel) ?>"
         tabindex="0">

    <div class="inv-card-stripe" style="background: <?= $stripeColor ?>"></div>

    <?php if ($stockLevel === 'out'): ?>
        <div class="inv-card-watermark" aria-hidden="true">نـفـد</div>
    <?php endif; ?>

    <header class="inv-card-head">
        <input type="checkbox"
               class="inv-card-check"
               data-inv-pick="<?= $model->id ?>"
               aria-label="<?= 'تحديد ' . Html::encode($model->item_name) ?>" />

        <div class="inv-card-avatar" aria-hidden="true">
            <i class="fa fa-cube"></i>
        </div>

        <div class="inv-card-head-meta">
            <span class="inv-status inv-status--<?= Html::encode($status) ?>">
                <i class="fa <?= $statusIcons[$status] ?? 'fa-question' ?>"></i>
                <?= Html::encode($model->getStatusLabel()) ?>
            </span>
        </div>
    </header>

    <div class="inv-card-body">
        <h3 class="inv-card-title" title="<?= Html::encode($model->item_name) ?>">
            <?= Html::encode($model->item_name) ?>
        </h3>

        <span class="inv-card-barcode" title="الباركود">
            <i class="fa fa-barcode" aria-hidden="true"></i> <?= Html::encode($model->item_barcode) ?>
        </span>

        <div class="inv-stock-block">
            <div class="inv-stock-row">
                <span class="inv-stock-label">
                    <i class="fa fa-archive"></i> المخزون المتوفر
                </span>
                <span class="inv-stock-value">
                    <?= number_format($stock) ?>
                    <?php if ($model->unit): ?>
                        <small style="color:var(--inv-text-3);font-weight:600;font-size:11px"><?= Html::encode($model->unit) ?></small>
                    <?php endif; ?>
                </span>
            </div>
            <div class="inv-stock-bar" role="progressbar"
                 aria-valuenow="<?= $stock ?>"
                 aria-valuemin="0"
                 aria-valuemax="<?= (int) $cap ?>"
                 aria-label="<?= 'مستوى المخزون ' . $stock ?>">
                <div class="inv-stock-fill" data-level="<?= $stockLevel ?>" style="width: <?= number_format($percent, 1) ?>%"></div>
            </div>
            <div class="inv-stock-meta">
                <span>
                    <?php if ($min > 0): ?>
                        الحد الأدنى: <strong style="color:var(--inv-text-2)"><?= number_format($min) ?></strong>
                    <?php else: ?>
                        بدون حد أدنى
                    <?php endif; ?>
                </span>
                <?php if ($turnoverLevel !== null): ?>
                    <span class="inv-turnover" data-level="<?= $turnoverLevel ?>"
                          title="معدّل دوران المخزون: <?= number_format($turnover, 1) ?> مرة في السنة (آخر 90 يوم)">
                        <i class="fa fa-refresh" aria-hidden="true"></i>
                        <?= number_format($turnover, 1) ?>×/سنة
                    </span>
                <?php else: ?>
                    <span class="inv-turnover" title="لا توجد بيانات كافية لحساب معدل الدوران">
                        <i class="fa fa-minus" aria-hidden="true"></i> —
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="inv-stats-grid">
            <div class="inv-stat-line">
                <span class="inv-stat-key"><i class="fa fa-tag"></i> السعر</span>
                <span class="inv-stat-val">
                    <?= $price > 0 ? number_format($price, 2) . ' <small style="color:var(--inv-text-3);font-weight:600">د.أ</small>' : '<span style="color:var(--inv-text-3)">—</span>' ?>
                </span>
            </div>
            <div class="inv-stat-line">
                <span class="inv-stat-key"><i class="fa fa-money"></i> القيمة</span>
                <span class="inv-stat-val inv-stat-val--strong">
                    <?= $value > 0 ? number_format($value, 0) . ' <small style="color:var(--inv-text-3);font-weight:600">د.أ</small>' : '<span style="color:var(--inv-text-3)">—</span>' ?>
                </span>
            </div>
            <?php if ($model->category): ?>
            <div class="inv-stat-line">
                <span class="inv-stat-key"><i class="fa fa-folder-o"></i> التصنيف</span>
                <span class="inv-stat-val">
                    <span class="inv-tag-cat"><?= Html::encode($model->category) ?></span>
                </span>
            </div>
            <?php endif; ?>
            <?php if ($daysAgo): ?>
            <div class="inv-stat-line">
                <span class="inv-stat-key"><i class="fa fa-clock-o"></i> آخر تحديث</span>
                <span class="inv-stat-val" style="color:var(--inv-text-2);font-weight:600"><?= Html::encode($daysAgo) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="inv-card-actions">
        <?= Html::a('<i class="fa fa-eye"></i> عرض', Url::to(['view', 'id' => $model->id]), [
            'class'         => 'inv-btn inv-btn--sm',
            'role'          => 'modal-remote',
            'data-pjax'     => '0',
            'title'         => 'عرض تفاصيل الصنف',
            'aria-label'    => 'عرض ' . $model->item_name,
        ]) ?>

        <?php if ($canUpdate): ?>
            <?= Html::a('<i class="fa fa-pencil"></i> تعديل', Url::to(['update', 'id' => $model->id]), [
                'class'         => 'inv-btn inv-btn--sm',
                'role'          => 'modal-remote',
                'data-pjax'     => '0',
                'title'         => 'تعديل الصنف',
                'aria-label'    => 'تعديل ' . $model->item_name,
            ]) ?>
        <?php endif; ?>

        <?php if ($canUpdate && $status === 'pending'): ?>
            <button type="button" class="inv-btn inv-btn--sm inv-btn--success inv-approve-btn"
                    data-id="<?= $model->id ?>"
                    title="اعتماد"
                    aria-label="اعتماد <?= Html::encode($model->item_name) ?>">
                <i class="fa fa-check"></i> اعتماد
            </button>
            <button type="button" class="inv-btn inv-btn--sm inv-btn--danger inv-reject-btn"
                    data-id="<?= $model->id ?>"
                    title="رفض"
                    aria-label="رفض <?= Html::encode($model->item_name) ?>">
                <i class="fa fa-times"></i>
            </button>
        <?php endif; ?>

        <?php if ($canDelete): ?>
            <?= Html::a('<i class="fa fa-trash"></i>', Url::to(['delete', 'id' => $model->id]), [
                'class'         => 'inv-btn inv-btn--sm inv-btn--icon inv-btn--danger',
                'data-confirm'  => false,
                'data-method'   => false,
                'data-request-method'  => 'post',
                'data-confirm-title'   => 'تأكيد الحذف',
                'data-confirm-message' => 'هل أنت متأكد من حذف هذا الصنف؟',
                'title'                => 'حذف',
                'aria-label'           => 'حذف ' . $model->item_name,
            ]) ?>
        <?php endif; ?>
    </footer>
</article>
