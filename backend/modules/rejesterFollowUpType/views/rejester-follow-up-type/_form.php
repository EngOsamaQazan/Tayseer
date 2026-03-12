<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\RejesterFollowUpType */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-10">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-2" style="margin-top: 27px">
            <?php if (!Yii::$app->request->isAjax) { ?>
                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            <?php } ?>
        </div>
        <?php ActiveForm::end(); ?>

    </div>
