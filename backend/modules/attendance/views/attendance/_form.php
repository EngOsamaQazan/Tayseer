<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use backend\models\Employee;
use backend\helpers\FlatpickrWidget;
use backend\modules\location\models\Location;

/* @var $this yii\web\View */
/* @var $model common\models\Attendance */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="attendance-form">

    <?php $form = ActiveForm::begin(); ?>

    <?=
    $form->field($model, 'user_id')->widget(Select2::classname(), [
        'data' => yii\helpers\ArrayHelper::map(Employee::find()->all(),'id','username'),
        'options' => ['placeholder' => 'Select a state ...'],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ])
    ?>
    
    <?=
    $form->field($model, 'location_id')->widget(Select2::classname(), [
        'data' => yii\helpers\ArrayHelper::map(Location::find()->all(),'id','location'),
        'options' => ['placeholder' => 'Select a state ...'],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ])
    ?>
    
    <?= $form->field($model, 'check_in_time')->widget(FlatpickrWidget::class, [
        'pluginOptions' => ['enableTime' => true, 'noCalendar' => true, 'dateFormat' => 'H:i', 'time_24hr' => true],
        'options' => ['class' => 'form-control', 'placeholder' => 'HH:MM'],
    ]) ?>
    
    <?= $form->field($model, 'check_out_time')->widget(FlatpickrWidget::class, [
        'pluginOptions' => ['enableTime' => true, 'noCalendar' => true, 'dateFormat' => 'H:i', 'time_24hr' => true],
        'options' => ['class' => 'form-control', 'placeholder' => 'HH:MM'],
    ]) ?>

    <?= $form->field($model, 'is_manual_actions')->dropDownList([ 'yes' => 'Yes', 'no' => 'No',], ['prompt' => '']) ?>


    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
