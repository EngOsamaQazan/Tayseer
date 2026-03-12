<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Lawyers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <?php
    if (isset($id)) {
        $form = ActiveForm::begin(['action' => Url::to(['update', 'id' => $id]), 'options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    } else {
        $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    }
    ?>
   

    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-6">
            <?=
            $form->field($model, 'status')->widget(kartik\select2\Select2::classname(), [
                'data' => [Yii::t('app', 'Active'), Yii::t('app', 'None Active')],
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a status.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
    </div>
    <div class = "row">
    <div class="col-lg-6">
            <?=
            $form->field($model, 'type')->widget(kartik\select2\Select2::classname(), [
                'data' => Yii::$app->params["lawyer_type"],
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a status.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
        <div>
        <?php
    
    echo $form->field($model, 'image[]')->fileInput(['multiple' => true, 'accept' => 'image/*']) ?>
        </div>
        </div class="col-lg-6">
   
    <div class="row">
        <div class="col-lg-12">
            <?= $form->field($model, 'notes')->textarea(['rows' => 6]) ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
