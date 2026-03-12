<?

use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

/* @var $model */


?>
    <div class="questions-bank box box-primary">

        <?php
        $form = yii\widgets\ActiveForm::begin([
            'id' => '_search',
            'method' => 'get',
            'action' => ['reports/total-customer-payments-index']
        ]);
        ?>
        <div class="row">
            <div class="col-lg-6">
                <?=
                $form->field($model, 'created_by')->widget(kartik\select2\Select2::class, [
                    'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_users"], function () {
    return Yii::$app->db->createCommand(Yii::$app->params['users_query'])->queryAll();
}, Yii::$app->params['time_duration']), 'id', 'username'),
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
                $form->field($model, 'type')->widget(kartik\select2\Select2::class, [
                    'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_income_category"], function () {
                        return Yii::$app->db->createCommand(Yii::$app->params['income_category_query'])->queryAll();
                    }, Yii::$app->params['time_duration']), 'id', 'name'),
                    'language' => 'de',
                    'options' => [
                        'placeholder' => 'Select a type.',
                        'multiple' => true
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
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, '_by')->widget(kartik\select2\Select2::class, [
                    'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_income_by"], function () {
                        return Yii::$app->db->createCommand(Yii::$app->params['income_by_query'])->queryAll();
                    }, Yii::$app->params['time_duration']), '_by', '_by'),
                    'options' => [
                        'placeholder' => 'Select a customer name.',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]);
                ?>
            </div>
            <div class="col-lg-6">
                <?=
                $form->field($model, 'followed_by')->widget(kartik\select2\Select2::class, [
                    'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_users"], function () {
                        return Yii::$app->db->createCommand(Yii::$app->params['users_query'])->queryAll();
                    }, Yii::$app->params['time_duration']), 'id', 'username'),
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
                $form->field($model, 'income_status')->widget(kartik\select2\Select2::class, [
                    'data' => ['', \Yii::t('app', 'active'), \Yii::t('app', 'judiciary')],
                    'language' => 'ar',
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

                <?= $form->field($model, 'company_id')->widget(kartik\select2\Select2::class, [
                    'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_company"], function () {
                        return Yii::$app->db->createCommand(Yii::$app->params['company_query'])->queryAll();
                    }, Yii::$app->params['time_duration']), 'id', 'name'),
                    'options' => [
                        'placeholder' => 'Select a company name.',
                        'multiple' => true
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]);
                ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <?=
                $form->field($model, 'from_date')->widget(\backend\helpers\FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]]);
                ?>
            </div>
            <div class="col-md-6">
                <?=
                $form->field($model, 'to_date')->widget(\backend\helpers\FlatpickrWidget::class, ['pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]]);
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'number_row')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-lg-6">
                <?= $form->field($model, 'payment_type')->widget(kartik\select2\Select2::classname(), [
                    'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_payment_type"], function () {
    return Yii::$app->db->createCommand(Yii::$app->params['payment_type_query'])->queryAll();
}, Yii::$app->params['time_duration']), 'id', 'name'),

                    'options' => [
                        'placeholder' => 'Select a payment type.',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ])->label(Yii::t('app','Payment Type'));
                ?>
        </div>
        <div class="form-group">
            <?= yii\helpers\Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
<?php yii\widgets\ActiveForm::end() ?>