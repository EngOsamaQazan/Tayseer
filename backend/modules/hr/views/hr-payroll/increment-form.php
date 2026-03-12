<?php
/**
 * نموذج إنشاء علاوة سنوية
 *
 * @var $model \backend\modules\hr\models\HrAnnualIncrement
 * @var $employeeList array
 * @var $serviceYearRange array [1=>1, 2=>2, ...]
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'إنشاء علاوة سنوية جديدة';
?>

<style>
.inc-form-page { padding: 20px; max-width: 700px; }
.inc-form-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.inc-form-card .form-group label {
    font-weight: 600; color: #334155; font-size: 13px;
}
.inc-form-card .form-control {
    border-radius: 8px; border-color: #d1d5db;
}
.inc-form-card .form-control:focus {
    border-color: #800020; box-shadow: 0 0 0 3px rgba(128,0,32,0.1);
}
.inc-form-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
}
.inc-form-header h1 { font-size: 20px; font-weight: 700; color: #800020; margin: 0; }
.inc-type-hint {
    background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;
    padding: 10px 14px; font-size: 12px; color: #1e40af; margin-top: 8px;
}
.inc-type-hint i { margin-left: 4px; }
</style>

<div class="inc-form-page">
    <div class="inc-form-header">
        <h1><i class="fa fa-plus-circle"></i> <?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['increments'], ['class' => 'btn btn-default btn-sm', 'style' => 'border-radius:8px']) ?>
    </div>

    <div class="inc-form-card">
        <?php $form = ActiveForm::begin(['id' => 'increment-form']); ?>

        <?= $form->field($model, 'user_id')->dropDownList($employeeList, [
            'prompt' => '— اختر الموظف —',
        ]) ?>

        <?php
        $serviceYearRange = $serviceYearRange ?? array_combine(range(1, 50), range(1, 50));
        $serviceYearLabels = [];
        foreach (array_keys($serviceYearRange) as $n) {
            $ord = [1 => 'الأولى', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة', 5 => 'الخامسة', 6 => 'السادسة', 7 => 'السابعة', 8 => 'الثامنة', 9 => 'التاسعة', 10 => 'العاشرة'];
            $serviceYearLabels[$n] = 'سنة الخدمة ' . ($ord[$n] ?? '#' . $n) . ' (' . $n . ')';
        }
        ?>
        <?= $form->field($model, 'service_year')->dropDownList(
            $serviceYearLabels,
            ['prompt' => '— اختر سنة الخدمة —']
        )->hint('العلاوة تُمنح حسب عدد سنوات الخدمة (1 = أول سنة، 2 = ثاني سنة، ...) وليس حسب السنة التقويمية.') ?>

        <?= $form->field($model, 'increment_type')->dropDownList([
            'fixed' => 'مبلغ ثابت (بالدينار)',
            'percentage' => 'نسبة مئوية من الراتب الأساسي (%)',
        ], ['prompt' => '— اختر النوع —']) ?>

        <div class="inc-type-hint">
            <i class="fa fa-info-circle"></i>
            <strong>مبلغ ثابت:</strong> مثلاً 50 يعني زيادة 50 دينار على الراتب الأساسي.
            <br>
            <i class="fa fa-info-circle"></i>
            <strong>نسبة مئوية:</strong> مثلاً 10 يعني زيادة 10% من الراتب الأساسي الحالي.
        </div>

        <?= $form->field($model, 'amount')->textInput([
            'type' => 'number',
            'step' => '0.01',
            'min' => '0',
            'placeholder' => 'المبلغ بالدينار أو النسبة المئوية',
        ])->label('المبلغ / النسبة') ?>

        <?= $form->field($model, 'effective_date')->textInput([
            'type' => 'date',
            'value' => $model->effective_date ?: date('Y-01-01'),
        ]) ?>

        <?= $form->field($model, 'notes')->textarea(['rows' => 3, 'placeholder' => 'ملاحظات اختيارية...']) ?>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
            <?= Html::a('إلغاء', ['increments'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px']) ?>
            <?= Html::submitButton('<i class="fa fa-save"></i> إنشاء العلاوة', [
                'class' => 'btn',
                'style' => 'background:#800020;color:#fff;border-radius:8px',
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
