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
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-6">
            <?=
            $form->field($model, 'status')->widget(kartik\select2\Select2::classname(), [
                'data' => [Yii::t('app', 'Active'), Yii::t('app', 'None Active')],
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
            <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-lg-6">
<?= $form->field($model, 'phone_number')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class ="row">
        <div class="col-lg-6">
            <?=
            $form->field($model, 'created_by')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(\common\models\User::find()->all(), 'id', 'username'),
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
        <div class="col-lg-6">
            <?=
            $form->field($model, 'last_update_by')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(\common\models\User::find()->all(), 'id', 'username'),
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


    <div class="form-group">
<?= yii\helpers\Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
    </div>
</div>

<?php yii\widgets\ActiveForm::end() ?>