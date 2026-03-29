<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\modules\notification\models\NotificationSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="notification-search card card-body mb-3">

    <?php $form = ActiveForm::begin([
        'id' => 'notification-search',
        'action' => ['index'],
        'method' => 'get',
    ]); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'sender_id')->textInput(['placeholder' => Yii::t('app', 'رقم المرسل')]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'recipient_id')->textInput(['placeholder' => Yii::t('app', 'رقم المستلم')]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'type_of_notification')->textInput(['placeholder' => Yii::t('app', 'نوع الإشعار')]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'is_unread')->dropDownList(
                ['' => Yii::t('app', 'الكل'), 1 => Yii::t('app', 'غير مقروء'), 0 => Yii::t('app', 'مقروء')],
                ['prompt' => false]
            ) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'title_html')->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'العنوان')]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'is_hidden')->dropDownList(
                ['' => Yii::t('app', 'الكل'), 0 => Yii::t('app', 'ظاهر'), 1 => Yii::t('app', 'مخفي')],
                ['prompt' => false]
            ) ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'بحث'), ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'إعادة تعيين'), ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
