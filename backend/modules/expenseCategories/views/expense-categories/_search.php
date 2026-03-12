<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model */

?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin([
        'id' => '_search',
        'method' => 'get',
        'action' => ['index']
    ]); ?>
    <div class="row">
        <div class="col-lg-10">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-2" style="margin-top: 30px">
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
