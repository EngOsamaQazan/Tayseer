<?php
/**
 * القسم القانوني — شاشة موحدة بتبويبات
 * 5 تبويبات: القضايا | إجراءات الأطراف | كشف المثابرة | المحولين للشكوى | قسم الحسم
 *
 * V2: All CSS in judiciary-v2.css, all JS in judiciary-v2.js
 *     NO Bootstrap 3, NO CrudAsset, NO inline <style>/<script>
 */
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'القسم القانوني';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCss('.content-header,.page-header{display:none!important}');

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/judiciary-v2.css?v=' . time());
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/pin-system.css?v=' . time());

$this->registerJsFile(Yii::$app->request->baseUrl . '/js/judiciary-v2.js?v=' . time(), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/pin-system.js?v=' . time(), [
    'depends' => [\yii\web\JqueryAsset::class],
]);

$activeTab = Yii::$app->request->get('tab', 'cases');

$pendingReqCount = (int)Yii::$app->db->createCommand(
    "SELECT COUNT(*) FROM " . Yii::$app->db->tablePrefix . "judiciary_customers_actions WHERE request_status = 'pending' AND (is_deleted = 0 OR is_deleted IS NULL)"
)->queryScalar();

$lhConfig = json_encode([
    'activeTab'       => $activeTab,
    'cases'           => Url::to(['tab-cases']),
    'actions'         => Url::to(['tab-actions']),
    'persistence'     => Url::to(['tab-persistence']),
    'legal'           => Url::to(['tab-legal']),
    'collection'      => Url::to(['tab-collection']),
    'tabCounts'       => Url::to(['tab-counts']),
    'updateReqStatus' => Url::to(['update-request-status']),
]);
$this->registerJs("window.LH_CONFIG = {$lhConfig};", \yii\web\View::POS_HEAD);
?>

<div class="lh-wrap">
    <!-- Pin Bar -->
    <div class="pin-bar" id="pin-bar"></div>

    <!-- Stats Cards -->
    <div class="lh-stats" id="lh-stats">
        <div class="lh-stat">
            <div class="lh-stat-icon" style="background:#FDF2F4;color:#800020"><i class="fa fa-balance-scale"></i></div>
            <div>
                <div class="lh-stat-val" id="lh-stat-cases" style="color:#800020">—</div>
                <div class="lh-stat-lbl">إجمالي القضايا</div>
            </div>
        </div>
        <div class="lh-stat">
            <div class="lh-stat-icon" style="background:#FEF3C7;color:#D97706"><i class="fa fa-exclamation-triangle"></i></div>
            <div>
                <div class="lh-stat-val" id="lh-stat-red" style="color:#DC2626">—</div>
                <div class="lh-stat-lbl">متأخرات (أحمر)</div>
            </div>
        </div>
        <div class="lh-stat">
            <div class="lh-stat-icon" style="background:#ECFDF5;color:#059669"><i class="fa fa-handshake-o"></i></div>
            <div>
                <div class="lh-stat-val" id="lh-stat-collection" style="color:#059669">—</div>
                <div class="lh-stat-lbl">قضايا الحسم</div>
            </div>
        </div>
        <div class="lh-stat">
            <div class="lh-stat-icon" style="background:#EFF6FF;color:#2563EB"><i class="fa fa-money"></i></div>
            <div>
                <div class="lh-stat-val" id="lh-stat-amount" style="color:#2563EB">—</div>
                <div class="lh-stat-lbl">المتاح للقبض</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="lh-tabs">
        <button class="lh-tab <?= $activeTab === 'cases' ? 'active' : '' ?>" data-tab="cases">
            <i class="fa fa-balance-scale"></i>
            <span class="lh-tab-label">القضايا</span>
            <span class="lh-badge" id="lh-badge-cases"><?= $counter ?></span>
        </button>
        <button class="lh-tab <?= $activeTab === 'actions' ? 'active' : '' ?>" data-tab="actions">
            <i class="fa fa-legal"></i>
            <span class="lh-tab-label">إجراءات الأطراف</span>
            <span class="lh-badge" id="lh-badge-actions">—</span>
        </button>
        <button class="lh-tab <?= $activeTab === 'persistence' ? 'active' : '' ?>" data-tab="persistence">
            <i class="fa fa-line-chart"></i>
            <span class="lh-tab-label">كشف المثابرة</span>
            <span class="lh-badge" id="lh-badge-persistence">—</span>
        </button>
        <button class="lh-tab <?= $activeTab === 'legal' ? 'active' : '' ?>" data-tab="legal">
            <i class="fa fa-exchange"></i>
            <span class="lh-tab-label">المحولين للشكوى</span>
            <span class="lh-badge" id="lh-badge-legal">—</span>
        </button>
        <button class="lh-tab <?= $activeTab === 'collection' ? 'active' : '' ?>" data-tab="collection">
            <i class="fa fa-handshake-o"></i>
            <span class="lh-tab-label">قسم الحسم</span>
            <span class="lh-badge" id="lh-badge-collection">—</span>
        </button>
        <?php if ($pendingReqCount > 0): ?>
        <a href="<?= Url::to(['index', 'tab' => 'cases', 'pending_requests' => 1]) ?>" class="lh-pending-queue">
            <i class="fa fa-clock-o"></i>
            <span>طلبات معلّقة</span>
            <span class="lh-pending-count"><?= $pendingReqCount ?></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Tab Content -->
    <div class="lh-content">
        <div class="lh-panel <?= $activeTab === 'cases' ? 'active' : '' ?>" id="lh-panel-cases" data-loaded="<?= $activeTab === 'cases' ? '1' : '0' ?>">
            <?php if ($activeTab === 'cases'): ?>
                <?= $this->render('_tab_cases', ['searchModel' => $searchModel, 'dataProvider' => $dataProvider, 'counter' => $counter]) ?>
            <?php endif; ?>
        </div>
        <div class="lh-panel <?= $activeTab === 'actions' ? 'active' : '' ?>" id="lh-panel-actions" data-loaded="0"></div>
        <div class="lh-panel <?= $activeTab === 'persistence' ? 'active' : '' ?>" id="lh-panel-persistence" data-loaded="0"></div>
        <div class="lh-panel <?= $activeTab === 'legal' ? 'active' : '' ?>" id="lh-panel-legal" data-loaded="0"></div>
        <div class="lh-panel <?= $activeTab === 'collection' ? 'active' : '' ?>" id="lh-panel-collection" data-loaded="0"></div>
    </div>
</div>

<!-- Modal (Bootstrap 5) -->
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

<!-- Case Timeline Side Panel -->
<div class="ctl-overlay" id="ctlOverlay"></div>
<div class="ctl-panel" id="ctlPanel">
    <div class="ctl-hdr">
        <h3><i class="fa fa-history"></i> <span id="ctlTitle">متابعة القضية</span></h3>
        <button class="ctl-close" id="ctlClose">&times;</button>
    </div>
    <div class="ctl-case-info" id="ctlCaseInfo"></div>
    <div class="ctl-toolbar">
        <a href="#" class="ctl-add-btn" id="ctlAddAction" role="modal-remote">
            <i class="fa fa-plus"></i> إضافة إجراء
        </a>
        <div class="ctl-filter-chips" id="ctlFilterChips">
            <span class="ctl-chip active" data-filter="all">الكل</span>
        </div>
    </div>
    <div class="ctl-body" id="ctlBody">
        <div class="ctl-loading"><i class="fa fa-spinner"></i><div>جاري التحميل...</div></div>
    </div>
</div>
