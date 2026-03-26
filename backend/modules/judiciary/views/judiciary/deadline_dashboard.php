<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\models\JudiciaryDeadline;

$this->title = 'لوحة المواعيد النهائية';
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/judiciary-v2.css?v=' . time());

$typeLabels  = JudiciaryDeadline::getTypeLabels();
$ajaxUrl     = Url::to(['deadline-dashboard-ajax']);
$viewBaseUrl = Url::to(['view', 'id' => '__ID__']);

$typeLabelsJson = json_encode($typeLabels, JSON_UNESCAPED_UNICODE);
?>

<div class="jv-page" id="dl-dashboard">
    <div class="jv-header">
        <div>
            <div class="jv-title"><i class="fa fa-clock-o" style="color:#DC2626"></i> <?= $this->title ?></div>
        </div>
        <div class="jv-actions" style="display:flex;gap:8px">
            <button onclick="DL.load(DL.tab,1)" class="btn btn-default" style="border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px"><i class="fa fa-refresh"></i> تحديث</button>
            <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px']) ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px" id="dl-summary"></div>

    <!-- Content Area -->
    <div class="jv-card" style="margin-bottom:20px">
        <div class="jv-card-title" style="display:flex;justify-content:space-between;align-items:center" id="dl-section-title"></div>
        <div id="dl-grid"></div>
        <div id="dl-pagination" style="display:flex;justify-content:center;align-items:center;gap:6px;padding:16px 0;margin-top:12px;border-top:1px solid #E2E8F0"></div>
    </div>
</div>

<?php
$initCounts = json_encode($counts, JSON_UNESCAPED_UNICODE);
$initItems  = json_encode($items, JSON_UNESCAPED_UNICODE);
$initTab    = $activeTab;
$initPage   = $page;
$initTotal  = $totalPages;
$perPageJs  = $perPage;

