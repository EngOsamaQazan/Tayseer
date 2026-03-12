<?php
/**
 * معالج (Wizard) فاتورة توريد جديدة — للمورد
 * الخطوات: 1 أصناف 2 بيانات وأسعار (الفرع إلزامي) 3 سيريالات 4 مراجعة وإنهاء
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Modal;
use backend\modules\inventoryInvoices\models\InventoryInvoices;

$this->title = 'فاتورة توريد جديدة (معالج)';
$this->params['breadcrumbs'][] = ['label' => 'أوامر الشراء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
\johnitvn\ajaxcrud\CrudAsset::register($this);

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
.inv-wizard-page .wizard-search-results { margin-top:10px; max-height:280px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; }
.inv-wizard-page .wizard-search-row { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1px solid #e2e8f0; }
.inv-wizard-page .wizard-search-row:last-child { border-bottom:none; }
.inv-wizard-page .wizard-selected-list { margin-top:12px; }
.inv-wizard-page .wizard-selected-row { display:flex; align-items:center; gap:10px; padding:8px 12px; background:#f1f5f9; border-radius:6px; margin-bottom:6px; }
.inv-wizard-page .wizard-step2-rows table { width:100%; margin-top:12px; }
.inv-wizard-page .wizard-step2-rows th, .inv-wizard-page .wizard-step2-rows td { padding:8px; text-align:right; }
.inv-wizard-page .wizard-step4-table { width:100%; margin:12px 0; }
.inv-wizard-page .wizard-step4-table th, .inv-wizard-page .wizard-step4-table td { padding:8px; border:1px solid #e2e8f0; }
</style>

<div class="inv-wizard-page" style="max-width:920px; margin:0 auto;">
    <h2 style="margin-bottom:20px"><i class="fa fa-file-text-o"></i> <?= Html::encode($this->title) ?></h2>

    <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : Html::encode($type) ?>" style="margin-bottom:16px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= $message ?>
    </div>
    <?php endforeach ?>

    <?= Html::beginForm(Url::to(['create-wizard']), 'post', ['id' => 'wizard-form']) ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>

    <ul class="nav nav-tabs" id="wizard-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#step1" data-step="1">1. الأصناف</a></li>
        <li role="presentation"><a href="#step2" data-step="2">2. البيانات والأسعار</a></li>
        <li role="presentation"><a href="#step3" data-step="3">3. السيريالات</a></li>
        <li role="presentation"><a href="#step4" data-step="4">4. المراجعة والإنهاء</a></li>
    </ul>

    <div class="tab-content" style="padding:24px; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; background:#fff;">
        <div id="step1" class="tab-pane active" data-step="1">
            <p class="text-muted">ابحث عن صنف ثم اضغط "إضافة للفاتورة". يمكنك إضافة صنف جديد غير موجود في القائمة عبر زر "إضافة صنف جديد".</p>
            <div class="form-group">
                <label>بحث عن صنف (اسم أو باركود)</label>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <input type="text" class="form-control" id="wizard-search-item" placeholder="اسم الصنف أو الباركود..." style="max-width:400px">
                    <?= Html::a('<i class="fa fa-plus"></i> <span>صنف جديد</span>', ['/inventoryItems/inventory-items/create'], [
                        'class' => 'fin-btn fin-btn--add',
                        'title' => 'إضافة صنف جديد',
                        'role' => 'modal-remote',
                        'style' => 'white-space:nowrap',
                    ]) ?>
                    <?= Html::a('<i class="fa fa-cubes"></i> <span>إضافة أصناف جديدة</span>', ['/inventoryItems/inventory-items/batch-create'], [
                        'class' => 'fin-btn fin-btn--add',
                        'title' => 'إضافة أصناف جديدة',
                        'role' => 'modal-remote',
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
            <button type="button" class="btn btn-warning btn-sm" id="wizard-reset" title="تفريغ جميع البيانات والبدء من جديد">
                <i class="fa fa-eraser"></i> إعادة تعيين
            </button>
            <div style="display:flex; gap:12px; align-items:center;">
                <button type="button" class="btn btn-default" id="wizard-prev" style="display:none">السابق</button>
                <button type="button" class="btn btn-primary" id="wizard-next">التالي</button>
                <button type="submit" class="btn btn-success" id="wizard-submit" style="display:none">إنهاء وإرسال</button>
            </div>
        </div>
    </div>
    <?= Html::endForm() ?>
</div>

<?php
$searchUrl = Url::to(['/inventoryItems/inventory-items/search-items']);
$csrfToken = Yii::$app->request->getCsrfToken();
$js = <<<JS
var STORAGE_KEY = 'inv_wizard_data';
var selectedItems = [];
var currentStep = 1;
var totalSteps = 4;

/* ═══════════════════════════════════════════════════════════
 *  حفظ واسترجاع البيانات من sessionStorage
 * ═══════════════════════════════════════════════════════════ */
