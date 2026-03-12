<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\modules\loanScheduling\models\LoanScheduling */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="loan-scheduling-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'contract_id')->textInput() ?>

    <?= $form->field($model, 'new_installment_date')->textInput() ?>

    <?= $form->field($model, 'monthly_installment')->textInput() ?>

    <?= $form->field($model, 'first_installment_date')->textInput() ?>

    <?= $form->field($model, 'status')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status_action_by')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <?= $form->field($model, 'created_by')->textInput() ?>

    <?= $form->field($model, 'last_update_by')->textInput() ?>

    <?= $form->field($model, 'is_deleted')->textInput() ?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
