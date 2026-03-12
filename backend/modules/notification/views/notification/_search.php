<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin([
        'id' => 'search',
        'action' => ['index'],
        'method' => 'get',
    ]); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'sender_id')->textInput() ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'recipient_id')->textInput() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'type_of_notification')->textInput() ?>
        </div>


        <div class="col-md-6">
            <?=
            $form->field($model, 'is_unread')->widget(kartik\select2\Select2::classname(), [
                'data' => [\common\models\Notification::IS_UNREAD, \common\models\Notification::IS_NOT_UNREAD],
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
            <?=
            $form->field($model, 'is_hidden')->widget(kartik\select2\Select2::classname(), [
                'data' => [\common\models\Notification::IS_HIDDEN, \common\models\Notification::IS_NOT_HIDDEN],
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
    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