$js = <<<JS
(function(){
var TABS = {
    expired:     {label:'متأخرة', icon:'fa-exclamation-circle', color:'#DC2626', bg:'#FEF2F2', border:'#FECACA'},
    approaching: {label:'تقترب',  icon:'fa-warning',           color:'#D97706', bg:'#FFFBEB', border:'#FDE68A'},
    pending:     {label:'قائمة',  icon:'fa-hourglass-half',    color:'#64748B', bg:'#F8FAFC', border:'#E2E8F0'}
};
var TYPE_LABELS = {$typeLabelsJson};
var AJAX_URL = '{$ajaxUrl}';
var VIEW_BASE = '{$viewBaseUrl}';

window.DL = {
    tab: '{$initTab}',
    page: {$initPage},
    counts: {$initCounts},
    items: {$initItems},
    totalPages: {$initTotal},
    perPage: {$perPageJs},
    loading: false,

    init: function() {
        this.renderSummary();
        this.renderContent();
    },

    load: function(tab, page) {
        if (this.loading) return;
        this.loading = true;
        this.tab = tab;
        this.page = page || 1;
        var grid = document.getElementById('dl-grid');
        grid.style.opacity = '0.5';

        var self = this;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', AJAX_URL + '?tab=' + tab + '&page=' + self.page);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            self.loading = false;
            grid.style.opacity = '1';
            if (xhr.status === 200) {
                var d = JSON.parse(xhr.responseText);
                self.counts = d.counts;
                self.items = d.items;
                self.totalPages = d.totalPages;
                self.page = d.page;
                self.renderSummary();
                self.renderContent();
                history.replaceState(null, '', location.pathname + '?tab=' + self.tab + '&page=' + self.page);
            }
        };
        xhr.onerror = function() { self.loading = false; grid.style.opacity = '1'; };
        xhr.send();
    },

    renderSummary: function() {
        var h = '';
        var keys = ['expired','approaching','pending'];
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i], t = TABS[k], c = this.counts[k] || 0, active = k === this.tab;
            h += '<a href="javascript:void(0)" onclick="DL.load(\'' + k + '\',1)" style="text-decoration:none;background:' + t.bg + ';border:1px solid ' + t.border + ';border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:12px;transition:all .2s;cursor:pointer' + (active ? ';box-shadow:0 4px 12px rgba(0,0,0,.12);transform:translateY(-1px)' : '') + '">'
                + '<div style="width:42px;height:42px;border-radius:10px;background:' + t.color + ';color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fa ' + t.icon + '"></i></div>'
                + '<div><div style="font-size:22px;font-weight:700;color:' + t.color + '">' + c.toLocaleString() + '</div>'
                + '<div style="font-size:12px;color:' + t.color + ';font-weight:' + (active ? '700' : '400') + '">' + t.label + '</div></div></a>';
        }
        document.getElementById('dl-summary').innerHTML = h;
    },

    renderContent: function() {
        var t = TABS[this.tab], total = this.counts[this.tab] || 0;
        var start = (this.page - 1) * this.perPage + 1;
        var end = Math.min(this.page * this.perPage, total);

        document.getElementById('dl-section-title').innerHTML =
            '<div><i class="fa ' + t.icon + '" style="color:' + t.color + '"></i> ' + t.label
            + ' <span style="background:' + t.bg + ';color:' + t.color + ';padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;margin-right:8px">' + total.toLocaleString() + '</span></div>'
            + (total > 0 ? '<div style="font-size:12px;color:#94A3B8">' + start + ' – ' + end + ' من ' + total.toLocaleString() + '</div>' : '');

        if (!this.items || this.items.length === 0) {
            document.getElementById('dl-grid').innerHTML = '<div style="text-align:center;padding:30px;color:#94A3B8"><i class="fa fa-check-circle" style="font-size:24px;display:block;margin-bottom:8px;color:#D1FAE5"></i>لا توجد مواعيد ' + t.label + '</div>';
            document.getElementById('dl-pagination').innerHTML = '';
            return;
        }

        var h = '<div class="jv-deadline-grid">';
        for (var i = 0; i < this.items.length; i++) {
            var dl = this.items[i];
            var typeLabel = TYPE_LABELS[dl.deadline_type] || dl.deadline_type;
            var caseNum = dl.judiciary_number || ('#' + dl.judiciary_id);
            var viewUrl = VIEW_BASE.replace('__ID__', dl.judiciary_id);
            var daysRem = dl.deadline_date ? Math.floor((new Date(dl.deadline_date) - new Date()) / 86400000) : null;

            h += '<div class="jv-deadline-card" style="background:' + t.bg + ';border:1px solid ' + t.border + '">';
            h += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">';
            h += '<div style="display:flex;align-items:center;gap:6px"><i class="fa ' + t.icon + '" style="color:' + t.color + ';font-size:14px"></i><span style="font-weight:700;font-size:12px;color:' + t.color + '">' + this.esc(typeLabel) + '</span></div>';
            h += '<a href="' + viewUrl + '" style="font-size:11px;font-weight:600;color:#2563EB;text-decoration:none">قضية ' + this.esc(caseNum) + '</a>';
            h += '</div>';

            h += '<div style="display:flex;justify-content:space-between;align-items:center;font-size:11px">';
            h += '<span style="color:#64748B"><i class="fa fa-calendar"></i> ' + (dl.deadline_date || '—') + '</span>';
            if (daysRem !== null) {
                h += '<span style="font-weight:700;color:' + t.color + '">';
                if (daysRem < 0) h += 'متأخر ' + Math.abs(daysRem).toLocaleString() + ' يوم';
                else if (daysRem === 0) h += 'اليوم!';
                else h += 'باقي ' + daysRem.toLocaleString() + ' يوم';
                h += '</span>';
            }
            h += '</div>';

            if (dl.action_name) {
                h += '<div style="font-size:11px;color:#475569;margin-top:6px;display:flex;align-items:center;gap:4px">';
                h += '<i class="fa fa-file-text-o" style="font-size:10px"></i><span style="font-weight:600">' + this.esc(dl.action_name) + '</span>';
                if (dl.customer_name) h += '<span style="color:#94A3B8">—</span><span>' + this.esc(dl.customer_name) + '</span>';
                h += '</div>';
            } else if (dl.label) {
                h += '<div style="font-size:11px;color:#475569;margin-top:6px">' + this.esc(dl.label) + '</div>';
            }
            h += '</div>';
        }
        h += '</div>';
        document.getElementById('dl-grid').innerHTML = h;

        this.renderPagination();
    },

    renderPagination: function() {
        var el = document.getElementById('dl-pagination');
        if (this.totalPages <= 1) { el.innerHTML = ''; return; }

        var h = '', p = this.page, tp = this.totalPages, range = 2;
        var s = Math.max(1, p - range), e = Math.min(tp, p + range);
        var btnStyle = 'width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;text-decoration:none;cursor:pointer;transition:all .15s;';

        if (p > 1) h += '<a href="javascript:void(0)" onclick="DL.load(DL.tab,' + (p-1) + ')" style="' + btnStyle + 'border:1px solid #E2E8F0;color:#475569" title="السابقة"><i class="fa fa-chevron-right"></i></a>';
        if (s > 1) {
            h += '<a href="javascript:void(0)" onclick="DL.load(DL.tab,1)" style="' + btnStyle + 'border:1px solid #E2E8F0;color:#475569">1</a>';
            if (s > 2) h += '<span style="color:#94A3B8;font-size:12px">…</span>';
        }
        for (var i = s; i <= e; i++) {
            var act = i === p;
            h += '<a href="javascript:void(0)" onclick="DL.load(DL.tab,' + i + ')" style="' + btnStyle + (act ? 'background:#2563EB;color:#fff;border:1px solid #2563EB' : 'border:1px solid #E2E8F0;color:#475569') + '">' + i + '</a>';
        }
        if (e < tp) {
            if (e < tp - 1) h += '<span style="color:#94A3B8;font-size:12px">…</span>';
            h += '<a href="javascript:void(0)" onclick="DL.load(DL.tab,' + tp + ')" style="' + btnStyle + 'border:1px solid #E2E8F0;color:#475569">' + tp + '</a>';
        }
        if (p < tp) h += '<a href="javascript:void(0)" onclick="DL.load(DL.tab,' + (p+1) + ')" style="' + btnStyle + 'border:1px solid #E2E8F0;color:#475569" title="التالية"><i class="fa fa-chevron-left"></i></a>';

        el.innerHTML = h;
    },

    esc: function(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
};

DL.init();
})();
JS;

$this->registerJs($js);
?>
