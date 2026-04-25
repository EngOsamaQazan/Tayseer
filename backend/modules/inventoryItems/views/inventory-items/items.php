<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  Inventory Items — Pro Redesign Screen
 *  Tayseer ERP — نظام تيسير
 *  ─────────────────────────────────────────────────────────────
 *  Standards: ISO 9241-110/112/125/143/171, WCAG 2.2 AA
 *  Plan ref:  docs/specs/INVENTORY_ITEMS_REDESIGN_PLAN.md
 * ═══════════════════════════════════════════════════════════════
 *
 * @var \backend\modules\inventoryItems\models\InventoryItemsSearch $searchModel
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var array $kpi
 * @var array $categories
 * @var string $viewMode
 * @var \yii\web\View $this
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use common\helper\Permissions;
use backend\modules\inventoryItems\models\InventoryItems;
use backend\widgets\ExportButtons;

$this->title = 'إدارة المخزون — الأصناف';

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/inv-items-pro.css?v=1');
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/inv-items-pro.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);

$canCreate = Permissions::can(Permissions::INVITEM_CREATE);
$canUpdate = Permissions::can(Permissions::INVITEM_UPDATE);
$canDelete = Permissions::can(Permissions::INVITEM_DELETE);

$req = Yii::$app->request;
$currentStatus   = $req->get('InventoryItemsSearch')['status']   ?? '';
$currentCategory = $req->get('InventoryItemsSearch')['category'] ?? '';
$currentName     = $req->get('InventoryItemsSearch')['item_name'] ?? '';
$currentSort     = $req->get('sort', '');
$flagFilter      = $req->get('flag', '');

/**
 * Helper: build URL preserving filters and updating one or more keys
 */
$urlWith = function (array $updates) use ($req) {
    $params = $req->queryParams;
    $params[0] = 'items';
    foreach ($updates as $k => $v) {
        if ($v === null || $v === '') {
            $parts = explode('.', $k);
            if (count($parts) === 2) {
                if (isset($params[$parts[0]])) unset($params[$parts[0]][$parts[1]]);
            } else {
                unset($params[$k]);
            }
        } else {
            $parts = explode('.', $k);
            if (count($parts) === 2) {
                if (!isset($params[$parts[0]])) $params[$parts[0]] = [];
                $params[$parts[0]][$parts[1]] = $v;
            } else {
                $params[$k] = $v;
            }
        }
    }
    unset($params['page']);
    return Url::to($params);
};

/* Built-in saved views (defaults) */
$builtinViews = [
    [
        'id'    => 'all',
        'name'  => 'كل الأصناف',
        'icon'  => 'fa-th-large',
        'url'   => $urlWith([
            'InventoryItemsSearch.status'    => null,
            'InventoryItemsSearch.category'  => null,
            'InventoryItemsSearch.item_name' => null,
            'flag' => null,
        ]),
        'active' => !$currentStatus && !$currentCategory && !$currentName && !$flagFilter,
    ],
    [
        'id'    => 'pending',
        'name'  => 'بانتظار الاعتماد',
        'icon'  => 'fa-clock-o',
        'url'   => $urlWith(['InventoryItemsSearch.status' => 'pending', 'flag' => null]),
        'active'=> $currentStatus === 'pending',
    ],
    [
        'id'    => 'low',
        'name'  => 'تحت الحد الأدنى',
        'icon'  => 'fa-exclamation-triangle',
        'url'   => $urlWith(['flag' => 'low', 'InventoryItemsSearch.status' => null]),
        'active'=> $flagFilter === 'low',
    ],
    [
        'id'    => 'out',
        'name'  => 'نافد المخزون',
        'icon'  => 'fa-ban',
        'url'   => $urlWith(['flag' => 'out', 'InventoryItemsSearch.status' => null]),
        'active'=> $flagFilter === 'out',
    ],
    [
        'id'    => 'recent',
        'name'  => 'مضاف هذا الأسبوع',
        'icon'  => 'fa-bolt',
        'url'   => $urlWith(['flag' => 'recent']),
        'active'=> $flagFilter === 'recent',
    ],
];
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'items']) ?>

