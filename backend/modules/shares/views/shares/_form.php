<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\helpers\FlatpickrWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Shares */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="shares-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'shareholder_id')->dropDownList(yii\helpers\ArrayHelper::map(app\models\Shareholders::find()->asArray()->all(), 'id', 'name'))->label('share holder name'); ?>

    <?= $form->field($model, 'amount')->textInput() ?>

      <?= $form->field($model, 'date')->widget(FlatpickrWidget::class, [
    'options' => ['placeholder' => 'Enter payment date ...'],
    'pluginOptions' => [
        'dateFormat' => 'Y-m-d'
    ]
]);?>

    <?= $form->field($model, 'receive_number')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'notes')->textarea() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
