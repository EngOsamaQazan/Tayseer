<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\modules\sms\models\Sms */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'type')->dropDownList([1=>'استحقاق واحد', 2=>'استحقاقين']) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
        <?= $form->field($model, 'massage')->textarea() ?>
        </div>
    </div>

        <?php if (!Yii::$app->request->isAjax) { ?>
            <div class="form-group">
                <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
        <?php } ?>

        <?php ActiveForm::end(); ?>

    </div>
