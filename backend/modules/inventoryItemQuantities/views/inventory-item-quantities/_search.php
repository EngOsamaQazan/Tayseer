<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use yii\helpers\ArrayHelper;
use backend\modules\inventoryItems\models\InventoryItems;

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
            <?=
            $form->field($model, 'item_id')->dropDownList(
                yii\helpers\ArrayHelper::map(InventoryItems::find()->all(), 'id', 'item_name'),
                ['prompt' => '-- اختر المنتج --', 'class' => 'form-control']
            );
            ?>
        </div>

        <div class="col-lg-6">
            <?=
            $form->field($model, 'locations_id')->widget(Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(InventoryStockLocations::find()->all(), 'id', 'locations_name'),
                'language' => 'de',
                'options' => ['placeholder' => 'Select a Location.'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <?=
            $form->field($model, 'suppliers_id')->dropDownList(
                yii\helpers\ArrayHelper::map(InventorySuppliers::find()->all(), 'id', 'name'),
                ['prompt' => '-- اختر المورد --', 'class' => 'form-control']
            );
            ?>
        </div>
        <div class="col-lg-6">

            <?= $form->field($model, 'quantity')->textInput() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'number_row')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app','Search'),['class'=>'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>


</div>
