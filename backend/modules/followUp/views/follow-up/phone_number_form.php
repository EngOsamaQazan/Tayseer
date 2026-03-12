<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\PhoneNumbers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="phone-numbers-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'customers_id')->hiddenInput()->label(false) ?>

    <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'phone_number_owner')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'owner_name')->textInput(['maxlength' => true]) ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
