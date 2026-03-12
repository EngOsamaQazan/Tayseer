<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

/* @var $this yii\web\View */
/* @var $model common\models\Holidays */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'start_at')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                'dateFormat' => 'Y-m-d',
            ]]); ?>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'end_at')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d',
                ]]); ?>
            </div>
        </div>


        <?php if (!Yii::$app->request->isAjax) { ?>
            <div class="form-group">
                <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
        <?php } ?>

        <?php ActiveForm::end(); ?>

    </div>
