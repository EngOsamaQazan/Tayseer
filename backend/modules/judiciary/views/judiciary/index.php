<?php
/**
 * القسم القانوني — شاشة موحدة بتبويبات
 * 4 تبويبات: القضايا | إجراءات الأطراف | كشف المثابرة | المحولين للشكوى
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use johnitvn\ajaxcrud\CrudAsset;

CrudAsset::register($this);
$this->title = 'القسم القانوني';
$this->params['breadcrumbs'][] = $this->title;

$activeTab = Yii::$app->request->get('tab', 'cases');

$pendingReqCount = (int)Yii::$app->db->createCommand(
    "SELECT COUNT(*) FROM " . Yii::$app->db->tablePrefix . "judiciary_customers_actions WHERE request_status = 'pending' AND (is_deleted = 0 OR is_deleted IS NULL)"
)->queryScalar();
?>

<style>
/* ═══ Legal Hub — Tab System ═══ */
.lh-wrap { font-family:'Tajawal','Cairo',sans-serif; direction:rtl; }

.lh-tabs {
    display:flex; gap:0; background:#fff; border-radius:12px 12px 0 0;
    border:1px solid #E2E8F0; border-bottom:none;
    overflow-x:auto; -webkit-overflow-scrolling:touch;
}
.lh-tab {
    display:flex; align-items:center; gap:8px; padding:14px 24px;
    font-size:13px; font-weight:600; color:#64748B;
    cursor:pointer; border:none; background:none;
    border-bottom:3px solid transparent; transition:all .2s;
    white-space:nowrap; position:relative;
}
.lh-tab:hover { color:#1E293B; background:#F8FAFC; }
.lh-tab.active {
    color:#800020; border-bottom-color:#800020; background:#fff;
}
.lh-tab i { font-size:15px; }
.lh-tab .lh-badge {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:22px; height:20px; padding:0 6px;
    border-radius:10px; font-size:10px; font-weight:700;
    background:#F1F5F9; color:#64748B;
}
.lh-tab.active .lh-badge { background:#800020; color:#fff; }

.lh-pending-queue{display:flex;align-items:center;gap:6px;padding:8px 16px;margin-right:auto;
  font-size:12px;font-weight:600;color:#92400E;background:#FEF3C7;border-radius:8px;
  text-decoration:none;transition:all .2s;white-space:nowrap;border:1px solid #FDE68A}
.lh-pending-queue:hover{background:#FDE68A;color:#78350F;text-decoration:none}
.lh-pending-queue i{font-size:14px}
.lh-pending-count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;
  padding:0 6px;border-radius:10px;font-size:10px;font-weight:700;background:#F59E0B;color:#fff}

.lh-content {
    background:#fff; border:1px solid #E2E8F0; border-top:none;
    border-radius:0 0 12px 12px; min-height:400px;
    position:relative;
}
.lh-panel { display:none; padding:16px; }
.lh-panel.active { display:block; }
.lh-panel.loading { display:flex; align-items:center; justify-content:center; min-height:300px; }

.lh-loader {
    display:flex; flex-direction:column; align-items:center; gap:12px; color:#94A3B8;
}
.lh-loader i { font-size:28px; animation:lh-spin 1s linear infinite; }
@keyframes lh-spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }

/* ═══ Timeline Side Panel ═══ */
.ctl-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:10000;opacity:0;transition:opacity .25s;pointer-events:none}
.ctl-overlay.open{opacity:1;pointer-events:auto}
.ctl-panel{position:fixed;top:0;left:0;width:520px;max-width:92vw;height:100vh;background:#fff;z-index:10001;
  transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;box-shadow:-4px 0 30px rgba(0,0,0,.15);direction:rtl}
.ctl-panel.open{transform:translateX(0)}
.ctl-hdr{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;
  border-bottom:2px solid #800020;background:linear-gradient(135deg,#fdf0f3,#fff)}
.ctl-hdr h3{margin:0;font-size:16px;font-weight:700;color:#800020;display:flex;align-items:center;gap:8px}
.ctl-hdr h3 i{font-size:18px}
.ctl-close{background:none;border:none;font-size:22px;color:#64748B;cursor:pointer;padding:4px 8px;border-radius:6px;transition:all .15s}
.ctl-close:hover{background:#F1F5F9;color:#1E293B}
.ctl-case-info{padding:12px 20px;background:#FAFBFC;border-bottom:1px solid #E2E8F0;display:flex;flex-wrap:wrap;gap:12px}
.ctl-info-item{display:flex;align-items:center;gap:4px;font-size:11px;color:#64748B}
.ctl-info-item b{color:#1E293B;font-weight:600}
.ctl-toolbar{padding:10px 20px;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.ctl-add-btn{display:inline-flex;align-items:center;gap:5px;font-size:12px;padding:6px 14px;border-radius:6px;
  background:#800020;color:#fff;border:none;cursor:pointer;font-weight:600;text-decoration:none;transition:all .15s;white-space:nowrap}
.ctl-add-btn:hover{background:#600018;color:#fff;text-decoration:none}
.ctl-filter-chips{display:flex;gap:4px;flex-wrap:wrap}
.ctl-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:10px;font-weight:600;
  cursor:pointer;border:1px solid #E2E8F0;background:#fff;color:#64748B;transition:all .15s;white-space:nowrap}
.ctl-chip:hover{border-color:#CBD5E1;background:#F8FAFC}
.ctl-chip.active{background:#800020;color:#fff;border-color:#800020}
.ctl-body{flex:1;overflow-y:auto;padding:16px 20px}
.ctl-body::-webkit-scrollbar{width:5px}
.ctl-body::-webkit-scrollbar-thumb{background:#CBD5E1;border-radius:3px}
.ctl-empty{text-align:center;padding:60px 20px;color:#94A3B8}
.ctl-empty i{font-size:40px;margin-bottom:12px;display:block}
.ctl-loading{text-align:center;padding:60px 20px;color:#94A3B8}
.ctl-loading i{font-size:28px;animation:lh-spin 1s linear infinite}

/* Timeline items */
.ctl-item{position:relative;padding:12px 16px 12px 12px;margin-bottom:12px;
  border:1px solid #E2E8F0;border-radius:10px;background:#fff;transition:all .15s;
  border-right:4px solid #CBD5E1}
.ctl-item:hover{box-shadow:0 2px 8px rgba(0,0,0,.06);border-color:#CBD5E1}
.ctl-item[data-nature="request"]{border-right-color:#3B82F6}
.ctl-item[data-nature="document"]{border-right-color:#8B5CF6}
.ctl-item[data-nature="doc_status"]{border-right-color:#F59E0B}
.ctl-item[data-nature="process"]{border-right-color:#10B981}
.ctl-item-hdr{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px}
.ctl-item-action{font-size:13px;font-weight:700;color:#1E293B}
.ctl-item-date{font-size:10px;color:#94A3B8;font-family:'Courier New',monospace;white-space:nowrap}
.ctl-item-party{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;
  font-size:10px;font-weight:600;background:#F1F5F9;color:#475569;margin-bottom:4px}
.ctl-item-party i{font-size:9px}
.ctl-item-note{font-size:11px;color:#64748B;line-height:1.6;margin-top:4px}
.ctl-item-meta{display:flex;align-items:center;gap:8px;margin-top:6px;font-size:9px;color:#94A3B8}
.ctl-item-status{display:inline-flex;align-items:center;gap:3px;padding:1px 6px;border-radius:4px;font-size:9px;font-weight:600}
.ctl-item-status.pending{background:#FEF3C7;color:#92400E}
.ctl-item-status.approved{background:#D1FAE5;color:#065F46}
.ctl-item-status.rejected{background:#FEE2E2;color:#991B1B}
.ctl-item-img{margin-top:6px}
.ctl-item-img a{font-size:10px;color:#3B82F6;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
.ctl-item-img a:hover{text-decoration:underline}

/* Approve/Reject actions */
.ctl-req-actions{display:flex;gap:6px;margin-top:8px;align-items:center;flex-wrap:wrap}
.ctl-req-btn{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:6px;font-size:11px;
  font-weight:600;border:1px solid;cursor:pointer;transition:all .15s;white-space:nowrap}
.ctl-req-btn.approve{background:#D1FAE5;color:#065F46;border-color:#A7F3D0}
.ctl-req-btn.approve:hover{background:#065F46;color:#fff}
.ctl-req-btn.reject{background:#FEE2E2;color:#991B1B;border-color:#FECACA}
.ctl-req-btn.reject:hover{background:#991B1B;color:#fff}
.ctl-req-btn:disabled{opacity:.5;cursor:not-allowed}
.ctl-decision-form{display:none;margin-top:8px;padding:8px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px}
.ctl-decision-form.open{display:block}
.ctl-decision-input{width:100%;padding:6px 10px;border:1px solid #E2E8F0;border-radius:6px;font-size:12px;
  resize:vertical;min-height:36px;font-family:inherit;margin-bottom:6px}
.ctl-decision-input:focus{border-color:#800020;outline:none;box-shadow:0 0 0 2px rgba(128,0,32,.1)}
.ctl-decision-actions{display:flex;gap:6px;justify-content:flex-end}
.ctl-decision-submit{padding:4px 14px;border-radius:6px;font-size:11px;font-weight:600;border:none;cursor:pointer;transition:all .15s}
.ctl-decision-submit.confirm-approve{background:#065F46;color:#fff}
.ctl-decision-submit.confirm-reject{background:#991B1B;color:#fff}
.ctl-decision-submit:hover{opacity:.85}
.ctl-decision-cancel{padding:4px 14px;border-radius:6px;font-size:11px;font-weight:600;border:1px solid #E2E8F0;
  background:#fff;color:#64748B;cursor:pointer}

/* date separator */
.ctl-date-sep{text-align:center;margin:16px 0 8px;position:relative}
.ctl-date-sep::before{content:'';position:absolute;top:50%;left:0;right:0;height:1px;background:#E2E8F0}
.ctl-date-sep span{position:relative;background:#fff;padding:0 12px;font-size:10px;font-weight:600;color:#94A3B8}

@media(max-width:600px){
  .ctl-panel{width:100vw;max-width:100vw}
  .ctl-case-info{flex-direction:column;gap:6px}
}

/* Timeline button in table */
.jud-timeline-btn{
  background:#fff;border:1px solid #800020;border-radius:8px;
  height:28px;display:inline-flex;align-items:center;justify-content:center;gap:4px;
  cursor:pointer;color:#800020;font-size:12px;font-weight:600;transition:all .15s;padding:4px 10px;white-space:nowrap}
.jud-timeline-btn:hover{background:#800020;color:#fff;border-color:#800020}

/* ═══ Dropdown & overflow fixes ═══ */
.lh-panel .jud-act-wrap, .lh-panel .jca-act-wrap { position:relative;display:inline-block; }
.lh-panel .jud-act-trigger, .lh-panel .jca-act-trigger {
    background:none;border:1px solid #E2E8F0;border-radius:6px;
    width:30px;height:28px;display:inline-flex;align-items:center;justify-content:center;
    cursor:pointer;color:#64748B;font-size:14px;transition:all .15s;padding:0;
}
.lh-panel .jud-act-trigger:hover, .lh-panel .jca-act-trigger:hover { background:#F1F5F9;color:#1E293B;border-color:#CBD5E1; }
.lh-panel .jud-act-menu, .lh-panel .jca-act-menu {
    display:none;position:fixed;left:auto;top:auto;margin:0;min-width:160px;
    background:#fff;border:1px solid #E2E8F0;border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;
    direction:rtl;font-size:12px;
}
.lh-panel .jud-act-wrap.open .jud-act-menu,
.lh-panel .jca-act-wrap.open .jca-act-menu { display:block; }
.lh-panel .jud-act-menu a, .lh-panel .jca-act-menu a {
    display:flex;align-items:center;gap:8px;padding:7px 14px;
    color:#334155;text-decoration:none;white-space:nowrap;transition:background .12s;
}
.lh-panel .jud-act-menu a:hover, .lh-panel .jca-act-menu a:hover { background:#F1F5F9;color:#1D4ED8; }
.lh-panel .jud-act-menu a i, .lh-panel .jca-act-menu a i { width:16px;text-align:center; }
.lh-panel .jud-act-divider, .lh-panel .jca-act-divider { height:1px;background:#E2E8F0;margin:4px 0; }

/* Grid overflow fix */
.lh-panel .panel-body, .lh-panel .kv-grid-container,
.lh-panel .table-responsive, .lh-panel .kv-grid-table { overflow:visible !important; }

/* Grid table styling */
.lh-panel .kv-grid-table{table-layout:fixed !important;width:100% !important;border:none !important}
.lh-panel .kv-grid-table thead th{background:#FAFBFC !important;font-weight:700 !important;font-size:11px !important;color:#64748B !important;border-bottom:2px solid #E2E8F0 !important;padding:8px 6px !important;white-space:nowrap}
.lh-panel .kv-grid-table thead th a{color:#64748B !important;text-decoration:none !important}
.lh-panel .kv-grid-table thead th a:hover{color:#334155 !important}
.lh-panel .kv-grid-table tbody td{font-size:12px;vertical-align:middle;padding:7px 6px !important;border-bottom:1px solid #F1F5F9 !important;border-top:none !important}
.lh-panel .kv-grid-table tbody tr{transition:background .15s}
.lh-panel .kv-grid-table tbody tr:hover{background:#FAFBFC}
.lh-panel .kv-grid-table .filters td{padding:4px 3px !important}
.lh-panel .kv-grid-table .filters input,.lh-panel .kv-grid-table .filters select{border-radius:6px !important;border:1px solid #E2E8F0 !important;font-size:11px !important;padding:3px 6px !important}
.lh-panel .table-bordered{border:none !important}
.lh-panel .table-bordered>thead>tr>th,.lh-panel .table-bordered>tbody>tr>td{border-right:none !important;border-left:none !important}
.lh-panel .panel{margin:0 !important;border:1px solid #E2E8F0 !important;border-radius:10px !important;box-shadow:none !important}
.lh-panel .panel-heading{background:#FAFBFC !important;border-bottom:1px solid #E2E8F0 !important;padding:8px 12px !important;border-radius:10px 10px 0 0 !important;font-size:13px;font-weight:700;color:#1E293B}
.lh-panel .panel-heading .badge{background:#800020;color:#fff;font-size:10px;padding:2px 8px;border-radius:10px}
.lh-panel .panel-body{border:none !important;padding:0 !important}
.lh-panel .panel-footer{background:#FAFBFC !important;border-top:1px solid #E2E8F0 !important;padding:6px 10px !important;border-radius:0 0 10px 10px !important}
.lh-panel .panel-heading .pull-right .btn{border-radius:6px !important;border:1px solid #E2E8F0 !important;background:#fff !important;color:#64748B !important;padding:4px 8px !important;font-size:12px;transition:all .15s}
.lh-panel .panel-heading .pull-right .btn:hover{background:#F1F5F9 !important;color:#1E293B !important}
.lh-panel .pagination>li>a,.lh-panel .pagination>li>span{border-radius:6px !important;margin:0 1px;border:1px solid #E2E8F0;color:#64748B;font-size:11px;padding:4px 8px}
.lh-panel .pagination>.active>a,.lh-panel .pagination>.active>span{background:#800020 !important;border-color:#800020 !important;color:#fff !important}
.lh-panel .pagination>li>a:hover{background:#F1F5F9;border-color:#CBD5E1}
/* Column resizer handle */
.lh-panel .kv-grid-table th{position:relative}
.lh-panel .kv-grid-table th .col-resize-handle{position:absolute;left:0;top:0;bottom:0;width:4px;cursor:col-resize;background:transparent;z-index:5}
.lh-panel .kv-grid-table th .col-resize-handle:hover,.lh-panel .kv-grid-table th .col-resize-handle.active{background:rgba(128,0,32,.3)}
@media (max-width:992px) {
    .lh-panel .kv-grid-table{font-size:11px}
    .lh-panel .kv-grid-table td,.lh-panel .kv-grid-table th{padding:5px 4px !important}
}

@media (max-width:768px) {
    .lh-tab { padding:10px 14px; font-size:12px; }
    .lh-tab span.lh-tab-label { display:none; }
}
</style>

<div class="lh-wrap">
    <!-- ═══ Tabs ═══ -->
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
        <?php if ($pendingReqCount > 0): ?>
        <a href="<?= Url::to(['index', 'tab' => 'cases', 'pending_requests' => 1]) ?>" class="lh-pending-queue">
            <i class="fa fa-clock-o"></i>
            <span>طلبات معلّقة</span>
            <span class="lh-pending-count"><?= $pendingReqCount ?></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- ═══ Tab Content ═══ -->
    <div class="lh-content">
        <!-- Tab: Cases (loaded inline since it's the default) -->
        <div class="lh-panel <?= $activeTab === 'cases' ? 'active' : '' ?>" id="lh-panel-cases" data-loaded="<?= $activeTab === 'cases' ? '1' : '0' ?>">
            <?php if ($activeTab === 'cases'): ?>
                <?= $this->render('_tab_cases', ['searchModel' => $searchModel, 'dataProvider' => $dataProvider, 'counter' => $counter]) ?>
            <?php endif; ?>
        </div>

        <!-- Tab: Party Actions (lazy) -->
        <div class="lh-panel <?= $activeTab === 'actions' ? 'active' : '' ?>" id="lh-panel-actions" data-loaded="0"></div>

        <!-- Tab: Persistence Report (lazy) -->
        <div class="lh-panel <?= $activeTab === 'persistence' ? 'active' : '' ?>" id="lh-panel-persistence" data-loaded="0"></div>

        <!-- Tab: Legal Department (lazy) -->
        <div class="lh-panel <?= $activeTab === 'legal' ? 'active' : '' ?>" id="lh-panel-legal" data-loaded="0"></div>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end() ?>

<!-- ═══ Case Timeline Side Panel ═══ -->
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

<?php
$casesUrl       = Url::to(['tab-cases']);
$actionsUrl     = Url::to(['tab-actions']);
$persistenceUrl = Url::to(['tab-persistence']);
$legalUrl       = Url::to(['tab-legal']);
$updateReqStatusUrl = Url::to(['update-request-status']);

$js = <<<JS
(function(){
    var urls = {
        cases:       '{$casesUrl}',
        actions:     '{$actionsUrl}',
        persistence: '{$persistenceUrl}',
        legal:       '{$legalUrl}',
        updateReqStatus: '{$updateReqStatusUrl}'
    };

    function loadTab(tab) {
        var \$panel = $('#lh-panel-' + tab);
        if (\$panel.data('loaded') == '1') return;

        \$panel.html('<div class="lh-loader"><i class="fa fa-spinner"></i><span>جاري التحميل...</span></div>').addClass('loading');

        $.get(urls[tab], function(html) {
            \$panel.html(html).removeClass('loading').data('loaded', '1');
            if (window._lhInitColResize) setTimeout(window._lhInitColResize, 300);
        }).fail(function() {
            \$panel.html('<div style="padding:40px;text-align:center;color:#EF4444"><i class="fa fa-exclamation-triangle"></i> حدث خطأ في التحميل</div>').removeClass('loading');
        });
    }

    // Tab click
    $(document).on('click', '.lh-tab', function() {
        var tab = $(this).data('tab');
        $('.lh-tab').removeClass('active');
        $(this).addClass('active');
        $('.lh-panel').removeClass('active');
        $('#lh-panel-' + tab).addClass('active');
        loadTab(tab);
    });

    // Custom dropdown (for both jud-act and jca-act)
    $(document).on('click', '.jud-act-trigger, .jca-act-trigger', function(e) {
        e.stopPropagation();
        var \$wrap = $(this).closest('.jud-act-wrap, .jca-act-wrap');
        var \$menu = \$wrap.find('.jud-act-menu, .jca-act-menu');
        var wasOpen = \$wrap.hasClass('open');
        $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
        if (!wasOpen) {
            \$wrap.addClass('open');
            var r = this.getBoundingClientRect();
            \$menu.css({ left: r.left + 'px', top: (r.bottom + 4) + 'px' });
        }
    });
    $(document).on('click', function() {
        $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
    });
    $(document).on('click', '.jud-act-menu a, .jca-act-menu a', function() {
        $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
    });

    // Load active tab if not cases (default)
    var activeTab = '{$activeTab}';
    if (activeTab !== 'cases') {
        loadTab(activeTab);
    }

    // ═══ Case Timeline Panel ═══
    var ctlData = null;
    var ctlFilter = 'all';

    function ctlOpen(url, label) {
        $('#ctlTitle').text('متابعة القضية ' + label);
        $('#ctlBody').html('<div class="ctl-loading"><i class="fa fa-spinner"></i><div>جاري التحميل...</div></div>');
        $('#ctlCaseInfo').html('');
        $('#ctlFilterChips').html('<span class="ctl-chip active" data-filter="all">الكل</span>');
        $('#ctlOverlay').addClass('open');
        setTimeout(function(){ $('#ctlPanel').addClass('open'); }, 30);
        ctlFilter = 'all';

        $.getJSON(url, function(res) {
            if (!res.success) {
                $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-exclamation-circle"></i><div>' + (res.message || 'خطأ') + '</div></div>');
                return;
            }
            ctlData = res;
            $('#ctlAddAction').attr('href', res.addActionUrl);
            ctlRenderInfo(res);
            ctlRenderChips(res.parties);
            ctlRenderTimeline(res.timeline);
        }).fail(function(){
            $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-exclamation-triangle"></i><div>حدث خطأ في الاتصال</div></div>');
        });
    }

    function ctlClose() {
        $('#ctlPanel').removeClass('open');
        setTimeout(function(){ $('#ctlOverlay').removeClass('open'); }, 300);
        ctlData = null;
    }

    function ctlRenderInfo(res) {
        var c = res['case'];
        var h = '';
        h += '<div class="ctl-info-item"><i class="fa fa-hashtag"></i> القضية: <b>' + (c.judiciary_number || '—') + '/' + (c.year || '') + '</b></div>';
        h += '<div class="ctl-info-item"><i class="fa fa-file-text-o"></i> العقد: <b>' + c.contract_id + '</b></div>';
        if (c.court) h += '<div class="ctl-info-item"><i class="fa fa-institution"></i> <b>' + esc(c.court) + '</b></div>';
        if (c.lawyer) h += '<div class="ctl-info-item"><i class="fa fa-user-secret"></i> <b>' + esc(c.lawyer) + '</b></div>';
        if (c.type) h += '<div class="ctl-info-item"><i class="fa fa-tag"></i> <b>' + esc(c.type) + '</b></div>';
        $('#ctlCaseInfo').html(h);
    }

    function ctlRenderChips(parties) {
        var h = '<span class="ctl-chip active" data-filter="all">الكل</span>';
        for (var i = 0; i < parties.length; i++) {
            var p = parties[i];
            var icon = p.type === 'guarantor' ? 'fa-shield' : 'fa-user';
            h += '<span class="ctl-chip" data-filter="' + p.id + '"><i class="fa ' + icon + '"></i> ' + esc(p.name.split(' ').slice(0,2).join(' ')) + '</span>';
        }
        $('#ctlFilterChips').html(h);
    }

    function ctlRenderTimeline(items) {
        if (!items || !items.length) {
            $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-inbox"></i><div>لا توجد إجراءات مسجلة</div></div>');
            return;
        }
        var filtered = items;
        if (ctlFilter !== 'all') {
            var fid = parseInt(ctlFilter);
            filtered = items.filter(function(i) { return i.customer_id === fid; });
        }
        if (!filtered.length) {
            $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-filter"></i><div>لا توجد إجراءات لهذا الطرف</div></div>');
            return;
        }

        var h = '';
        var lastDate = '';
        for (var i = 0; i < filtered.length; i++) {
            var a = filtered[i];
            var d = a.action_date || '';
            if (d && d !== lastDate) {
                h += '<div class="ctl-date-sep"><span>' + esc(d) + '</span></div>';
                lastDate = d;
            }
            var statusHtml = '';
            if (a.request_status) {
                var sMap = {pending:'قيد الانتظار', approved:'مقبول', rejected:'مرفوض'};
                statusHtml = '<span class="ctl-item-status ' + a.request_status + '">' + (sMap[a.request_status] || a.request_status) + '</span>';
            }
            h += '<div class="ctl-item" data-nature="' + (a.action_nature || 'process') + '" data-id="' + (a.id || '') + '">';
            h += '<div class="ctl-item-hdr">';
            h += '<span class="ctl-item-action">' + esc(a.action_name || '—') + '</span>';
            h += '<span class="ctl-item-date">' + esc(d) + '</span>';
            h += '</div>';
            h += '<div class="ctl-item-party"><i class="fa fa-user"></i> ' + esc(a.customer_name || '') + '</div>';
            if (statusHtml) h += '<div>' + statusHtml + '</div>';
            if (a.decision_text) h += '<div class="ctl-item-note" style="color:#1E293B;font-weight:600"><i class="fa fa-gavel" style="color:#F59E0B;margin-left:4px"></i> ' + esc(a.decision_text) + '</div>';
            if (a.note) h += '<div class="ctl-item-note">' + esc(a.note) + '</div>';
            if (a.image) h += '<div class="ctl-item-img"><a href="' + a.image + '" target="_blank"><i class="fa fa-paperclip"></i> مرفق</a></div>';
            h += '<div class="ctl-item-meta">';
            if (a.created_by) h += '<span><i class="fa fa-user-circle"></i> ' + esc(a.created_by) + '</span>';
            if (a.created_at) h += '<span>' + esc(a.created_at) + '</span>';
            h += '</div>';
            if (a.request_status === 'pending' && a.id) {
                h += '<div class="ctl-req-actions">';
                h += '<button type="button" class="ctl-req-btn approve" data-action-id="' + a.id + '" data-status="approved"><i class="fa fa-check"></i> موافقة</button>';
                h += '<button type="button" class="ctl-req-btn reject" data-action-id="' + a.id + '" data-status="rejected"><i class="fa fa-times"></i> رفض</button>';
                h += '</div>';
                h += '<div class="ctl-decision-form" id="ctl-df-' + a.id + '">';
                h += '<textarea class="ctl-decision-input" placeholder="نص القرار أو سبب الرفض (اختياري)..." rows="2"></textarea>';
                h += '<div class="ctl-decision-actions">';
                h += '<button type="button" class="ctl-decision-cancel">إلغاء</button>';
                h += '<button type="button" class="ctl-decision-submit" data-action-id="' + a.id + '">تأكيد</button>';
                h += '</div></div>';
            }
            h += '</div>';
        }
        $('#ctlBody').html(h);
    }

    function esc(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    $(document).on('click', '.jud-timeline-btn', function(e) {
        e.stopPropagation();
        e.preventDefault();
        ctlOpen($(this).data('url'), $(this).data('label'));
    });

    $('#ctlClose, #ctlOverlay').on('click', function() { ctlClose(); });

    $(document).on('click', '.ctl-chip', function() {
        $('.ctl-chip').removeClass('active');
        $(this).addClass('active');
        ctlFilter = $(this).data('filter');
        if (ctlData) ctlRenderTimeline(ctlData.timeline);
    });

    /* Approve / Reject workflow */
    var pendingDecision = {};
    $(document).on('click', '.ctl-req-btn', function() {
        var id = $(this).data('action-id');
        var st = $(this).data('status');
        pendingDecision = {id: id, status: st};
        var \$form = $('#ctl-df-' + id);
        \$form.addClass('open');
        \$form.find('.ctl-decision-submit')
            .removeClass('confirm-approve confirm-reject')
            .addClass(st === 'approved' ? 'confirm-approve' : 'confirm-reject')
            .text(st === 'approved' ? 'تأكيد الموافقة' : 'تأكيد الرفض');
        \$form.find('.ctl-decision-input').focus();
    });

    $(document).on('click', '.ctl-decision-cancel', function() {
        $(this).closest('.ctl-decision-form').removeClass('open');
        pendingDecision = {};
    });

    $(document).on('click', '.ctl-decision-submit', function() {
        if (!pendingDecision.id) return;
        var \$btn = $(this);
        var \$form = \$btn.closest('.ctl-decision-form');
        var decisionText = \$form.find('.ctl-decision-input').val().trim();
        \$btn.prop('disabled', true).text('جاري الحفظ...');
        $.post(urls.updateReqStatus, {
            id: pendingDecision.id,
            status: pendingDecision.status,
            decision_text: decisionText,
            _csrf: yii.getCsrfToken()
        }).done(function(res) {
            if (res.success && ctlData && ctlData.timeline) {
                for (var j = 0; j < ctlData.timeline.length; j++) {
                    if (ctlData.timeline[j].id == pendingDecision.id) {
                        ctlData.timeline[j].request_status = res.new_status;
                        if (decisionText) ctlData.timeline[j].decision_text = decisionText;
                        break;
                    }
                }
                ctlRenderTimeline(ctlData.timeline);
            }
        }).fail(function() {
            alert('حدث خطأ أثناء الحفظ');
            \$btn.prop('disabled', false).text('تأكيد');
        }).always(function() { pendingDecision = {}; });
    });

})();
JS;
$this->registerJs($js);
?>

<script>
// Column Resizer — inline script to avoid PHP HEREDOC variable conflicts
(function() {
    var SK = 'lh_col_widths_';

    function initResize() {
        var headers = document.querySelectorAll('.lh-panel .kv-grid-table thead th');
        if (!headers.length) return;

        headers.forEach(function(th) {
            if (th.querySelector('.col-resize-handle')) return;
            var handle = document.createElement('div');
            handle.className = 'col-resize-handle';
            th.appendChild(handle);
        });

        restoreWidths();
    }

    function restoreWidths() {
        document.querySelectorAll('.lh-panel .kv-grid-table').forEach(function(table) {
            var tid = table.id || 'default';
            try {
                var saved = localStorage.getItem(SK + tid);
                if (!saved) return;
                var widths = JSON.parse(saved);
                var ths = table.querySelectorAll('thead th');
                Object.keys(widths).forEach(function(i) {
                    var th = ths[parseInt(i)];
                    if (th) { th.style.width = widths[i]; th.style.minWidth = widths[i]; }
                });
            } catch(e) {}
        });
    }

    // Drag logic using event delegation
    var dragging = false, startX = 0, startW = 0, activeEl = null, activeTid = '';

    document.addEventListener('mousedown', function(e) {
        if (!e.target.classList.contains('col-resize-handle')) return;
        e.preventDefault();
        e.stopPropagation();
        dragging = true;
        activeEl = e.target.parentElement;
        var table = activeEl.closest('table');
        activeTid = table ? (table.id || 'default') : 'default';
        startX = e.pageX;
        startW = activeEl.offsetWidth;
        e.target.classList.add('active');
    }, true);

    document.addEventListener('mousemove', function(e) {
        if (!dragging || !activeEl) return;
        var newW = Math.max(40, startW + (startX - e.pageX));
        activeEl.style.width = newW + 'px';
        activeEl.style.minWidth = newW + 'px';
    });

    document.addEventListener('mouseup', function() {
        if (!dragging) return;
        dragging = false;
        document.querySelectorAll('.col-resize-handle.active').forEach(function(h) { h.classList.remove('active'); });
        if (activeEl) {
            try {
                var table = activeEl.closest('table');
                if (table) {
                    var widths = {};
                    table.querySelectorAll('thead th').forEach(function(th, i) { widths[i] = th.style.width || (th.offsetWidth + 'px'); });
                    localStorage.setItem(SK + activeTid, JSON.stringify(widths));
                }
            } catch(e) {}
        }
        activeEl = null;
    });

    // Expose globally for tab loading
    window._lhInitColResize = initResize;

    // Init after DOM is ready and table is rendered
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(initResize, 300); });
    } else {
        setTimeout(initResize, 300);
    }
})();
</script>
