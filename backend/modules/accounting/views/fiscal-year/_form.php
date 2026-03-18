<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\modules\accounting\models\FiscalYear;

$form = ActiveForm::begin([
    'id' => 'fiscal-year-form',
    'options' => ['class' => 'jadal-form'],
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{hint}\n{error}",
        'labelOptions' => ['class' => 'control-label'],
    ],
]);
?>

<div class="box box-primary">
    <div class="box-body">
        <fieldset class="jadal-fieldset">
            <legend><i class="fa fa-calendar"></i> بيانات السنة المالية</legend>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'مثال: 2026',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'start_date')->textInput([
                        'type' => 'date',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'end_date')->textInput([
                        'type' => 'date',
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'status')->widget(Select2::class, [
                        'data' => FiscalYear::getStatuses(),
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'is_current')->widget(Select2::class, [
                        'data' => [0 => 'لا', 1 => 'نعم'],
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                    ])->hint('عند اختيار "نعم" سيتم إلغاء السنة الحالية السابقة') ?>
                </div>
            </div>
        </fieldset>

        <?php if ($model->isNewRecord): ?>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            سيتم تلقائيا توليد 12 فترة شهرية عند حفظ السنة المالية.
        </div>
        <?php endif; ?>
    </div>
    <div class="box-footer jadal-form-actions">
        <?= Html::submitButton(
            $model->isNewRecord ? '<i class="fa fa-save"></i> حفظ' : '<i class="fa fa-check"></i> تحديث',
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        ) ?>
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
