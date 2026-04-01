<?php
/**
 * معالج (Wizard) فاتورة توريد جديدة — للمورد
 * الخطوات: 1 أصناف 2 بيانات وأسعار (الفرع إلزامي) 3 سيريالات 4 مراجعة وإنهاء
 */
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\inventoryInvoices\models\InventoryInvoices;

$this->title = 'فاتورة توريد جديدة (معالج)';
$this->params['breadcrumbs'][] = ['label' => 'أوامر الشراء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

$activeBranches = isset($activeBranches) ? $activeBranches : [];
$branchList = [];
foreach ($activeBranches as $loc) {
    $branchList[$loc->id] = $loc->locations_name;
}
$suppliersList = isset($suppliersList) ? $suppliersList : [];
$companiesList = isset($companiesList) ? $companiesList : [];
?>
<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'invoices']) ?>

<style>
/* Override Vuexy tab animation that hides content via opacity:0 */
.inv-wizard-page .tab-content .tab-pane {
    opacity: 1 !important;
    transform: none !important;
}
.inv-wizard-page { color: #2c2c2c; }
.inv-wizard-page h2 { color: #1e293b; }
.inv-wizard-page p, .inv-wizard-page label, .inv-wizard-page strong { color: #2c2c2c; }
.inv-wizard-page .text-muted { color: #64748b !important; }
.inv-wizard-page .text-warning { color: #b45309 !important; }
.inv-wizard-page .text-danger { color: #dc2626 !important; }
.inv-wizard-page .form-control { color: #2c2c2c; background: #fff; border: 1px solid #d1d5db; }
.inv-wizard-page .form-control:focus { border-color: #800020; box-shadow: 0 0 0 3px rgba(128,0,32,.1); }
.inv-wizard-page .form-control::placeholder { color: #9ca3af; }
.inv-wizard-page .control-label { color: #374151; font-weight: 600; }
.inv-wizard-page .nav-tabs { border-bottom: 2px solid #e2e8f0; }
.inv-wizard-page .nav-tabs a { color: #64748b; padding: 10px 18px; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; display: inline-block; }
.inv-wizard-page .nav-tabs li.active a { color: #800020; border-bottom-color: #800020; }
.inv-wizard-page .btn { color: inherit; }
.inv-wizard-page .btn-secondary { color: #374151; background: #f3f4f6; border: 1px solid #d1d5db; }
.inv-wizard-page .btn-primary { color: #fff; }
.inv-wizard-page .btn-success { color: #fff; }
.inv-wizard-page .btn-warning { color: #fff; }
.inv-wizard-page .btn-danger { color: #fff; }
.inv-wizard-page .table { color: #2c2c2c; }
.inv-wizard-page .table th { color: #374151; background: #f8fafc; }
.inv-wizard-page .table td { color: #2c2c2c; }
.inv-wizard-page .wizard-search-results { margin-top:10px; max-height:280px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; }
.inv-wizard-page .wizard-search-row { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1px solid #e2e8f0; color: #2c2c2c; }
.inv-wizard-page .wizard-search-row:last-child { border-bottom:none; }
.inv-wizard-page .wizard-selected-list { margin-top:12px; color: #2c2c2c; }
.inv-wizard-page .wizard-selected-row { display:flex; align-items:center; gap:10px; padding:8px 12px; background:#f1f5f9; border-radius:6px; margin-bottom:6px; color: #2c2c2c; }
.inv-wizard-page .wizard-step2-rows table { width:100%; margin-top:12px; }
.inv-wizard-page .wizard-step2-rows th, .inv-wizard-page .wizard-step2-rows td { padding:8px; text-align:right; }
.inv-wizard-page .wizard-step4-table { width:100%; margin:12px 0; }
.inv-wizard-page .wizard-step4-table th, .inv-wizard-page .wizard-step4-table td { padding:8px; border:1px solid #e2e8f0; }
.inv-wizard-page #wizard-no-items { color: #6b7280 !important; }
/* ─ Saved drafts panel ─ */
.inv-wizard-page .wizard-drafts-panel { margin-bottom:20px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; overflow:hidden; }
.inv-wizard-page .wizard-drafts-toggle { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; cursor:pointer; user-select:none; background:#f1f5f9; border-bottom:1px solid #e2e8f0; }
.inv-wizard-page .wizard-drafts-toggle:hover { background:#e2e8f0; }
.inv-wizard-page .wizard-drafts-toggle h4 { margin:0; font-size:14px; color:#334155; }
.inv-wizard-page .wizard-drafts-toggle .badge { background:#800020; color:#fff; font-size:11px; padding:3px 8px; border-radius:10px; margin-right:8px; }
.inv-wizard-page .wizard-drafts-toggle .fa-chevron-down { transition:transform .2s; color:#64748b; }
.inv-wizard-page .wizard-drafts-toggle.open .fa-chevron-down { transform:rotate(180deg); }
.inv-wizard-page .wizard-drafts-body { padding:14px 16px; display:none; }
.inv-wizard-page .wizard-drafts-body.open { display:block; }
.inv-wizard-page .wizard-draft-card { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; margin-bottom:8px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; gap:12px; }
.inv-wizard-page .wizard-draft-card:last-child { margin-bottom:0; }
.inv-wizard-page .wizard-draft-info { flex:1; min-width:0; }
.inv-wizard-page .wizard-draft-info .draft-label { font-weight:600; color:#1e293b; font-size:13px; }
.inv-wizard-page .wizard-draft-info .draft-meta { font-size:11px; color:#64748b; margin-top:2px; }
.inv-wizard-page .wizard-draft-info .draft-summary { font-size:11px; color:#94a3b8; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:420px; }
.inv-wizard-page .wizard-draft-actions { display:flex; gap:6px; flex-shrink:0; }
.inv-wizard-page .wizard-no-saved-drafts { color:#94a3b8; font-size:13px; text-align:center; padding:8px 0; }
</style>

<div class="inv-wizard-page" style="max-width:920px; margin:0 auto;">
    <h2 style="margin-bottom:20px"><i class="fa fa-file-text-o"></i> <?= Html::encode($this->title) ?></h2>

    <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : Html::encode($type) ?> alert-dismissible fade show" style="margin-bottom:16px;" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    </div>
    <?php endforeach ?>

    <!-- لوحة المسودات المحفوظة -->
    <div class="wizard-drafts-panel" id="wizard-drafts-panel">
        <div class="wizard-drafts-toggle" id="wizard-drafts-toggle">
            <h4><i class="fa fa-folder-open-o"></i> المسودات المحفوظة <span class="badge" id="wizard-drafts-count">0</span></h4>
            <i class="fa fa-chevron-down"></i>
        </div>
        <div class="wizard-drafts-body" id="wizard-drafts-body">
            <div id="wizard-drafts-list">
                <div class="wizard-no-saved-drafts">لا توجد مسودات محفوظة.</div>
            </div>
        </div>
    </div>

    <?= Html::beginForm(Url::to(['create-wizard']), 'post', ['id' => 'wizard-form']) ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>

    <ul class="nav nav-tabs" id="wizard-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#step1" data-step="1">1. الأصناف</a></li>
        <li role="presentation"><a href="#step2" data-step="2">2. البيانات والأسعار</a></li>
        <li role="presentation"><a href="#step3" data-step="3">3. السيريالات</a></li>
        <li role="presentation"><a href="#step4" data-step="4">4. المراجعة والإنهاء</a></li>
    </ul>

    <div class="tab-content" style="padding:24px; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; background:#fff; color:#2c2c2c;">
        <div id="step1" class="tab-pane active show" data-step="1">
            <p class="text-muted">ابحث عن صنف ثم اضغط "إضافة للفاتورة". يمكنك إضافة صنف جديد غير موجود في القائمة عبر زر "إضافة صنف جديد".</p>
            <div class="form-group">
                <label>بحث عن صنف (اسم أو باركود)</label>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <input type="text" class="form-control" id="wizard-search-item" placeholder="اسم الصنف أو الباركود..." style="max-width:400px">
                    <?= Html::a('<i class="fa fa-plus"></i> <span>صنف جديد</span>', ['/inventoryItems/inventory-items/create'], [
                        'class' => 'fin-btn fin-btn--add',
                        'title' => 'إضافة صنف جديد',
                        'role' => 'modal-remote',
                        'data-pjax' => 0,
                        'style' => 'white-space:nowrap',
                    ]) ?>
                    <?= Html::a('<i class="fa fa-cubes"></i> <span>إضافة أصناف جديدة</span>', ['/inventoryItems/inventory-items/batch-create'], [
                        'class' => 'fin-btn fin-btn--add',
                        'title' => 'إضافة أصناف جديدة',
                        'role' => 'modal-remote',
                        'data-pjax' => 0,
                        'style' => 'background:#0ea5e9; white-space:nowrap',
                    ]) ?>
                </div>
            </div>
            <div id="wizard-search-results" class="wizard-search-results" style="display:none;"></div>
            <div id="wizard-selected-list" class="wizard-selected-list">
                <strong>الأصناف المختارة:</strong>
                <div id="wizard-selected-items"></div>
                <p id="wizard-no-items" class="text-muted">لم تتم إضافة أصناف بعد.</p>
            </div>
        </div>
        <div id="step2" class="tab-pane" data-step="2" style="display:none">
            <p class="text-muted">تعبئة بيانات الفاتورة والسعر والكمية لكل صنف.</p>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group required">
                        <label class="control-label">موقع التخزين</label>
                        <?= Html::dropDownList('branch_id', null, $branchList, [
                            'id' => 'wizard-branch-id',
                            'class' => 'form-control',
                            'prompt' => '-- اختر موقع التخزين --',
                            'style' => 'max-width:100%',
                        ]) ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group required">
                        <label class="control-label">المورد</label>
                        <?= Html::dropDownList('suppliers_id', null, $suppliersList, [
                            'id' => 'wizard-suppliers-id',
                            'class' => 'form-control',
                            'prompt' => '-- اختر المورد --',
                            'style' => 'max-width:100%',
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group required">
                        <label class="control-label">الشركة</label>
                        <?= Html::dropDownList('company_id', null, $companiesList, [
                            'id' => 'wizard-company-id',
                            'class' => 'form-control',
                            'prompt' => '-- اختر الشركة --',
                            'style' => 'max-width:100%',
                        ]) ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>طريقة الدفع</label>
                        <?= Html::dropDownList('type', InventoryInvoices::TYPE_CASH, InventoryInvoices::getTypeList(), [
                            'id' => 'wizard-type',
                            'class' => 'form-control',
                            'style' => 'max-width:100%',
                        ]) ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>التاريخ</label>
                        <input type="date" name="date" id="wizard-date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>ملاحظات</label>
                <textarea name="invoice_notes" id="wizard-notes" class="form-control" rows="2" placeholder="اختياري"></textarea>
            </div>
            <div id="wizard-step2-rows" class="wizard-step2-rows">
                <strong>الكمية والسعر لكل صنف:</strong>
                <table class="table table-bordered">
                    <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th></tr></thead>
                    <tbody id="wizard-step2-tbody"></tbody>
                </table>
            </div>
        </div>
        <div id="step3" class="tab-pane" data-step="3" style="display:none">
            <p class="text-warning"><strong><i class="fa fa-exclamation-circle"></i> إدخال الأرقام التسلسلية إلزامي.</strong> عدد الأسطر يجب أن يساوي الكمية بالضبط لكل صنف (سطر واحد لكل قطعة — لا أقل ولا أكثر).</p>
            <div id="wizard-step3-body"></div>
        </div>
        <div id="step4" class="tab-pane" data-step="4" style="display:none">
            <p class="text-muted">مراجعة الملخص ثم إرسال الفاتورة.</p>
            <div id="wizard-step4-summary"></div>
        </div>

        <div class="wizard-actions" style="margin-top:24px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="btn btn-warning btn-sm" id="wizard-reset" title="تفريغ جميع البيانات والبدء من جديد">
                    <i class="fa fa-eraser"></i> إعادة تعيين
                </button>
                <button type="button" class="btn btn-info btn-sm" id="wizard-save-draft-btn" title="حفظ الإدخال الحالي كمسودة يمكن استعادتها لاحقاً">
                    <i class="fa fa-floppy-o"></i> حفظ كمسودة
                </button>
            </div>
            <div style="display:flex; gap:12px; align-items:center;">
                <button type="button" class="btn btn-secondary" id="wizard-prev" style="display:none">السابق</button>
                <button type="button" class="btn btn-primary" id="wizard-next">التالي</button>
                <button type="submit" class="btn btn-success" id="wizard-submit" style="display:none">إنهاء وإرسال</button>
            </div>
        </div>
    </div>
    <?= Html::endForm() ?>
</div>

<?php
$searchUrl        = Url::to(['/inventoryItems/inventory-items/search-items']);
$saveDraftUrl     = Url::to(['save-wizard-draft']);
$loadDraftUrl     = Url::to(['load-wizard-draft']);
$clearDraftUrl    = Url::to(['clear-wizard-draft']);
$listDraftsUrl    = Url::to(['list-wizard-drafts']);
$saveDraftAsUrl   = Url::to(['save-wizard-draft-as']);
$deleteDraftUrl   = Url::to(['delete-wizard-draft']);
$restoreDraftUrl  = Url::to(['restore-wizard-draft']);
$csrfParam        = Yii::$app->request->csrfParam;
$csrfToken        = Yii::$app->request->getCsrfToken();
$js = <<<JS
var selectedItems = [];
var currentStep = 1;
var totalSteps = 4;
var _draftTimer = null;
var _savingDraft = false;

/* ═══════════════════════════════════════════════════════════
 *  مؤشر الحفظ التلقائي
 * ═══════════════════════════════════════════════════════════ */
(function(){
    var ind = document.createElement('div');
    ind.id = 'wizard-draft-indicator';
    ind.style.cssText = 'position:fixed;bottom:20px;left:20px;z-index:9999;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;display:none;transition:opacity .3s;box-shadow:0 2px 8px rgba(0,0,0,.15);';
    document.body.appendChild(ind);
})();
function showDraftStatus(msg, type) {
    var el = $('#wizard-draft-indicator');
    el.stop(true).text(msg);
    if (type === 'saving') {
        el.css({background:'#fef3c7',color:'#92400e',opacity:1}).show();
    } else if (type === 'saved') {
        el.css({background:'#d1fae5',color:'#065f46',opacity:1}).show();
        setTimeout(function(){ el.fadeOut(600); }, 2000);
    } else if (type === 'error') {
        el.css({background:'#fee2e2',color:'#991b1b',opacity:1}).show();
        setTimeout(function(){ el.fadeOut(600); }, 3000);
    }
}

/* ═══════════════════════════════════════════════════════════
 *  حفظ / تحميل / حذف المسودة — سيرفر
 * ═══════════════════════════════════════════════════════════ */
function collectWizardState() {
    var step2Data = {};
    $('#wizard-step2-tbody tr').each(function(){
        var idx = $(this).data('index');
        step2Data[idx] = {
            qty:   $(this).find('.line-qty').val(),
            price: $(this).find('.line-price').val()
        };
    });
    var serialsData = {};
    $('.wizard-serial-ta').each(function(){
        serialsData[$(this).data('index')] = $(this).val();
    });
    return {
        selectedItems: selectedItems,
        currentStep:   currentStep,
        branch_id:     $('#wizard-branch-id').val(),
        suppliers_id:  $('#wizard-suppliers-id').val(),
        company_id:    $('#wizard-company-id').val(),
        type:          $('#wizard-type').val(),
        date:          $('#wizard-date').val(),
        notes:         $('#wizard-notes').val(),
        step2Data:     step2Data,
        serialsData:   serialsData
    };
}

function saveWizardState() {
    if (_draftTimer) clearTimeout(_draftTimer);
    _draftTimer = setTimeout(_flushDraft, 800);
}
function _flushDraft() {
    if (_savingDraft) { setTimeout(_flushDraft, 500); return; }
    _savingDraft = true;
    showDraftStatus('جاري حفظ المسودة...', 'saving');
    var payload = {};
    payload['$csrfParam'] = '$csrfToken';
    payload['draft_data'] = JSON.stringify(collectWizardState());
    $.post('$saveDraftUrl', payload)
     .done(function(r){ showDraftStatus('تم حفظ المسودة', 'saved'); })
     .fail(function()  { showDraftStatus('فشل حفظ المسودة', 'error'); })
     .always(function(){ _savingDraft = false; });
}

function restoreWizardState() {
    $.get('$loadDraftUrl', function(r) {
        if (!r || !r.ok || !r.data) return;
        var state = r.data;
        if (!state.selectedItems || state.selectedItems.length === 0) return;

        selectedItems = state.selectedItems;
        renderSelected(true);

        if (state.branch_id)    $('#wizard-branch-id').val(state.branch_id);
        if (state.suppliers_id) $('#wizard-suppliers-id').val(state.suppliers_id);
        if (state.company_id)   $('#wizard-company-id').val(state.company_id);
        if (state.type)         $('#wizard-type').val(state.type);
        if (state.date)         $('#wizard-date').val(state.date);
        if (state.notes)        $('#wizard-notes').val(state.notes);

        if (state.step2Data) {
            $('#wizard-step2-tbody tr').each(function(){
                var idx = $(this).data('index');
                var d = state.step2Data[idx];
                if (d) {
                    $(this).find('.line-qty').val(d.qty);
                    $(this).find('.line-price').val(d.price);
                }
            });
        }
        if (state.serialsData) {
            buildStep3Body();
            $('.wizard-serial-ta').each(function(){
                var idx = $(this).data('index');
                if (state.serialsData[idx]) $(this).val(state.serialsData[idx]);
            });
        }
        var restoreStep = state.currentStep || 1;
        if (restoreStep > 1) goStep(restoreStep);
    }, 'json');
}

function clearWizardState() {
    var payload = {};
    payload['$csrfParam'] = '$csrfToken';
    $.post('$clearDraftUrl', payload);
}

/* ═══════════════════════════════════════════════════════════ */

function renderSelected(skipSave) {
    var html = '';
    selectedItems.forEach(function(it, i) {
        html += '<div class="wizard-selected-row" data-index="'+i+'">';
        html += '<span>'+ (it.name || it.text) +'</span>';
        html += '<button type="button" class="btn btn-xs btn-danger wizard-remove-item" data-index="'+i+'"><i class="fa fa-times"></i></button>';
        html += '</div>';
    });
    $('#wizard-selected-items').html(html);
    $('#wizard-no-items').toggle(selectedItems.length === 0);
    $('#wizard-step2-tbody').empty();
    selectedItems.forEach(function(it, i) {
        var price = parseFloat(it.price) || 0;
        var row = '<tr data-index="'+i+'">';
        row += '<td><input type="hidden" name="ItemsInventoryInvoices['+i+'][inventory_items_id]" value="'+it.id+'">'+ (it.name || it.text) +'</td>';
        row += '<td><input type="number" min="1" name="ItemsInventoryInvoices['+i+'][number]" class="form-control input-sm line-qty" value="1" style="width:80px;direction:ltr"></td>';
        row += '<td><input type="number" step="0.01" min="0" name="ItemsInventoryInvoices['+i+'][single_price]" class="form-control input-sm line-price" value="'+price+'" style="width:100px;direction:ltr"></td>';
        row += '</tr>';
        $('#wizard-step2-tbody').append(row);
    });
    buildStep4Summary();
    if (!skipSave) saveWizardState();
}
function buildStep4Summary() {
    var rows = [];
    var total = 0;
    $('#wizard-step2-tbody tr').each(function(){
        var idx = $(this).data('index');
        var it = selectedItems[idx];
        if (!it) return;
        var qty = parseFloat($(this).find('.line-qty').val()) || 0;
        var price = parseFloat($(this).find('.line-price').val()) || 0;
        var lineTotal = qty * price;
        total += lineTotal;
        rows.push({ name: it.name || it.text, qty: qty, price: price, total: lineTotal });
    });
    var html = '<table class="table table-bordered wizard-step4-table"><thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>';
    rows.forEach(function(r){
        html += '<tr><td>'+r.name+'</td><td>'+r.qty+'</td><td>'+r.price.toFixed(2)+'</td><td>'+r.total.toFixed(2)+'</td></tr>';
    });
    html += '</tbody><tfoot><tr><th colspan="3">المجموع</th><th>'+total.toFixed(2)+'</th></tr></tfoot></table>';
    $('#wizard-step4-summary').html(html);
}

$('#wizard-search-item').on('input', function(){
    var q = $(this).val().trim();
    if (q.length < 2) { $('#wizard-search-results').hide().empty(); return; }
    $.get('$searchUrl', { q: q }, function(data) {
        var res = data.results || [];
        var html = '';
        res.forEach(function(r) {
            var already = selectedItems.some(function(s){ return s.id == r.id; });
            var label = (r.text || r.name || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            html += '<div class="wizard-search-row">';
            html += '<span class="wizard-result-label">'+ label +'</span>';
            if (already) html += '<span class="text-muted">مضاف</span>';
            else html += '<button type="button" class="btn btn-xs btn-success wizard-add-item" data-id="'+r.id+'" data-price="'+(parseFloat(r.price)||0)+'">إضافة</button>';
            html += '</div>';
        });
        $('#wizard-search-results').html(html || '<div class="wizard-search-row text-muted">لا توجد نتائج</div>').show();
    }, 'json');
});

$(document).on('click', '.wizard-add-item', function(){
    var row = $(this).closest('.wizard-search-row');
    var label = row.find('.wizard-result-label').text();
    var id = $(this).data('id'), price = parseFloat($(this).data('price')) || 0;
    selectedItems.push({ id: id, name: label, text: label, price: price });
    renderSelected();
    row.find('button').replaceWith('<span class="text-muted">مضاف</span>');
});
$(document).on('click', '.wizard-remove-item', function(){
    var i = $(this).data('index');
    selectedItems.splice(i, 1);
    renderSelected();
});

/* حفظ تلقائي لحظي عند تغيير أي حقل */
$(document).on('change input', '#wizard-branch-id, #wizard-suppliers-id, #wizard-company-id, #wizard-type, #wizard-date, #wizard-notes', function(){
    saveWizardState();
});
$(document).on('change input', '.line-qty, .line-price', function(){
    saveWizardState();
});
$(document).on('input', '.wizard-serial-ta', function(){
    saveWizardState();
});

function buildStep3Body() {
    var savedSerials = {};
    $('.wizard-serial-ta').each(function(){
        savedSerials[$(this).data('index')] = $(this).val();
    });
    var html = '';
    $('#wizard-step2-tbody tr').each(function(){
        var idx = $(this).data('index');
        var it = selectedItems[idx];
        if (!it) return;
        var qty = parseInt($(this).find('.line-qty').val(), 10) || 0;
        if (qty < 1) return;
        var prev = savedSerials[idx] || '';
        html += '<div class="form-group">';
        html += '<label class="control-label">'+ (it.name || it.text) +' <span class="text-danger">(بالضبط '+qty+' رقم تسلسلي — لا أقل ولا أكثر)</span></label>';
        html += '<textarea name="Serials['+idx+']" class="form-control wizard-serial-ta" data-index="'+idx+'" data-required-qty="'+qty+'" rows="'+Math.min(Math.max(qty,2),8)+'" placeholder="أدخل رقماً تسلسلياً في كل سطر - سطر واحد لكل قطعة" style="direction:ltr;font-family:monospace">'+ prev +'</textarea>';
        html += '</div>';
    });
    $('#wizard-step3-body').html(html);
}
function validateSerials() {
    var ok = true;
    $('.wizard-serial-ta').each(function(){
        var required = parseInt($(this).data('required-qty'), 10) || 0;
        var lines = $(this).val().split(/\\n/).map(function(s){ return s.trim(); }).filter(Boolean);
        if (lines.length !== required) {
            ok = false;
            $(this).addClass('has-error');
        } else {
            $(this).removeClass('has-error');
        }
    });
    return ok;
}

$('#wizard-tabs a').on('click', function(e){ e.preventDefault(); var step = $(this).data('step'); if (step) goStep(parseInt(step,10)); });
function goStep(n){
    if (n === 2 && selectedItems.length === 0) { alert('يرجى إضافة صنف واحد على الأقل في الخطوة 1.'); return; }
    if (n === 3) buildStep3Body();
    if (n === 4) {
        if (!validateSerials()) {
            alert('عدد الأرقام التسلسلية يجب أن يساوي الكمية بالضبط لكل صنف (لا أقل ولا أكثر).');
            return;
        }
        buildStep4Summary();
    }
    currentStep = n;
    $('.tab-pane').hide().removeClass('show');
    $('#step'+n).show().addClass('show');
    $('#wizard-tabs li').removeClass('active').eq(n-1).addClass('active');
    $('#wizard-prev').toggle(n > 1);
    $('#wizard-next').toggle(n < totalSteps);
    $('#wizard-submit').toggle(n === totalSteps);
    saveWizardState();
}
$('#wizard-prev').on('click', function(){ goStep(currentStep - 1); });
$('#wizard-next').on('click', function(){ goStep(currentStep + 1); });

/* زر إعادة التعيين — الزر الوحيد الذي يحذف المسودة */
$('#wizard-reset').on('click', function(){
    TayseerConfirm('سيتم تفريغ كل الحقول والأصناف المختارة.', 'إعادة تعيين؟').then(function(ok){
        if (!ok) return;
        clearWizardState();
        selectedItems = [];
        currentStep = 1;
        $('#wizard-selected-items').empty();
        $('#wizard-no-items').show();
        $('#wizard-step2-tbody').empty();
        $('#wizard-step3-body').empty();
        $('#wizard-step4-summary').empty();
        $('#wizard-search-item').val('');
        $('#wizard-search-results').hide().empty();
        $('#wizard-branch-id').val('');
        $('#wizard-suppliers-id').val('');
        $('#wizard-company-id').val('');
        $('#wizard-type').val('cash');
        $('#wizard-date').val(new Date().toISOString().slice(0,10));
        $('#wizard-notes').val('');
        goStep(1);
    });
});

$('#wizard-form').on('submit', function(e){
    e.preventDefault();
    var branchId = $('#wizard-branch-id').val();
    var supplierId = $('#wizard-suppliers-id').val();
    var companyId = $('#wizard-company-id').val();
    if (!branchId) { showWizardError('يرجى اختيار موقع التخزين.'); return false; }
    if (!supplierId) { showWizardError('يرجى اختيار المورد.'); return false; }
    if (!companyId) { showWizardError('يرجى اختيار الشركة.'); return false; }
    var ok = true;
    $('#wizard-step2-tbody .line-qty, #wizard-step2-tbody .line-price').each(function(){
        var v = parseFloat($(this).val());
        if ($(this).hasClass('line-qty') && (isNaN(v) || v < 1)) ok = false;
        if ($(this).hasClass('line-price') && (isNaN(v) || v < 0)) ok = false;
    });
    if (!ok) { showWizardError('يرجى تعبئة الكمية (≥1) والسعر (≥0) لكل صنف.'); return false; }
    if (!validateSerials()) {
        showWizardError('عدد الأرقام التسلسلية يجب أن يساوي الكمية بالضبط لكل صنف (لا أقل ولا أكثر).');
        return false;
    }
    var wizBtn = document.getElementById('wizard-submit');
    var origText = wizBtn.innerHTML;
    wizBtn.disabled = true;
    wizBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...';
    hideWizardError();
    var formEl = document.getElementById('wizard-form');
    $.ajax({
        url: formEl.getAttribute('action') || window.location.href,
        type: 'POST',
        data: $(formEl).serialize(),
        dataType: 'json',
        success: function(r) {
            if (r && r.ok && r.redirect) {
                clearWizardState();
                window.location.href = r.redirect;
            } else {
                wizBtn.disabled = false;
                wizBtn.innerHTML = origText;
                showWizardError(r && r.msg ? r.msg : 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.');
            }
        },
        error: function(xhr) {
            wizBtn.disabled = false;
            wizBtn.innerHTML = origText;
            var msg = 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.';
            try {
                var r = JSON.parse(xhr.responseText);
                if (r && r.msg) msg = r.msg;
            } catch(ex){}
            showWizardError(msg);
        }
    });
    return false;
});
function showWizardError(msg) {
    var el = document.getElementById('wizard-ajax-error');
    if (!el) {
        el = document.createElement('div');
        el.id = 'wizard-ajax-error';
        el.style.cssText = 'position:sticky;top:0;z-index:9999;margin:0 -24px 15px -24px;padding:18px 24px 18px 50px;font-size:15px;font-weight:600;direction:rtl;text-align:right;border-bottom:3px solid #dc2626;background:#fef2f2;color:#991b1b;box-shadow:0 4px 12px rgba(220,38,38,.15);';
        el.innerHTML = '<button type="button" style="position:absolute;left:14px;top:14px;background:none;border:none;font-size:22px;cursor:pointer;color:#991b1b;line-height:1;" onclick="hideWizardError();">&times;</button><i class="fa fa-exclamation-triangle" style="margin-left:8px;color:#dc2626;"></i> <span id="wizard-err-text"></span>';
        var tabContent = document.querySelector('.inv-wizard-page .tab-content');
        if (tabContent) tabContent.insertBefore(el, tabContent.firstChild);
    }
    document.getElementById('wizard-err-text').textContent = msg;
    el.style.display = 'block';
    el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}
function hideWizardError() {
    var el = document.getElementById('wizard-ajax-error');
    if (el) el.style.display = 'none';
}

/* حفظ فوري قبل مغادرة الصفحة */
$(window).on('beforeunload', function(){
    if (selectedItems.length > 0 && !_savingDraft) {
        var payload = {};
        payload['$csrfParam'] = '$csrfToken';
        payload['draft_data'] = JSON.stringify(collectWizardState());
        navigator.sendBeacon('$saveDraftUrl', new URLSearchParams(payload));
    }
});

/* ═══════════════════════════════════════════════════════════
 *  المسودات المحفوظة يدوياً (حد أقصى 3)
 * ═══════════════════════════════════════════════════════════ */
$('#wizard-drafts-toggle').on('click', function(){
    $(this).toggleClass('open');
    $('#wizard-drafts-body').toggleClass('open');
});

function loadSavedDraftsList() {
    $.get('$listDraftsUrl', function(r){
        if (!r || !r.ok) return;
        var drafts = r.drafts || [];
        $('#wizard-drafts-count').text(drafts.length);
        if (drafts.length === 0) {
            $('#wizard-drafts-list').html('<div class="wizard-no-saved-drafts">لا توجد مسودات محفوظة.</div>');
            return;
        }
        var html = '';
        drafts.forEach(function(d){
            html += '<div class="wizard-draft-card" data-draft-id="'+d.id+'">';
            html += '<div class="wizard-draft-info">';
            html += '<div class="draft-label">'+escHtml(d.label)+'</div>';
            html += '<div class="draft-meta"><i class="fa fa-clock-o"></i> '+d.date+'</div>';
            if (d.summary) html += '<div class="draft-summary" title="'+escHtml(d.summary)+'"><i class="fa fa-cube"></i> '+escHtml(d.summary)+'</div>';
            html += '</div>';
            html += '<div class="wizard-draft-actions">';
            html += '<button type="button" class="btn btn-xs btn-primary wizard-restore-draft" data-id="'+d.id+'" title="استعادة هذه المسودة"><i class="fa fa-upload"></i> استعادة</button>';
            html += '<button type="button" class="btn btn-xs btn-danger wizard-delete-draft" data-id="'+d.id+'" title="حذف هذه المسودة"><i class="fa fa-trash"></i></button>';
            html += '</div>';
            html += '</div>';
        });
        $('#wizard-drafts-list').html(html);
    }, 'json');
}
function escHtml(s) {
    return $('<span>').text(s || '').html();
}

/* حفظ كمسودة يدوية */
$('#wizard-save-draft-btn').on('click', function(){
    if (selectedItems.length === 0) {
        alert('لا يوجد بيانات لحفظها كمسودة. أضف أصناف أولاً.');
        return;
    }
    var label = prompt('أدخل اسماً للمسودة (اختياري):', 'مسودة ' + new Date().toLocaleString('ar-SA'));
    if (label === null) return;
    var payload = {};
    payload['$csrfParam'] = '$csrfToken';
    payload['draft_data'] = JSON.stringify(collectWizardState());
    payload['draft_label'] = label || '';
    showDraftStatus('جاري حفظ المسودة...', 'saving');
    $.post('$saveDraftAsUrl', payload, function(r){
        if (r && r.ok) {
            showDraftStatus('تم حفظ المسودة بنجاح', 'saved');
            loadSavedDraftsList();
        } else {
            showDraftStatus('فشل حفظ المسودة', 'error');
        }
    }, 'json').fail(function(){ showDraftStatus('فشل حفظ المسودة', 'error'); });
});

/* استعادة مسودة محفوظة */
$(document).on('click', '.wizard-restore-draft', function(){
    var draftId = $(this).data('id');
    TayseerConfirm('سيتم استبدال البيانات الحالية في المعالج.', 'استعادة المسودة؟').then(function(ok){
        if (!ok) return;
        $.get('$restoreDraftUrl', {draft_id: draftId}, function(r){
            if (!r || !r.ok || !r.data) { alert('فشل تحميل المسودة.'); return; }
            applyDraftState(r.data);
            showDraftStatus('تم استعادة المسودة', 'saved');
        }, 'json').fail(function(){ alert('فشل تحميل المسودة.'); });
    });
});

/* حذف مسودة */
$(document).on('click', '.wizard-delete-draft', function(){
    var draftId = $(this).data('id');
    TayseerConfirm('هل أنت متأكد من حذف هذه المسودة؟').then(function(ok){
        if (!ok) return;
        var payload = {};
        payload['$csrfParam'] = '$csrfToken';
        payload['draft_id'] = draftId;
        $.post('$deleteDraftUrl', payload, function(r){
            loadSavedDraftsList();
        }, 'json');
    });
});

/* تطبيق بيانات مسودة على النموذج */
function applyDraftState(state) {
    if (!state || !state.selectedItems || state.selectedItems.length === 0) return;
    selectedItems = state.selectedItems;
    currentStep = 1;
    renderSelected(true);

    if (state.branch_id)    $('#wizard-branch-id').val(state.branch_id);
    if (state.suppliers_id) $('#wizard-suppliers-id').val(state.suppliers_id);
    if (state.company_id)   $('#wizard-company-id').val(state.company_id);
    if (state.type)         $('#wizard-type').val(state.type);
    if (state.date)         $('#wizard-date').val(state.date);
    if (state.notes)        $('#wizard-notes').val(state.notes);

    if (state.step2Data) {
        $('#wizard-step2-tbody tr').each(function(){
            var idx = $(this).data('index');
            var d = state.step2Data[idx];
            if (d) {
                $(this).find('.line-qty').val(d.qty);
                $(this).find('.line-price').val(d.price);
            }
        });
    }
    if (state.serialsData) {
        buildStep3Body();
        $('.wizard-serial-ta').each(function(){
            var idx = $(this).data('index');
            if (state.serialsData[idx]) $(this).val(state.serialsData[idx]);
        });
    }
    goStep(1);
    saveWizardState();
}

/* استعادة المسودة التلقائية + تحميل قائمة المسودات المحفوظة عند تحميل الصفحة */
restoreWizardState();
loadSavedDraftsList();
JS;
$this->registerJs($js);
?>
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--ty-clr-primary,#800020)"></i>
                </div>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
