<?
use yii\widgets\ActiveForm;
/* @var $model */
?>
    <div class="questions-bank box box-primary">

        <?php
        $form = yii\widgets\ActiveForm::begin([
            'id' => '_search',
            'method' => 'get',
            'action' => ['reports/index2']
        ]);
        ?>
        <div class="row">
            <div class="col-lg-6">
                <?=
                $form->field($model, 'date_from')->widget(\backend\helpers\FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]]);
                ?>
            </div>
            <div class="col-lg-6">
                <?=
                $form->field($model, 'date_to')->widget(\backend\helpers\FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]]);
                ?>
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
                <?= $form->field($model, 'number_row')->textInput(['maxlength' => true]) ?>
            </div>
        </div>
            <div class="col-lg-6" >
                <div class="form-group">
                    <?= yii\helpers\Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>

<?php yii\widgets\ActiveForm::end() ?>