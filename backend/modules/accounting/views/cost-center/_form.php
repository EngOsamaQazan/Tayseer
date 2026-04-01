<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\accounting\models\CostCenter;

$form = ActiveForm::begin([
    'id' => 'cost-center-form',
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
            <legend><i class="fa fa-building"></i> بيانات مركز التكلفة</legend>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'code')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'مثال: CC01',
                        'style' => 'font-family:monospace; font-weight:700;',
                    ]) ?>
                </div>
                <div class="col-md-5">
                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'اسم مركز التكلفة',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'parent_id')->dropDownList(CostCenter::getDropdownList(), ['prompt' => '-- بدون (مركز رئيسي) --', 'class' => 'form-control']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'is_active')->dropDownList([1 => 'فعال', 0 => 'غير فعال'], ['prompt' => '-- الحالة --', 'class' => 'form-control']) ?>
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
