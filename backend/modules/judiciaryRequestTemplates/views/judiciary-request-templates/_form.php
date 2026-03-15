<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\models\JudiciaryRequestTemplate;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryRequestTemplate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="judiciary-request-template-form">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'jadal-form'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'control-label'],
        ],
    ]); ?>

    <div class="box box-primary">
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'اسم القالب']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'template_type')->dropDownList(
                        JudiciaryRequestTemplate::getTypeLabels(),
                        ['prompt' => 'اختر نوع القالب...']
                    ) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'template_content')->textarea([
                        'rows' => 15,
                        'placeholder' => 'محتوى القالب...',
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'is_combinable')->checkbox() ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
                </div>
            </div>
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

</div>
