<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

/* @var $this yii\web\View */
/* @var $model backend\modules\incomeCategory\models\IncomeCategory */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">


    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, '_by')->textInput() ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'amount')->textInput() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?=
            $form->field($model, 'payment_type')->widget(kartik\select2\Select2::classname(), [
                'data' => \yii\helpers\ArrayHelper::map(\backend\modules\paymentType\models\PaymentType::find()->all(),'id','name'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a type.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'receipt_bank')->textInput() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'payment_purpose')->textInput() ?>
        </div>
        <div class="col-lg-6">
            <?=
            $form->field($model, 'date')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                'dateFormat' => 'Y-m-d'
            ]]);
            ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'type')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(\backend\modules\incomeCategory\models\IncomeCategory::find()->all(), 'id', 'name'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a contract.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'document_number')->textInput() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'contract_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(\backend\modules\contracts\models\Contracts::find()->all(), 'id', 'id'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a contract.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <?= $form->field($model, 'notes')->textarea(['row' => 6,'col'=>6]) ?>

        </div>

    </div>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
