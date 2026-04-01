<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\CostCenter;
use backend\modules\accounting\models\FiscalYear;

$accounts = Account::getLeafAccounts();
$costCenters = CostCenter::getDropdownList();
$fiscalYears = ArrayHelper::map(
    FiscalYear::find()->where(['status' => 'open'])->orderBy(['start_date' => SORT_DESC])->all(),
    'id',
    'name'
);

$form = ActiveForm::begin([
    'id' => 'journal-entry-form',
    'options' => ['class' => 'jadal-form'],
]);
?>

<div class="box box-primary">
    <div class="box-body">
        <fieldset class="jadal-fieldset">
            <legend><i class="fa fa-book"></i> بيانات القيد</legend>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'entry_date')->textInput(['type' => 'date', 'value' => $model->entry_date ?: date('Y-m-d')]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'fiscal_year_id')->dropDownList($fiscalYears, ['prompt' => '-- اختر السنة المالية --', 'class' => 'form-control'])->hint('يُحدد تلقائيا من التاريخ') ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'description')->textInput(['placeholder' => 'بيان القيد...', 'maxlength' => true]) ?>
                </div>
            </div>
        </fieldset>

        <fieldset class="jadal-fieldset">
            <legend><i class="fa fa-list"></i> بنود القيد</legend>

            <div class="alert alert-info" style="padding:8px 12px; margin-bottom:10px;">
                <i class="fa fa-balance-scale"></i>
                <strong>القاعدة الذهبية:</strong> مجموع المدين يجب أن يساوي مجموع الدائن.
                <span id="balance-indicator" class="pull-left" style="font-weight:700;"></span>
            </div>

            <table class="table table-bordered table-condensed" id="lines-table">
                <thead>
                    <tr style="background:#f5f6f8;">
                        <th class="text-center" style="width:30%;">الحساب</th>
                        <th class="text-center" style="width:15%;">مركز التكلفة</th>
                        <th class="text-center" style="width:15%;">مدين</th>
                        <th class="text-center" style="width:15%;">دائن</th>
                        <th class="text-center" style="width:20%;">البيان</th>
                        <th class="text-center" style="width:5%;"></th>
                    </tr>
                </thead>
                <tbody id="lines-body">
                    <?php foreach ($lines as $i => $line): ?>
                    <tr class="line-row">
                        <td>
                            <select name="JournalEntryLine[<?= $i ?>][account_id]" class="form-control select2-account" required>
                                <option value="">اختر الحساب...</option>
                                <?php foreach ($accounts as $accId => $accName): ?>
                                <option value="<?= $accId ?>" <?= $line->account_id == $accId ? 'selected' : '' ?>><?= Html::encode($accName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="JournalEntryLine[<?= $i ?>][cost_center_id]" class="form-control">
                                <option value="">—</option>
                                <?php foreach ($costCenters as $ccId => $ccName): ?>
                                <option value="<?= $ccId ?>" <?= $line->cost_center_id == $ccId ? 'selected' : '' ?>><?= Html::encode($ccName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="JournalEntryLine[<?= $i ?>][debit]" class="form-control debit-input text-left" value="<?= $line->debit ?: '' ?>" placeholder="0.00"></td>
                        <td><input type="number" step="0.01" min="0" name="JournalEntryLine[<?= $i ?>][credit]" class="form-control credit-input text-left" value="<?= $line->credit ?: '' ?>" placeholder="0.00"></td>
                        <td><input type="text" name="JournalEntryLine[<?= $i ?>][description]" class="form-control" value="<?= Html::encode($line->description) ?>" placeholder="بيان البند"></td>
                        <td class="text-center">
                            <?php if ($i > 0): ?>
                            <button type="button" class="btn btn-danger btn-xs remove-line"><i class="fa fa-times"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f5f6f8; font-weight:800;">
                        <td colspan="2" class="text-left">المجموع</td>
                        <td class="text-left" id="total-debit">0.00</td>
                        <td class="text-left" id="total-credit">0.00</td>
                        <td colspan="2">
                            <span id="difference-label" class="text-muted">الفرق: 0.00</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <button type="button" class="btn btn-secondary btn-sm" id="add-line">
                                <i class="fa fa-plus"></i> إضافة بند
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>
    </div>
    <div class="box-footer jadal-form-actions">
        <?= Html::submitButton(
            $model->isNewRecord ? '<i class="fa fa-save"></i> حفظ كمسودة' : '<i class="fa fa-check"></i> تحديث',
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        ) ?>
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>

<?php
$accountsJson = json_encode($accounts, JSON_UNESCAPED_UNICODE);
$costCentersJson = json_encode($costCenters, JSON_UNESCAPED_UNICODE);

$js = <<<JS
var lineIndex = {$i};
var accounts = {$accountsJson};
var costCenters = {$costCentersJson};

function buildOptions(data, selected) {
    var html = '<option value="">—</option>';
    for (var key in data) {
        var sel = (key == selected) ? ' selected' : '';
        html += '<option value="' + key + '"' + sel + '>' + data[key] + '</option>';
    }
    return html;
}

$('#add-line').on('click', function() {
    lineIndex++;
    var accOpts = '<option value="">اختر الحساب...</option>';
    for (var key in accounts) { accOpts += '<option value="' + key + '">' + accounts[key] + '</option>'; }
    var ccOpts = buildOptions(costCenters, '');
    var row = '<tr class="line-row">' +
        '<td><select name="JournalEntryLine['+lineIndex+'][account_id]" class="form-control" required>'+accOpts+'</select></td>' +
        '<td><select name="JournalEntryLine['+lineIndex+'][cost_center_id]" class="form-control">'+ccOpts+'</select></td>' +
        '<td><input type="number" step="0.01" min="0" name="JournalEntryLine['+lineIndex+'][debit]" class="form-control debit-input text-left" placeholder="0.00"></td>' +
        '<td><input type="number" step="0.01" min="0" name="JournalEntryLine['+lineIndex+'][credit]" class="form-control credit-input text-left" placeholder="0.00"></td>' +
        '<td><input type="text" name="JournalEntryLine['+lineIndex+'][description]" class="form-control" placeholder="بيان البند"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-danger btn-xs remove-line"><i class="fa fa-times"></i></button></td>' +
        '</tr>';
    $('#lines-body').append(row);
    recalculate();
});

$(document).on('click', '.remove-line', function() {
    $(this).closest('tr').remove();
    recalculate();
});

$(document).on('input', '.debit-input, .credit-input', function() {
    var row = $(this).closest('tr');
    if ($(this).hasClass('debit-input') && parseFloat($(this).val()) > 0) {
        row.find('.credit-input').val('');
    } else if ($(this).hasClass('credit-input') && parseFloat($(this).val()) > 0) {
        row.find('.debit-input').val('');
    }
    recalculate();
});

function recalculate() {
    var totalDebit = 0, totalCredit = 0;
    $('.debit-input').each(function() { totalDebit += parseFloat($(this).val()) || 0; });
    $('.credit-input').each(function() { totalCredit += parseFloat($(this).val()) || 0; });
    $('#total-debit').text(totalDebit.toFixed(2));
    $('#total-credit').text(totalCredit.toFixed(2));
    var diff = Math.abs(totalDebit - totalCredit);
    $('#difference-label').text('الفرق: ' + diff.toFixed(2));
    if (diff < 0.005 && totalDebit > 0) {
        $('#balance-indicator').html('<span class="text-success"><i class="fa fa-check-circle"></i> متوازن</span>');
        $('#difference-label').removeClass('text-danger').addClass('text-success');
    } else {
        $('#balance-indicator').html('<span class="text-danger"><i class="fa fa-times-circle"></i> غير متوازن</span>');
        $('#difference-label').removeClass('text-success').addClass('text-danger');
    }
}

recalculate();
JS;

$this->registerJs($js);
?>
