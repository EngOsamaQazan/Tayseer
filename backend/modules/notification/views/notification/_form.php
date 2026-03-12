<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
/* @var $this yii\web\View */
/* @var $model common\models\Notification */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'type_of_notification')->textInput() ?>
        </div>
        <div class="col-md-6">
            <?=
            $form->field($model, 'recipient_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(\common\models\User::find()->all(), 'id', 'username'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a court.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
    </div>
    <div class="row">

        <div class="col-md-6">
            <?= $form->field($model, 'title_html')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'href')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <?= $form->field($model, 'body_html')->textarea(['rows' => 6]) ?>
    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
