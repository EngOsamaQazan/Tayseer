<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
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
                    <?= $form->field($model, 'parent_id')->dropDownList(Account::getParentDropdownList($model->id), ['prompt' => '-- بدون (حساب رئيسي) --', 'class' => 'form-control']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'type')->dropDownList(Account::getTypes(), ['prompt' => '-- اختر نوع الحساب --', 'class' => 'form-control', 'disabled' => $model->parent_id ? true : false])->hint($model->parent_id ? 'يُحدد تلقائيا من الحساب الرئيسي' : '') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'nature')->dropDownList(Account::getNatures(), ['prompt' => '-- اختر طبيعة الحساب --', 'class' => 'form-control', 'disabled' => $model->parent_id ? true : false])->hint($model->parent_id ? 'يُحدد تلقائيا من الحساب الرئيسي' : '') ?>
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
                    <?= $form->field($model, 'is_parent')->dropDownList([0 => 'لا (حساب فرعي)', 1 => 'نعم (حساب رئيسي)'], ['prompt' => '-- اختر --', 'class' => 'form-control'])->hint('الحساب الرئيسي لا يقبل قيود مباشرة') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'is_active')->dropDownList([1 => 'فعال', 0 => 'غير فعال'], ['prompt' => '-- الحالة --', 'class' => 'form-control']) ?>
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
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
