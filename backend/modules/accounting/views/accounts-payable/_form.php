<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\Payable;

$accounts = Account::getLeafAccounts();

$form = ActiveForm::begin([
    'id' => 'payable-form',
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
            <legend><i class="fa fa-arrow-circle-up"></i> بيانات الذمة الدائنة</legend>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'vendor_name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'اسم المورد أو الجهة',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'account_id')->dropDownList($accounts, ['prompt' => '-- اختر الحساب --', 'class' => 'form-control']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'amount')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'placeholder' => '0.00']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'due_date')->textInput(['type' => 'date']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'category')->dropDownList(Payable::getCategories(), ['prompt' => '-- اختر التصنيف --', 'class' => 'form-control']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'reference_number')->textInput(['placeholder' => 'رقم الفاتورة أو المرجع']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'status')->dropDownList(Payable::getStatuses(), ['prompt' => '-- الحالة --', 'class' => 'form-control']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'description')->textarea(['rows' => 2, 'placeholder' => 'وصف الذمة...']) ?>
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
