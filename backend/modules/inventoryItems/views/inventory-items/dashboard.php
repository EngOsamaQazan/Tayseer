<?php
/**
 * لوحة تحكم المخزون — الشاشة الرئيسية الاحترافية
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\helper\Permissions;

$this->title = 'إدارة المخزون';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'dashboard']) ?>

<style>
/* ── تعريف المتغيرات المطلوبة من fin-transactions.css ── */
.inv-dashboard {
    --fin-font: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    --fin-credit: #166534;
    --fin-credit-bg: #dcfce7;
    --fin-debit: #991b1b;
    --fin-debit-bg: #fee2e2;
    --fin-gold: #92400e;
    --fin-gold-bg: #fef3c7;
    --fin-neutral: #475569;
    --fin-neutral-bg: #f1f5f9;
    --fin-border: #cbd5e1;
    --fin-bg: #f8fafc;
    --fin-surface: #ffffff;
    --fin-text: #1e293b;
    --fin-text2: #475569;
    --fin-r: 10px;
    --fin-r-sm: 6px;
    --fin-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
    --fin-shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    --fin-primary: #075985;
    --clr-primary-400: #075985;
    --clr-primary-600: #0c4a6e;
}

/* ── بطاقات الإحصائيات ── */
.inv-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
.inv-stat { display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-radius: 12px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #cbd5e1; transition: all 0.2s; cursor: default; }
.inv-stat:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); transform: translateY(-2px); }
.inv-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.inv-stat-body { display: flex; flex-direction: column; }
.inv-stat-num { font-size: 24px; font-weight: 800; line-height: 1.1; font-family: 'Cairo', sans-serif; }
.inv-stat-lbl { font-size: 12px; font-weight: 600; color: #475569; margin-top: 3px; }

.inv-stat--total .inv-stat-icon { background: #e0f2fe; color: #075985; }
.inv-stat--total .inv-stat-num { color: #075985; }
.inv-stat--pending .inv-stat-icon { background: #fef3c7; color: #92400e; }
.inv-stat--pending .inv-stat-num { color: #92400e; }
.inv-stat--approved .inv-stat-icon { background: #dcfce7; color: #166534; }
.inv-stat--approved .inv-stat-num { color: #166534; }
.inv-stat--invoices .inv-stat-icon { background: #ede9fe; color: #5b21b6; }
.inv-stat--invoices .inv-stat-num { color: #5b21b6; }
.inv-stat--suppliers .inv-stat-icon { background: #fce7f3; color: #9d174d; }
.inv-stat--suppliers .inv-stat-num { color: #9d174d; }
.inv-stat--rejected .inv-stat-icon { background: #fee2e2; color: #991b1b; }
.inv-stat--rejected .inv-stat-num { color: #991b1b; }

/* ── الأزرار السريعة ── */
.inv-quick { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 22px; }
.inv-quick-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 700; text-decoration: none !important; transition: all 0.15s; border: 2px solid transparent; }
.inv-quick-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,0.12); }
.inv-qb--purchase { background: #075985; color: #fff !important; }
.inv-qb--purchase:hover { background: #0c4a6e; }
.inv-qb--item { background: #fff; color: #166534 !important; border-color: #166534; }
.inv-qb--item:hover { background: #dcfce7; }
.inv-qb--adjust { background: #fff; color: #5b21b6 !important; border-color: #5b21b6; }
.inv-qb--adjust:hover { background: #ede9fe; }

/* ── اللوحات ── */
.inv-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px; }
@media (max-width: 900px) { .inv-panels { grid-template-columns: 1fr; } }
.inv-panel { background: #fff; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
.inv-panel-head { padding: 14px 18px; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-weight: 700; font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.inv-panel-body { padding: 0; max-height: 360px; overflow-y: auto; }

/* ── تنبيهات نقص المخزون ── */
.inv-alert-row { display: flex; align-items: center; padding: 10px 18px; border-bottom: 1px solid #e2e8f0; gap: 12px; }
.inv-alert-row:last-child { border-bottom: none; }
.inv-alert-row:hover { background: #fefce8; }
.inv-alert-icon { width: 36px; height: 36px; border-radius: 8px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.inv-alert-info { flex: 1; }
.inv-alert-name { font-weight: 700; font-size: 13.5px; color: #1e293b; }
.inv-alert-detail { font-size: 12px; color: #64748b; margin-top: 2px; }
.inv-alert-badge { padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; background: #fee2e2; color: #dc2626; }

/* ── آخر الحركات ── */
.inv-mov-row { display: flex; align-items: center; padding: 10px 18px; border-bottom: 1px solid #f1f5f9; gap: 12px; }
.inv-mov-row:last-child { border-bottom: none; }
.inv-mov-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
.inv-mov-icon--in { background: var(--inv-ok-bg); color: var(--inv-ok); }
.inv-mov-icon--out { background: var(--inv-danger-bg); color: var(--inv-danger); }
.inv-mov-icon--adj { background: var(--inv-purple-bg); color: var(--inv-purple); }
.inv-mov-icon--tr { background: var(--inv-primary-bg); color: var(--inv-primary); }
.inv-mov-info { flex: 1; }
.inv-mov-title { font-weight: 600; font-size: 13px; color: #1e293b; }
.inv-mov-meta { font-size: 11.5px; color: #94a3b8; margin-top: 1px; }
.inv-mov-qty { font-weight: 800; font-size: 14px; }
.inv-mov-qty--plus { color: var(--inv-ok); }
.inv-mov-qty--minus { color: var(--inv-danger); }

/* ── آخر أوامر الشراء ── */
.inv-po-row { display: flex; align-items: center; padding: 10px 18px; border-bottom: 1px solid #f1f5f9; gap: 12px; }
.inv-po-row:last-child { border-bottom: none; }
.inv-po-num { font-weight: 700; font-size: 13.5px; color: var(--inv-primary); min-width: 60px; }
.inv-po-info { flex: 1; font-size: 13px; color: #334155; }
.inv-po-date { font-size: 12px; color: #94a3b8; }
.inv-po-amount { font-weight: 700; font-size: 13px; color: #1e293b; }

.inv-empty { padding: 30px; text-align: center; color: #94a3b8; font-size: 13.5px; }
.inv-empty i { font-size: 28px; display: block; margin-bottom: 8px; opacity: 0.5; }
</style>

<div class="inv-dashboard">

    <!-- ═══ إحصائيات ═══ -->
    <div class="inv-stats">
        <div class="inv-stat inv-stat--total">
            <div class="inv-stat-icon"><i class="fa fa-cubes"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['total']) ?></span>
                <span class="inv-stat-lbl">إجمالي الأصناف</span>
            </div>
        </div>
        <div class="inv-stat inv-stat--approved">
            <div class="inv-stat-icon"><i class="fa fa-check-circle"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['approved']) ?></span>
                <span class="inv-stat-lbl">أصناف معتمدة</span>
            </div>
        </div>
        <div class="inv-stat inv-stat--pending">
            <div class="inv-stat-icon"><i class="fa fa-clock-o"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['pending']) ?></span>
                <span class="inv-stat-lbl">بانتظار الموافقة</span>
            </div>
        </div>
        <div class="inv-stat inv-stat--invoices">
            <div class="inv-stat-icon"><i class="fa fa-shopping-cart"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['invoices']) ?></span>
                <span class="inv-stat-lbl">أوامر الشراء</span>
            </div>
        </div>
        <div class="inv-stat inv-stat--suppliers">
            <div class="inv-stat-icon"><i class="fa fa-truck"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['suppliers']) ?></span>
                <span class="inv-stat-lbl">الموردين</span>
            </div>
        </div>
        <?php if ($stats['rejected'] > 0): ?>
        <div class="inv-stat inv-stat--rejected">
            <div class="inv-stat-icon"><i class="fa fa-times-circle"></i></div>
            <div class="inv-stat-body">
                <span class="inv-stat-num"><?= number_format($stats['rejected']) ?></span>
                <span class="inv-stat-lbl">مرفوض</span>
            </div>
        </div>
        <?php endif ?>
    </div>

    <!-- ═══ أزرار سريعة ═══ -->
    <div class="inv-quick">
        <?php if (Permissions::can(Permissions::INVINV_CREATE)): ?>
        <a href="<?= Url::to(['/inventoryInvoices/inventory-invoices/create-wizard']) ?>" class="inv-quick-btn inv-qb--purchase" style="background:#5b21b6; border-color:#5b21b6">
            <i class="fa fa-file-text-o"></i> فاتورة توريد جديدة (معالج)
        </a>
        <a href="<?= Url::to(['/inventoryInvoices/inventory-invoices/create']) ?>" class="inv-quick-btn inv-qb--purchase">
            <i class="fa fa-shopping-cart"></i> أمر شراء جديد
        </a>
        <?php endif ?>
        <?php if (Permissions::can(Permissions::INVITEM_CREATE)): ?>
        <a href="<?= Url::to(['create']) ?>" class="inv-quick-btn inv-qb--item" role="modal-remote">
            <i class="fa fa-plus"></i> إضافة صنف
        </a>
        <?php endif ?>
        <?php if (Permissions::can(Permissions::INVITEM_VIEW) || Yii::$app->user->can(Permissions::INVENTORY_ITEMS_QUANTITY)): ?>
        <a href="<?= Url::to(['movements']) ?>" class="inv-quick-btn inv-qb--adjust">
            <i class="fa fa-exchange"></i> حركات المخزون
        </a>
        <?php endif ?>
    </div>

    <!-- ═══ اللوحات ═══ -->
    <div class="inv-panels">

        <!-- تنبيهات نقص المخزون -->
        <div class="inv-panel">
            <div class="inv-panel-head">
                <i class="fa fa-exclamation-triangle" style="color:#d97706"></i>
                تنبيهات نقص المخزون
                <?php if (count($lowStockItems) > 0): ?>
                    <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:12px;font-size:11px;margin-right:auto"><?= count($lowStockItems) ?> صنف</span>
                <?php endif ?>
            </div>
            <div class="inv-panel-body">
                <?php if (empty($lowStockItems)): ?>
                    <div class="inv-empty">
                        <i class="fa fa-check-circle" style="color:#15803d"></i>
                        جميع الأصناف فوق الحد الأدنى
                    </div>
                <?php else: ?>
                    <?php foreach ($lowStockItems as $ls): ?>
                    <div class="inv-alert-row">
                        <div class="inv-alert-icon"><i class="fa fa-exclamation"></i></div>
                        <div class="inv-alert-info">
                            <div class="inv-alert-name"><?= Html::encode($ls['item']->item_name) ?></div>
                            <div class="inv-alert-detail">المتوفر: <?= $ls['stock'] ?> — الحد الأدنى: <?= $ls['item']->min_stock_level ?></div>
                        </div>
                        <span class="inv-alert-badge">ينقص <?= $ls['deficit'] ?></span>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>

        <!-- آخر الحركات -->
        <div class="inv-panel">
            <div class="inv-panel-head">
                <i class="fa fa-exchange" style="color:var(--inv-primary)"></i>
                آخر حركات المخزون
                <a href="<?= Url::to(['movements']) ?>" style="margin-right:auto;font-size:12px;font-weight:600;color:var(--inv-primary)">عرض الكل ←</a>
            </div>
            <div class="inv-panel-body">
                <?php if (empty($recentMovements)): ?>
                    <div class="inv-empty">
                        <i class="fa fa-inbox"></i>
                        لا توجد حركات بعد
                    </div>
                <?php else: ?>
                    <?php foreach ($recentMovements as $mv): ?>
                    <?php
                        $iconMap = ['IN' => 'inv-mov-icon--in', 'OUT' => 'inv-mov-icon--out', 'ADJUSTMENT' => 'inv-mov-icon--adj', 'TRANSFER' => 'inv-mov-icon--tr', 'RETURN' => 'inv-mov-icon--in'];
                        $faMap   = ['IN' => 'fa-arrow-down', 'OUT' => 'fa-arrow-up', 'ADJUSTMENT' => 'fa-sliders', 'TRANSFER' => 'fa-exchange', 'RETURN' => 'fa-undo'];
                        $isIn    = in_array($mv->movement_type, ['IN', 'RETURN']);
                    ?>
                    <div class="inv-mov-row">
                        <div class="inv-mov-icon <?= $iconMap[$mv->movement_type] ?? 'inv-mov-icon--adj' ?>">
                            <i class="fa <?= $faMap[$mv->movement_type] ?? 'fa-circle' ?>"></i>
                        </div>
                        <div class="inv-mov-info">
                            <div class="inv-mov-title"><?= Html::encode($mv->item ? $mv->item->item_name : '#' . $mv->item_id) ?></div>
                            <div class="inv-mov-meta"><?= $mv->getTypeLabel() ?> — <?= $mv->created_at ? date('Y-m-d H:i', $mv->created_at) : '' ?></div>
                        </div>
                        <span class="inv-mov-qty <?= $isIn ? 'inv-mov-qty--plus' : 'inv-mov-qty--minus' ?>">
                            <?= $isIn ? '+' : '-' ?><?= number_format($mv->quantity) ?>
                        </span>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>

        <!-- آخر أوامر الشراء (لمن يملك صلاحية فواتير المخزون) -->
        <?php if (Permissions::can(Permissions::INVINV_VIEW)): ?>
        <div class="inv-panel" style="grid-column: 1 / -1">
            <div class="inv-panel-head">
                <i class="fa fa-shopping-cart" style="color:var(--inv-purple)"></i>
                آخر أوامر الشراء
                <a href="<?= Url::to(['/inventoryInvoices/inventory-invoices/index']) ?>" style="margin-right:auto;font-size:12px;font-weight:600;color:var(--inv-primary)">عرض الكل ←</a>
            </div>
            <div class="inv-panel-body">
                <?php if (empty($recentOrders)): ?>
                    <div class="inv-empty">
                        <i class="fa fa-inbox"></i>
                        لا توجد أوامر شراء
                    </div>
                <?php else: ?>
                    <?php foreach ($recentOrders as $po): ?>
                    <div class="inv-po-row">
                        <span class="inv-po-num">#<?= $po->id ?></span>
                        <div class="inv-po-info">
                            <?= Html::encode($po->suppliers ? $po->suppliers->name : 'مورد غير محدد') ?>
                            <?php if ($po->company): ?>
                                <span style="color:#94a3b8;margin:0 4px">·</span>
                                <?= Html::encode($po->company->name) ?>
                            <?php endif ?>
                        </div>
                        <span class="inv-po-date"><?= $po->date ?: date('Y-m-d', $po->created_at) ?></span>
                        <?php if ($po->total_amount): ?>
                            <span class="inv-po-amount"><?= number_format($po->total_amount, 2) ?></span>
                        <?php endif ?>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>
    </div>

</div>

<?php
use yii\bootstrap\Modal;
Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'options' => ['class' => 'modal fade', 'tabindex' => false], 'size' => Modal::SIZE_LARGE]);
Modal::end();

\johnitvn\ajaxcrud\CrudAsset::register($this);
?>
