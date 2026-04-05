<?php
/**
 * الدائرة القانونية — واجهة V3 محسّنة
 * Legal Department — Enhanced V3 UI
 * Bootstrap 5 modals, microinteractions, dark mode support
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
use common\helper\Permissions;
use backend\widgets\ExportButtons;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\judiciary\models\Judiciary;
use backend\helpers\NameHelper;

/* Assets */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . Yii::$app->params['assetVersion']);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/legal-department-v3.css?v=' . Yii::$app->params['assetVersion']);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-themes.css?v=' . Yii::$app->params['assetVersion']);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/contracts-v2.js?v=' . Yii::$app->params['assetVersion'], [
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

/* Pre-fetch jobs */
$jobsRows = \backend\modules\jobs\models\Jobs::find()->select(['id', 'name', 'job_type'])->asArray()->all();
$jobsMap = ArrayHelper::map($jobsRows, 'id', 'name');
$jobToTypeMap = ArrayHelper::map($jobsRows, 'id', 'job_type');
$jobTypesMap = ArrayHelper::map(
    \backend\modules\jobs\models\JobsType::find()->select(['id', 'name'])->asArray()->all(), 'id', 'name'
);

/* Stats: sector breakdown by first customer's job_type */
$totalContracts = $dataCount;
$sectorCounts = Yii::$app->db->createCommand(
    "SELECT jt.name AS sector_name, COUNT(DISTINCT c.id) AS cnt
     FROM os_contracts c
     INNER JOIN os_contracts_customers cc ON cc.contract_id = c.id
     INNER JOIN os_customers cust ON cust.id = cc.customer_id AND cust.is_deleted = 0
     INNER JOIN os_jobs j ON j.id = cust.job_title AND j.is_deleted = 0
     INNER JOIN os_jobs_type jt ON jt.id = j.job_type
     WHERE c.status = 'legal_department' AND c.is_deleted = 0
     GROUP BY jt.id, jt.name
     ORDER BY cnt DESC"
)->queryAll();
$sectorMap = ArrayHelper::map($sectorCounts, 'sector_name', 'cnt');
$countWithJob = array_sum(array_column($sectorCounts, 'cnt'));
$countNoJob = $totalContracts - $countWithJob;
?>

