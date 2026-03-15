<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\models\JudiciaryAuthority;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryAuthority */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="judiciary-authorities-form">
    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'authority_type')->dropDownList(JudiciaryAuthority::getTypeList(), ['prompt' => '— اختر نوع الجهة —']) ?>
            <?= $form->field($model, 'notes')->textarea(['rows' => 4]) ?>
            <div class="form-group">
                <?= Html::submitButton($model->isNewRecord ? 'حفظ' : 'تحديث', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
