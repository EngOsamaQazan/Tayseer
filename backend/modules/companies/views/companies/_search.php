<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use common\models\User;
use backend\modules\companies\models\Companies;
/* @var $model */
$companies  =   Yii::$app->cache->getOrSet(Yii::$app->params["key_customers_name"], function () {
    return Yii::$app->db->createCommand(Yii::$app->params['company_name_query'])->queryAll();
}, Yii::$app->params['time_duration']);
?>
<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin([
        'id' => '_search',
        'method' => 'get',
        'action' => ['index'],
    ]); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'name')->widget(Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map($companies, 'name', 'name'),
                'language' => 'de',
                'options' => ['placeholder' => 'Select a company.'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
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
        <?= Html::submitButton(Yii::t('app','Search'),['class'=>'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>


</div>
