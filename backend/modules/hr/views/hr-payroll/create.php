<?php
/**
 * إنشاء مسيرة رواتب جديدة — New Payroll Run
 *
 * @var $model \backend\modules\hr\models\HrPayrollRun
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'إنشاء مسيرة رواتب جديدة';

$arabicMonths = [
    1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',
    4  => 'أبريل',  5  => 'مايو',   6  => 'يونيو',
    7  => 'يوليو',  8  => 'أغسطس',  9  => 'سبتمبر',
    10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
];
?>

<style>
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}

.hr-form-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm);
    padding: 28px; max-width: 600px;
}
.hr-form-card .form-group { margin-bottom: 18px; }
.hr-form-card label {
    font-weight: 600; font-size: 13px; color: var(--clr-text, #212529);
}
.hr-form-card .help-block { font-size: 11px; color: #dc3545; }

.hr-form-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}
@media (max-width: 576px) { .hr-form-row { grid-template-columns: 1fr; } }

.hr-form-actions {
    display: flex; gap: 8px; padding-top: 18px;
    border-top: 1px solid var(--clr-border, #eee); margin-top: 10px;
}
</style>

<div class="hr-page">
    <!-- Header -->
    <div class="hr-page-header">
        <h1><i class="fa fa-plus-circle"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <!-- Form Card -->
    <div class="hr-form-card">
        <?php $form = ActiveForm::begin([
            'id' => 'payroll-run-form',
            'enableAjaxValidation' => false,
        ]); ?>

        <!-- الشهر والسنة -->
        <div class="hr-form-row">
            <?= $form->field($model, 'period_month')->dropDownList($arabicMonths, [
                'prompt' => '— اختر الشهر —',
                'class' => 'form-control',
                'value' => $model->period_month ?: date('m'),
            ])->label('الشهر') ?>

            <?= $form->field($model, 'period_year')->dropDownList(
                array_combine(
                    range(date('Y') - 2, date('Y') + 1),
                    range(date('Y') - 2, date('Y') + 1)
                ),
                [
                    'class' => 'form-control',
                    'value' => $model->period_year ?: date('Y'),
                ]
            )->label('السنة') ?>
        </div>

        <!-- رمز المسيرة (auto-generated, hidden) -->
        <?= $form->field($model, 'run_code')->hiddenInput([
            'value' => $model->run_code ?: 'PAY-' . date('Y') . '-' . str_pad(date('m'), 2, '0', STR_PAD_LEFT),
        ])->label(false) ?>

        <!-- ملاحظات -->
        <?= $form->field($model, 'notes')->textarea([
            'rows' => 3,
            'placeholder' => 'ملاحظات حول هذه المسيرة...',
            'class' => 'form-control',
            'style' => 'resize:vertical',
        ])->label('ملاحظات') ?>

        <!-- Buttons -->
        <div class="hr-form-actions">
            <?= Html::submitButton('<i class="fa fa-save"></i> إنشاء المسيرة', [
                'class' => 'btn btn-primary',
            ]) ?>
            <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], [
                'class' => 'btn btn-default',
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
// Auto-generate run_code when month/year changes
$js = <<<JS
$('#hrpayrollrun-period_month, #hrpayrollrun-period_year').on('change', function() {
    var m = $('#hrpayrollrun-period_month').val();
    var y = $('#hrpayrollrun-period_year').val();
    if (m && y) {
        m = ('0' + m).slice(-2);
        $('#hrpayrollrun-run_code').val('PAY-' + y + '-' + m);
    }
});
JS;
$this->registerJs($js);
?>
