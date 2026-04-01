<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\modules\sharedExpenses\models\SharedExpenseAllocation;

/** @var yii\web\View $this */
/** @var backend\modules\sharedExpenses\models\SharedExpenseAllocation $model */

$methods = SharedExpenseAllocation::getAllocationMethods();
$isUpdate = !$model->isNewRecord;
$existingLines = $isUpdate ? $model->lines : [];
?>

<style>
[x-cloak] { display: none !important; }
:root {
    --se-primary: #8b5cf6;
    --se-primary-light: #ede9fe;
    --se-primary-dark: #7c3aed;
    --se-border: #e2e8f0;
    --se-bg: #f8fafc;
    --se-r: 12px;
    --se-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.se-form-page { max-width: 1000px; margin: 0 auto; }

.se-card { background: #fff; border-radius: var(--se-r); box-shadow: var(--se-shadow); border: 1px solid var(--se-border); margin-bottom: 18px; overflow: hidden; }
.se-card-title { font-size: 15px; font-weight: 700; color: #1e293b; padding: 16px 20px; background: var(--se-bg); border-bottom: 1px solid var(--se-border); display: flex; align-items: center; gap: 8px; }
.se-card-title i { color: var(--se-primary); }
.se-card-title .se-step { background: var(--se-primary); color: #fff; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; margin-left: 8px; }
.se-card-body { padding: 20px; }

.se-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
.se-form-row .form-group { margin-bottom: 14px; }
.se-form-row .form-group label { font-size: 13px; color: #475569; font-weight: 600; margin-bottom: 4px; }
.se-form-row .form-control { border-radius: 8px; border: 1px solid var(--se-border); }

.se-methods { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
.se-method-card { border: 2px solid var(--se-border); border-radius: var(--se-r); padding: 20px; cursor: pointer; transition: all .25s; text-align: center; position: relative; }
.se-method-card:hover { border-color: var(--se-primary); background: #faf5ff; }
.se-method-card.active { border-color: var(--se-primary); background: var(--se-primary-light); box-shadow: 0 0 0 3px rgba(139,92,246,.15); }
.se-method-card.active::after { content: '\f00c'; font-family: FontAwesome; position: absolute; top: 10px; left: 10px; background: var(--se-primary); color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; }
.se-method-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--se-primary-light); color: var(--se-primary); display: flex; align-items: center; justify-content: center; font-size: 20px; margin: 0 auto 10px; }
.se-method-name { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.se-method-desc { font-size: 12px; color: #64748b; line-height: 1.5; }

.se-results-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.se-results-table thead th { background: var(--se-bg); padding: 10px 14px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--se-border); text-align: right; font-size: 12px; }
.se-results-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.se-results-table tbody td { padding: 12px 14px; color: #334155; vertical-align: middle; }
.se-results-table tbody tr:hover { background: #faf5ff; }
.se-results-table tfoot td { padding: 12px 14px; font-weight: 700; color: #1e293b; background: var(--se-bg); border-top: 2px solid var(--se-border); }
.se-results-table input.se-pct-input { width: 80px; border: 1px solid var(--se-border); border-radius: 6px; padding: 6px 8px; font-size: 13px; text-align: center; }
.se-results-table input.se-pct-input:focus { border-color: var(--se-primary); outline: none; box-shadow: 0 0 0 2px rgba(139,92,246,.15); }

.se-results-empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
.se-results-empty i { font-size: 36px; margin-bottom: 8px; display: block; opacity: .5; }

.se-loading { text-align: center; padding: 30px; }
.se-loading i { font-size: 24px; color: var(--se-primary); animation: se-spin 1s linear infinite; }
@keyframes se-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.se-submit-bar { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; background: var(--se-bg); border-top: 1px solid var(--se-border); }
.se-submit-bar .btn { border-radius: 8px; padding: 9px 28px; font-weight: 600; font-size: 14px; }

@media (max-width: 768px) {
    .se-methods { grid-template-columns: 1fr; }
    .se-form-row { grid-template-columns: 1fr; }
    .se-results-table { font-size: 12px; }
}
</style>

<div class="se-form-page">
    <?php $form = ActiveForm::begin(['id' => 'shared-expense-form']); ?>
    <?= $form->errorSummary($model) ?>

    <?= $form->field($model, 'allocation_method')->hiddenInput(['id' => 'allocation-method-input'])->label(false) ?>

    <!-- Card 1: بيانات التوزيع -->
    <div class="se-card">
        <div class="se-card-title">
            <span class="se-step">1</span>
            <i class="fa fa-info-circle"></i> بيانات التوزيع
        </div>
        <div class="se-card-body">
            <div class="se-form-row">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'مثال: إيجار شهر يناير 2026', 'id' => 'allocation-name']) ?>
                <?= $form->field($model, 'total_amount')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0.01', 'placeholder' => '0.00', 'id' => 'total-amount-input']) ?>
            </div>
            <div class="se-form-row">
                <?= $form->field($model, 'allocation_date')->input('date', ['id' => 'allocation-date']) ?>
                <?= $form->field($model, 'period_from')->input('date') ?>
            </div>
            <div class="se-form-row">
                <?= $form->field($model, 'period_to')->input('date') ?>
            </div>
            <?= $form->field($model, 'notes')->textarea(['rows' => 3, 'placeholder' => 'ملاحظات إضافية...']) ?>
        </div>
    </div>

    <!-- Card 2: طريقة التوزيع -->
    <div class="se-card">
        <div class="se-card-title">
            <span class="se-step">2</span>
            <i class="fa fa-sliders"></i> طريقة التوزيع
        </div>
        <div class="se-card-body">
            <div class="se-methods">
                <div class="se-method-card <?= $model->allocation_method === 'عدد_العقود' ? 'active' : '' ?>" data-method="عدد_العقود">
                    <div class="se-method-icon"><i class="fa fa-file-text"></i></div>
                    <div class="se-method-name">عدد العقود</div>
                    <div class="se-method-desc">توزيع بناءً على عدد عقود كل محفظة</div>
                </div>
                <div class="se-method-card <?= $model->allocation_method === 'صافي_الدين' ? 'active' : '' ?>" data-method="صافي_الدين">
                    <div class="se-method-icon"><i class="fa fa-money"></i></div>
                    <div class="se-method-name">صافي الدين</div>
                    <div class="se-method-desc">توزيع بناءً على صافي ذمم العملاء لكل محفظة</div>
                </div>
                <div class="se-method-card <?= $model->allocation_method === 'يدوي' ? 'active' : '' ?>" data-method="يدوي">
                    <div class="se-method-icon"><i class="fa fa-pencil"></i></div>
                    <div class="se-method-name">يدوي</div>
                    <div class="se-method-desc">تحديد النسب يدوياً لكل محفظة</div>
                </div>
                <div class="se-method-card <?= $model->allocation_method === 'بالتساوي' ? 'active' : '' ?>" data-method="بالتساوي">
                    <div class="se-method-icon"><i class="fa fa-balance-scale"></i></div>
                    <div class="se-method-name">بالتساوي</div>
                    <div class="se-method-desc">توزيع بالتساوي على جميع المحافظ</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: نتائج التوزيع -->
    <div class="se-card">
        <div class="se-card-title">
            <span class="se-step">3</span>
            <i class="fa fa-table"></i> نتائج التوزيع
        </div>
        <div class="se-card-body" style="padding:0;"
             x-data="{ state: '<?= (empty($existingLines) && $model->isNewRecord) ? 'idle' : 'results' ?>' }"
             id="se-results-area">
            <div class="se-loading" id="se-loading"
                 x-show="state === 'loading'" x-cloak
                 x-transition.opacity.duration.300ms>
                <i class="fa fa-spinner"></i>
                <p style="color:#64748b;margin-top:8px;font-size:13px">جاري حساب التوزيع...</p>
            </div>
            <div id="se-results-container">
                <div class="se-results-empty" id="se-results-empty"
                     x-show="state === 'idle' || state === 'empty'" x-cloak
                     x-transition.opacity.duration.300ms>
                    <i class="fa fa-calculator"></i>
                    <p>اختر طريقة التوزيع وحدد المبلغ لعرض النتائج</p>
                </div>
                <div id="se-results-table-wrap"
                     x-show="state === 'results'" x-cloak
                     x-transition.opacity.duration.300ms>
                    <table class="se-results-table">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>المحفظة</th>
                                <th>القيمة المرجعية</th>
                                <th>النسبة %</th>
                                <th>المبلغ الموزّع</th>
                            </tr>
                        </thead>
                        <tbody id="se-results-body">
                            <?php if (!empty($existingLines)): ?>
                                <?php foreach ($existingLines as $i => $line): ?>
                                    <tr>
                                        <td style="color:#94a3b8"><?= $i + 1 ?></td>
                                        <td style="font-weight:600"><?= Html::encode($line->company->name ?? '—') ?></td>
                                        <td><?= number_format($line->metric_value, 2) ?></td>
                                        <td>
                                            <?php if ($model->allocation_method === 'يدوي'): ?>
                                                <input type="number" class="se-pct-input manual-pct" step="0.01" min="0" max="100"
                                                       name="Lines[<?= $i ?>][percentage]" value="<?= $line->percentage ?>"
                                                       data-index="<?= $i ?>">
                                            <?php else: ?>
                                                <?= number_format($line->percentage, 2) ?>%
                                                <input type="hidden" name="Lines[<?= $i ?>][percentage]" value="<?= $line->percentage ?>">
                                            <?php endif ?>
                                        </td>
                                        <td style="font-weight:600;color:var(--se-primary)">
                                            <span class="line-amount"><?= number_format($line->allocated_amount, 2) ?></span>
                                        </td>
                                        <input type="hidden" name="Lines[<?= $i ?>][company_id]" value="<?= $line->company_id ?>">
                                        <input type="hidden" name="Lines[<?= $i ?>][metric_value]" value="<?= $line->metric_value ?>">
                                        <input type="hidden" name="Lines[<?= $i ?>][allocated_amount]" class="line-amount-input" value="<?= $line->allocated_amount ?>">
                                    </tr>
                                <?php endforeach ?>
                            <?php endif ?>
                        </tbody>
                        <tfoot id="se-results-foot">
                            <?php if (!empty($existingLines)): ?>
                                <tr>
                                    <td colspan="3">الإجمالي</td>
                                    <td id="total-pct"><?= number_format(array_sum(array_map(function($l){ return $l->percentage; }, $existingLines)), 2) ?>%</td>
                                    <td id="total-amount" style="color:var(--se-primary)"><?= number_format(array_sum(array_map(function($l){ return $l->allocated_amount; }, $existingLines)), 2) ?></td>
                                </tr>
                            <?php endif ?>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="se-card">
        <div class="se-submit-bar">
            <?= Html::a('إلغاء', ['index'], ['class' => 'btn btn-secondary']) ?>
            <?= Html::submitButton($model->isNewRecord ? '<i class="fa fa-save"></i> حفظ التوزيع' : '<i class="fa fa-save"></i> حفظ التعديلات', [
                'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
                'style' => 'background:var(--se-primary);border-color:var(--se-primary)',
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$calculateUrl = Url::to(['calculate']);
$currentMethod = $model->allocation_method ?: '';
$js = <<<JS
(function() {
    var selectedMethod = '{$currentMethod}';
    var calculateUrl = '{$calculateUrl}';

    function triggerCalculation() {
        var totalAmount = parseFloat($('#total-amount-input').val()) || 0;
        if (!selectedMethod || totalAmount <= 0) return;

        var _seData = Alpine.$data(document.getElementById('se-results-area'));

        /* OLD jQuery - replaced by Alpine.js
        $('#se-loading').show();
        $('#se-results-empty').hide();
        $('#se-results-table-wrap').hide();
        */
        _seData.state = 'loading';

        $.ajax({
            url: calculateUrl,
            type: 'POST',
            data: {
                method: selectedMethod,
                total_amount: totalAmount,
                _csrf: yii.getCsrfToken()
            },
            success: function(res) {
                /* OLD jQuery - replaced by Alpine.js
                $('#se-loading').hide();
                */
                if (res.success && res.lines && res.lines.length > 0) {
                    renderResults(res.lines);
                    /* OLD jQuery - replaced by Alpine.js
                    $('#se-results-table-wrap').show();
                    */
                    _seData.state = 'results';
                } else {
                    $('#se-results-empty').html('<i class="fa fa-exclamation-circle" style="font-size:36px;display:block;margin-bottom:8px;opacity:.5"></i><p>' + (res.message || 'لا توجد بيانات') + '</p>');
                    /* OLD jQuery - replaced by Alpine.js
                    $('#se-results-empty').show();
                    */
                    _seData.state = 'empty';
                }
            },
            error: function() {
                /* OLD jQuery - replaced by Alpine.js
                $('#se-loading').hide();
                */
                $('#se-results-empty').html('<i class="fa fa-exclamation-triangle" style="font-size:36px;display:block;margin-bottom:8px;opacity:.5"></i><p>حدث خطأ أثناء حساب التوزيع</p>');
                /* OLD jQuery - replaced by Alpine.js
                $('#se-results-empty').show();
                */
                _seData.state = 'empty';
            }
        });
    }

    function renderResults(lines) {
        var tbody = $('#se-results-body');
        var tfoot = $('#se-results-foot');
        tbody.empty();
        tfoot.empty();

        var isManual = (selectedMethod === 'يدوي');
        var totalPct = 0, totalAmt = 0;

        $.each(lines, function(i, line) {
            var pctCell = '';
            if (isManual) {
                pctCell = '<input type="number" class="se-pct-input manual-pct" step="0.01" min="0" max="100" ' +
                          'name="Lines[' + i + '][percentage]" value="' + line.percentage + '" data-index="' + i + '">';
            } else {
                pctCell = parseFloat(line.percentage).toFixed(2) + '%' +
                          '<input type="hidden" name="Lines[' + i + '][percentage]" value="' + line.percentage + '">';
            }

            var row = '<tr>' +
                '<td style="color:#94a3b8">' + (i + 1) + '</td>' +
                '<td style="font-weight:600">' + escapeHtml(line.company_name) + '</td>' +
                '<td>' + parseFloat(line.metric_value).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                '<td>' + pctCell + '</td>' +
                '<td style="font-weight:600;color:var(--se-primary)"><span class="line-amount">' + parseFloat(line.allocated_amount).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span></td>' +
                '<input type="hidden" name="Lines[' + i + '][company_id]" value="' + line.company_id + '">' +
                '<input type="hidden" name="Lines[' + i + '][metric_value]" value="' + line.metric_value + '">' +
                '<input type="hidden" name="Lines[' + i + '][allocated_amount]" class="line-amount-input" value="' + line.allocated_amount + '">' +
                '</tr>';

            tbody.append(row);
            totalPct += parseFloat(line.percentage);
            totalAmt += parseFloat(line.allocated_amount);
        });

        tfoot.html('<tr><td colspan="3">الإجمالي</td>' +
            '<td id="total-pct">' + totalPct.toFixed(2) + '%</td>' +
            '<td id="total-amount" style="color:var(--se-primary)">' + totalAmt.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td></tr>');

        if (isManual) {
            bindManualInputs();
        }
    }

    function bindManualInputs() {
        $(document).off('input', '.manual-pct').on('input', '.manual-pct', function() {
            var totalAmount = parseFloat($('#total-amount-input').val()) || 0;
            var totalPct = 0;
            var totalAmt = 0;

            $('.manual-pct').each(function() {
                var pct = parseFloat($(this).val()) || 0;
                var amt = (pct / 100) * totalAmount;
                amt = Math.round(amt * 100) / 100;
                var row = $(this).closest('tr');
                row.find('.line-amount').text(amt.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}));
                row.find('.line-amount-input').val(amt);
                totalPct += pct;
                totalAmt += amt;
            });

            $('#total-pct').text(totalPct.toFixed(2) + '%');
            $('#total-amount').text(totalAmt.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}));
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // Method card selection
    $(document).on('click', '.se-method-card', function() {
        $('.se-method-card').removeClass('active');
        $(this).addClass('active');
        selectedMethod = $(this).data('method');
        $('#allocation-method-input').val(selectedMethod);
        triggerCalculation();
    });

    // Total amount change
    var debounceTimer;
    $('#total-amount-input').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            if (selectedMethod) {
                if (selectedMethod === 'يدوي') {
                    // For manual, just recalculate amounts based on percentages
                    var totalAmount = parseFloat($('#total-amount-input').val()) || 0;
                    if ($('.manual-pct').length > 0) {
                        $('.manual-pct').trigger('input');
                    } else {
                        triggerCalculation();
                    }
                } else {
                    triggerCalculation();
                }
            }
        }, 500);
    });

    // Bind manual inputs if already loaded
    if ($('.manual-pct').length > 0) {
        bindManualInputs();
    }
})();
JS;
$this->registerJs($js);
?>
