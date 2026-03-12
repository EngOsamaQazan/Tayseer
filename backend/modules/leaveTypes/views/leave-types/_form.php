<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\designation\models\Designation;
use backend\modules\department\models\Department;
use backend\modules\leaveTypes\models\LeaveTypes;
use yii\helpers\ArrayHelper;
use backend\modules\location\models\Location;


/* @var $this yii\web\View */
/* @var $model common\models\LeavePolicy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <div class="row">
        <?php $form = ActiveForm::begin(); ?>

        <div class="col-md-6">
            <?= $form->field($model, 'title')->textInput() ?>
        </div> 
        <div class="col-md-6">
            <?= $form->field($model, 'leave_type')->dropDownList(yii\helpers\ArrayHelper::map(LeaveTypes::find()->all(), 'id', 'title'), ['prompt' => Yii::t('app', 'select Leave Types')])->label(Yii::t('app', 'LeaveTypes'), ['class' => 'col-md-6']) ?>
        </div> 
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'total_days')->textInput() ?>
        </div> 
         <div class="col-md-6">
            <?= $form->field($model, 'year')->dropDownList(['2020' => '2020', '2021' => '2021', '2022' => '2022', '2023' => '2023', '2024' => '2024'], ['prompt' => Yii::t('app', 'select year')])->label(Yii::t('app', 'year'), ['class' => 'col-md-6']) ?>
        </div> 
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'department')->dropDownList(yii\helpers\ArrayHelper::map(Department::find()->all(), 'id', 'title'), ['prompt' => Yii::t('app', 'all department')])->label(Yii::t('app', 'department'), ['class' => 'col-md-6']) ?>
        </div> 
        <div class="col-md-6">
            <?= $form->field($model, 'designation')->dropDownList(yii\helpers\ArrayHelper::map(Designation::find()->all(), 'id', 'title'), ['prompt' => Yii::t('app', 'all Designation')])->label(Yii::t('app', 'Designation'), ['class' => 'col-md-6']) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'location')->dropDownList(yii\helpers\ArrayHelper::map(Location::find()->all(), 'id', 'location'), ['prompt' => Yii::t('app', 'all location')])->label(Yii::t('app', 'location'), ['class' => 'col-md-6']) ?>
        </div> 
        <div class="col-md-6">
            <?= $form->field($model, 'gender')->dropDownList([ 'all' => 'All', 'Female' => 'Female', 'Male' => 'Male',], ['prompt' => Yii::t('app', 'select gender')]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'marital_status')->dropDownList([ 'all' => 'All', 'single' => 'Single', 'married' => 'Married',], ['prompt' => Yii::t('app', 'select marital status')]) ?>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'status')->dropDownList([ 'active' => 'Active', 'unActive' => 'UnActive',], ['prompt' => Yii::t('app', 'select status')]) ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>
</div>
