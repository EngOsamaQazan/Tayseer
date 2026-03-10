<?php
/**
 * بحث متقدم - الدائرة القانونية - بناء من الصفر
 * حقول بحث مع كاش للقوائم المنسدلة
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use kartik\select2\Select2;

/* بيانات مرجعية من الكاش */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$users = $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d);
/* العملاء يتم تحميلهم عبر AJAX */
$jobs = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);

/* عقود الدائرة القانونية */
$legalContracts = ArrayHelper::map(
    \backend\modules\contracts\models\Contracts::find()
        ->select(['id'])->where(['status' => 'legal_department', 'is_deleted' => 0])
        ->asArray()->all(),
    'id', 'id'
);
?>

<div class="box box-primary jadal-search-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث في الدائرة القانونية</h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'id' => 'legal-search',
            'method' => 'get',
            'action' => ['contracts/legal-department'],
            'options' => ['class' => 'jadal-search-form'],
        ]) ?>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'id')->widget(Select2::class, [
                    'data' => $legalContracts,
                    'options' => ['placeholder' => 'رقم العقد'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label('رقم العقد') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'customer_name')->widget(Select2::class, [
                    'initValueText' => $model->customer_name,
                    'options' => ['placeholder' => 'ابحث بالاسم أو الرقم الوطني...'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'minimumInputLength' => 1,
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['/customers/search-customers', 'mode' => 'name']),
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
            <div class="col-md-2">
                <?= $form->field($model, 'from_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'من تاريخ'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('من') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'to_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'إلى تاريخ'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('إلى') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'seller_id')->widget(Select2::class, [
                    'data' => ArrayHelper::map($users, 'id', 'username'),
                    'options' => ['placeholder' => 'البائع'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('البائع') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'followed_by')->widget(Select2::class, [
                    'data' => ArrayHelper::map($users, 'id', 'username'),
                    'options' => ['placeholder' => 'المتابع'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المتابع') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'type')->dropDownList(['normal' => 'عادي', 'solidarity' => 'تضامني'], ['prompt' => '-- النوع --'])->label('نوع العقد') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'job_title')->widget(Select2::class, [
                    'data' => ArrayHelper::map($jobs, 'id', 'name'),
                    'options' => ['placeholder' => 'الوظيفة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الوظيفة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'phone_number')->textInput(['placeholder' => 'الهاتف'])->label('الهاتف') ?>
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
