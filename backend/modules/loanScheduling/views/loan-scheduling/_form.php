<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  نموذج إنشاء / تعديل تسوية — يدعم شهري + أسبوعي + جدولة دين
 *  مع حساب تلقائي لإجمالي الدين
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

/* @var $model backend\modules\loanScheduling\models\LoanScheduling */

$tpl = '{label}{input}{error}';

/* ── حساب إجمالي الدين تلقائياً عند وجود عقد ── */
$_contractObj = null;
$_totalValue = 0;
$_lawyerCost = 0;
$_allExpenses = 0;
$_paidAmount = 0;
$_autoTotal = 0;
$_netDebt = 0;

if (!empty($model->contract_id)) {
    $_contractObj = \backend\modules\contracts\models\Contracts::findOne($model->contract_id);
    if ($_contractObj) {
        $_vbLoan = \backend\modules\followUp\helper\ContractCalculations::fromView($model->contract_id);
        if ($_vbLoan) {
            $_totalValue = $_vbLoan['contractValue'];
            $_lawyerCost = $_vbLoan['lawyerCost'];
            $_allExpenses = $_vbLoan['expenses'];
            $_paidAmount = $_vbLoan['paid'];
            $_autoTotal = $_vbLoan['totalDebt'];
            $_netDebt = $_vbLoan['remaining'];
        } else {
            $_totalValue = (float)($_contractObj->total_value ?? 0);
            $_autoTotal = $_totalValue;
            $_netDebt = 0;
        }
    }
}
?>

