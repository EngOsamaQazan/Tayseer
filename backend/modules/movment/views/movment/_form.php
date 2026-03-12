<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model common\models\Movment */
/* @var $form yii\widgets\ActiveForm */
?>

<div class='row'>
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <div class="col-lg-3">
        <?php
        $receipt_image = 'images/campanyLogo/logo.png';
        ?>
        <div>
            <?php
            if ($model->isNewRecord) {
                echo Html::img(Url::to([$receipt_image]), ['style' => 'width: 200px;height:200px ;object-fit: contain; margin-top: 20px']);
            } else {
                echo Html::img(Url::to([$model->receipt_image]), ['style' => 'width:200px;height:200px; object-fit: contain; margin-top: 20px']);
            }
            echo $form->field($model, 'receipt_image')->fileInput()
            ?>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="questions-bank box box-primary">
            <div class="row">
                <div class="col-lg-6">
                    <?= $form->field($model, 'movement_number')->textInput() ?>
                </div>
                <div class="col-lg-6">
                    <?= $form->field($model, 'bank_receipt_number')->textInput() ?>
                </div>
            </div>
            <div class="row">

                <div class="col-lg-6">
                    <?= $form->field($model, 'financial_value')->textInput() ?>
                </div>
            </div>
            <?php if (!Yii::$app->request->isAjax) { ?>
                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            <?php } ?>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>
