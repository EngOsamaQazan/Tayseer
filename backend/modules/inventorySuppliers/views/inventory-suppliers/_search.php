<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use common\models\User;
use backend\modules\companies\models\Companies;

/* @var $model */

?>
<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin([
        'id' => '_search',
        'method' => 'get',
        'action' => ['index'],
    ]); ?>
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
            <?= $form->field($model, 'name')->widget(Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(\backend\modules\inventorySuppliers\models\InventorySuppliers::find()->all(), 'name', 'name'),
                'language' => 'de',
                'options' => ['placeholder' => 'Select a company.'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
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
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'number_row')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>


</div>
