<?php

use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\helpers\FlatpickrWidget;

/* @var $model */

$court = Yii::$app->cache->getOrSet("l1", function () {
    return Yii::$app->db->createCommand(Yii::$app->params['court_query'])->queryAll();
}, Yii::$app->params['time_duration']);


?>
    <div class="questions-bank box box-primary">

        <?php
        $form = yii\widgets\ActiveForm::begin([
            'id' => '_search',
            'method' => 'get',
            'action' => ['/reports/reports/judiciary-index']
        ]);
        ?>
        <div class="row">
            <div class="col-lg-6">
                <?=
                $form->field($model, 'court_id')->widget(kartik\select2\Select2::classname(), [
                    'data' =>yii\helpers\ArrayHelper::map($court,'id','name'),
                    'language' => 'de',
                    'options' => [
                        'placeholder' => 'Select a court.',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]);
                ?>
            </div>
            <div class="col-lg-6">
                <?=
                $form->field($model, 'type_id')->widget(kartik\select2\Select2::classname(), [
                    'data' => Yii::$app->cache->getOrSet(Yii::$app->params["key_judiciary_type"], function () {
                        return yii\helpers\ArrayHelper::map(JudiciaryType::find()->all(), 'id', 'name');
                    }, Yii::$app->params['time_duration']),
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
                <?=
                $form->field($model, 'lawyer_id')->widget(kartik\select2\Select2::classname(), [
                    'data' => Yii::$app->cache->getOrSet(Yii::$app->params["key_lawyer"], function () {
                        return yii\helpers\ArrayHelper::map(Lawyers::find()->all(), 'id', 'name');
                    }, Yii::$app->params['time_duration']),
                    'language' => 'de',
                    'options' => [
                        'placeholder' => 'Select a lawyer.',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]);
                ?>
            </div>
            <div class="col-lg-6">
                <?= $form->field($model, 'lawyer_cost')->textInput() ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'case_cost')->textInput() ?>

            </div>
            <div class="col-lg-6">
                <?=
                $form->field($model, 'contract_id')->widget(kartik\select2\Select2::classname(), [
                    'data' => Yii::$app->cache->getOrSet(Yii::$app->params["key_judiciary_contract"], function () {
                        return yii\helpers\ArrayHelper::map(\backend\modules\judiciary\models\Judiciary::find()->all(), 'contract_id', 'contract_id');
                    }, Yii::$app->params['time_duration']),
                  'language' => 'de',
                    'options' => [
                        'placeholder' => 'Select a lawyer.',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ])->label(Yii::t('app', 'Contract ID'));
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?=
                $form->field($model, 'from_income_date')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]])->label(Yii::t('app', 'من تاريخ الورود'));
                ?>

            </div>
            <div class="col-lg-6">
                <?=
                $form->field($model, 'to_income_date')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]])->label(Yii::t('app', 'الى تاريخ الورود'));
                ?>

            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'year')->widget(kartik\select2\Select2::classname(), [
                    'data' => $model->year(),
                    'language' => 'de',
                    'options' => [
                        'placeholder' => 'Select a year.',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ])->label(Yii::t('app', 'Year'));

                ?>
            </div>
            <div class="col-lg-6">
                <?= $form->field($model, 'judiciary_number')->textInput()->label(Yii::t('app', 'Judiciary Number')); ?>

            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'number_row')->textInput(['maxlength' => true]) ?>
            </div>
        </div>
        <div class="form-group">
            <?= yii\helpers\Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
<?php yii\widgets\ActiveForm::end() ?>