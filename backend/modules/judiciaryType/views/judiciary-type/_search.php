<?
use yii\widgets\ActiveForm;
/* @var $model */
?>
<div class="questions-bank box box-primary">

    <?php
    $form = yii\widgets\ActiveForm::begin([
                'id' => '_search',
                'method' => 'get',
                'action' => ['index']
    ]);
    ?>
    <div class = "row">
        <div class="col-md-10">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-2" style="margin-top: 27px">
            <div class="form-group">
                <?= yii\helpers\Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <?php yii\widgets\ActiveForm::end() ?>
    </div>
</div>