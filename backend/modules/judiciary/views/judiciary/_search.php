<?php
/**
 * بحث متقدم - القضايا
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\modules\jobs\models\Jobs;
use backend\modules\jobs\models\JobsType;

$cache = Yii::$app->cache;
$courts  = $cache->getOrSet('lookup_courts', fn() => ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name'), 3600);
$types   = $cache->getOrSet('lookup_judiciary_types', fn() => ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name'), 3600);
$lawyers = $cache->getOrSet('lookup_lawyers', fn() => ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name'), 3600);
$judActions = $cache->getOrSet('lookup_judiciary_actions_all', fn() => ArrayHelper::map(
    JudiciaryActions::find()->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])->orderBy(['name' => SORT_ASC])->asArray()->all(),
    'id', 'name'
), 3600);
$jobs = $cache->getOrSet('lookup_jobs', fn() => ArrayHelper::map(
    Jobs::find()->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])->orderBy(['name' => SORT_ASC])->asArray()->all(),
    'id', 'name'
), 3600);
$jobsTypes = $cache->getOrSet('lookup_jobs_types', fn() => ArrayHelper::map(JobsType::find()->asArray()->all(), 'id', 'name'), 3600);
$hasFilters = $model->judiciary_number || $model->contract_id || $model->court_id || $model->type_id || $model->lawyer_id || $model->year || $model->from_income_date || $model->to_income_date || $model->party_name || $model->last_party_action || $model->job_title || $model->jobs_type;
?>

<div class="jud-search <?= $hasFilters ? '' : '' ?>">
    <div class="jud-search-header" onclick="$(this).closest('.jud-search').toggleClass('collapsed')">
        <h4><i class="fa fa-search"></i> فلاتر البحث <?= $hasFilters ? '<span style="background:#800020;color:#fff;font-size:10px;padding:1px 8px;border-radius:10px">نشط</span>' : '' ?></h4>
        <i class="fa fa-chevron-down jud-search-toggle"></i>
    </div>
    <div class="jud-search-body">
        <?php $form = ActiveForm::begin([
            'id' => 'judiciary-search',
            'method' => 'get',
            'action' => ['index'],
        ]) ?>

        <!-- الصف الأول: أرقام + طرف + محكمة -->
        <div class="jud-filter-row">
            <div class="jud-filter-col">
                <?= $form->field($model, 'judiciary_number')->textInput(['placeholder' => '2313'])->label('رقم القضية') ?>
            </div>
            <div class="jud-filter-col">
                <?= $form->field($model, 'contract_id')->textInput(['placeholder' => 'رقم العقد', 'type' => 'number'])->label('رقم العقد') ?>
            </div>
            <div class="jud-filter-col-wide">
                <?= $form->field($model, 'party_name')->textInput(['placeholder' => 'اسم العميل أو الكفيل'])->label('اسم الطرف') ?>
            </div>
            <div class="jud-filter-col-wide">
                <?= $form->field($model, 'court_id')->dropDownList($courts, [
                    'prompt' => '-- الكل --', 'class' => 'form-control',
                ])->label('المحكمة') ?>
            </div>
        </div>

        <!-- الصف الثاني: نوع + محامي + سنة + تواريخ + أزرار -->
        <div class="jud-filter-row">
            <div class="jud-filter-col">
                <?= $form->field($model, 'type_id')->dropDownList($types, [
                    'prompt' => '-- الكل --', 'class' => 'form-control',
                ])->label('النوع') ?>
            </div>
            <div class="jud-filter-col-wide">
                <?= $form->field($model, 'lawyer_id')->dropDownList($lawyers, [
                    'prompt' => '-- الكل --', 'class' => 'form-control',
                ])->label('المحامي') ?>
            </div>
            <div class="jud-filter-col">
                <?= $form->field($model, 'year')->dropDownList($model->year(), ['prompt' => 'الكل'])->label('السنة') ?>
            </div>
            <div class="jud-filter-col">
                <?= $form->field($model, 'from_income_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'من', 'style' => 'font-size:12px'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('ورود من') ?>
            </div>
            <div class="jud-filter-col">
                <?= $form->field($model, 'to_income_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'إلى', 'style' => 'font-size:12px'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('ورود إلى') ?>
            </div>
            <div class="jud-filter-col-wide">
                <?= $form->field($model, 'last_party_action')->dropDownList($judActions, [
                    'prompt' => '-- الكل --', 'class' => 'form-control',
                ])->label('آخر إجراء على الأطراف') ?>
            </div>
        </div>

        <!-- الصف الثالث: وظيفة + نوع وظيفة + أزرار -->
        <div class="jud-filter-row">
            <div class="jud-filter-col-wide">
                <?= $form->field($model, 'job_title')->dropDownList($jobs, [
                    'prompt' => '-- الكل --', 'class' => 'form-control',
                ])->label('جهة العمل') ?>
            </div>
            <div class="jud-filter-col-wide">
                <?= $form->field($model, 'jobs_type')->dropDownList($jobsTypes, [
                    'prompt' => '-- الكل --', 'class' => 'form-control',
                ])->label('نوع الوظيفة') ?>
            </div>
            <div class="jud-search-actions">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fa fa-times"></i>', ['index'], ['class' => 'btn btn-default', 'title' => 'مسح الفلاتر']) ?>
            </div>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</div>
