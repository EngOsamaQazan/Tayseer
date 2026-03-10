<?php
/**
 * الدائرة القانونية — واجهة V2 حديثة ومتجاوبة
 * Legal Department — Modern responsive UI (same philosophy as contracts/index)
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
use yii\bootstrap\Modal;
use common\helper\Permissions;
use backend\widgets\ExportButtons;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\judiciary\models\Judiciary;
use backend\helpers\NameHelper;

/* Assets */
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . time());
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/contracts-v2.js?v=' . time(), [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$this->title = 'الدائرة القانونية';
$this->params['breadcrumbs'][] = ['label' => 'العقود', 'url' => ['index']];
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

$begin = $pagination ? $pagination->getOffset() + 1 : 1;
$end   = $begin + count($models) - 1;

/* Pre-fetch judiciary records for all contracts to avoid N+1 */
$contractIds = ArrayHelper::getColumn($models, 'id');
$judiciaryMap = [];
if (!empty($contractIds)) {
    $judRecords = Judiciary::find()
        ->where(['contract_id' => $contractIds])
        ->orderBy(['id' => SORT_DESC])
        ->all();
    foreach ($judRecords as $jud) {
        if (!isset($judiciaryMap[$jud->contract_id])) {
            $judiciaryMap[$jud->contract_id] = $jud;
        }
    }
}

/* Pre-fetch jobs (id → name, id → job_type FK) and job types (id → name) */
$jobsRows = \backend\modules\jobs\models\Jobs::find()->select(['id', 'name', 'job_type'])->asArray()->all();
$jobsMap = ArrayHelper::map($jobsRows, 'id', 'name');
$jobToTypeMap = ArrayHelper::map($jobsRows, 'id', 'job_type');
$jobTypesMap = ArrayHelper::map(
    \backend\modules\jobs\models\JobsType::find()->select(['id', 'name'])->asArray()->all(), 'id', 'name'
);
?>

