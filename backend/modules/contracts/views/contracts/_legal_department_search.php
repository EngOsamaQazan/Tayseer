<?php
/**
 * بحث متقدم - الدائرة القانونية - بناء من الصفر
 * حقول بحث مع كاش للقوائم المنسدلة
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

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
                <?= $form->field($model, 'id')->dropDownList($legalContracts, [
                    'prompt' => '-- رقم العقد --', 'class' => 'form-control',
                ])->label('رقم العقد') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'customer_name')->textInput([
                    'placeholder' => 'ابحث بالاسم أو الرقم الوطني...', 'class' => 'form-control',
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
                <?= $form->field($model, 'seller_id')->dropDownList(ArrayHelper::map($users, 'id', 'username'), [
                    'prompt' => '-- البائع --', 'class' => 'form-control',
                ])->label('البائع') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'followed_by')->dropDownList(ArrayHelper::map($users, 'id', 'username'), [
                    'prompt' => '-- المتابع --', 'class' => 'form-control',
                ])->label('المتابع') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'type')->dropDownList(\backend\modules\contracts\models\Contracts::getTypeLabels(), ['prompt' => '-- النوع --'])->label('نوع العقد') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'job_title')->dropDownList(ArrayHelper::map($jobs, 'id', 'name'), [
                    'prompt' => '-- الوظيفة --', 'class' => 'form-control',
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
