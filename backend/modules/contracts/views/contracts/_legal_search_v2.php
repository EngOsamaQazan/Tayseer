<?php
/**
 * بحث متقدم — الدائرة القانونية — V2
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

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
            <?= $form->field($model, 'id', ['template' => '{input}'])->dropDownList($legalContracts, [
                'prompt' => '-- رقم العقد --', 'class' => 'form-control', 'aria-label' => 'رقم العقد',
            ]) ?>
        </div>
        <div class="ct-filter-group" style="flex:1;min-width:160px">
            <label>العميل</label>
            <?= $form->field($model, 'customer_name', ['template' => '{input}'])->textInput([
                'placeholder' => 'ابحث بالاسم أو الرقم الوطني...',
                'class' => 'form-control', 'aria-label' => 'بحث العميل',
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
            <?= $form->field($model, 'job_title', ['template' => '{input}'])->dropDownList(ArrayHelper::map($jobs, 'id', 'name'), [
                'prompt' => '-- الوظيفة --', 'class' => 'form-control', 'aria-label' => 'الوظيفة',
            ]) ?>
        </div>
        <div class="ct-filter-group" style="flex:1;min-width:120px">
            <label>نوع الوظيفة</label>
            <?= $form->field($model, 'job_Type', ['template' => '{input}'])->dropDownList(ArrayHelper::map($jobTypes, 'id', 'name'), [
                'prompt' => '-- نوع الوظيفة --', 'class' => 'form-control', 'aria-label' => 'نوع الوظيفة',
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
