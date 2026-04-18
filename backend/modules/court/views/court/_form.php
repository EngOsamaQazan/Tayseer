<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\PhoneInputWidget;

/* @var $this yii\web\View */
/* @var $model backend\modules\court\models\Court */
/* @var $form yii\widgets\ActiveForm */

$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$db    = Yii::$app->db;
$d     = isset($p['time_duration']) ? $p['time_duration'] : 31536000;

$cityRows = [];
if (isset($p['key_city'], $p['city_query'])) {
    $cityRows = $cache->getOrSet($p['key_city'], function () use ($db, $p) {
        return $db->createCommand($p['city_query'])->queryAll();
    }, $d);
}
if (empty($cityRows)) {
    $cityRows = \backend\modules\city\models\City::find()->select(['id', 'name'])->asArray()->all();
}
$cityList = ArrayHelper::map($cityRows, 'id', 'name');
?>
<div class="questions-bank box box-primary">
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'city')->dropDownList($cityList, ['prompt' => '-- اختر المدينة --', 'class' => 'form-control']) ?>
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
            ])->label(false); ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>
</div>
<style>
    #court-phone_number {
        width: 340% !important;
    }
</style>