<div class="inv-items-pro" dir="rtl">

    <!-- ═══════════════════════════════════════════════════════
         ① HERO HEADER
         ═══════════════════════════════════════════════════════ -->
    <header class="inv-hero">
        <div class="inv-hero-main">
            <h1 class="inv-hero-title">
                <i class="fa fa-cubes"></i>
                أصناف المخزون
            </h1>
            <p class="inv-hero-sub">
                <span><?= number_format($kpi['total']) ?> صنف · <?= number_format($kpi['units']) ?> قطعة في المستودع</span>
                <span style="color:var(--inv-border-2)">|</span>
                <button type="button" class="inv-live" data-live-toggle data-live="on"
                        aria-label="تبديل التحديث المباشر"
                        title="إيقاف التحديث المباشر">
                    <span class="inv-live-dot"></span>
                    <span data-live-label>مباشر</span>
                </button>
            </p>
        </div>
        <div class="inv-hero-actions">
            <?php if ($canCreate): ?>
                <?= Html::a('<i class="fa fa-plus"></i> صنف جديد', Url::to(['create']), [
                    'class' => 'inv-btn inv-btn--primary',
                    'role'  => 'modal-remote',
                    'data-pjax' => '0',
                    'title' => 'إضافة صنف جديد (Alt+N)',
                    'accesskey' => 'n',
                ]) ?>
                <?= Html::a('<i class="fa fa-cubes"></i> إضافة دفعة', Url::to(['batch-create']), [
                    'class' => 'inv-btn',
                    'role'  => 'modal-remote',
                    'data-pjax' => '0',
                    'title' => 'إضافة مجموعة أصناف دفعة واحدة',
                ]) ?>
            <?php endif; ?>
            <?= Html::a('<i class="fa fa-refresh"></i>', Url::to(['items']), [
                'class' => 'inv-btn inv-btn--icon inv-btn--ghost',
                'title' => 'تحديث الصفحة',
                'aria-label' => 'تحديث',
            ]) ?>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════
         ② KPI STRIP
         ═══════════════════════════════════════════════════════ -->
    <div class="inv-kpi-strip" role="region" aria-label="إحصائيات المخزون">
        <div class="inv-kpi inv-kpi--total" data-kpi="total">
            <div class="inv-kpi-row">
                <div class="inv-kpi-icon"><i class="fa fa-cubes"></i></div>
                <span class="inv-kpi-trend" title="إجمالي الأصناف"><i class="fa fa-database"></i></span>
            </div>
            <div class="inv-kpi-num"><?= number_format($kpi['total']) ?></div>
            <div class="inv-kpi-lbl">إجمالي الأصناف</div>
        </div>
        <div class="inv-kpi inv-kpi--approved" data-kpi="approved">
            <div class="inv-kpi-row">
                <div class="inv-kpi-icon"><i class="fa fa-check-circle"></i></div>
                <span class="inv-kpi-trend"><?= $kpi['total'] ? round(($kpi['approved']/$kpi['total'])*100) : 0 ?>%</span>
            </div>
            <div class="inv-kpi-num"><?= number_format($kpi['approved']) ?></div>
            <div class="inv-kpi-lbl">معتمدة</div>
        </div>
        <div class="inv-kpi inv-kpi--pending" data-kpi="pending">
            <div class="inv-kpi-row">
                <div class="inv-kpi-icon"><i class="fa fa-clock-o"></i></div>
                <?php if ($kpi['pending'] > 0): ?>
                    <span class="inv-kpi-trend" style="color:var(--inv-warning)"><i class="fa fa-bell"></i></span>
                <?php endif; ?>
            </div>
            <div class="inv-kpi-num"><?= number_format($kpi['pending']) ?></div>
            <div class="inv-kpi-lbl">بانتظار الاعتماد</div>
        </div>
        <div class="inv-kpi inv-kpi--low" data-kpi="low">
            <div class="inv-kpi-row">
                <div class="inv-kpi-icon"><i class="fa fa-exclamation-triangle"></i></div>
                <?php if ($kpi['low'] > 0): ?>
                    <span class="inv-kpi-trend" style="color:#d97706"><i class="fa fa-arrow-down"></i></span>
                <?php endif; ?>
            </div>
            <div class="inv-kpi-num"><?= number_format($kpi['low']) ?></div>
            <div class="inv-kpi-lbl">تحت الحد الأدنى</div>
        </div>
        <div class="inv-kpi inv-kpi--out" data-kpi="out">
            <div class="inv-kpi-row">
                <div class="inv-kpi-icon"><i class="fa fa-ban"></i></div>
                <?php if ($kpi['out'] > 0): ?>
                    <span class="inv-kpi-trend" style="color:var(--inv-danger)"><i class="fa fa-times"></i></span>
                <?php endif; ?>
            </div>
            <div class="inv-kpi-num"><?= number_format($kpi['out']) ?></div>
            <div class="inv-kpi-lbl">نافد المخزون</div>
        </div>
        <div class="inv-kpi inv-kpi--value" data-kpi="value">
            <div class="inv-kpi-row">
                <div class="inv-kpi-icon"><i class="fa fa-money"></i></div>
                <span class="inv-kpi-trend">د.أ</span>
            </div>
            <div class="inv-kpi-num"><?= number_format($kpi['value'], 0) ?></div>
            <div class="inv-kpi-lbl">قيمة المخزون</div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ③ SAVED VIEWS BAR
         ═══════════════════════════════════════════════════════ -->
    <div class="inv-saved" role="region" aria-label="العروض المحفوظة">
        <span class="inv-saved-label"><i class="fa fa-star"></i> عروض سريعة:</span>
        <?php foreach ($builtinViews as $v): ?>
            <a href="<?= $v['url'] ?>"
               class="inv-sv <?= $v['active'] ? 'inv-sv--active' : '' ?>"
               data-pjax="0"
               data-inv-pill>
                <i class="fa <?= $v['icon'] ?>"></i> <?= Html::encode($v['name']) ?>
            </a>
        <?php endforeach; ?>
        <span data-sv-list style="display:contents"></span>
        <button type="button" class="inv-sv" data-sv-save title="حفظ المرشحات الحالية كعرض جديد"
                style="background:transparent;border-style:dashed;color:var(--inv-text-2)">
            <i class="fa fa-plus"></i> حفظ هذا العرض
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ④ STATUS PILLS
         ═══════════════════════════════════════════════════════ -->
    <nav class="inv-pills" aria-label="فلترة الحالة">
        <a href="<?= $urlWith(['InventoryItemsSearch.status' => null, 'flag' => null]) ?>"
           class="inv-pill"
           data-inv-pill
           data-pjax="0"
           aria-pressed="<?= !$currentStatus && !$flagFilter ? 'true' : 'false' ?>">
            <i class="fa fa-th"></i> الكل
            <span class="inv-pill-count"><?= number_format($kpi['total']) ?></span>
        </a>
        <a href="<?= $urlWith(['InventoryItemsSearch.status' => 'approved', 'flag' => null]) ?>"
           class="inv-pill"
           data-inv-pill data-pjax="0" data-tone="success"
           aria-pressed="<?= $currentStatus === 'approved' ? 'true' : 'false' ?>">
            <i class="fa fa-check"></i> معتمد
            <span class="inv-pill-count"><?= number_format($kpi['approved']) ?></span>
        </a>
        <a href="<?= $urlWith(['InventoryItemsSearch.status' => 'pending', 'flag' => null]) ?>"
           class="inv-pill"
           data-inv-pill data-pjax="0" data-tone="warning"
           aria-pressed="<?= $currentStatus === 'pending' ? 'true' : 'false' ?>">
            <i class="fa fa-clock-o"></i> بانتظار
            <span class="inv-pill-count"><?= number_format($kpi['pending']) ?></span>
        </a>
        <a href="<?= $urlWith(['InventoryItemsSearch.status' => 'rejected', 'flag' => null]) ?>"
           class="inv-pill"
           data-inv-pill data-pjax="0" data-tone="danger"
           aria-pressed="<?= $currentStatus === 'rejected' ? 'true' : 'false' ?>">
            <i class="fa fa-times"></i> مرفوض
            <span class="inv-pill-count"><?= number_format($kpi['rejected']) ?></span>
        </a>
        <span style="flex:1;border-inline-start:1px solid var(--inv-border);height:18px;margin:0 4px"></span>
        <a href="<?= $urlWith(['flag' => 'low', 'InventoryItemsSearch.status' => null]) ?>"
           class="inv-pill"
           data-inv-pill data-pjax="0" data-tone="amber"
           aria-pressed="<?= $flagFilter === 'low' ? 'true' : 'false' ?>">
            <i class="fa fa-exclamation-triangle"></i> تحت الحد
            <span class="inv-pill-count"><?= number_format($kpi['low']) ?></span>
        </a>
        <a href="<?= $urlWith(['flag' => 'out', 'InventoryItemsSearch.status' => null]) ?>"
           class="inv-pill"
           data-inv-pill data-pjax="0" data-tone="danger"
           aria-pressed="<?= $flagFilter === 'out' ? 'true' : 'false' ?>">
            <i class="fa fa-ban"></i> نفد
            <span class="inv-pill-count"><?= number_format($kpi['out']) ?></span>
        </a>
    </nav>

    <!-- ═══════════════════════════════════════════════════════
         ⑤ CATEGORY CHIPS
         ═══════════════════════════════════════════════════════ -->
    <?php if (!empty($categories)): ?>
        <div class="inv-chips" role="region" aria-label="فلترة التصنيفات">
            <a href="<?= $urlWith(['InventoryItemsSearch.category' => null]) ?>"
               class="inv-chip"
               data-inv-chip data-pjax="0"
               aria-pressed="<?= !$currentCategory ? 'true' : 'false' ?>">
                <i class="fa fa-folder-o"></i> كل التصنيفات
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= $urlWith(['InventoryItemsSearch.category' => $cat]) ?>"
                   class="inv-chip"
                   data-inv-chip data-pjax="0"
                   aria-pressed="<?= $currentCategory === $cat ? 'true' : 'false' ?>">
                    <?= Html::encode($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════
         ⑥ TOOLBAR (sticky)
         ═══════════════════════════════════════════════════════ -->
    <div class="inv-toolbar" role="toolbar" aria-label="أدوات البحث والعرض">
        <div class="inv-search" data-inv-search>
            <input type="search"
                   value="<?= Html::encode($currentName) ?>"
                   placeholder="ابحث باسم الصنف أو الباركود... ( / للتركيز )"
                   aria-label="البحث في الأصناف"
                   autocomplete="off" />
            <i class="fa fa-search inv-search-icon" aria-hidden="true"></i>
            <button type="button" class="inv-search-clear" aria-label="مسح البحث" title="مسح">
                <i class="fa fa-times-circle"></i>
            </button>
        </div>

        <select class="inv-sort" data-inv-sort aria-label="فرز النتائج">
            <option value="">ترتيب افتراضي (الأحدث)</option>
            <option value="item_name"  <?= $currentSort === 'item_name'  ? 'selected' : '' ?>>الاسم — تصاعدي</option>
            <option value="-item_name" <?= $currentSort === '-item_name' ? 'selected' : '' ?>>الاسم — تنازلي</option>
            <option value="-unit_price" <?= $currentSort === '-unit_price' ? 'selected' : '' ?>>السعر — الأعلى</option>
            <option value="unit_price"  <?= $currentSort === 'unit_price'  ? 'selected' : '' ?>>السعر — الأقل</option>
            <option value="-created_at" <?= $currentSort === '-created_at' ? 'selected' : '' ?>>التاريخ — الأحدث</option>
            <option value="created_at"  <?= $currentSort === 'created_at'  ? 'selected' : '' ?>>التاريخ — الأقدم</option>
        </select>

        <div class="inv-view-toggle" role="group" aria-label="وضع العرض">
            <a href="<?= $urlWith(['view' => 'cards']) ?>"
               data-inv-view="cards"
               aria-pressed="<?= $viewMode === 'cards' ? 'true' : 'false' ?>"
               title="عرض البطاقات">
                <i class="fa fa-th-large"></i> بطاقات
            </a>
            <a href="<?= $urlWith(['view' => 'table']) ?>"
               data-inv-view="table"
               aria-pressed="<?= $viewMode === 'table' ? 'true' : 'false' ?>"
               title="عرض الجدول">
                <i class="fa fa-list"></i> جدول
            </a>
        </div>

        <?= ExportButtons::widget([
            'excelRoute' => ['export-items-excel'],
            'pdfRoute'   => ['export-items-pdf'],
        ]) ?>

        <button type="button" class="inv-btn inv-btn--ghost inv-btn--sm" data-inv-select-all
                title="تحديد الكل في هذه الصفحة" aria-label="تحديد الكل">
            <i class="fa fa-check-square-o"></i> تحديد الكل
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ⑦ CONTENT (Pjax)
         ═══════════════════════════════════════════════════════ -->
    <div id="ajaxCrudDatatable">
        <?php Pjax::begin([
            'id' => 'crud-datatable-pjax',
            'timeout' => 8000,
            'enablePushState' => true,
            'options' => ['data-pjax-container' => true],
        ]); ?>

        <?php if ($viewMode === 'cards'): ?>
            <?php
            $models = $dataProvider->getModels();
            if (empty($models)):
            ?>
                <!-- Empty State -->
                <div class="inv-empty">
                    <div class="inv-empty-icon"><i class="fa fa-cube"></i></div>
                    <h3>
                        <?php if ($currentName || $currentStatus || $currentCategory || $flagFilter): ?>
                            لا توجد أصناف تطابق المرشحات الحالية
                        <?php else: ?>
                            ابدأ ببناء مخزونك
                        <?php endif; ?>
                    </h3>
                    <p>
                        <?php if ($currentName || $currentStatus || $currentCategory || $flagFilter): ?>
                            جرّب تعديل البحث أو إزالة بعض المرشحات لرؤية المزيد من النتائج.
                        <?php else: ?>
                            أضف أول صنف في مستودعك لتبدأ في إدارة المخزون باحترافية.
                        <?php endif; ?>
                    </p>
                    <div class="inv-empty-actions">
                        <?php if ($currentName || $currentStatus || $currentCategory || $flagFilter): ?>
                            <?= Html::a('<i class="fa fa-times"></i> مسح كل المرشحات', Url::to(['items']), [
                                'class' => 'inv-btn',
                                'data-pjax' => '0',
                            ]) ?>
                        <?php endif; ?>
                        <?php if ($canCreate): ?>
                            <?= Html::a('<i class="fa fa-plus"></i> إضافة صنف', Url::to(['create']), [
                                'class' => 'inv-btn inv-btn--primary',
                                'role'  => 'modal-remote',
                                'data-pjax' => '0',
                            ]) ?>
                            <?= Html::a('<i class="fa fa-cubes"></i> إضافة دفعة', Url::to(['batch-create']), [
                                'class' => 'inv-btn',
                                'role'  => 'modal-remote',
                                'data-pjax' => '0',
                            ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="inv-cards" role="list">
                    <?php foreach ($models as $model): ?>
                        <?= $this->render('_card', [
                            'model'     => $model,
                            'stock'     => $model->getTotalStock(),
                            'turnover'  => method_exists($model, 'getTurnover') ? $model->getTurnover() : null,
                            'canUpdate' => $canUpdate,
                            'canDelete' => $canDelete,
                        ]) ?>
                    <?php endforeach; ?>
                </div>

                <!-- Summary + Pagination -->
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;padding:10px 4px;color:var(--inv-text-2);font-size:12.5px">
                    <span>
                        <i class="fa fa-th-large"></i>
                        عرض
                        <strong style="color:var(--inv-text-1)"><?= ($dataProvider->pagination->page * $dataProvider->pagination->pageSize) + 1 ?>—<?= min(($dataProvider->pagination->page + 1) * $dataProvider->pagination->pageSize, $dataProvider->totalCount) ?></strong>
                        من
                        <strong style="color:var(--inv-text-1)"><?= number_format($dataProvider->totalCount) ?></strong>
                        صنف
                    </span>
                    <?= \yii\widgets\LinkPager::widget([
                        'pagination' => $dataProvider->pagination,
                        'options' => ['class' => 'pagination', 'style' => 'margin:0'],
                        'firstPageLabel' => 'الأولى',
                        'lastPageLabel'  => 'الأخيرة',
                        'prevPageLabel'  => '‹',
                        'nextPageLabel'  => '›',
                        'maxButtonCount' => 5,
                    ]) ?>
                </div>
            <?php endif; ?>

        <?php else: /* TABLE MODE */ ?>
            <?= GridView::widget([
                'id' => 'crud-datatable',
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => require(__DIR__ . '/_columns.php'),
                'summary' => '<span style="font-size:12.5px;color:var(--inv-text-2)"><i class="fa fa-table"></i> عرض <b>{begin}–{end}</b> من <b>{totalCount}</b> صنف</span>',
                'striped' => true,
                'condensed' => true,
                'responsive' => true,
                'panel' => [
                    'type' => 'default',
                    'heading' => '<i class="fa fa-cubes"></i> أصناف المخزون <span class="badge" style="background:var(--inv-brand);margin-right:6px">' . number_format($dataProvider->totalCount) . '</span>',
                ],
                'pjax' => false,
            ]) ?>
        <?php endif; ?>

        <?php Pjax::end(); ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ⑨ FLOATING BULK ACTION BAR
         ═══════════════════════════════════════════════════════ -->
    <div id="inv-bulk-bar" class="inv-bulk-bar" data-show="0" role="region" aria-label="إجراءات جماعية">
        <span class="inv-bulk-count"><span data-bulk-count>0</span> محدّد</span>

        <?php if ($canUpdate): ?>
            <button type="button" class="inv-btn inv-btn--sm inv-btn--success" data-bulk-approve title="اعتماد جماعي">
                <i class="fa fa-check"></i> اعتماد
            </button>
            <button type="button" class="inv-btn inv-btn--sm" data-bulk-reject title="رفض جماعي">
                <i class="fa fa-times"></i> رفض
            </button>
        <?php endif; ?>

        <?php if ($canDelete): ?>
            <button type="button" class="inv-btn inv-btn--sm inv-btn--danger" data-bulk-delete title="حذف جماعي">
                <i class="fa fa-trash"></i> حذف
            </button>
        <?php endif; ?>

        <button type="button" class="inv-btn inv-btn--sm inv-btn--ghost" data-bulk-cancel title="إلغاء التحديد">
            <i class="fa fa-times-circle"></i> إلغاء
        </button>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════
     Modal (Bootstrap-compatible)
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:#800020"></i>
                </div>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>

<?php
$approveBaseUrl = Url::to(['approve', 'id' => '__ID__']);
$rejectBaseUrl  = Url::to(['reject',  'id' => '__ID__']);
$bulkApproveUrl = Url::to(['bulk-approve']);
$bulkRejectUrl  = Url::to(['bulk-reject']);
$bulkDeleteUrl  = Url::to(['bulk-delete']);
$streamUrl      = Url::to(['items-stream']);
$csrf           = Yii::$app->request->csrfToken;

$cfg = json_encode([
    'csrf'           => $csrf,
    'approveBaseUrl' => $approveBaseUrl,
    'rejectBaseUrl'  => $rejectBaseUrl,
    'bulkApproveUrl' => $bulkApproveUrl,
    'bulkRejectUrl'  => $bulkRejectUrl,
    'bulkDeleteUrl'  => $bulkDeleteUrl,
    'streamUrl'      => $streamUrl,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$js = <<<JS
window.InvItemsProCfg = {$cfg};

/* Per-card approve/reject (single) */
$(document).on('click', '.inv-approve-btn', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    if (!confirm('هل تريد اعتماد هذا الصنف؟')) return;
    $.post(window.InvItemsProCfg.approveBaseUrl.replace('__ID__', id),
        { _csrf: window.InvItemsProCfg.csrf }, function(resp){
            if (resp && resp.success) {
                if (window.InvItemsPro && window.InvItemsPro.toast) window.InvItemsPro.toast(resp.message || 'تم الاعتماد', 'success');
                $.pjax.reload({container: '#crud-datatable-pjax'});
            } else {
                if (window.InvItemsPro && window.InvItemsPro.toast) window.InvItemsPro.toast((resp && resp.message) || 'خطأ', 'danger');
            }
        }, 'json');
});
$(document).on('click', '.inv-reject-btn', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    var reason = prompt('سبب الرفض (اختياري):');
    if (reason === null) return;
    $.post(window.InvItemsProCfg.rejectBaseUrl.replace('__ID__', id),
        { reason: reason, _csrf: window.InvItemsProCfg.csrf }, function(resp){
            if (resp && resp.success) {
                if (window.InvItemsPro && window.InvItemsPro.toast) window.InvItemsPro.toast(resp.message || 'تم الرفض', 'success');
                $.pjax.reload({container: '#crud-datatable-pjax'});
            } else {
                if (window.InvItemsPro && window.InvItemsPro.toast) window.InvItemsPro.toast((resp && resp.message) || 'خطأ', 'danger');
            }
        }, 'json');
});

/* Force-reload after modal save */
$(document).ajaxComplete(function(e, xhr, settings) {
    try {
        var resp = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : null;
        if (resp && resp.forceClose && resp.forceReload) {
            if (window.InvItemsPro && window.InvItemsPro.toast) {
                window.InvItemsPro.toast('تمت العملية بنجاح', 'success');
            }
        }
    } catch(ex) {}
});
JS;
$this->registerJs($js);
?>
