<?php
/**
 * بحث متقدم - إجراءات العملاء القضائية - بناء من الصفر
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use kartik\select2\Select2;

/* بيانات مرجعية - كاش */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

/* العملاء يتم تحميلهم عبر AJAX */
$users = ArrayHelper::map(
    $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d),
    'id', 'username'
);
$actions = ArrayHelper::map(\backend\modules\judiciaryActions\models\JudiciaryActions::find()->asArray()->all(), 'id', 'name');
$courts = ArrayHelper::map(\backend\modules\court\models\Court::find()->asArray()->all(), 'id', 'name');
$lawyers = ArrayHelper::map(\backend\modules\lawyers\models\Lawyers::find()->asArray()->all(), 'id', 'name');
$years = ArrayHelper::map(
    \backend\modules\judiciary\models\Judiciary::find()->select('year')->where(['not', ['year' => null]])->distinct()->asArray()->all(),
    'year', 'year'
);
?>

<div class="box box-primary jadal-search-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث في الإجراءات القضائية</h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'id' => 'jca-search',
            'method' => 'get',
            'action' => ['index'],
            'options' => ['class' => 'jadal-search-form'],
        ]) ?>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'judiciary_number')->textInput(['placeholder' => 'رقم القضية (مثل 2313)'])->label('رقم القضية') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'customers_id')->widget(Select2::class, [
                    'initValueText' => $model->customers_id,
                    'options' => ['placeholder' => 'ابحث بالاسم أو الرقم الوطني...'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'minimumInputLength' => 1,
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['/customers/search-customers']),
                            'dataType' => 'json', 'delay' => 250,
                            'data' => new \yii\web\JsExpression('function(p){return{q:p.term}}'),
                            'processResults' => new \yii\web\JsExpression('function(d){return d}'),
                            'cache' => true,
                        ],
                        'templateResult' => new \yii\web\JsExpression("function(i){if(i.loading)return i.text;var h='<div><b>'+i.text+'</b>';if(i.id_number)h+=' <small style=\"color:#64748b\">· '+i.id_number+'</small>';if(i.phone)h+=' <small style=\"color:#0891b2\">☎ '+i.phone+'</small>';return $(h+'</div>')}"),
                        'templateSelection' => new \yii\web\JsExpression("function(i){return i.text||i.id}"),
                    ],
                ])->label('العميل') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'judiciary_actions_id')->widget(Select2::class, [
                    'data' => $actions,
                    'options' => ['placeholder' => 'الإجراء', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الإجراء') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'year')->widget(Select2::class, [
                    'data' => $years,
                    'options' => ['placeholder' => 'السنة'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label('السنة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'contract_id')->textInput(['placeholder' => 'رقم العقد', 'type' => 'number'])->label('العقد') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'court_name')->widget(Select2::class, [
                    'data' => $courts,
                    'options' => ['placeholder' => 'المحكمة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحكمة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'lawyer_name')->widget(Select2::class, [
                    'data' => $lawyers,
                    'options' => ['placeholder' => 'المحامي'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحامي') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'created_by')->widget(Select2::class, [
                    'data' => $users,
                    'options' => ['placeholder' => 'المنشئ'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المنشئ') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'form_action_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'من تاريخ الإجراء'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('من تاريخ') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'to_action_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'إلى تاريخ الإجراء'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('إلى تاريخ') ?>
            </div>
            <div class="col-md-2">
                <div class="form-group" style="margin-top:24px">
                    <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary btn-block']) ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</div>
