<?php
/**
 * قائمة العملاء — V3 (تصميم مطابق لشاشة العقود)
 * Customers index — Modern responsive UI (matches Contracts design)
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\helper\Permissions;
use backend\widgets\ExportButtons;
use backend\helpers\NameHelper;
use backend\helpers\PhoneHelper;

/* Assets — shared contracts design + customer-specific overrides */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . Yii::$app->params['assetVersion']);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/customers-v2.css?v=' . Yii::$app->params['assetVersion']);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/customers-v2.js?v=' . Yii::$app->params['assetVersion'], [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=' . Yii::$app->params['assetVersion'], [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$this->title = 'العملاء';
$this->params['breadcrumbs'][] = $this->title;

/* Data */
$models     = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$sort       = $dataProvider->getSort();
$totalCount = $dataProvider->getTotalCount();
$searchCounter = $searchCounter ?? $totalCount;

/* Batch queries for current page — avoids N+1 */
$customerIds = array_map(fn($m) => $m->id, $models);
$judCustomerIds = [];
$contractsByCustomer = [];

if (!empty($customerIds)) {
    $idList = implode(',', array_map('intval', $customerIds));

    $judCustomerIds = array_flip(Yii::$app->db->createCommand(
        "SELECT DISTINCT cc.customer_id FROM {{%contracts_customers}} cc
         INNER JOIN {{%judiciary}} j ON j.contract_id = cc.contract_id
            AND (j.is_deleted = 0 OR j.is_deleted IS NULL)
         WHERE cc.customer_id IN ($idList)"
    )->queryColumn());

    $ccRows = Yii::$app->db->createCommand(
        "SELECT customer_id,
                GROUP_CONCAT(contract_id ORDER BY contract_id DESC SEPARATOR ',') as contracts
         FROM {{%contracts_customers}}
         WHERE customer_id IN ($idList)
         GROUP BY customer_id"
    )->queryAll();
    foreach ($ccRows as $row) {
        $contractsByCustomer[$row['customer_id']] = explode(',', $row['contracts']);
    }
}

/* Sort helper */
$sortOrders = $sort->getAttributeOrders();
$sortLink = function ($attribute, $label) use ($sort, $sortOrders) {
    $url = $sort->createUrl($attribute);
    $icon = '';
    if (isset($sortOrders[$attribute])) {
        $icon = $sortOrders[$attribute] === SORT_ASC
            ? ' <i class="fa fa-sort-up ct-sort-icon active"></i>'
            : ' <i class="fa fa-sort-down ct-sort-icon active"></i>';
    } else {
        $icon = ' <i class="fa fa-sort ct-sort-icon"></i>';
    }
    return '<a href="' . Html::encode($url) . '">' . $label . $icon . '</a>';
};

$begin = $pagination->getOffset() + 1;
$end   = $begin + count($models) - 1;
?>

<div class="ct-page cust-page" role="main" aria-label="صفحة العملاء">

    <!-- Flash messages -->
    <?php foreach (['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'] as $type => $icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="ct-alert ct-alert-<?= $type === 'error' ? 'danger' : $type ?>" role="alert"
                 x-data="{ show: true }" x-show="show" x-transition x-cloak
                 x-init="setTimeout(() => show = false, 5000)">
                <i class="fa fa-<?= $icon ?>"></i>
                <span><?php $flash = Yii::$app->session->getFlash($type); echo is_array($flash) ? implode('<br>', $flash) : $flash; ?></span>
                <button class="ct-alert-close" aria-label="إغلاق" @click="show = false">&times;</button>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <!-- ===== PAGE HEADER ===== -->
    <div class="ct-page-hdr">
        <div class="ct-title-area">
            <h1><i class="fa fa-users" style="margin-left:8px;opacity:.7"></i>العملاء</h1>
            <span class="ct-count" aria-label="إجمالي العملاء"><?= number_format($searchCounter) ?></span>
        </div>
        <div class="ct-hdr-actions">
            <?php if (Permissions::can(Permissions::CUST_CREATE)): ?>
            <a href="<?= Url::to(['create']) ?>" class="ct-btn ct-btn-primary" aria-label="إضافة عميل جديد">
                <i class="fa fa-plus"></i> <span class="ct-hide-xs">إضافة عميل</span>
            </a>
            <?php endif ?>
            <?php if (Permissions::can(Permissions::CUST_EXPORT)): ?>
                <?= ExportButtons::widget([
                    'excelRoute' => ['export-excel'],
                    'pdfRoute' => ['export-pdf'],
                    'excelBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                    'pdfBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                ]) ?>
            <?php endif ?>
            <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctFilterToggle" aria-label="فتح الفلاتر">
                <i class="fa fa-sliders" style="font-size:18px"></i>
            </button>
        </div>
    </div>

    <!-- ===== STATS CARDS ===== -->
    <div class="ct-status-cards">
        <div class="ct-stat-card ct-stat-active" style="--card-color:#800020;--card-bg:#FDF2F4">
            <div class="ct-stat-icon"><i class="fa fa-users"></i></div>
            <div class="ct-stat-info">
                <span class="ct-stat-num"><?= number_format($searchCounter) ?></span>
                <span class="ct-stat-label">إجمالي العملاء</span>
            </div>
        </div>
        <div class="ct-stat-card" style="--card-color:#059669;--card-bg:#ECFDF5">
            <div class="ct-stat-icon"><i class="fa fa-file-text-o"></i></div>
            <div class="ct-stat-info">
                <span class="ct-stat-num"><?= number_format($totalCount) ?></span>
                <span class="ct-stat-label">نتائج البحث</span>
            </div>
        </div>
        <div class="ct-stat-card" style="--card-color:#2563EB;--card-bg:#EFF6FF">
            <div class="ct-stat-icon"><i class="fa fa-user-plus"></i></div>
            <div class="ct-stat-info">
                <span class="ct-stat-num">—</span>
                <span class="ct-stat-label">عملاء اليوم</span>
            </div>
        </div>
        <div class="ct-stat-card" style="--card-color:#D97706;--card-bg:#FEF3C7">
            <div class="ct-stat-icon"><i class="fa fa-balance-scale"></i></div>
            <div class="ct-stat-info">
                <span class="ct-stat-num">—</span>
                <span class="ct-stat-label">مشتكى عليهم</span>
            </div>
        </div>
    </div>

    <!-- ===== FILTER SECTION ===== -->
    <div class="ct-filter-wrap" id="ctFilterWrap">
        <div class="ct-filter-backdrop" id="ctFilterBackdrop"></div>
        <div class="ct-filter-panel" id="ctFilterPanel">
            <div class="ct-drawer-handle ct-show-sm"></div>
            <div class="ct-filter-hdr">
                <h3><i class="fa fa-search"></i> بحث وفلترة</h3>
                <span class="ct-filter-toggle-icon ct-hide-sm"><i class="fa fa-chevron-up"></i></span>
                <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctDrawerClose" aria-label="إغلاق"
                        style="font-size:20px;padding:4px 8px">&times;</button>
            </div>
            <div class="ct-filter-body">
                <?= $this->render('_search', ['model' => $searchModel]) ?>
            </div>
        </div>
    </div>

    <!-- ===== FILTER CHIPS ===== -->
    <div class="ct-chips" id="ctChips" aria-label="الفلاتر النشطة"></div>

    <!-- ===== TOOLBAR ===== -->
    <div class="ct-toolbar">
        <div class="ct-summary">
            <?php if ($totalCount > 0): ?>
                عرض <strong><?= number_format($begin) ?>–<?= number_format($end) ?></strong>
                من أصل <strong><?= number_format($totalCount) ?></strong> عميل
            <?php else: ?>
                لا توجد نتائج
            <?php endif ?>
        </div>
        <div class="ct-quick-search">
            <i class="fa fa-search"></i>
            <input type="text" id="ctQuickSearch" placeholder="بحث سريع في النتائج..."
                   aria-label="بحث سريع في النتائج المعروضة">
        </div>
    </div>

    <!-- ===== DATA TABLE ===== -->
    <div class="ct-table-wrap">
        <?php if (empty($models)): ?>
            <div class="ct-empty">
                <i class="fa fa-inbox"></i>
                <p>لا يوجد عملاء مطابقين لمعايير البحث</p>
                <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline">
                    <i class="fa fa-refresh"></i> عرض جميع العملاء
                </a>
            </div>
        <?php else: ?>
            <table class="ct-table" role="grid">
                <thead>
                    <tr>
                        <th class="ct-th-id"><?= $sortLink('id', '#') ?></th>
                        <th><?= $sortLink('name', 'الاسم') ?></th>
                        <th>الهاتف</th>
                        <th>الرقم الوطني</th>
                        <th style="text-align:center">مشتكى عليه</th>
                        <th>العقود</th>
                        <th>الوظيفة</th>
                        <th style="text-align:center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m):
                        $isSued    = isset($judCustomerIds[$m->id]);
                        $contracts = $contractsByCustomer[$m->id] ?? [];
                        $jobName   = $m->jobs->name ?? '—';
                        $phone     = PhoneHelper::toLocal($m->primary_phone_number);
                    ?>
                    <tr data-id="<?= $m->id ?>">
                        <td class="ct-td-id" data-label="#">
                            <?= $m->id ?>
                        </td>
                        <td class="ct-td-customer" data-label="الاسم" title="<?= Html::encode($m->name) ?>">
                            <?php if (Permissions::can(Permissions::CUST_UPDATE)): ?>
                                <a href="<?= Url::to(['update', 'id' => $m->id]) ?>" class="cust-name-link">
                                    <?= Html::encode(NameHelper::short($m->name)) ?>
                                </a>
                            <?php else: ?>
                                <?= Html::encode(NameHelper::short($m->name)) ?>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-phone" data-label="الهاتف">
                            <span dir="ltr"><?= Html::encode($phone) ?></span>
                        </td>
                        <td class="ct-td-idnum" data-label="الوطني">
                            <span dir="ltr"><?= Html::encode($m->id_number ?: '—') ?></span>
                        </td>
                        <td class="ct-td-sued" data-label="مشتكى عليه">
                            <?php if ($isSued): ?>
                                <span class="ct-badge ct-st-judiciary">نعم</span>
                            <?php else: ?>
                                <span class="ct-badge ct-st-active">لا</span>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-contracts" data-label="العقود">
                            <?php if (empty($contracts)): ?>
                                <span style="color:#94a3b8">—</span>
                            <?php else: ?>
                                <?php foreach ($contracts as $cid): ?>
                                    <a href="<?= Url::to(['/followUp/follow-up/index', 'contract_id' => $cid]) ?>"
                                       class="cust-contract-badge" title="متابعة العقد <?= $cid ?>">
                                        <?= $cid ?>
                                    </a>
                                <?php endforeach ?>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-job" data-label="الوظيفة">
                            <?= Html::encode($jobName) ?>
                        </td>
                        <td class="ct-td-actions" data-label="">
                            <div class="ct-act-wrap">
                                <button class="ct-act-trigger" aria-label="إجراءات العميل <?= $m->id ?>"
                                        aria-haspopup="true" tabindex="0">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="ct-act-menu" role="menu">
                                    <?php if (Permissions::can(Permissions::CUST_UPDATE)): ?>
                                    <a href="<?= Url::to(['update', 'id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-pencil text-primary"></i> تعديل
                                    </a>
                                    <?php endif ?>
                                    <a href="<?= Url::to(['view', 'id' => $m->id]) ?>" role="modal-remote">
                                        <i class="fa fa-eye text-info"></i> عرض
                                    </a>
                                    <a href="<?= Url::to(['/contracts/contracts/create', 'id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-file-text-o text-success"></i> إضافة عقد
                                    </a>
                                    <?php if (Permissions::can(Permissions::CUST_UPDATE)): ?>
                                        <div class="ct-act-divider"></div>
                                        <a href="<?= Url::to(['update-contact', 'id' => $m->id]) ?>" role="modal-remote">
                                            <i class="fa fa-phone text-warning"></i> تحديث اتصال
                                        </a>
                                    <?php endif ?>
                                    <?php if (Permissions::can(Permissions::CUST_DELETE)): ?>
                                        <div class="ct-act-divider"></div>
                                        <a href="<?= Url::to(['delete', 'id' => $m->id]) ?>"
                                           class="cust-delete-link"
                                           data-confirm-msg="هل أنت متأكد من حذف هذا العميل؟"
                                           role="menuitem">
                                            <i class="fa fa-trash text-danger"></i> حذف
                                        </a>
                                    <?php endif ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <!-- ===== PAGINATION ===== -->
    <?php if ($totalCount > 0): ?>
    <div class="ct-pagination-wrap">
        <?= LinkPager::widget([
            'pagination' => $pagination,
            'prevPageLabel' => '<i class="fa fa-chevron-right"></i>',
            'nextPageLabel' => '<i class="fa fa-chevron-left"></i>',
            'firstPageLabel' => '<i class="fa fa-angle-double-right"></i>',
            'lastPageLabel' => '<i class="fa fa-angle-double-left"></i>',
            'maxButtonCount' => 7,
            'options' => ['class' => 'pagination', 'aria-label' => 'تصفح الصفحات'],
        ]) ?>
    </div>
    <?php endif ?>

</div><!-- /.ct-page -->

<!-- ===== MODALS ===== -->
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--clr-primary,#800020)"></i>
                </div>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
