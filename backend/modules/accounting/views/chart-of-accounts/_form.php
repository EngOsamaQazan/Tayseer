<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\modules\accounting\models\Account;

$form = ActiveForm::begin([
    'id' => 'account-form',
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
            <legend><i class="fa fa-info-circle"></i> بيانات الحساب</legend>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'code')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'مثال: 1101',
                        'style' => 'font-family:monospace; font-size:16px; font-weight:700;',
                    ]) ?>
                </div>
                <div class="col-md-5">
                    <?= $form->field($model, 'name_ar')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'اسم الحساب بالعربية',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'name_en')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Account Name (English)',
                        'dir' => 'ltr',
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'parent_id')->widget(Select2::class, [
                        'data' => Account::getParentDropdownList($model->id),
                        'options' => ['placeholder' => 'بدون (حساب رئيسي)'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'type')->widget(Select2::class, [
                        'data' => Account::getTypes(),
                        'options' => ['placeholder' => 'اختر نوع الحساب...'],
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                        'disabled' => $model->parent_id ? true : false,
                    ])->hint($model->parent_id ? 'يُحدد تلقائيا من الحساب الرئيسي' : '') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'nature')->widget(Select2::class, [
                        'data' => Account::getNatures(),
                        'options' => ['placeholder' => 'اختر طبيعة الحساب...'],
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                        'disabled' => $model->parent_id ? true : false,
                    ])->hint($model->parent_id ? 'يُحدد تلقائيا من الحساب الرئيسي' : '') ?>
                </div>
            </div>
        </fieldset>

        <fieldset class="jadal-fieldset">
            <legend><i class="fa fa-cog"></i> إعدادات الحساب</legend>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'opening_balance')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'placeholder' => '0.00',
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'is_parent')->widget(Select2::class, [
                        'data' => [0 => 'لا (حساب فرعي)', 1 => 'نعم (حساب رئيسي)'],
                        'options' => ['placeholder' => 'اختر...'],
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                    ])->hint('الحساب الرئيسي لا يقبل قيود مباشرة') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'is_active')->widget(Select2::class, [
                        'data' => [1 => 'فعال', 0 => 'غير فعال'],
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'description')->textarea([
                        'rows' => 3,
                        'placeholder' => 'وصف اختياري للحساب...',
                    ]) ?>
                </div>
            </div>
        </fieldset>
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
