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
use yii\widgets\LinkPager;
use common\helper\Permissions;
use backend\modules\inventoryItems\models\InventoryItems;
use backend\widgets\ExportButtons;

$this->title = 'إدارة المخزون — الأصناف';

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/inv-items-pro.css?v=2');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/inv-items-pro.js?v=2', [
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

        <?php else: /* TABLE MODE — Custom HTML, no Kartik */ ?>
            <?php
            $models = $dataProvider->getModels();
            if (empty($models)):
            ?>
                <div class="inv-empty">
                    <div class="inv-empty-icon"><i class="fa fa-table"></i></div>
                    <h3>لا توجد أصناف لعرضها</h3>
                    <p>جرّب تعديل المرشحات أو إضافة صنف جديد.</p>
                </div>
            <?php else: ?>
                <div class="inv-table-pro-wrap">
                    <div class="inv-table-pro-head">
                        <h3 class="inv-table-pro-title">
                            <i class="fa fa-cubes"></i> أصناف المخزون
                            <span class="inv-table-pro-count"><?= number_format($dataProvider->totalCount) ?></span>
                        </h3>
                        <div style="font-size:12px;color:var(--inv-text-2)">
                            عرض <strong style="color:var(--inv-text-1)"><?= ($dataProvider->pagination->page * $dataProvider->pagination->pageSize) + 1 ?></strong>—<strong style="color:var(--inv-text-1)"><?= min(($dataProvider->pagination->page + 1) * $dataProvider->pagination->pageSize, $dataProvider->totalCount) ?></strong>
                        </div>
                    </div>

                    <div class="inv-table-pro-scroll">
                        <table class="inv-table-pro" role="table" aria-label="أصناف المخزون">
                            <thead>
                                <tr>
                                    <th style="width:38px"><input type="checkbox" data-inv-select-all-cb aria-label="تحديد الكل"></th>
                                    <th>الصنف</th>
                                    <th>الباركود</th>
                                    <th>المخزون</th>
                                    <th>السعر</th>
                                    <th>القيمة</th>
                                    <th>الدوران</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th style="text-align:end">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($models as $m):
                                    $stock = $m->getTotalStock();
                                    $minVal = (int) $m->min_stock_level;
                                    $level = 'ok';
                                    if ($stock <= 0)            $level = 'out';
                                    elseif ($minVal > 0 && $stock < $minVal) $level = 'low';
                                    $cap = $minVal > 0 ? max($minVal * 2, $minVal + 1) : max($stock, 100);
                                    $percent = $cap > 0 ? min(100, max(0, ($stock / $cap) * 100)) : 0;

                                    $turnover = method_exists($m, 'getTurnover') ? $m->getTurnover() : null;

                                    $statusBadge = [
                                        'approved' => 'inv-pro-badge--success',
                                        'pending'  => 'inv-pro-badge--warning',
                                        'rejected' => 'inv-pro-badge--danger',
                                    ][$m->status] ?? '';

                                    $statusIcon = [
                                        'approved' => 'fa-check-circle',
                                        'pending'  => 'fa-clock-o',
                                        'rejected' => 'fa-times-circle',
                                        'draft'    => 'fa-pencil',
                                    ][$m->status] ?? 'fa-question';
                                ?>
                                <tr data-inv-row-id="<?= $m->id ?>">
                                    <td data-label="تحديد">
                                        <input type="checkbox" class="inv-card-check" data-inv-pick="<?= $m->id ?>" aria-label="تحديد الصنف">
                                    </td>
                                    <td data-label="الصنف">
                                        <div class="inv-cell-strong"><?= Html::encode($m->item_name) ?></div>
                                        <?php if ($m->category): ?>
                                            <span class="inv-pro-badge inv-pro-badge--info" style="margin-top:3px">
                                                <i class="fa fa-folder-o"></i> <?= Html::encode($m->category) ?>
                                            </span>
                                        <?php endif ?>
                                    </td>
                                    <td data-label="الباركود" style="direction:ltr;font-family:Courier New,monospace;font-weight:700;font-size:12px">
                                        <?= Html::encode($m->item_barcode) ?>
                                    </td>
                                    <td data-label="المخزون">
                                        <div style="min-width:140px">
                                            <div class="inv-cell-num" style="color:<?= $level === 'out' ? '#b91c1c' : ($level === 'low' ? '#b45309' : '#15803d') ?>">
                                                <?= number_format($stock) ?>
                                                <?php if ($m->unit): ?><small style="color:#94a3b8;font-weight:600;font-size:11px"><?= Html::encode($m->unit) ?></small><?php endif ?>
                                            </div>
                                            <div style="height:5px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin-top:4px">
                                                <div style="height:100%;width:<?= number_format($percent, 1) ?>%;background:<?= $level === 'out' ? '#b91c1c' : ($level === 'low' ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#15803d,#22c55e)') ?>;border-radius:999px;transition:width .5s"></div>
                                            </div>
                                            <?php if ($minVal > 0): ?>
                                                <div class="inv-cell-muted" style="margin-top:2px">حد أدنى: <?= number_format($minVal) ?></div>
                                            <?php endif ?>
                                        </div>
                                    </td>
                                    <td data-label="السعر" class="inv-cell-num">
                                        <?php if ($m->unit_price): ?>
                                            <?= number_format($m->unit_price, 2) ?> <small style="color:#94a3b8">د.أ</small>
                                        <?php else: ?>
                                            <span style="color:#cbd5e1">—</span>
                                        <?php endif ?>
                                    </td>
                                    <td data-label="القيمة" class="inv-cell-num" style="color:#6d28d9;font-weight:800">
                                        <?php $val = $stock * (float) $m->unit_price; ?>
                                        <?= $val > 0 ? number_format($val, 0) . ' <small style="color:#94a3b8">د.أ</small>' : '<span style="color:#cbd5e1">—</span>' ?>
                                    </td>
                                    <td data-label="الدوران">
                                        <?php if ($turnover === null): ?>
                                            <span style="color:#cbd5e1">—</span>
                                        <?php else:
                                            $tColor = $turnover >= 4 ? 'success' : ($turnover >= 1 ? 'warning' : 'danger');
                                        ?>
                                            <span class="inv-pro-badge inv-pro-badge--<?= $tColor ?>">
                                                <i class="fa fa-refresh"></i> <?= number_format($turnover, 1) ?>×
                                            </span>
                                        <?php endif ?>
                                    </td>
                                    <td data-label="الحالة">
                                        <span class="inv-pro-badge <?= $statusBadge ?>">
                                            <i class="fa <?= $statusIcon ?>"></i> <?= Html::encode($m->getStatusLabel()) ?>
                                        </span>
                                    </td>
                                    <td data-label="التاريخ" class="inv-cell-muted" style="font-variant-numeric:tabular-nums">
                                        <?php if ($m->created_at): ?>
                                            <div style="font-size:12px;color:#475569"><?= date('Y-m-d', $m->created_at) ?></div>
                                        <?php else: ?>—<?php endif ?>
                                    </td>
                                    <td data-label="إجراءات" class="inv-cell-actions">
                                        <?= Html::a('<i class="fa fa-eye"></i>', Url::to(['view', 'id' => $m->id]), [
                                            'class' => 'inv-pro-btn inv-pro-btn--sm inv-pro-btn--icon',
                                            'role' => 'modal-remote', 'data-pjax' => '0',
                                            'title' => 'عرض', 'aria-label' => 'عرض ' . $m->item_name,
                                        ]) ?>
                                        <?php if ($canUpdate): ?>
                                            <?= Html::a('<i class="fa fa-pencil"></i>', Url::to(['update', 'id' => $m->id]), [
                                                'class' => 'inv-pro-btn inv-pro-btn--sm inv-pro-btn--icon',
                                                'role' => 'modal-remote', 'data-pjax' => '0',
                                                'title' => 'تعديل', 'aria-label' => 'تعديل ' . $m->item_name,
                                            ]) ?>
                                        <?php endif ?>
                                        <?php if ($canUpdate && $m->status === 'pending'): ?>
                                            <button type="button" class="inv-pro-btn inv-pro-btn--sm inv-pro-btn--icon inv-pro-btn--success inv-approve-btn"
                                                    data-id="<?= $m->id ?>" title="اعتماد"><i class="fa fa-check"></i></button>
                                            <button type="button" class="inv-pro-btn inv-pro-btn--sm inv-pro-btn--icon inv-pro-btn--danger-solid inv-reject-btn"
                                                    data-id="<?= $m->id ?>" title="رفض"><i class="fa fa-times"></i></button>
                                        <?php endif ?>
                                        <?php if ($canDelete): ?>
                                            <?= Html::a('<i class="fa fa-trash"></i>', Url::to(['delete', 'id' => $m->id]), [
                                                'class' => 'inv-pro-btn inv-pro-btn--sm inv-pro-btn--icon inv-pro-btn--danger',
                                                'data-confirm-title' => 'تأكيد الحذف',
                                                'data-confirm-message' => 'هل أنت متأكد من حذف هذا الصنف؟',
                                                'data-method' => 'post',
                                                'title' => 'حذف', 'aria-label' => 'حذف ' . $m->item_name,
                                            ]) ?>
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="inv-pager-pro">
                    <span class="inv-pager-pro-info">
                        عرض <strong><?= ($dataProvider->pagination->page * $dataProvider->pagination->pageSize) + 1 ?>—<?= min(($dataProvider->pagination->page + 1) * $dataProvider->pagination->pageSize, $dataProvider->totalCount) ?></strong>
                        من <strong><?= number_format($dataProvider->totalCount) ?></strong> صنف
                    </span>
                    <?= LinkPager::widget([
                        'pagination' => $dataProvider->pagination,
                        'options' => ['class' => 'pagination'],
                        'firstPageLabel' => '«',
                        'lastPageLabel'  => '»',
                        'prevPageLabel'  => '‹',
                        'nextPageLabel'  => '›',
                        'maxButtonCount' => 7,
                    ]) ?>
                </div>
            <?php endif; ?>
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

<!--
     Note: legacy #ajaxCrudModal removed.
     Modals now rendered dynamically by InvItemsPro.Modal (vanilla JS).
     -->


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
