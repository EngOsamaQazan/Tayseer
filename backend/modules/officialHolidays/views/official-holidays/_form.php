<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\models\Holiday;

/* @var $this yii\web\View */
/* @var $model backend\models\Holiday */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="official-holidays-form">
    <div class="box box-primary">
        <div class="box-body">
            <?php $form = ActiveForm::begin([
                'id' => 'official-holiday-form',
                'options' => ['class' => ''],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{hint}\n{error}",
                    'labelOptions' => ['class' => 'control-label'],
                ],
            ]); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'holiday_date')->input('date', [
                        'class' => 'form-control',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'year')->input('number', [
                        'class' => 'form-control',
                        'min' => 2000,
                        'max' => 2100,
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'source')->dropDownList([
                        Holiday::SOURCE_MANUAL => 'يدوي',
                        Holiday::SOURCE_API => 'تلقائي (API)',
                    ], ['prompt' => 'اختر المصدر']) ?>
                </div>
            </div>

            <div class="form-group">
                <?= Html::submitButton(
                        $model->isNewRecord ? '<i class="fa fa-save"></i> حفظ' : '<i class="fa fa-check"></i> تحديث',
                        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
                    ) ?>
                <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
