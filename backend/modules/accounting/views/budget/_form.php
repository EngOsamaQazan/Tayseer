<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\modules\accounting\models\FiscalYear;

$fyList = ArrayHelper::map(
    FiscalYear::find()->orderBy(['start_date' => SORT_DESC])->all(),
    'id', 'name'
);

$form = ActiveForm::begin([
    'id' => 'budget-form',
    'options' => ['class' => 'jadal-form'],
]);
?>

<div class="box box-primary">
    <div class="box-body">
        <fieldset class="jadal-fieldset">
            <legend><i class="fa fa-pie-chart"></i> بيانات الموازنة</legend>
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'مثال: موازنة 2026']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'fiscal_year_id')->dropDownList($fyList, ['prompt' => '-- اختر السنة المالية --', 'class' => 'form-control']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'notes')->textarea(['rows' => 3, 'placeholder' => 'ملاحظات حول الموازنة...']) ?>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="box-footer jadal-form-actions">
        <?= Html::submitButton(
            $model->isNewRecord ? '<i class="fa fa-save"></i> إنشاء الموازنة' : '<i class="fa fa-check"></i> تحديث',
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        ) ?>
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