function saveWizardState() {
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
    var state = {
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
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch(e) {}
}

function restoreWizardState() {
    try {
        var raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return false;
        var state = JSON.parse(raw);
        if (!state || !state.selectedItems || state.selectedItems.length === 0) return false;

        selectedItems = state.selectedItems;
        renderSelected();

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
                if (state.serialsData[idx]) {
                    $(this).val(state.serialsData[idx]);
                }
            });
        }

        var restoreStep = state.currentStep || 1;
        if (restoreStep > 1) goStep(restoreStep);
        return true;
    } catch(e) { return false; }
}

function clearWizardState() {
    try { sessionStorage.removeItem(STORAGE_KEY); } catch(e) {}
}

/* ═══════════════════════════════════════════════════════════ */

function renderSelected() {
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
    saveWizardState();
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

/* حفظ تلقائي عند تغيير أي حقل في الخطوة 2 */
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
    /* إزالة رسائل الخطأ/النجاح القديمة عند التنقل بين الخطوات */
    $('.inv-wizard-page > .alert').remove();
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
    $('.tab-pane').hide();
    $('#step'+n).show();
    $('#wizard-tabs li').removeClass('active').eq(n-1).addClass('active');
    $('#wizard-prev').toggle(n > 1);
    $('#wizard-next').toggle(n < totalSteps);
    $('#wizard-submit').toggle(n === totalSteps);
    saveWizardState();
}
$('#wizard-prev').on('click', function(){ goStep(currentStep - 1); });
$('#wizard-next').on('click', function(){ goStep(currentStep + 1); });

/* زر إعادة التعيين */
$('#wizard-reset').on('click', function(){
    if (!confirm('هل أنت متأكد من إعادة تعيين جميع البيانات؟ سيتم تفريغ كل الحقول والأصناف المختارة.')) return;
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

$('#wizard-form').on('submit', function(){
    var branchId = $('#wizard-branch-id').val();
    var supplierId = $('#wizard-suppliers-id').val();
    var companyId = $('#wizard-company-id').val();
    if (!branchId) { alert('يرجى اختيار موقع التخزين.'); return false; }
    if (!supplierId) { alert('يرجى اختيار المورد.'); return false; }
    if (!companyId) { alert('يرجى اختيار الشركة.'); return false; }
    var ok = true;
    $('#wizard-step2-tbody .line-qty, #wizard-step2-tbody .line-price').each(function(){
        var v = parseFloat($(this).val());
        if ($(this).hasClass('line-qty') && (isNaN(v) || v < 1)) ok = false;
        if ($(this).hasClass('line-price') && (isNaN(v) || v < 0)) ok = false;
    });
    if (!ok) { alert('يرجى تعبئة الكمية (≥1) والسعر (≥0) لكل صنف.'); return false; }
    if (!validateSerials()) {
        alert('عدد الأرقام التسلسلية يجب أن يساوي الكمية بالضبط لكل صنف (لا أقل ولا أكثر).');
        return false;
    }
    clearWizardState();
    return true;
});

/* استعادة الحالة المحفوظة عند تحميل الصفحة */
restoreWizardState();
JS;
$this->registerJs($js);

Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'options' => ['class' => 'modal fade', 'tabindex' => false], 'size' => Modal::SIZE_LARGE]);
Modal::end();
?>
