<?php
/**
 * معالج التجهيز الجماعي للقضايا — Batch Judiciary Case Creator
 * يعرض نموذج البيانات المشتركة + جدول معاينة حية للعقود
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress;
use backend\modules\companies\models\Companies;

$this->title = 'تجهيز القضايا — معالج جماعي';
$this->params['breadcrumbs'][] = ['label' => 'الدائرة القانونية', 'url' => ['/contracts/contracts/legal-department']];
$this->params['breadcrumbs'][] = $this->title;

/* Reference data */
$courts    = ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name');
$types     = ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name');
$lawyers   = ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name');
$addresses = ArrayHelper::map(JudiciaryInformAddress::find()->asArray()->all(), 'id', 'address');
$companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
$years     = array_combine(range(date('Y'), 2010), range(date('Y'), 2010));

/* Contract IDs for hidden field */
$contractIdsList = implode(',', ArrayHelper::getColumn($contractsData, 'id'));

/* JSON data for JS */
$contractsJson = json_encode($contractsData, JSON_UNESCAPED_UNICODE);

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . Yii::$app->params['assetVersion']);
$this->registerCss('.content-header,.page-header { display: none !important; }');
?>

<style>
/* ═══ Batch Wizard Styles ═══ */
.bw-page { max-width: 1400px; margin: 0 auto; padding: 0 16px 40px; }
.bw-header {
    display: flex; align-items: center; gap: 16px; margin-bottom: 24px;
    padding: 20px 24px; background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%);
    border-radius: 12px; color: #fff;
}
.bw-header h1 { font-size: 22px; font-weight: 700; margin: 0; }
.bw-header .bw-count {
    background: #fbbf24; color: #1a365d; font-weight: 800;
    padding: 4px 14px; border-radius: 20px; font-size: 15px;
}
.bw-header .bw-back {
    margin-right: auto; color: rgba(255,255,255,.8); text-decoration: none;
    display: flex; align-items: center; gap: 6px; font-size: 14px;
}
.bw-header .bw-back:hover { color: #fff; }

.bw-grid { display: grid; grid-template-columns: 380px 1fr; gap: 24px; }
@media (max-width: 1024px) { .bw-grid { grid-template-columns: 1fr; } }

/* Form Panel */
.bw-form-panel {
    background: #fff; border-radius: 12px; padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e2e8f0;
    position: sticky; top: 20px; align-self: start;
}
.bw-form-title {
    font-size: 16px; font-weight: 700; color: #1a365d; margin-bottom: 20px;
    padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;
    display: flex; align-items: center; gap: 8px;
}
.bw-field { margin-bottom: 16px; }
.bw-field label {
    display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;
}
.bw-field input, .bw-field select {
    width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 14px; transition: border-color .2s;
}
.bw-field input:focus, .bw-field select:focus {
    outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}
.bw-pct-group {
    display: flex; align-items: center; gap: 8px;
}
.bw-pct-group input { flex: 1; }
.bw-pct-group span { font-size: 18px; font-weight: 700; color: #64748b; }
.bw-note {
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;
    padding: 10px 14px; font-size: 12px; color: #92400e; margin-top: 16px;
}
.bw-note i { margin-left: 4px; }

/* Table Panel */
.bw-table-panel {
    background: #fff; border-radius: 12px; padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e2e8f0;
}
.bw-table-title {
    font-size: 16px; font-weight: 700; color: #1a365d; margin-bottom: 16px;
    display: flex; align-items: center; justify-content: space-between;
}
.bw-table-wrap { overflow-x: auto; }
.bw-table {
    width: 100%; border-collapse: collapse; font-size: 13px;
}
.bw-table th {
    background: #f1f5f9; color: #475569; font-weight: 700; padding: 10px 12px;
    text-align: right; border-bottom: 2px solid #e2e8f0; white-space: nowrap;
}
.bw-table td {
    padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;
}
.bw-table tr:hover { background: #f8fafc; }
.bw-table .bw-money { font-weight: 600; font-family: 'Courier New', monospace; direction: ltr; text-align: left; }
.bw-table .bw-fee { color: #dc2626; font-weight: 700; }
.bw-remove-btn {
    background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 16px;
    padding: 4px 8px; border-radius: 4px; transition: all .2s;
}
.bw-remove-btn:hover { color: #ef4444; background: #fef2f2; }

/* Summary Row */
.bw-summary {
    display: flex; gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 2px solid #e2e8f0;
    flex-wrap: wrap;
}
.bw-summary-card {
    flex: 1; min-width: 140px; background: #f8fafc; border-radius: 8px;
    padding: 12px 16px; text-align: center;
}
.bw-summary-card .bw-sv { font-size: 20px; font-weight: 800; color: #1a365d; }
.bw-summary-card .bw-sl { font-size: 11px; color: #64748b; margin-top: 2px; }

/* Submit */
.bw-submit-area {
    margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap;
}
.bw-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 12px 32px;
    border-radius: 10px; font-size: 15px; font-weight: 700; border: none; cursor: pointer;
    transition: all .2s; text-decoration: none;
}
.bw-btn-primary { background: #1a365d; color: #fff; }
.bw-btn-primary:hover { background: #2d3748; color: #fff; }
.bw-btn-outline { background: #fff; color: #475569; border: 2px solid #d1d5db; }
.bw-btn-outline:hover { background: #f8fafc; color: #1a365d; border-color: #94a3b8; }
</style>

<div class="bw-page">

    <!-- Header -->
    <div class="bw-header">
        <i class="fa fa-gavel" style="font-size:28px;opacity:.8"></i>
        <h1>تجهيز القضايا — معالج جماعي</h1>
        <span class="bw-count" id="bwContractCount"><?= count($contractsData) ?></span>
        <a href="<?= Url::to(['/contracts/contracts/legal-department']) ?>" class="bw-back">
            <i class="fa fa-arrow-left"></i> العودة للدائرة القانونية
        </a>
    </div>

    <form id="bwForm" method="POST" action="<?= Url::to(['batch-create']) ?>">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
        <input type="hidden" name="batch_submit" value="1">
        <input type="hidden" name="contract_ids" id="bwContractIds" value="<?= Html::encode($contractIdsList) ?>">

        <div class="bw-grid">

            <!-- ═══ القسم الأيسر: البيانات المشتركة ═══ -->
            <div class="bw-form-panel">
                <div class="bw-form-title">
                    <i class="fa fa-cog"></i> البيانات المشتركة
                </div>

                <div class="bw-field">
                    <label><span style="color:#dc2626">*</span> المحكمة</label>
                    <?= Select2::widget([
                        'name' => 'court_id',
                        'data' => $courts,
                        'options' => ['placeholder' => 'اختر المحكمة', 'id' => 'bwCourt'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>

                <div class="bw-field">
                    <label><span style="color:#dc2626">*</span> المحامي</label>
                    <?= Select2::widget([
                        'name' => 'lawyer_id',
                        'data' => $lawyers,
                        'options' => ['placeholder' => 'اختر المحامي', 'id' => 'bwLawyer'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>

                <div class="bw-field">
                    <label>نوع القضية</label>
                    <?= Select2::widget([
                        'name' => 'type_id',
                        'data' => $types,
                        'options' => ['placeholder' => 'اختر النوع', 'id' => 'bwType'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>

                <div class="bw-field">
                    <label>الموطن المختار</label>
                    <?= Select2::widget([
                        'name' => 'judiciary_inform_address_id',
                        'data' => $addresses,
                        'options' => ['placeholder' => 'اختر الموطن المختار', 'id' => 'bwAddress'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>

                <div class="bw-field">
                    <label>الشركة</label>
                    <?= Select2::widget([
                        'name' => 'company_id',
                        'data' => $companies,
                        'options' => ['placeholder' => 'اختر الشركة', 'id' => 'bwCompany'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>

                <div class="bw-field">
                    <label>السنة</label>
                    <select name="year" id="bwYear">
                        <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="bw-field">
                    <label><i class="fa fa-percent" style="color:#3b82f6"></i> نسبة أتعاب المحامي (%)</label>
                    <div class="bw-pct-group">
                        <input type="number" name="lawyer_percentage" id="bwPercentage"
                               value="10" min="0" max="100" step="0.5" placeholder="10">
                        <span>%</span>
                    </div>
                </div>

                <div class="bw-note">
                    <i class="fa fa-info-circle"></i>
                    <strong>ملاحظة:</strong> رسوم القضية لا تُدخل هنا — تُسجل لاحقاً عند الدفع الفعلي لكل قضية على حدة.
                </div>
            </div>

            <!-- ═══ القسم الأيمن: جدول العقود ═══ -->
            <div class="bw-table-panel">
                <div class="bw-table-title">
                    <span><i class="fa fa-list"></i> العقود المختارة</span>
                    <span style="font-size:13px;color:#64748b" id="bwTableCount"><?= count($contractsData) ?> عقد</span>
                </div>

                <div class="bw-table-wrap">
                    <table class="bw-table" id="bwTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العميل</th>
                                <th>تاريخ البيع</th>
                                <th>الإجمالي</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th>أتعاب المحامي</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="bwTableBody">
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="bw-summary">
                    <div class="bw-summary-card">
                        <div class="bw-sv" id="bwSumContracts">0</div>
                        <div class="bw-sl">عدد العقود</div>
                    </div>
                    <div class="bw-summary-card">
                        <div class="bw-sv" id="bwSumRemaining">0</div>
                        <div class="bw-sl">إجمالي المتبقي</div>
                    </div>
                    <div class="bw-summary-card">
                        <div class="bw-sv" id="bwSumFees" style="color:#dc2626">0</div>
                        <div class="bw-sl">إجمالي الأتعاب</div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="bw-submit-area">
                    <a href="<?= Url::to(['/contracts/contracts/legal-department']) ?>" class="bw-btn bw-btn-outline">
                        <i class="fa fa-times"></i> إلغاء
                    </a>
                    <button type="submit" class="bw-btn bw-btn-primary" id="bwSubmitBtn">
                        <i class="fa fa-gavel"></i> إنشاء القضايا وطباعة
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<?php
$this->registerJs(<<<JS
(function(){
    var contracts = $contractsJson;
    var pctInput = document.getElementById('bwPercentage');
    var tbody = document.getElementById('bwTableBody');
    var idsInput = document.getElementById('bwContractIds');

    function fmt(n) { return Number(n).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2}); }

    function render() {
        var pct = parseFloat(pctInput.value) || 0;
        var html = '';
        var totalRemaining = 0, totalFees = 0;

        for (var i = 0; i < contracts.length; i++) {
            var c = contracts[i];
            var fee = Math.round(c.remaining * (pct / 100) * 100) / 100;
            totalRemaining += c.remaining;
            totalFees += fee;

            html += '<tr data-idx="' + i + '">' +
                '<td>' + c.id + '</td>' +
                '<td title="' + c.customer + '">' + c.customer + '</td>' +
                '<td>' + (c.sale_date || '—') + '</td>' +
                '<td class="bw-money">' + fmt(c.total) + '</td>' +
                '<td class="bw-money">' + fmt(c.paid) + '</td>' +
                '<td class="bw-money">' + fmt(c.remaining) + '</td>' +
                '<td class="bw-money bw-fee">' + fmt(fee) + '</td>' +
                '<td><button type="button" class="bw-remove-btn" data-idx="' + i + '" title="حذف من الدفعة"><i class="fa fa-trash-o"></i></button></td>' +
                '</tr>';
        }
        tbody.innerHTML = html;

        // Update summaries
        document.getElementById('bwSumContracts').textContent = contracts.length;
        document.getElementById('bwSumRemaining').textContent = fmt(totalRemaining);
        document.getElementById('bwSumFees').textContent = fmt(totalFees);
        document.getElementById('bwContractCount').textContent = contracts.length;
        document.getElementById('bwTableCount').textContent = contracts.length + ' عقد';

        // Update hidden ids
        var ids = [];
        for (var j = 0; j < contracts.length; j++) ids.push(contracts[j].id);
        idsInput.value = ids.join(',');
    }

    // Re-render on percentage change
    pctInput.addEventListener('input', render);

    // Remove contract from batch
    $(document).on('click', '.bw-remove-btn', function(e){
        e.preventDefault();
        var idx = parseInt($(this).data('idx'));
        if (contracts.length <= 1) {
            alert('يجب أن يبقى عقد واحد على الأقل');
            return;
        }
        contracts.splice(idx, 1);
        render();
    });

    // Validate before submit
    document.getElementById('bwForm').addEventListener('submit', function(e){
        if (contracts.length === 0) {
            e.preventDefault();
            alert('لا توجد عقود للتجهيز');
            return;
        }
        var court = document.getElementById('bwCourt');
        var lawyer = document.getElementById('bwLawyer');
        if (!court.value) {
            e.preventDefault();
            alert('الرجاء اختيار المحكمة');
            court.focus();
            return;
        }
        if (!lawyer.value) {
            e.preventDefault();
            alert('الرجاء اختيار المحامي');
            lawyer.focus();
            return;
        }
        // Confirm
        if (!confirm('سيتم إنشاء ' + contracts.length + ' قضية. هل تريد المتابعة؟')) {
            e.preventDefault();
        }
    });

    // Initial render
    render();
})();
JS
);
?>
