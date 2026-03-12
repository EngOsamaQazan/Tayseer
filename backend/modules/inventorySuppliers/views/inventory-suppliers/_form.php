<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\User;
use backend\modules\companies\models\Companies;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model common\models\InventorySuppliers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'company_id')->widget(Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(Companies::find()->all(), 'id', 'name'),
                'language' => 'de',
                'options' => ['placeholder' => 'Select a company.'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'adress')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
