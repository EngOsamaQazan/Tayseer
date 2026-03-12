<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

/* @var $this yii\web\View */
/* @var $model common\models\Workdays */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <div class="row">

        <?php $form = ActiveForm::begin(); ?>
        <div class="col-md-6">
            <?= $form->field($model, 'day_name')->dropDownList(['Mondays' => 'Mondays', 'Tuesdays' => 'Tuesdays', 'Wednesdays' => 'Wednesdays', 'Thursdays' => 'Thursdays', 'Fridays' => 'Fridays', 'Saturdays' => 'Saturdays', 'Sundays' => 'Sundays',], ['prompt' => '']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'start_at')->widget(FlatpickrWidget::class, [
                'pluginOptions' => ['enableTime' => true, 'noCalendar' => true, 'dateFormat' => 'H:i', 'time_24hr' => true],
                'options' => ['class' => 'form-control', 'placeholder' => 'HH:MM'],
            ]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'end_at')->widget(FlatpickrWidget::class, [
                'pluginOptions' => ['enableTime' => true, 'noCalendar' => true, 'dateFormat' => 'H:i', 'time_24hr' => true],
                'options' => ['class' => 'form-control', 'placeholder' => 'HH:MM'],
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'status')->dropDownList(['working_day' => 'Working day', 'day_off' => 'Day off',], ['prompt' => '']) ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