<?php $isIframe = Yii::$app->request->get('_iframe'); ?>
<div class="ct-page ct-legal-page<?= $isIframe ? ' ct-iframe-mode' : '' ?>" role="main" aria-label="صفحة الدائرة القانونية">

    <!-- Flash messages -->
    <?php foreach (['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'] as $type => $icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="ct-alert ct-alert-<?= $type === 'error' ? 'danger' : $type ?>" role="alert"
                 x-data="{ show: true }" x-show="show" x-transition x-cloak
                 x-init="setTimeout(() => show = false, 5000)">
                <i class="fa fa-<?= $icon ?>"></i>
                <span><?= Yii::$app->session->getFlash($type) ?></span>
                <button class="ct-alert-close" @click="show = false" aria-label="إغلاق">&times;</button>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <!-- ===== PAGE HEADER ===== -->
    <div class="ct-page-hdr">
        <div class="ct-title-area">
            <h1><i class="fa fa-legal" style="margin-left:8px;opacity:.7"></i>الدائرة القانونية</h1>
            <span class="ct-count" aria-label="إجمالي العقود"><?= number_format($dataCount) ?></span>
        </div>
        <div class="ct-hdr-actions">
            <?php
            $isShowAll = Yii::$app->request->get('show_all');
            $showAllParams = Yii::$app->request->queryParams;
            if ($isShowAll) {
                unset($showAllParams['show_all']);
            } else {
                $showAllParams['show_all'] = 1;
            }
            $showAllUrl = Url::to(array_merge(['index-legal-department'], $showAllParams));
            ?>
            <a href="<?= $showAllUrl ?>" class="ct-btn ct-btn-outline" id="ctShowAllBtn" aria-label="<?= $isShowAll ? 'عرض مرقّم' : 'عرض الجميع' ?>">
                <i class="fa fa-<?= $isShowAll ? 'list' : 'th-list' ?>"></i>
                <span class="ct-hide-xs"><?= $isShowAll ? 'عرض مرقّم' : 'عرض الجميع' ?></span>
            </a>
            <?= ExportButtons::widget([
                'excelRoute' => ['export-legal-excel'],
                'pdfRoute' => ['export-legal-pdf'],
                'excelBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
                'pdfBtnClass' => 'ct-btn ct-btn-outline ct-hide-sm',
            ]) ?>
            <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctFilterToggle" aria-label="فتح الفلاتر">
                <i class="fa fa-sliders" style="font-size:18px"></i>
            </button>
        </div>
    </div>

    <!-- ===== LEGAL SUMMARY CARDS ===== -->
    <?php
    $totalContracts = $dataCount;
    $withCase = (int) Judiciary::find()
        ->innerJoin('os_contracts c2', 'c2.id = os_judiciary.contract_id')
        ->where(['c2.status' => 'legal_department', 'c2.is_deleted' => 0, 'os_judiciary.is_deleted' => 0])
        ->count('DISTINCT os_judiciary.contract_id');
    $withoutCase = $totalContracts - $withCase;
    $totalLegalCosts = (int) Yii::$app->db->createCommand(
        'SELECT COALESCE(SUM(COALESCE(j.case_cost,0) + COALESCE(j.lawyer_cost,0)),0)
         FROM os_judiciary j INNER JOIN os_contracts c2 ON c2.id = j.contract_id
         WHERE c2.status = :st AND c2.is_deleted = 0 AND j.is_deleted = 0',
        [':st' => 'legal_department']
    )->queryScalar();
    ?>
    <div class="ct-legal-stats">
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#FFF3E0;color:#E65100"><i class="fa fa-legal"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($totalContracts) ?></span>
                <span class="ct-legal-stat-label">إجمالي العقود</span>
            </div>
        </div>
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#E8F5E9;color:#2E7D32"><i class="fa fa-check-circle"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($withCase) ?></span>
                <span class="ct-legal-stat-label">لديها قضية مسجلة</span>
            </div>
        </div>
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#FFF8E1;color:#F57F17"><i class="fa fa-exclamation-circle"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($withoutCase) ?></span>
                <span class="ct-legal-stat-label">بدون قضية (بانتظار التسجيل)</span>
            </div>
        </div>
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#FCE4EC;color:#C62828"><i class="fa fa-money"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($totalLegalCosts) ?></span>
                <span class="ct-legal-stat-label">إجمالي التكاليف القانونية</span>
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
                <?= $this->render('_legal_search_v2', ['model' => $searchModel]) ?>
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
            <div class="ct-empty">
                <i class="fa fa-inbox"></i>
                <p>لا توجد عقود مطابقة لمعايير البحث</p>
                <a href="<?= Url::to(['legal-department']) ?>" class="ct-btn ct-btn-outline">
                    <i class="fa fa-refresh"></i> عرض جميع العقود
                </a>
            </div>
        <?php else: ?>
            <table class="ct-table" role="grid">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center">
                            <input type="checkbox" id="ctSelectAll" title="تحديد الكل" style="cursor:pointer;width:18px;height:18px">
                        </th>
                        <th class="ct-th-id"><?= $sortLink('id', '#') ?></th>
                        <th><?= $sortLink('customer_name', 'الأطراف') ?></th>
                        <th><?= $sortLink('total_value', 'الإجمالي') ?></th>
                        <th><?= $sortLink('remaining', 'المتبقي') ?></th>
                        <th><?= $sortLink('job_name', 'الوظيفة') ?></th>
                        <th><?= $sortLink('job_type_name', 'نوع الوظيفة') ?></th>
                        <th style="text-align:center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m):
                        /* Parties: names + national IDs */
                        $allParties = $m->customersAndGuarantor;
                        $partiesHtml = [];
                        $firstCustomer = $allParties[0] ?? null;
                        foreach ($allParties as $p) {
                            $line = Html::encode(NameHelper::short($p->name));
                            if ($p->id_number) $line .= ' <small style="color:#64748b">(' . Html::encode($p->id_number) . ')</small>';
                            $partiesHtml[] = $line;
                        }
                        $partiesDisplay = implode('<br>', $partiesHtml) ?: '—';
                        $partiesTitle = implode('، ', ArrayHelper::getColumn($allParties, 'name'));

                        /* Total with judiciary costs */
                        $jud = $judiciaryMap[$m->id] ?? null;
                        $total = $m->total_value;
                        if ($jud) $total += ($jud->case_cost ?? 0) + ($jud->lawyer_cost ?? 0);

                        /* Remaining */
                        $totalForRemain = $m->total_value;
                        if ($jud) {
                            $caseCosts = \backend\modules\expenses\models\Expenses::find()
                                ->where(['contract_id' => $m->id, 'category_id' => 4])->sum('amount') ?? 0;
                            $totalForRemain += $caseCosts + ($jud->lawyer_cost ?? 0);
                        }
                        $paid = ContractInstallment::find()->where(['contract_id' => $m->id])->sum('amount') ?? 0;
                        $remaining = $totalForRemain - $paid;

                        /* Job info from first customer: Customer.job_title → Jobs.id → Jobs.job_type → JobsType.id */
                        $jobId = ($firstCustomer && $firstCustomer->job_title) ? $firstCustomer->job_title : null;
                        $jobName = $jobId ? ($jobsMap[$jobId] ?? '—') : '—';
                        $jobTypeId = $jobId ? ($jobToTypeMap[$jobId] ?? null) : null;
                        $jobTypeName = $jobTypeId ? ($jobTypesMap[$jobTypeId] ?? '—') : '—';

                    ?>
                    <tr data-id="<?= $m->id ?>">
                        <td style="text-align:center;vertical-align:middle">
                            <?php if (!$jud): ?>
                            <input type="checkbox" class="ct-batch-check" value="<?= $m->id ?>"
                                   data-remaining="<?= round($remaining, 2) ?>"
                                   data-customer="<?= Html::encode($partiesTitle) ?>"
                                   style="cursor:pointer;width:18px;height:18px">
                            <?php else: ?>
                            <span class="ct-text-muted" title="تم إنشاء القضية"><i class="fa fa-check-circle text-success" style="font-size:16px"></i></span>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-id" data-label="#">
                            <?= $m->id ?>
                        </td>
                        <td class="ct-td-customer" data-label="الأطراف" title="<?= Html::encode($partiesTitle) ?>" style="white-space:normal;min-width:180px">
                            <?= $partiesDisplay ?>
                        </td>
                        <td class="ct-td-money" data-label="الإجمالي">
                            <?= number_format($total, 0) ?>
                        </td>
                        <td class="ct-td-money ct-td-remain" data-label="المتبقي">
                            <?= number_format($remaining, 0) ?>
                        </td>
                        <td data-label="الوظيفة">
                            <?= Html::encode($jobName) ?>
                        </td>
                        <td data-label="نوع الوظيفة">
                            <?= Html::encode($jobTypeName) ?>
                        </td>
                        <td class="ct-td-actions" data-label="">
                            <div class="ct-act-wrap">
                                <button class="ct-act-trigger" aria-label="إجراءات العقد <?= $m->id ?>"
                                        aria-haspopup="true" tabindex="0">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="ct-act-menu" role="menu">
                                    <a href="<?= Url::to(['/followUp/follow-up/panel', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-dashboard text-primary"></i> لوحة التحكم
                                    </a>
                                    <a href="<?= Url::to(['update', 'id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-pencil text-primary"></i> تعديل
                                    </a>
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
                                    <?php if ($jud): ?>
                                    <div class="ct-act-divider"></div>
                                    <a href="<?= Url::to(['/judiciary/judiciary/update', 'id' => $jud->id, 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-gavel text-danger"></i> ملف القضية
                                    </a>
                                    <a href="<?= Url::to(['/collection/collection/create', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-hand-paper-o text-warning"></i> تحصيل
                                    </a>
                                    <?php else: ?>
                                    <div class="ct-act-divider"></div>
                                    <a href="<?= Url::to(['/judiciary/judiciary/create', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-gavel text-danger"></i> إنشاء قضية
                                    </a>
                                    <?php endif ?>
                                    <?php if ($isManager): ?>
                                        <div class="ct-act-divider"></div>
                                        <a href="#" class="yeas-finish" data-url="<?= Url::to(['finish', 'id' => $m->id]) ?>" role="menuitem">
                                            <i class="fa fa-check-circle text-success"></i> إنهاء العقد
                                        </a>
                                        <a href="#" class="yeas-cancel" data-url="<?= Url::to(['cancel', 'id' => $m->id]) ?>" role="menuitem">
                                            <i class="fa fa-ban text-danger"></i> إلغاء العقد
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
    <?php if ($dataCount > 0 && $pagination): ?>
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
<?php Modal::begin([
    'id' => 'finishContractModal',
    'header' => '<h4 class="modal-title"><i class="fa fa-check-circle text-success"></i> تأكيد إنهاء العقد</h4>',
    'size' => Modal::SIZE_SMALL,
]) ?>
<div class="ct-modal-body">
    <p class="lead">هل أنت متأكد من إنهاء هذا العقد؟</p>
    <p class="text-muted">سيتم تغيير حالة العقد إلى "منتهي"</p>
    <div class="ct-modal-actions">
        <a id="finishContractBtn" href="#" class="ct-btn ct-btn-primary" style="background:#28a745;border-color:#28a745">
            <i class="fa fa-check"></i> نعم، إنهاء
        </a>
        <button type="button" class="ct-btn ct-btn-outline" data-dismiss="modal">
            <i class="fa fa-times"></i> إلغاء
        </button>
    </div>
</div>
<?php Modal::end() ?>

<?php Modal::begin([
    'id' => 'cancelContractModal',
    'header' => '<h4 class="modal-title"><i class="fa fa-ban text-danger"></i> تأكيد إلغاء العقد</h4>',
    'size' => Modal::SIZE_SMALL,
]) ?>
<div class="ct-modal-body">
    <p class="lead">هل أنت متأكد من إلغاء هذا العقد؟</p>
    <p class="text-danger"><i class="fa fa-exclamation-triangle"></i> تحذير: لا يمكن التراجع عن هذا الإجراء</p>
    <div class="ct-modal-actions">
        <a id="cancelContractBtn" href="#" class="ct-btn ct-btn-primary" style="background:#dc3545;border-color:#dc3545">
            <i class="fa fa-ban"></i> نعم، إلغاء
        </a>
        <button type="button" class="ct-btn ct-btn-outline" data-dismiss="modal">
            <i class="fa fa-times"></i> تراجع
        </button>
    </div>
</div>
<?php Modal::end() ?>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>

<!-- ===== BATCH ACTION FLOATING BAR ===== -->
<div id="ctBatchBar" class="ct-batch-bar"
     x-data="{ showBar: false, batchCount: 0 }"
     @update-batch.window="showBar = $event.detail.show; batchCount = $event.detail.count"
     x-show="showBar" x-transition x-cloak>
    <div class="ct-batch-bar-inner">
        <span class="ct-batch-bar-count">
            <i class="fa fa-check-square-o"></i>
            تم تحديد <strong id="ctBatchCount" x-text="batchCount">0</strong> عقد
        </span>
        <button type="button" id="ctBatchClear" class="ct-btn ct-btn-outline" style="border-color:rgba(255,255,255,.4);color:#fff;padding:6px 16px">
            <i class="fa fa-times"></i> إلغاء التحديد
        </button>
        <form id="ctBatchForm" method="POST" action="<?= Url::to(['/judiciary/judiciary/batch-create']) ?>" style="display:inline">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
            <input type="hidden" name="contract_ids" id="ctBatchIds" value="">
            <button type="submit" class="ct-btn ct-btn-primary" style="padding:8px 24px;font-weight:700;font-size:14px">
                <i class="fa fa-gavel"></i> تجهيز القضايا
            </button>
        </form>
    </div>
</div>

<?php
$this->registerCss(<<<'CSS'
/* ═══ Batch Selection Bar ═══ */
.ct-batch-bar {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 1050;
    background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
    padding: 12px 20px;
    animation: ctSlideUp .3s ease;
}
@keyframes ctSlideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
.ct-batch-bar-inner {
    max-width: 1200px; margin: 0 auto;
    display: flex; align-items: center; justify-content: center; gap: 16px; flex-wrap: wrap;
}
.ct-batch-bar-count { color: #fff; font-size: 15px; }
.ct-batch-bar-count strong { color: #fbbf24; font-size: 18px; margin: 0 4px; }
/* Checkbox highlight row */
tr.ct-row-selected { background: #fffbeb !important; }
tr.ct-row-selected:hover { background: #fef3c7 !important; }
CSS
);

$this->registerJs(<<<'JS'
/* ═══ Batch Selection Logic ═══ */
(function(){
    var $bar = $('#ctBatchBar'), $count = $('#ctBatchCount'), $ids = $('#ctBatchIds');
    var $checkAll = $('#ctSelectAll');
    var selected = {};

    function updateBar() {
        var keys = Object.keys(selected);
        var n = keys.length;
        /* OLD jQuery - replaced by Alpine.js */
        // $count.text(n);
        // if (n > 0) { $bar.stop(true).slideDown(300); } else { $bar.stop(true).slideUp(200); }
        $ids.val(keys.join(','));
        window.dispatchEvent(new CustomEvent('update-batch',{detail:{show:n>0,count:n}}));
    }

    $(document).on('change', '.ct-batch-check', function(){
        var id = $(this).val();
        if (this.checked) {
            selected[id] = true;
            $(this).closest('tr').addClass('ct-row-selected');
        } else {
            delete selected[id];
            $(this).closest('tr').removeClass('ct-row-selected');
        }
        updateBar();
        // Update select-all state
        var total = $('.ct-batch-check').length;
        var checked = $('.ct-batch-check:checked').length;
        $checkAll.prop('checked', checked === total && total > 0);
        $checkAll.prop('indeterminate', checked > 0 && checked < total);
    });

    $checkAll.on('change', function(){
        var isChecked = this.checked;
        $('.ct-batch-check').each(function(){
            this.checked = isChecked;
            var id = $(this).val();
            if (isChecked) {
                selected[id] = true;
                $(this).closest('tr').addClass('ct-row-selected');
            } else {
                delete selected[id];
                $(this).closest('tr').removeClass('ct-row-selected');
            }
        });
        updateBar();
    });

    $('#ctBatchClear').on('click', function(){
        selected = {};
        $('.ct-batch-check').prop('checked', false);
        $checkAll.prop('checked', false).prop('indeterminate', false);
        $('tr.ct-row-selected').removeClass('ct-row-selected');
        updateBar();
    });

    // Validate before submit
    $('#ctBatchForm').on('submit', function(e){
        if (Object.keys(selected).length === 0) {
            e.preventDefault();
            alert('الرجاء تحديد عقد واحد على الأقل');
        }
    });
})();

/* OLD jQuery - replaced by Alpine.js */
// $('.ct-alert-close').on('click', function(){ $(this).closest('.ct-alert').fadeOut(300); });
// setTimeout(function(){ $('.ct-alert').fadeOut(500); }, 5000);
JS
);

if (Yii::$app->request->get('_iframe')) {
    $this->registerJs(<<<'IFRAME_JS'
(function(){
    function isSamePage(url) {
        return url && (url.indexOf('index-legal-department') !== -1 || url.indexOf('legal-department') !== -1);
    }
    function ensureIframeParam(url) {
        if (url && url.indexOf('_iframe') === -1) {
            return url + (url.indexOf('?') !== -1 ? '&' : '?') + '_iframe=1';
        }
        return url;
    }
    function stripIframeParam(url) {
        return url.replace(/([?&])_iframe=1&?/g, '$1').replace(/[?&]$/, '');
    }

    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
        if (link.getAttribute('role') === 'modal-remote') return;
        if (link.hasAttribute('data-pjax')) return;

        if (isSamePage(href)) {
            link.setAttribute('href', ensureIframeParam(href));
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        window.top.location.href = href;
    }, true);

    document.addEventListener('submit', function(e) {
        var form = e.target;
        var action = form.getAttribute('action') || '';
        if (action && !isSamePage(action)) {
            form.setAttribute('target', '_top');
        } else if (!form.querySelector('input[name="_iframe"]')) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = '_iframe'; inp.value = '1';
            form.appendChild(inp);
        }
    }, true);

    new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            for (var i = 0; i < m.addedNodes.length; i++) {
                var node = m.addedNodes[i];
                if (node.tagName === 'FORM' && !node.getAttribute('target')) {
                    var action = node.getAttribute('action') || '';
                    if (action && !isSamePage(action)) {
                        node.setAttribute('target', '_top');
                    }
                }
            }
        });
    }).observe(document.body, { childList: true });

    $(document).off('click', '#ctExportBtn').on('click', '#ctExportBtn', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var params = window.location.search;
        var exportUrl = window.location.pathname +
            (params ? params + '&' : '?') + 'export=csv';
        window.top.location.href = stripIframeParam(exportUrl);
    });

    $(document).off('click', '.ct-chip-remove').on('click', '.ct-chip-remove', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var param = $(this).data('param');
        if (param === '__all') {
            window.location.href = ensureIframeParam(window.location.pathname);
            return;
        }
        var params = new URLSearchParams(window.location.search);
        params.delete(param);
        if (!params.has('_iframe')) params.set('_iframe', '1');
        window.location.href = window.location.pathname + '?' + params.toString();
    });
})();
IFRAME_JS
    );
}
?>
