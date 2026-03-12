<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\ExpenseCategories */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

<?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="row">
            <div class="col-lg-12">

<?= $form->field($model, 'description')->textarea(['rows' => 6])  ?>

            </div>
        </div>
            <?php if (!Yii::$app->request->isAjax) { ?>
            <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
        <?php } ?>

<?php ActiveForm::end(); ?>

    </div>
