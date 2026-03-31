<?php
/**
 * عقود — واجهة V2 حديثة ومتجاوبة
 * Contracts index — Modern responsive UI
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
/* Bootstrap 3 Modal removed — using Bootstrap 5 HTML instead */
use common\helper\Permissions;
use backend\widgets\ExportButtons;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\judiciary\models\Judiciary;
use backend\helpers\NameHelper;

/* Assets */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . Yii::$app->params['assetVersion']);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/contracts-v2.js?v=' . Yii::$app->params['assetVersion'], [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$this->title = 'العقود';
$this->params['breadcrumbs'][] = $this->title;

/* Data */
$isManager  = Yii::$app->user->can(Permissions::MANAGER);
$models     = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$sort       = $dataProvider->getSort();
$allUsers   = $isManager
    ? ArrayHelper::map(
        Yii::$app->db->createCommand(
            "SELECT DISTINCT u.id, u.username FROM {{%user}} u
             INNER JOIN {{%auth_assignment}} a ON a.user_id = u.id
             WHERE u.blocked_at IS NULL AND u.employee_type = 'Active'
             ORDER BY u.username"
        )->queryAll(),
        'id', 'username'
    ) : [];

$batch = $batchData ?? [];

$statusLabels = [
    'active' => 'نشط', 'judiciary' => 'قضاء', 'judiciary_active' => 'قضاء فعّال',
    'judiciary_paid' => 'قضاء مسدد', 'legal_department' => 'قانوني',
    'settlement' => 'تسوية', 'finished' => 'منتهي', 'canceled' => 'ملغي',
];

$sc = $statusCounts ?? [];
$activeFilter = Yii::$app->request->get('ContractsSearch', [])['status'] ?? '';

$cards = [
    ['key' => '',                  'label' => 'الكل',        'icon' => 'fa-th-list',       'color' => '#64748b', 'bg' => '#f1f5f9'],
    ['key' => 'active',            'label' => 'نشط',         'icon' => 'fa-check-circle',  'color' => '#1a7a35', 'bg' => '#e6f9ed'],
    ['key' => 'judiciary_active',  'label' => 'قضاء فعّال',   'icon' => 'fa-gavel',         'color' => '#c62828', 'bg' => '#fce4e4'],
    ['key' => 'judiciary_paid',    'label' => 'قضاء مسدد',   'icon' => 'fa-gavel',         'color' => '#2e7d32', 'bg' => '#e8f5e9'],
    ['key' => 'legal_department',  'label' => 'قانوني',      'icon' => 'fa-balance-scale', 'color' => '#0c5460', 'bg' => '#d1ecf1'],
    ['key' => 'settlement',        'label' => 'تسوية',       'icon' => 'fa-handshake-o',   'color' => '#5a2d82', 'bg' => '#ede5f6'],
    ['key' => 'finished',          'label' => 'منتهي',       'icon' => 'fa-flag-checkered','color' => '#495057', 'bg' => '#e9ecef'],
    ['key' => 'canceled',          'label' => 'ملغي',        'icon' => 'fa-ban',           'color' => '#6c757d', 'bg' => '#e9ecef'],
];
$totalAll = array_sum($sc);

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

