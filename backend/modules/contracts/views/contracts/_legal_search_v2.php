<?php
/**
 * بحث متقدم — الدائرة القانونية — V3
 * تصميم احترافي بنمط فلاتر القضايا
 * Select2 للبحث في القوائم المنسدلة
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

$hasFilters = $model->id || $model->customer_name || $model->from_date || $model->to_date
           || $model->type || $model->job_title || $model->job_Type;
?>

<?php $form = ActiveForm::begin([
    'id'      => 'legal-search-v2',
    'method'  => 'get',
    'action'  => ['contracts/index-legal-department'],
    'options' => ['class' => 'ct-search-form'],
]) ?>

<?php if (Yii::$app->request->get('_iframe')): ?>
    <input type="hidden" name="_iframe" value="1">
<?php endif ?>

<div class="ct-filter-rows">

    <!-- العميل — حقل البحث الرئيسي -->
    <div class="ct-filter-col-wide ct-filter-search">
        <label><i class="fa fa-search"></i> العميل</label>
        <?= $form->field($model, 'customer_name', ['template' => '{input}'])->textInput([
            'placeholder' => 'ابحث بالاسم أو الرقم الوطني...',
            'class' => 'form-control',
        ]) ?>
    </div>

    <!-- من تاريخ -->
    <div class="ct-filter-col">
        <label>من تاريخ</label>
        <?= $form->field($model, 'from_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
            'options' => ['placeholder' => 'من', 'autocomplete' => 'off'],
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
        ]) ?>
    </div>

    <!-- إلى تاريخ -->
    <div class="ct-filter-col">
        <label>إلى تاريخ</label>
        <?= $form->field($model, 'to_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
            'options' => ['placeholder' => 'إلى', 'autocomplete' => 'off'],
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
        ]) ?>
    </div>

    <!-- رقم العقد -->
    <div class="ct-filter-col">
        <label>رقم العقد</label>
        <?= $form->field($model, 'id', ['template' => '{input}'])->widget(Select2::class, [
            'data' => $legalContracts,
            'options' => ['placeholder' => '-- رقم العقد --'],
            'pluginOptions' => [
                'allowClear' => true,
                'dir' => 'rtl',
            ],
            'theme' => Select2::THEME_DEFAULT,
        ]) ?>
    </div>

    <!-- نوع العقد -->
    <div class="ct-filter-col">
        <label>نوع العقد</label>
        <?= $form->field($model, 'type', ['template' => '{input}'])->dropDownList(
            \backend\modules\contracts\models\Contracts::getTypeLabels(),
            ['class' => 'form-control', 'prompt' => '-- الجميع --']
        ) ?>
    </div>

    <!-- الوظيفة — Select2 مع بحث -->
    <div class="ct-filter-col-wide">
        <label>الوظيفة</label>
        <?= $form->field($model, 'job_title', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($jobs, 'id', 'name'),
            'options' => ['placeholder' => '-- الوظيفة --'],
            'pluginOptions' => [
                'allowClear' => true,
                'dir' => 'rtl',
            ],
            'theme' => Select2::THEME_DEFAULT,
        ]) ?>
    </div>

    <!-- نوع الوظيفة -->
    <div class="ct-filter-col">
        <label>نوع الوظيفة</label>
        <?= $form->field($model, 'job_Type', ['template' => '{input}'])->dropDownList(ArrayHelper::map($jobTypes, 'id', 'name'), [
            'prompt' => '-- نوع الوظيفة --', 'class' => 'form-control',
        ]) ?>
    </div>

    <!-- أزرار -->
    <div class="ct-filter-actions">
        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
            'class' => 'ct-btn ct-btn-primary',
        ]) ?>
        <a href="<?= Url::to(array_merge(['index-legal-department'], Yii::$app->request->get('_iframe') ? ['_iframe' => 1] : [])) ?>" class="ct-btn ct-btn-outline">
            <i class="fa fa-refresh"></i> <span class="ct-hide-xs">إعادة تعيين</span>
        </a>
    </div>

</div>

<?php ActiveForm::end() ?>
