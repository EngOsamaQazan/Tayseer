<?php
/**
 * بحث متقدم — الدائرة القانونية — V2
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use kartik\select2\Select2;

$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

$jobs     = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);
$jobTypes = \backend\modules\jobs\models\JobsType::find()->select(['id', 'name'])->asArray()->all();

$legalContracts = ArrayHelper::map(
    \backend\modules\contracts\models\Contracts::find()
        ->select(['id'])->where(['status' => 'legal_department', 'is_deleted' => 0])
        ->asArray()->all(),
    'id', 'id'
);
?>

<?php $form = ActiveForm::begin([
    'id'      => 'legal-search-v2',
    'method'  => 'get',
    'action'  => ['contracts/legal-department'],
    'options' => ['class' => 'ct-search-form'],
]) ?>

<div class="ct-legal-filters">

    <!-- سطر ١ -->
    <div class="ct-lf-row">
        <div class="ct-filter-group" style="width:110px">
            <label>رقم العقد</label>
            <?= $form->field($model, 'id', ['template' => '{input}'])->widget(Select2::class, [
                'data' => $legalContracts,
                'options' => ['placeholder' => 'رقم العقد', 'aria-label' => 'رقم العقد'],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
            ]) ?>
        </div>
        <div class="ct-filter-group" style="flex:1;min-width:160px">
            <label>العميل</label>
            <?= $form->field($model, 'customer_name', ['template' => '{input}'])->widget(Select2::class, [
                'initValueText' => $model->customer_name,
                'options' => [
                    'placeholder' => 'ابحث بالاسم أو الرقم الوطني...',
                    'aria-label' => 'بحث العميل',
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'dir' => 'rtl',
                    'minimumInputLength' => 1,
                    'ajax' => [
                        'url' => Url::to(['/customers/search-customers', 'mode' => 'name']),
                        'dataType' => 'json',
                        'delay' => 250,
                        'data' => new \yii\web\JsExpression('function(p){return{q:p.term}}'),
                        'processResults' => 'function(d){return d}',
                        'cache' => true,
                    ],
                    'templateResult' => new \yii\web\JsExpression(
                        "function(i){if(i.loading)return i.text;" .
                        "var h='<div><b>'+i.text+'</b>';" .
                        "if(i.id_number)h+=' <small style=\"color:#64748b\">· '+i.id_number+'</small>';" .
                        "if(i.phone)h+=' <small style=\"color:#0891b2\">☎ '+i.phone+'</small>';" .
                        "return $(h+'</div>')}"
                    ),
                    'templateSelection' => new \yii\web\JsExpression("function(i){return i.text||i.id}"),
                ],
            ]) ?>
        </div>
        <div class="ct-lf-date-pair">
            <div class="ct-filter-group">
                <label>من تاريخ</label>
                <?= $form->field($model, 'from_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'من', 'aria-label' => 'من تاريخ', 'autocomplete' => 'off'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ]) ?>
            </div>
            <div class="ct-filter-group">
                <label>إلى تاريخ</label>
                <?= $form->field($model, 'to_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'إلى', 'aria-label' => 'إلى تاريخ', 'autocomplete' => 'off'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ]) ?>
            </div>
        </div>
    </div>

    <!-- سطر ٢ -->
    <div class="ct-lf-row">
        <div class="ct-filter-group" style="width:120px">
            <label>نوع العقد</label>
            <?= $form->field($model, 'type', ['template' => '{input}'])->dropDownList(
                \backend\modules\contracts\models\Contracts::getTypeLabels(),
                ['class' => 'form-control', 'prompt' => '-- الجميع --', 'aria-label' => 'نوع العقد']
            ) ?>
        </div>
        <div class="ct-filter-group" style="flex:1;min-width:120px">
            <label>الوظيفة</label>
            <?= $form->field($model, 'job_title', ['template' => '{input}'])->widget(Select2::class, [
                'data' => ArrayHelper::map($jobs, 'id', 'name'),
                'options' => ['placeholder' => 'الوظيفة', 'aria-label' => 'الوظيفة'],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
            ]) ?>
        </div>
        <div class="ct-filter-group" style="flex:1;min-width:120px">
            <label>نوع الوظيفة</label>
            <?= $form->field($model, 'job_Type', ['template' => '{input}'])->widget(Select2::class, [
                'data' => ArrayHelper::map($jobTypes, 'id', 'name'),
                'options' => ['placeholder' => 'نوع الوظيفة', 'aria-label' => 'نوع الوظيفة'],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
            ]) ?>
        </div>
        <div class="ct-filter-actions">
            <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
                'class' => 'ct-btn ct-btn-primary',
            ]) ?>
            <a href="<?= Url::to(['legal-department']) ?>" class="ct-btn ct-btn-outline">
                <i class="fa fa-refresh"></i> إعادة تعيين
            </a>
        </div>
    </div>

</div>

<?php ActiveForm::end() ?>