<div class="ct-page" role="main" aria-label="صفحة العقود">

    <!-- Flash messages (Alpine.js) -->
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
            <h1><i class="fa fa-file-text-o" style="margin-left:8px;opacity:.7"></i>العقود</h1>
            <span class="ct-count" aria-label="إجمالي العقود"><?= number_format($dataCount) ?></span>
            <span class="ct-count-note">لا تشمل العقود المُلغاة</span>
        </div>
        <div class="ct-hdr-actions">
            <?php if (Permissions::can(Permissions::CONT_CREATE)): ?>
            <a href="<?= Url::to(['create']) ?>" class="ct-btn ct-btn-primary" aria-label="إضافة عقد جديد">
                <i class="fa fa-plus"></i> <span class="ct-hide-xs">إضافة عقد</span>
            </a>
            <?php endif ?>
            <?= ExportButtons::widget([
                'excelRoute' => ['export-excel'],
                'pdfRoute' => ['export-pdf'],
                'excelBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                'pdfBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
            ]) ?>
            <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctFilterToggle" aria-label="فتح الفلاتر">
                <i class="fa fa-sliders" style="font-size:18px"></i>
            </button>
        </div>
    </div>

    <!-- ===== STATUS CARDS ===== -->
    <div class="ct-status-cards">
        <?php foreach ($cards as $c):
            $cnt = $c['key'] === '' ? $totalAll : (int)($sc[$c['key']] ?? 0);
            $isActive = $activeFilter === $c['key'];
            $url = $c['key'] === ''
                ? Url::to(['index'])
                : Url::to(['index', 'ContractsSearch[status]' => $c['key']]);
        ?>
        <a href="<?= $url ?>"
           class="ct-stat-card<?= $isActive ? ' ct-stat-active' : '' ?>"
           style="--card-color:<?= $c['color'] ?>;--card-bg:<?= $c['bg'] ?>">
            <div class="ct-stat-icon"><i class="fa <?= $c['icon'] ?>"></i></div>
            <div class="ct-stat-info">
                <span class="ct-stat-num"><?= number_format($cnt) ?></span>
                <span class="ct-stat-label"><?= $c['label'] ?></span>
            </div>
        </a>
        <?php endforeach ?>
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
            <?php if ($dataCount > 0): ?>
                عرض <strong><?= number_format($begin) ?>–<?= number_format($end) ?></strong>
                من أصل <strong><?= number_format($dataCount) ?></strong> عقد
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
            <!-- Empty state -->
            <div class="ct-empty">
                <i class="fa fa-inbox"></i>
                <p>لا توجد عقود مطابقة لمعايير البحث</p>
                <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline">
                    <i class="fa fa-refresh"></i> عرض جميع العقود
                </a>
            </div>
        <?php else: ?>
            <table class="ct-table" role="grid">
                <thead>
                    <tr>
                        <th class="ct-th-id"><?= $sortLink('id', '#') ?></th>
                        <th><?= $sortLink('seller_id', 'البائع') ?></th>
                        <th>العميل</th>
                        <th>المستحق</th>
                        <th><?= $sortLink('Date_of_sale', 'التاريخ') ?></th>
                        <th>الدفعة الأولى</th>
                        <th><?= $sortLink('total_value', 'الإجمالي') ?></th>
                        <th><?= $sortLink('status', 'الحالة') ?></th>
                        <th>المتبقي</th>
                        <th>المتابع</th>
                        <th style="text-align:center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m):
                        $cid = $m->id;
                        $bd = $batch[$cid] ?? [];
                        $remaining = (float)($bd['remaining'] ?? 0);
                        $total = (float)($bd['totalDebt'] ?? $m->total_value);
                        $isJudPaid = $bd['isJudiciaryPaid'] ?? false;

                        $calc = new ContractCalculations($cid);
                        $deserved = $calc->deservedAmount();

                        $nm = ($namesMap ?? [])[$cid] ?? null;
                        $customerFullNames = $nm ? ($nm['client_names'] ?: '—') : '—';
                        $customerNames = $nm ? implode('، ', array_map([NameHelper::class, 'short'], explode('، ', $nm['client_names'] ?? ''))) ?: '—' : '—';
                        $customerIdNumbers = [];
                        $sellerName = isset($m->seller->name) ? NameHelper::short($m->seller->name) : '—';
                        $followName = $allUsers[$m->followed_by] ?? ($m->followedBy->username ?? '—');
                    ?>
                    <tr data-id="<?= $m->id ?>">
                        <td class="ct-td-id" data-label="#">
                            <?= $m->id ?>
                        </td>
                        <td class="ct-td-seller" data-label="البائع">
                            <?= Html::encode($sellerName) ?>
                        </td>
                        <td class="ct-td-customer" data-label="العميل" title="<?= Html::encode($customerFullNames) ?>">
                            <?= Html::encode($customerNames) ?>
                            <?php if ($customerIdNumbers): ?>
                                <span class="ct-id-nums"><?= Html::encode(implode(' | ', $customerIdNumbers)) ?></span>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-money ct-td-due" data-label="المستحق">
                            <?= number_format($deserved, 0) ?>
                        </td>
                        <td class="ct-td-date" data-label="التاريخ">
                            <?= $m->Date_of_sale ?>
                        </td>
                        <td class="ct-td-money" data-label="الدفعة الأولى">
                            <?= $m->first_installment_value ? number_format($m->first_installment_value, 0) : '—' ?>
                        </td>
                        <td class="ct-td-money" data-label="الإجمالي">
                            <?= number_format($total, 0) ?>
                        </td>
                        <td class="ct-td-status" data-label="الحالة">
                            <?php if ($isJudPaid): ?>
                                <span class="ct-badge ct-st-judiciary-paid">قضاء مسدد</span>
                            <?php elseif ($m->status === 'judiciary'): ?>
                                <span class="ct-badge ct-st-judiciary">قضاء فعّال</span>
                            <?php else: ?>
                                <span class="ct-badge ct-st-<?= $m->status ?>">
                                    <?= $statusLabels[$m->status] ?? $m->status ?>
                                </span>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-money ct-td-remain" data-label="المتبقي">
                            <?= number_format($remaining, 0) ?>
                        </td>
                        <td class="ct-td-follow" data-label="المتابع">
                            <?php if ($isManager): ?>
                                <?= Html::dropDownList('followedBy', $m->followed_by, $allUsers, [
                                    'class' => 'ct-follow-select followUpUser',
                                    'data-contract-id' => $m->id,
                                    'prompt' => '-- اختر --',
                                ]) ?>
                            <?php else: ?>
                                <?= Html::encode($followName) ?>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-actions" data-label="">
                            <div class="ct-act-wrap">
                                <button class="ct-act-trigger" aria-label="إجراءات العقد <?= $m->id ?>"
                                        aria-haspopup="true" tabindex="0">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="ct-act-menu" role="menu">
                                    <?php if (Permissions::can(Permissions::CONT_UPDATE)): ?>
                                    <a href="<?= Url::to(['update', 'id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-pencil text-primary"></i> تعديل
                                    </a>
                                    <?php endif ?>
                                    <a href="<?= Url::to(['print-preview', 'id' => $m->id]) ?>" target="_blank" role="menuitem">
                                        <i class="fa fa-print text-info"></i> طباعة
                                    </a>
                                    <div class="ct-act-divider"></div>
                                    <a href="<?= Url::to(['/contractInstallment/contract-installment/index', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-money text-success"></i> الدفعات
                                    </a>
                                    <a href="<?= Url::to(['/followUp/follow-up/index', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-comments text-primary"></i> المتابعة
                                    </a>
                                    <a href="<?= Url::to(['/loanScheduling/loan-scheduling/create', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-calendar text-info"></i> جدولة
                                    </a>
                                    <a href="<?= Url::to(['view', 'id' => $m->id]) . '#contractAdjustmentsPanel' ?>" role="menuitem">
                                        <i class="fa fa-tags text-warning"></i> الخصومات
                                    </a>
                                    <?php if ($m->status === 'judiciary'): ?>
                                        <a href="<?= Url::to(['/collection/collection/create', 'contract_id' => $m->id]) ?>" role="menuitem">
                                            <i class="fa fa-gavel text-danger"></i> تحصيل
                                        </a>
                                    <?php endif ?>
                                    <?php if ($isManager): ?>
                                        <div class="ct-act-divider"></div>
                                        <?php if (Permissions::can(Permissions::CONT_UPDATE)): ?>
                                        <a href="#" class="yeas-cancel" data-url="<?= Url::to(['cancel-contract', 'contract_id' => $m->id]) ?>" role="menuitem">
                                            <i class="fa fa-ban text-danger"></i> إلغاء العقد
                                        </a>
                                        <?php endif ?>
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
    <?php if ($dataCount > 0): ?>
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
<div class="modal fade" id="cancelContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-ban text-danger"></i> تأكيد إلغاء العقد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="ct-modal-body">
                    <p class="lead">هل أنت متأكد من إلغاء هذا العقد؟</p>
                    <p class="text-danger"><i class="fa fa-exclamation-triangle"></i> تحذير: لا يمكن التراجع عن هذا الإجراء</p>
                    <div class="ct-modal-actions">
                        <a id="cancelContractBtn" href="#" class="ct-btn ct-btn-primary" style="background:#dc3545;border-color:#dc3545">
                            <i class="fa fa-ban"></i> نعم، إلغاء
                        </a>
                        <button type="button" class="ct-btn ct-btn-outline" data-bs-dismiss="modal">
                            <i class="fa fa-times"></i> تراجع
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<?php
/* OLD jQuery - replaced by Alpine.js
$this->registerJs(<<<'JS'
// Auto-dismiss flash alerts
$('.ct-alert-close').on('click', function(){ $(this).closest('.ct-alert').fadeOut(300); });
setTimeout(function(){ $('.ct-alert').fadeOut(500); }, 5000);
JS
);
*/
?>
