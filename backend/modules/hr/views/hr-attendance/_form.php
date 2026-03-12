<?php
/**
 * نموذج إدخال الحضور اليدوي — Manual Attendance Entry Form
 *
 * @var $model \backend\modules\hr\models\HrAttendance
 * @var $employees array [id => name]
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use kartik\select2\Select2;

?>

<style>
.hr-form-wrap { padding: 10px 0; }
.hr-form-wrap .form-group { margin-bottom: 16px; }
.hr-form-wrap label { font-weight: 600; font-size: 13px; color: var(--clr-text, #212529); }
.hr-form-wrap .help-block { font-size: 11px; color: #dc3545; }
.hr-form-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}
@media (max-width: 576px) { .hr-form-row { grid-template-columns: 1fr; } }
.hr-form-actions {
    display: flex; gap: 8px; justify-content: flex-end;
    padding-top: 16px; border-top: 1px solid #eee; margin-top: 10px;
}
</style>

<div class="hr-form-wrap">
    <?php $form = ActiveForm::begin([
        'id' => 'attendance-form',
        'enableAjaxValidation' => false,
        'options' => ['class' => 'hr-attendance-form'],
    ]); ?>

    <!-- الموظف -->
    <?= $form->field($model, 'user_id')->widget(Select2::class, [
        'data' => $employees ?? [],
        'options' => [
            'placeholder' => 'اختر الموظف...',
            'dir' => 'rtl',
        ],
        'pluginOptions' => [
            'allowClear' => true,
            'width' => '100%',
        ],
    ])->label('الموظف') ?>

    <!-- التاريخ -->
    <?= $form->field($model, 'attendance_date')->widget(FlatpickrWidget::class, [
        'value' => $model->attendance_date ?: date('Y-m-d'),
        'pluginOptions' => [
            'dateFormat' => 'Y-m-d',
        ],
        'options' => ['class' => 'form-control'],
    ])->label('تاريخ الحضور') ?>

    <!-- أوقات الدخول والخروج -->
    <div class="hr-form-row">
        <div class="form-group">
            <label for="hrattendance-check_in_time">وقت الدخول</label>
            <?= Html::activeInput('time', $model, 'check_in_time', [
                'class' => 'form-control',
                'id' => 'hrattendance-check_in_time',
            ]) ?>
        </div>
        <div class="form-group">
            <label for="hrattendance-check_out_time">وقت الخروج</label>
            <?= Html::activeInput('time', $model, 'check_out_time', [
                'class' => 'form-control',
                'id' => 'hrattendance-check_out_time',
            ]) ?>
        </div>
    </div>

    <!-- الحالة -->
    <?= $form->field($model, 'status')->dropDownList([
        'present'    => 'حاضر',
        'absent'     => 'غائب',
        'leave'      => 'إجازة',
        'holiday'    => 'عطلة',
        'half_day'   => 'نصف يوم',
        'remote'     => 'عن بُعد',
    ], [
        'prompt' => '— اختر الحالة —',
        'class' => 'form-control',
    ])->label('الحالة') ?>

    <!-- ملاحظات -->
    <?= $form->field($model, 'notes')->textarea([
        'rows' => 3,
        'placeholder' => 'ملاحظات إضافية...',
        'class' => 'form-control',
        'style' => 'resize:vertical',
    ])->label('ملاحظات') ?>

    <!-- سبب التعديل -->
    <div class="form-group">
        <label for="adjustment_reason">سبب الإدخال اليدوي</label>
        <?= Html::textInput('adjustment_reason', 'إدخال يدوي', [
            'class' => 'form-control',
            'id' => 'adjustment_reason',
            'placeholder' => 'سبب التعديل...',
        ]) ?>
    </div>

    <!-- أزرار -->
    <div class="hr-form-actions">
        <?= Html::submitButton(
            '<i class="fa fa-save"></i> ' . ($model->isNewRecord ? 'حفظ' : 'تحديث'),
            ['class' => 'btn btn-primary']
        ) ?>
        <?= Html::button('<i class="fa fa-times"></i> إلغاء', [
            'class' => 'btn btn-default',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
