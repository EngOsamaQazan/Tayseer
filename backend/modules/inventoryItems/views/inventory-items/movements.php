<?php
/**
 * شاشة حركات المخزون — سجل كامل لكل تغيير بالكميات
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\LinkPager;
use backend\modules\inventoryItems\models\StockMovement;
use backend\modules\inventoryItems\models\InventoryItems;

$this->title = 'إدارة المخزون';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'movements']) ?>

<style>
.sm-page {
    max-width: 1100px;
    --fin-font: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    --fin-credit: #166534; --fin-credit-bg: #dcfce7;
    --fin-debit: #991b1b; --fin-debit-bg: #fee2e2;
    --fin-gold: #92400e; --fin-gold-bg: #fef3c7;
    --fin-neutral: #475569; --fin-neutral-bg: #f1f5f9;
    --fin-border: #cbd5e1; --fin-bg: #f8fafc; --fin-surface: #ffffff;
    --fin-text: #1e293b; --fin-text2: #475569;
    --fin-r: 10px; --fin-r-sm: 6px;
    --fin-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
    --fin-shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    --fin-primary: #075985; --clr-primary-400: #075985; --clr-primary-600: #0c4a6e;
}

/* ── الفلترة ── */
.sm-filter { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 18px; padding: 16px 20px; background: #fff; border-radius: 12px; border: 1px solid #cbd5e1; }
.sm-f-group { display: flex; flex-direction: column; gap: 4px; }
.sm-f-group label { font-size: 12px; font-weight: 700; color: #475569; }
.sm-f-group select, .sm-f-group input { padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #f8fafc; color: #1e293b; }
.sm-f-group select:focus, .sm-f-group input:focus { border-color: #0369a1; outline: none; box-shadow: 0 0 0 3px rgba(3,105,161,0.1); }
.sm-f-btn { padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; }
.sm-f-search { background: #0369a1; color: #fff; }
.sm-f-reset { background: #f1f5f9; color: #64748b; }

/* ── الجدول ── */
.sm-table { width: 100%; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.sm-table thead th { padding: 12px 16px; background: #f8fafc; font-size: 12.5px; font-weight: 700; color: #334155; border-bottom: 2px solid #e2e8f0; text-align: right; }
.sm-table tbody td { padding: 10px 16px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sm-table tbody tr:hover { background: #f8fafc; }

/* ── شارات الحركة ── */
.sm-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
.sm-badge--in { background: #dcfce7; color: #15803d; }
.sm-badge--out { background: #fee2e2; color: #dc2626; }
.sm-badge--transfer { background: #e0f2fe; color: #0369a1; }
.sm-badge--adjust { background: #ede9fe; color: #7c3aed; }
.sm-badge--return { background: #fef3c7; color: #d97706; }

.sm-qty { font-weight: 800; font-size: 14px; }
.sm-qty--plus { color: #15803d; }
.sm-qty--minus { color: #dc2626; }
.sm-qty--neutral { color: #7c3aed; }

.sm-empty { text-align: center; padding: 40px; color: #94a3b8; }
.sm-empty i { font-size: 32px; display: block; margin-bottom: 10px; opacity: 0.4; }
</style>

<div class="sm-page">

    <!-- ═══ فلترة ═══ -->
    <form method="get" action="<?= Url::to(['movements']) ?>" class="sm-filter">
        <div class="sm-f-group">
            <label>نوع الحركة</label>
            <select name="type">
                <option value="">— الكل —</option>
                <?php foreach (StockMovement::getTypeList() as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filterType == $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="sm-f-group">
            <label>الصنف</label>
            <select name="item_id">
                <option value="">— الكل —</option>
                <?php foreach (InventoryItems::find()->orderBy('item_name')->all() as $item): ?>
                    <option value="<?= $item->id ?>" <?= $filterItem == $item->id ? 'selected' : '' ?>><?= Html::encode($item->item_name) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="sm-f-group">
            <label>من تاريخ</label>
            <input type="date" name="from" value="<?= Html::encode($filterFrom) ?>">
        </div>
        <div class="sm-f-group">
            <label>إلى تاريخ</label>
            <input type="date" name="to" value="<?= Html::encode($filterTo) ?>">
        </div>
        <button type="submit" class="sm-f-btn sm-f-search"><i class="fa fa-search"></i> بحث</button>
        <a href="<?= Url::to(['movements']) ?>" class="sm-f-btn sm-f-reset"><i class="fa fa-times"></i> مسح</a>
    </form>

    <!-- ═══ الجدول ═══ -->
    <?php $models = $dataProvider->getModels(); ?>
    <?php if (empty($models)): ?>
        <div class="sm-empty">
            <i class="fa fa-exchange"></i>
            لا توجد حركات مخزون
        </div>
    <?php else: ?>
        <table class="sm-table">
            <thead>
                <tr>
                    <th style="width:40px">م</th>
                    <th>الصنف</th>
                    <th style="width:140px">نوع الحركة</th>
                    <th style="width:80px">الكمية</th>
                    <th>ملاحظات</th>
                    <th style="width:80px">المرجع</th>
                    <th style="width:140px">التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($models as $i => $mv): ?>
                <?php $isIn = in_array($mv->movement_type, ['IN', 'RETURN']); ?>
                <tr>
                    <td style="color:#94a3b8"><?= $dataProvider->pagination->offset + $i + 1 ?></td>
                    <td>
                        <strong><?= Html::encode($mv->item ? $mv->item->item_name : '#' . $mv->item_id) ?></strong>
                        <?php if ($mv->supplier): ?>
                            <br><small style="color:#94a3b8"><?= Html::encode($mv->supplier->name) ?></small>
                        <?php endif ?>
                    </td>
                    <td><span class="sm-badge <?= $mv->getTypeCssClass() ?>"><?= $mv->getTypeLabel() ?></span></td>
                    <td>
                        <span class="sm-qty <?= $isIn ? 'sm-qty--plus' : ($mv->movement_type === 'ADJUSTMENT' ? 'sm-qty--neutral' : 'sm-qty--minus') ?>">
                            <?= $isIn ? '+' : ($mv->movement_type === 'OUT' ? '-' : '±') ?><?= number_format($mv->quantity) ?>
                        </span>
                    </td>
                    <td style="color:#64748b;font-size:12.5px"><?= Html::encode($mv->notes ?: '—') ?></td>
                    <td style="font-size:12px;color:#94a3b8">
                        <?php if ($mv->reference_type && $mv->reference_id): ?>
                            <?= $mv->reference_type ?> #<?= $mv->reference_id ?>
                        <?php else: ?>
                            —
                        <?php endif ?>
                    </td>
                    <td style="font-size:12.5px;color:#64748b"><?= $mv->created_at ? date('Y-m-d H:i', $mv->created_at) : '—' ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <div style="margin-top:16px;text-align:center">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'pagination'],
            ]) ?>
        </div>
    <?php endif ?>
</div>
