<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\PhoneInputWidget;
/* @var $this yii\web\View */
/* @var $model common\models\Court */
/* @var $form yii\widgets\ActiveForm */
?>
 <div class="questions-bank box box-primary">
        <?php Html::dropDownList('') ?>
        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-lg-6">
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
</div>
     <div class="col-lg-6">
        <?=
        $form->field($model, 'city')->widget(kartik\select2\Select2::classname(), [
            'data' => Yii::$app->params['city'],
            'language' => 'de',
            'options' => [
                'placeholder' => 'Select a type.',
            ],
            'pluginOptions' => [
                'allowClear' => true
            ],
        ]);
        ?>
    </div>
</div>
<div class="row">
            <div class="col-lg-6">
    <?= $form->field($model, 'adress')->textInput(['maxlength' => true]) ?>
</div>
<div class="col-lg-6">
    <label class="control-label"> رقم الهاتف </label>
    <br>
    <?= $form->field($model, 'phone_number')->widget(PhoneInputWidget::class, [
        'options' => ['class' => 'form-control'],
    ])->label(false);?>
</div>
</div>
  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
<style>
    #court-phone_number{
        width: 340% !important;
    }
</style>