<style>
.loan-modal-form .form-group{margin-bottom:18px}
.loan-modal-form .control-label{font-size:13px;font-weight:600;color:#555;margin-bottom:6px;display:block}
.loan-modal-form .control-label .fa{margin-left:5px;color:var(--fin-primary,#800020);font-size:12px}
.loan-modal-form .form-control{border-radius:6px;height:42px;font-size:14px;border:1.5px solid #ddd;transition:border-color .2s}
.loan-modal-form .form-control:focus{border-color:var(--fin-primary,#800020);box-shadow:0 0 0 3px rgba(128,0,32,.08)}
.loan-modal-form .help-block{font-size:12px;margin-top:4px}
.loan-modal-form .has-error .form-control{border-color:#e74c3c}
.loan-modal-form .has-error .help-block{color:#e74c3c}
.loan-modal-form hr{margin:16px 0;border-color:#f0f0f0}
.loan-modal-form .loan-section-title{font-size:12px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #f0f0f0}
.loan-modal-form .loan-amount-input{font-weight:600;text-align:center;font-size:16px!important}
.loan-modal-form .btn-submit{width:100%;height:48px;font-size:15px;font-weight:700;border-radius:8px;border:0;letter-spacing:.3px}
.loan-modal-form .btn-submit-create{background:var(--fin-primary,#800020);color:#fff}
.loan-modal-form .btn-submit-create:hover{background:#600018;color:#fff}
.loan-modal-form .btn-submit-update{background:#2563eb;color:#fff}
.loan-modal-form .btn-submit-update:hover{background:#1d4ed8;color:#fff}
.ls-type-toggle{display:flex;gap:8px;margin-bottom:16px}
.ls-type-btn{flex:1;padding:12px 16px;border:2px solid #e2e8f0;border-radius:8px;text-align:center;cursor:pointer;transition:all .2s;background:#f8f9fa;font-weight:600;font-size:13px}
.ls-type-btn:hover{border-color:#800020;background:#fff}
.ls-type-btn.active{border-color:#800020;background:#800020;color:#fff}
.ls-type-btn i{display:block;font-size:20px;margin-bottom:4px}
.ls-schedule-preview{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-top:12px}
.ls-schedule-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
.ls-schedule-row:last-child{border-bottom:none}
.ls-schedule-row .label-text{color:#64748b}
.ls-schedule-row .value-text{font-weight:700;color:#1e293b}
.ls-debt-card{background:linear-gradient(135deg,#f0f4ff,#e8eeff);border:1px solid #c7d2fe;border-radius:8px;padding:16px;margin-bottom:16px}
.ls-debt-row{display:flex;justify-content:space-between;font-size:13px;padding:4px 0;color:#475569}
.ls-debt-row.ls-debt-total{border-top:2px solid #800020;margin-top:8px;padding-top:8px;font-size:15px;font-weight:700;color:#800020}
</style>

<div class="loan-modal-form">
<?php $form = ActiveForm::begin([
    'id' => 'loan-form',
    'fieldConfig' => ['template' => $tpl, 'options' => ['tag' => 'div']],
]); ?>

<?php if (!empty($model->contract_id)): ?>
    <?= $form->field($model, 'contract_id')->hiddenInput()->label(false) ?>

    <!-- ═══ بطاقة إجمالي الدين (محسوب تلقائياً) ═══ -->
    <?php if ($_contractObj): ?>
    <div class="ls-debt-card">
        <div class="loan-section-title" style="margin-top:0;border-bottom:none">
            إجمالي الدين — عقد #<?= Html::encode($model->contract_id) ?>
        </div>
        <div class="ls-debt-row"><span>المبلغ الأصلي للعقد</span><span><?= number_format($_totalValue, 2) ?> د.أ</span></div>
        <?php if ($_allExpenses > 0): ?>
        <div class="ls-debt-row"><span>إجمالي المصاريف (Outcome)</span><span><?= number_format($_allExpenses, 2) ?> د.أ</span></div>
        <?php endif ?>
        <?php if ($_lawyerCost > 0): ?>
        <div class="ls-debt-row"><span>أتعاب المحاماة</span><span><?= number_format($_lawyerCost, 2) ?> د.أ</span></div>
        <?php endif ?>
        <div class="ls-debt-row" style="border-top:1px solid #c7d2fe;margin-top:4px;padding-top:6px"><span>الإجمالي قبل الخصم</span><span><?= number_format($_autoTotal, 2) ?> د.أ</span></div>
        <div class="ls-debt-row" style="color:#059669"><span><i class="fa fa-check-circle"></i> المدفوع</span><span style="color:#059669">- <?= number_format($_paidAmount, 2) ?> د.أ</span></div>
        <div class="ls-debt-row ls-debt-total"><span>صافي الدين</span><span><?= number_format($_netDebt, 2) ?> د.أ</span></div>
    </div>
    <?= $form->field($model, 'total_debt')->hiddenInput(['id' => 'ls-total-debt', 'value' => $model->total_debt ?: $_netDebt])->label(false) ?>
    <?php else: ?>
    <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> العقد رقم <?= Html::encode($model->contract_id) ?> غير موجود!</div>
    <?php endif ?>

<?php else: ?>
    <!-- رقم العقد يدوي (حالة نادرة) -->
    <div class="loan-section-title">بيانات العقد</div>
    <div class="row">
        <div class="col-xs-12">
            <?= $form->field($model, 'contract_id')->textInput([
                'type' => 'number',
                'class' => 'form-control',
                'placeholder' => 'أدخل رقم العقد',
            ])->label('<i class="fa fa-file-text-o"></i> رقم العقد') ?>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <?= $form->field($model, 'total_debt')->textInput([
                'type' => 'number', 'step' => '0.01',
                'class' => 'form-control loan-amount-input',
                'placeholder' => '0.00', 'id' => 'ls-total-debt',
                'oninput' => 'LoanForm.calculate()',
            ])->label('<i class="fa fa-calculator"></i> إجمالي الدين') ?>
        </div>
    </div>
    <hr>
<?php endif ?>

<!-- ═══ نوع التسوية ═══ -->
<div class="loan-section-title">نوع التسوية</div>
<div class="ls-type-toggle">
    <div class="ls-type-btn <?= ($model->settlement_type ?: 'monthly') == 'monthly' ? 'active' : '' ?>" data-type="monthly" onclick="LoanForm.setType('monthly')">
        <i class="fa fa-calendar"></i> شهري
    </div>
    <div class="ls-type-btn <?= $model->settlement_type == 'weekly' ? 'active' : '' ?>" data-type="weekly" onclick="LoanForm.setType('weekly')">
        <i class="fa fa-calendar-o"></i> أسبوعي
    </div>
</div>
<?= $form->field($model, 'settlement_type')->hiddenInput(['id' => 'ls-settlement-type', 'value' => $model->settlement_type ?: 'monthly'])->label(false) ?>

<!-- ═══ تفاصيل التسوية ═══ -->
<div class="loan-section-title">تفاصيل التسوية</div>
<div class="row">
    <div class="col-sm-6">
        <?= $form->field($model, 'first_payment')->textInput([
            'type' => 'number',
            'step' => '0.01',
            'class' => 'form-control loan-amount-input',
            'placeholder' => '0.00',
            'id' => 'ls-first-payment',
            'oninput' => 'LoanForm.calculate()',
        ])->label('<i class="fa fa-money"></i> الدفعة الأولى (مبلغ ثابت)') ?>
    </div>
    <div class="col-sm-6">
        <?= $form->field($model, 'monthly_installment')->textInput([
            'type' => 'number',
            'step' => '0.01',
            'class' => 'form-control loan-amount-input',
            'placeholder' => '0.00',
            'id' => 'ls-installment',
            'oninput' => 'LoanForm.calculate()',
        ])->label('<i class="fa fa-money"></i> <span id="ls-installment-label">القسط الشهري</span>') ?>
    </div>
</div>
<div class="row">
    <div class="col-sm-6">
        <?= $form->field($model, 'first_installment_date')->widget(FlatpickrWidget::class, [
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            'pluginEvents' => ['changeDate' => 'function(e){ LoanForm.onFirstDateChange(); }'],
            'options' => ['placeholder' => 'yyyy-mm-dd', 'class' => 'form-control'],
        ])->label('<i class="fa fa-calendar"></i> تاريخ الدفعة الأولى للتسوية') ?>
    </div>
    <div class="col-sm-6">
        <?= $form->field($model, 'new_installment_date')->widget(FlatpickrWidget::class, [
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            'pluginEvents' => ['changeDate' => 'function(e){ LoanForm.validateNewDate(); }'],
            'options' => ['placeholder' => 'yyyy-mm-dd', 'class' => 'form-control'],
        ])->label('<i class="fa fa-calendar-plus-o"></i> تاريخ القسط الجديد') ?>
        <span class="help-block" id="ls-date-error" x-data x-show="$store.loanPreview.dateError" x-transition x-cloak style="color:#e74c3c;font-size:11px"></span>
    </div>
</div>

<!-- ═══ معاينة جدولة الدين ═══ -->
<div class="ls-schedule-preview" id="ls-schedule-box" x-data x-show="$store.loanPreview.visible" x-transition.duration.200ms x-cloak>
    <div class="loan-section-title" style="border-bottom:none;margin-bottom:8px">معاينة الجدولة</div>
    <div class="ls-schedule-row"><span class="label-text">إجمالي الدين</span><span class="value-text" id="ls-preview-debt">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">الدفعة الأولى</span><span class="value-text" id="ls-preview-fp">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">المبلغ المتبقي بعد الدفعة</span><span class="value-text" id="ls-preview-after-fp">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">قيمة القسط</span><span class="value-text" id="ls-preview-installment">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">عدد الأقساط</span><span class="value-text" id="ls-preview-count">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">آخر قسط (تقريبي)</span><span class="value-text" id="ls-preview-last-date">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">المستحق الكلي (دفعة + أقساط)</span><span class="value-text" id="ls-preview-total-due">—</span></div>
</div>

<?= $form->field($model, 'installments_count')->hiddenInput(['id' => 'ls-installments-count'])->label(false) ?>
<?= $form->field($model, 'remaining_debt')->hiddenInput(['id' => 'ls-remaining-debt'])->label(false) ?>

<!-- ═══ ملاحظات ═══ -->
<div class="row" style="margin-top:12px">
    <div class="col-xs-12">
        <?= $form->field($model, 'notes')->textarea([
            'rows' => 2,
            'placeholder' => 'ملاحظات إضافية (اختياري)...',
            'style' => 'height:auto',
        ])->label('<i class="fa fa-sticky-note-o"></i> ملاحظات') ?>
    </div>
</div>

<!-- ═══ زر الحفظ ═══ -->
<hr>
<div class="form-group" style="margin-bottom:0">
    <?= Html::submitButton(
        $model->isNewRecord
            ? '<i class="fa fa-plus-circle"></i> إنشاء التسوية'
            : '<i class="fa fa-check-circle"></i> حفظ التعديلات',
        [
            'class' => 'btn btn-submit ' . ($model->isNewRecord ? 'btn-submit-create' : 'btn-submit-update'),
        ]
    ) ?>
</div>

<?php ActiveForm::end(); ?>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.store('loanPreview', { visible: false, dateError: false });
});
var LoanForm = (function(){
    function setType(type) {
        document.getElementById('ls-settlement-type').value = type;
        document.querySelectorAll('.ls-type-btn').forEach(function(btn){
            btn.classList.toggle('active', btn.getAttribute('data-type') === type);
        });
        var label = document.getElementById('ls-installment-label');
        label.textContent = type === 'weekly' ? 'القسط الأسبوعي' : 'القسط الشهري';
        calculate();
    }

    function calculate() {
        var debtEl = document.getElementById('ls-total-debt');
        var debt = debtEl ? (parseFloat(debtEl.value) || 0) : 0;
        var fp = parseFloat(document.getElementById('ls-first-payment').value) || 0;
        var installment = parseFloat(document.getElementById('ls-installment').value) || 0;
        var box = document.getElementById('ls-schedule-box');
        var afterFp = Math.max(0, debt - fp);

        if (installment > 0 && afterFp > 0) {
            var count = Math.ceil(afterFp / installment);
            var type = document.getElementById('ls-settlement-type').value;
            var totalDue = fp + (count * installment);

            var firstDate = document.querySelector('[name="LoanScheduling[first_installment_date]"]');
            var lastDate = '—';
            if (firstDate && firstDate.value) {
                var d = new Date(firstDate.value);
                if (type === 'weekly') d.setDate(d.getDate() + (count - 1) * 7);
                else d.setMonth(d.getMonth() + (count - 1));
                lastDate = d.toISOString().split('T')[0];
            }

            document.getElementById('ls-preview-debt').textContent = debt.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('ls-preview-fp').textContent = fp > 0 ? fp.toLocaleString('ar-JO') + ' د.أ' : 'لا يوجد';
            document.getElementById('ls-preview-after-fp').textContent = afterFp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('ls-preview-installment').textContent = installment.toLocaleString('ar-JO') + ' د.أ ' + (type === 'weekly' ? '(أسبوعي)' : '(شهري)');
            document.getElementById('ls-preview-count').textContent = count + ' قسط';
            document.getElementById('ls-preview-last-date').textContent = lastDate;
            document.getElementById('ls-preview-total-due').textContent = totalDue.toLocaleString('ar-JO') + ' د.أ';

            document.getElementById('ls-installments-count').value = count;
            document.getElementById('ls-remaining-debt').value = Math.max(0, afterFp - count * installment);
            if (Alpine && Alpine.store) Alpine.store('loanPreview').visible = true;
        } else if (fp > 0 && installment <= 0) {
            document.getElementById('ls-preview-debt').textContent = debt.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('ls-preview-fp').textContent = fp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('ls-preview-after-fp').textContent = afterFp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('ls-preview-installment').textContent = '—';
            document.getElementById('ls-preview-count').textContent = '—';
            document.getElementById('ls-preview-last-date').textContent = '—';
            document.getElementById('ls-preview-total-due').textContent = fp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('ls-installments-count').value = 0;
            document.getElementById('ls-remaining-debt').value = afterFp;
            if (Alpine && Alpine.store) Alpine.store('loanPreview').visible = true;
        } else {
            if (Alpine && Alpine.store) Alpine.store('loanPreview').visible = false;
        }
    }

    function onFirstDateChange() {
        var firstEl = document.querySelector('[name="LoanScheduling[first_installment_date]"]');
        var newEl = document.querySelector('[name="LoanScheduling[new_installment_date]"]');
        if (firstEl && firstEl.value && newEl) {
            var type = document.getElementById('ls-settlement-type').value;
            var d = new Date(firstEl.value);
            if (type === 'weekly') {
                d.setDate(d.getDate() + 7);
            } else {
                d.setMonth(d.getMonth() + 1);
            }
            newEl.value = d.toISOString().split('T')[0];
            if (newEl._flatpickr) newEl._flatpickr.setDate(newEl.value, false);
        }
        calculate();
        validateNewDate();
    }

    function validateNewDate() {
        var firstEl = document.querySelector('[name="LoanScheduling[first_installment_date]"]');
        var newEl = document.querySelector('[name="LoanScheduling[new_installment_date]"]');
        var errEl = document.getElementById('ls-date-error');
        var _lsStore = (Alpine && Alpine.store) ? Alpine.store('loanPreview') : null;
        if (!firstEl || !firstEl.value || !newEl || !newEl.value) {
            if (_lsStore) _lsStore.dateError = false;
            return true;
        }
        var firstDate = new Date(firstEl.value);
        var newDate = new Date(newEl.value);
        var minDate = new Date(firstEl.value);
        minDate.setDate(minDate.getDate() + 7);

        if (newDate <= firstDate) {
            errEl.textContent = 'يجب أن يكون تاريخ القسط الجديد بعد تاريخ الدفعة الأولى';
            if (_lsStore) _lsStore.dateError = true;
            newEl.style.borderColor = '#e74c3c';
            return false;
        }
        if (newDate < minDate) {
            errEl.textContent = 'يجب أن يكون تاريخ القسط الجديد بعد الدفعة الأولى بأسبوع على الأقل';
            if (_lsStore) _lsStore.dateError = true;
            newEl.style.borderColor = '#e74c3c';
            return false;
        }
        if (_lsStore) _lsStore.dateError = false;
        newEl.style.borderColor = '#ddd';
        return true;
    }

    return { setType: setType, calculate: calculate, onFirstDateChange: onFirstDateChange, validateNewDate: validateNewDate };
})();
</script>