<?php $isIframe = Yii::$app->request->get('_iframe'); ?>
<div class="ct-page ld-page<?= $isIframe ? ' ct-iframe-mode' : '' ?>" role="main" aria-label="صفحة الدائرة القانونية">

    <!-- Flash messages -->
    <?php foreach (['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'] as $type => $icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="ld-toast ld-toast-<?= $type === 'error' ? 'danger' : $type ?>" role="alert">
                <div class="ld-toast-icon"><i class="fa fa-<?= $icon ?>"></i></div>
                <span><?= Yii::$app->session->getFlash($type) ?></span>
                <button class="ld-toast-close" onclick="this.parentElement.remove()" aria-label="إغلاق">&times;</button>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <!-- ===== PAGE HEADER ===== -->
    <div class="ld-header">
        <div class="ld-header-main">
            <div class="ld-header-icon">
                <i class="fa fa-legal"></i>
            </div>
            <div class="ld-header-text">
                <h1>الدائرة القانونية</h1>
                <p class="ld-header-sub">إدارة العقود المحولة للقسم القانوني والقضايا المسجلة</p>
            </div>
            <span class="ld-badge-count"><?= number_format($dataCount) ?></span>
        </div>
        <div class="ld-header-actions">
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
            <a href="<?= $showAllUrl ?>" class="ld-action-btn" id="ctShowAllBtn" aria-label="<?= $isShowAll ? 'عرض مرقّم' : 'عرض الجميع' ?>">
                <i class="fa fa-<?= $isShowAll ? 'list' : 'th-list' ?>"></i>
                <span class="ld-hide-xs"><?= $isShowAll ? 'عرض مرقّم' : 'عرض الجميع' ?></span>
            </a>
            <?= ExportButtons::widget([
                'excelRoute' => ['export-legal-excel'],
                'pdfRoute' => ['export-legal-pdf'],
                'excelBtnClass' => 'ld-action-btn ld-hide-sm',
                'pdfBtnClass' => 'ld-action-btn ld-hide-sm',
            ]) ?>
            <button class="ld-action-btn ld-action-icon ld-show-sm" id="ctFilterToggle" aria-label="فتح الفلاتر">
                <i class="fa fa-sliders"></i>
            </button>
        </div>
    </div>

    <!-- ===== STATS DASHBOARD ===== -->
    <div class="ld-stats">
        <div class="ld-stat" style="--stat-color: #1565C0; --stat-bg: #E3F2FD; --stat-bg-dark: rgba(21, 101, 192, 0.15)">
            <div class="ld-stat-visual">
                <div class="ld-stat-icon"><i class="fa fa-briefcase"></i></div>
            </div>
            <div class="ld-stat-body">
                <span class="ld-stat-value"><?= number_format($totalContracts) ?></span>
                <span class="ld-stat-label">إجمالي العقود</span>
            </div>
        </div>
        <?php
        $sectorColors = [
            ['color' => '#2E7D32', 'bg' => '#E8F5E9', 'dark' => 'rgba(46,125,50,0.15)', 'icon' => 'fa-institution'],
            ['color' => '#E65100', 'bg' => '#FFF3E0', 'dark' => 'rgba(230,81,0,0.15)', 'icon' => 'fa-building'],
            ['color' => '#7B1FA2', 'bg' => '#F3E5F5', 'dark' => 'rgba(123,31,162,0.15)', 'icon' => 'fa-users'],
            ['color' => '#00838F', 'bg' => '#E0F7FA', 'dark' => 'rgba(0,131,143,0.15)', 'icon' => 'fa-industry'],
        ];
        $idx = 0;
        foreach ($sectorCounts as $sector):
            $sc = $sectorColors[$idx % count($sectorColors)];
            $pct = $totalContracts > 0 ? round(($sector['cnt'] / $totalContracts) * 100) : 0;
        ?>
        <div class="ld-stat" style="--stat-color: <?= $sc['color'] ?>; --stat-bg: <?= $sc['bg'] ?>; --stat-bg-dark: <?= $sc['dark'] ?>">
            <div class="ld-stat-visual">
                <div class="ld-stat-icon"><i class="fa <?= $sc['icon'] ?>"></i></div>
            </div>
            <div class="ld-stat-body">
                <span class="ld-stat-value"><?= number_format($sector['cnt']) ?></span>
                <span class="ld-stat-label"><?= Html::encode($sector['sector_name']) ?></span>
            </div>
            <div class="ld-stat-indicator <?= $pct >= 50 ? 'ld-indicator-warning' : 'ld-indicator-success' ?>"><?= $pct ?>%</div>
        </div>
        <?php $idx++; endforeach ?>
        <?php if ($countNoJob > 0): ?>
        <div class="ld-stat" style="--stat-color: #F57F17; --stat-bg: #FFF8E1; --stat-bg-dark: rgba(245,127,23,0.15)">
            <div class="ld-stat-visual">
                <div class="ld-stat-icon"><i class="fa fa-user-times"></i></div>
            </div>
            <div class="ld-stat-body">
                <span class="ld-stat-value"><?= number_format($countNoJob) ?></span>
                <span class="ld-stat-label">لا يعمل / غير محدد</span>
            </div>
        </div>
        <?php endif ?>
    </div>

    <!-- ===== SECTOR BREAKDOWN BAR ===== -->
    <?php if (!empty($sectorCounts) && $totalContracts > 0): ?>
    <div class="ld-progress-wrap">
        <div class="ld-progress-header">
            <span>توزيع العقود حسب القطاع</span>
            <span class="ld-progress-pct"><?= count($sectorCounts) ?> قطاعات</span>
        </div>
        <div class="ld-sector-bar">
            <?php $cIdx = 0; foreach ($sectorCounts as $sec):
                $pct = round(($sec['cnt'] / $totalContracts) * 100, 1);
                $clr = $sectorColors[$cIdx % count($sectorColors)]['color'];
            ?>
            <div class="ld-sector-segment" style="width:<?= max($pct, 2) ?>%;background:<?= $clr ?>" title="<?= Html::encode($sec['sector_name']) ?>: <?= $sec['cnt'] ?> (<?= $pct ?>%)"></div>
            <?php $cIdx++; endforeach ?>
            <?php if ($countNoJob > 0):
                $noPct = round(($countNoJob / $totalContracts) * 100, 1);
            ?>
            <div class="ld-sector-segment" style="width:<?= max($noPct, 2) ?>%;background:#9E9E9E" title="غير محدد: <?= $countNoJob ?> (<?= $noPct ?>%)"></div>
            <?php endif ?>
        </div>
        <div class="ld-progress-legend">
            <?php $cIdx = 0; foreach ($sectorCounts as $sec):
                $clr = $sectorColors[$cIdx % count($sectorColors)]['color'];
            ?>
            <span class="ld-legend-item"><i class="fa fa-circle" style="color:<?= $clr ?>"></i> <?= Html::encode($sec['sector_name']) ?> (<?= number_format($sec['cnt']) ?>)</span>
            <?php $cIdx++; endforeach ?>
            <?php if ($countNoJob > 0): ?>
            <span class="ld-legend-item"><i class="fa fa-circle" style="color:#9E9E9E"></i> غير محدد (<?= number_format($countNoJob) ?>)</span>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>

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
    <div class="ld-toolbar">
        <div class="ld-toolbar-info">
            <?php if ($dataCount > 0): ?>
                <span class="ld-toolbar-summary">
                    عرض <strong><?= number_format($begin) ?>–<?= number_format($end) ?></strong>
                    من أصل <strong><?= number_format($dataCount) ?></strong> عقد
                </span>
            <?php else: ?>
                <span class="ld-toolbar-summary">لا توجد نتائج</span>
            <?php endif ?>
        </div>
        <div class="ct-quick-search">
            <i class="fa fa-search"></i>
            <input type="text" id="ctQuickSearch" placeholder="بحث سريع في النتائج..."
                   aria-label="بحث سريع في النتائج المعروضة">
        </div>
    </div>

    <!-- ===== DATA TABLE ===== -->
    <div class="ld-table-container">
        <?php if (empty($models)): ?>
            <div class="ld-empty">
                <div class="ld-empty-icon">
                    <i class="fa fa-inbox"></i>
                </div>
                <h3>لا توجد عقود مطابقة</h3>
                <p>لم يتم العثور على عقود تتوافق مع معايير البحث المحددة</p>
                <a href="<?= Url::to(['legal-department']) ?>" class="ld-action-btn ld-action-primary">
                    <i class="fa fa-refresh"></i> عرض جميع العقود
                </a>
            </div>
        <?php else: ?>
            <table class="ct-table ld-table" role="grid">
                <thead>
                    <tr>
                        <th style="width:44px;text-align:center">
                            <label class="ld-checkbox-wrap" title="تحديد الكل">
                                <input type="checkbox" id="ctSelectAll">
                                <span class="ld-checkbox-mark"></span>
                            </label>
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
                    <?php $rowIndex = 0; foreach ($models as $m):
                        $allParties = $m->customersAndGuarantor;
                        $partiesHtml = [];
                        $firstCustomer = $allParties[0] ?? null;
                        foreach ($allParties as $p) {
                            $line = Html::encode(NameHelper::short($p->name));
                            if ($p->id_number) $line .= ' <small class="ld-id-num">(' . Html::encode($p->id_number) . ')</small>';
                            $partiesHtml[] = $line;
                        }
                        $partiesDisplay = implode('<br>', $partiesHtml) ?: '—';
                        $partiesTitle = implode('، ', ArrayHelper::getColumn($allParties, 'name'));

                        $b = ($balanceMap ?? [])[$m->id] ?? null;
                        $total = $b ? (float)$b['total_value'] + (float)$b['total_expenses'] + (float)$b['total_lawyer_cost'] : (float)$m->total_value;
                        $remaining = $b ? (float)$b['remaining_balance'] : 0;

                        $jobId = ($firstCustomer && $firstCustomer->job_title) ? $firstCustomer->job_title : null;
                        $jobName = $jobId ? ($jobsMap[$jobId] ?? '—') : '—';
                        $jobTypeId = $jobId ? ($jobToTypeMap[$jobId] ?? null) : null;
                        $jobTypeName = $jobTypeId ? ($jobTypesMap[$jobTypeId] ?? '—') : '—';

                        $jud = $judiciaryMap[$m->id] ?? null;
                        $hasCase = (bool) $jud;
                    ?>
                    <tr data-id="<?= $m->id ?>" class="ld-row" style="--row-delay: <?= $rowIndex * 0.03 ?>s">
                        <td style="text-align:center;vertical-align:middle" data-label="">
                            <?php if (!$hasCase): ?>
                            <label class="ld-checkbox-wrap">
                                <input type="checkbox" class="ct-batch-check" value="<?= $m->id ?>"
                                       data-remaining="<?= round($remaining, 2) ?>"
                                       data-customer="<?= Html::encode($partiesTitle) ?>">
                                <span class="ld-checkbox-mark"></span>
                            </label>
                            <?php else: ?>
                            <span class="ld-case-check" title="تم إنشاء القضية"><i class="fa fa-check-circle"></i></span>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-id" data-label="#"><a href="<?= Url::to(['/followUp/follow-up/panel', 'contract_id' => $m->id]) ?>" class="ct-id-link"><?= $m->id ?></a></td>
                        <td class="ct-td-customer" data-label="الأطراف" title="<?= Html::encode($partiesTitle) ?>" style="white-space:normal;min-width:180px">
                            <?= $partiesDisplay ?>
                        </td>
                        <td class="ct-td-money" data-label="الإجمالي"><?= number_format($total, 0) ?></td>
                        <td class="ct-td-money ct-td-remain" data-label="المتبقي"><?= number_format($remaining, 0) ?></td>
                        <td data-label="الوظيفة"><?= Html::encode($jobName) ?></td>
                        <td data-label="نوع الوظيفة"><?= Html::encode($jobTypeName) ?></td>
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
                                    <?php if ($hasCase): ?>
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
                    <?php $rowIndex++; endforeach ?>
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

</div><!-- /.ld-page -->

<!-- ===== BOOTSTRAP 5 MODALS (hidden by default) ===== -->
<?php if ($isManager): ?>
<div class="modal fade" id="finishContractModal" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content ld-modal-content">
            <div class="ld-modal-icon ld-modal-icon-success">
                <i class="fa fa-check-circle"></i>
            </div>
            <h4 class="ld-modal-title">تأكيد إنهاء العقد</h4>
            <p class="ld-modal-text">هل أنت متأكد من إنهاء هذا العقد؟<br><small>سيتم تغيير حالة العقد إلى "منتهي"</small></p>
            <div class="ld-modal-actions">
                <a id="finishContractBtn" href="#" class="ld-action-btn ld-action-success">
                    <i class="fa fa-check"></i> نعم، إنهاء
                </a>
                <button type="button" class="ld-action-btn" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> إلغاء
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelContractModal" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content ld-modal-content">
            <div class="ld-modal-icon ld-modal-icon-danger">
                <i class="fa fa-ban"></i>
            </div>
            <h4 class="ld-modal-title">تأكيد إلغاء العقد</h4>
            <p class="ld-modal-text">هل أنت متأكد من إلغاء هذا العقد؟</p>
            <p class="ld-modal-warning"><i class="fa fa-exclamation-triangle"></i> تحذير: لا يمكن التراجع عن هذا الإجراء</p>
            <div class="ld-modal-actions">
                <a id="cancelContractBtn" href="#" class="ld-action-btn ld-action-danger">
                    <i class="fa fa-ban"></i> نعم، إلغاء
                </a>
                <button type="button" class="ld-action-btn" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> تراجع
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--t-primary,#800020)"></i>
                </div>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>

<!-- ===== BATCH ACTION FLOATING BAR ===== -->
<div id="ctBatchBar" class="ld-batch-bar" style="display:none">
    <div class="ld-batch-inner">
        <div class="ld-batch-info">
            <div class="ld-batch-icon"><i class="fa fa-check-square-o"></i></div>
            <span>تم تحديد <strong id="ctBatchCount">0</strong> عقد</span>
        </div>
        <div class="ld-batch-actions">
            <button type="button" id="ctBatchClear" class="ld-action-btn ld-action-ghost">
                <i class="fa fa-times"></i> إلغاء التحديد
            </button>
            <form id="ctBatchForm" method="POST" action="<?= Url::to(['/judiciary/judiciary/batch-create']) ?>" style="display:inline">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                <input type="hidden" name="contract_ids" id="ctBatchIds" value="">
                <button type="submit" class="ld-action-btn ld-action-primary ld-batch-submit">
                    <i class="fa fa-gavel"></i> تجهيز القضايا
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
(function(){
    var $bar = document.getElementById('ctBatchBar'),
        $count = document.getElementById('ctBatchCount'),
        $ids = document.getElementById('ctBatchIds'),
        $checkAll = document.getElementById('ctSelectAll'),
        selected = {};

    function updateBar() {
        var keys = Object.keys(selected), n = keys.length;
        $count.textContent = n;
        $ids.value = keys.join(',');
        $bar.style.display = n > 0 ? '' : 'none';
        if (n > 0) $bar.classList.add('ld-batch-visible');
        else $bar.classList.remove('ld-batch-visible');
    }

    $(document).on('change', '.ct-batch-check', function(){
        var id = this.value;
        if (this.checked) { selected[id] = true; $(this).closest('tr').addClass('ct-row-selected'); }
        else { delete selected[id]; $(this).closest('tr').removeClass('ct-row-selected'); }
        updateBar();
        var total = document.querySelectorAll('.ct-batch-check').length;
        var checked = document.querySelectorAll('.ct-batch-check:checked').length;
        if ($checkAll) { $checkAll.checked = checked === total && total > 0; $checkAll.indeterminate = checked > 0 && checked < total; }
    });

    if ($checkAll) $checkAll.addEventListener('change', function(){
        var isChecked = this.checked;
        document.querySelectorAll('.ct-batch-check').forEach(function(cb){
            cb.checked = isChecked;
            var id = cb.value;
            if (isChecked) { selected[id] = true; cb.closest('tr').classList.add('ct-row-selected'); }
            else { delete selected[id]; cb.closest('tr').classList.remove('ct-row-selected'); }
        });
        updateBar();
    });

    document.getElementById('ctBatchClear').addEventListener('click', function(){
        selected = {};
        document.querySelectorAll('.ct-batch-check').forEach(function(cb){ cb.checked = false; });
        if ($checkAll) { $checkAll.checked = false; $checkAll.indeterminate = false; }
        document.querySelectorAll('tr.ct-row-selected').forEach(function(tr){ tr.classList.remove('ct-row-selected'); });
        updateBar();
    });

    document.getElementById('ctBatchForm').addEventListener('submit', function(e){
        if (Object.keys(selected).length === 0) { e.preventDefault(); alert('الرجاء تحديد عقد واحد على الأقل'); }
    });

    // Row entrance animation via IntersectionObserver
    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (entry.isIntersecting) {
                    entry.target.classList.add('ld-row-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.ld-row').forEach(function(row){ obs.observe(row); });
    } else {
        document.querySelectorAll('.ld-row').forEach(function(row){ row.classList.add('ld-row-visible'); });
    }

    // Stat counter animation
    document.querySelectorAll('.ld-stat-value').forEach(function(el){
        var target = parseInt(el.textContent.replace(/,/g, ''), 10);
        if (isNaN(target) || target === 0) return;
        var duration = 800, start = 0, startTime = null;
        function animate(time) {
            if (!startTime) startTime = time;
            var progress = Math.min((time - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(target * eased).toLocaleString('en-US');
            if (progress < 1) requestAnimationFrame(animate);
            else el.textContent = target.toLocaleString('en-US');
        }
        requestAnimationFrame(animate);
    });

    // Sector bar animation
    document.querySelectorAll('.ld-sector-segment').forEach(function(seg){
        var w = seg.style.width;
        seg.style.width = '0%';
        setTimeout(function(){ seg.style.width = w; }, 400);
    });
})();
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
