<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\modules\notification\models\Notification;

/* @var $this yii\web\View */
/* @var $model backend\modules\notification\models\Notification */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="notification-form card card-body">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'type_of_notification')->dropDownList(
                Notification::getTypeLabels(),
                ['prompt' => Yii::t('app', 'اختر النوع')]
            ) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'recipient_id')->dropDownList(ArrayHelper::map(\common\models\User::find()->select(['id', 'username'])->orderBy('username')->asArray()->all(), 'id', 'username'), ['prompt' => '-- اختر المستلم --', 'class' => 'form-control']) ?>
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

    <?php if (!Yii::$app->request->isAjax): ?>
        <div class="form-group">
            <?= Html::submitButton(
                $model->isNewRecord ? Yii::t('app', 'إرسال') : Yii::t('app', 'تحديث'),
                ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
            ) ?>
        </div>
    <?php endif ?>

    <?php ActiveForm::end(); ?>

</div>
