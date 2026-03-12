<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use common\models\User;

/* @var $model */
?>
<?php $form = ActiveForm::begin([
    'id' => '_search',
    'method' => 'get',
    'action' => ['index'],]) ?>
<div class="questions-bank box box-primary">
    <div class="row">
        <div class="col-lg-6">
            <?=
            $form->field($model, 'user_id')->widget(Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(User::find()->all(), 'id', 'username'),
                'language' => 'de',
                'options' => ['placeholder' => 'Select a user.'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'movement_number')->textInput() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'bank_receipt_number')->textInput() ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'financial_value')->textInput() ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>