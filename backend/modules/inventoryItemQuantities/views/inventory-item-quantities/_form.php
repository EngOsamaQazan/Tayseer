<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use yii\helpers\ArrayHelper;
use backend\modules\inventoryItems\models\InventoryItems;

/* @var $this yii\web\View */
/* @var $model common\models\InventoryItemQuantities */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'item_id')->dropDownList(yii\helpers\ArrayHelper::map(InventoryItems::find()->all(), 'id', 'item_name'), ['prompt' => '-- اختر الصنف --', 'class' => 'form-control']) ?>
        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'locations_id')->dropDownList(yii\helpers\ArrayHelper::map(InventoryStockLocations::find()->all(), 'id', 'locations_name'), ['prompt' => '-- اختر الموقع --', 'class' => 'form-control']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'suppliers_id')->dropDownList(yii\helpers\ArrayHelper::map(InventorySuppliers::find()->all(), 'id', 'name'), ['prompt' => '-- اختر المورد --', 'class' => 'form-control']) ?>
        </div>
        <div class="col-lg-6">

            <?= $form->field($model, 'quantity')->textInput() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'company_id')->dropDownList(\yii\helpers\ArrayHelper::map(\backend\modules\companies\models\Companies::find()->all(), 'id', 'name'), ['prompt' => '-- اختر الشركة --', 'class' => 'form-control']) ?>
        </div>
    </div>
    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>