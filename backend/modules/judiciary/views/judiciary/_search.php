<?php
/**
 * بحث متقدم - القضايا
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\helpers\FlatpickrWidget;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\judiciaryType\models\JudiciaryType;

$cache = Yii::$app->cache;
$courts  = $cache->getOrSet('lookup_courts', fn() => ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name'), 3600);
$types   = $cache->getOrSet('lookup_judiciary_types', fn() => ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name'), 3600);
$lawyers = $cache->getOrSet('lookup_lawyers', fn() => ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name'), 3600);
$hasFilters = $model->judiciary_number || $model->contract_id || $model->court_id || $model->type_id || $model->lawyer_id || $model->year || $model->from_income_date || $model->to_income_date || $model->party_name;
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
                <?= $form->field($model, 'court_id')->widget(Select2::class, [
                    'data' => $courts, 'options' => ['placeholder' => 'الكل'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownAutoWidth' => true],
                ])->label('المحكمة') ?>
            </div>
        </div>

        <!-- الصف الثاني: نوع + محامي + سنة + تواريخ + أزرار -->
        <div class="jud-filter-row">
            <div class="jud-filter-col">
                <?= $form->field($model, 'type_id')->widget(Select2::class, [
                    'data' => $types, 'options' => ['placeholder' => 'الكل'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownAutoWidth' => true],
                ])->label('النوع') ?>
            </div>
            <div class="jud-filter-col-wide">
                <?= $form->field($model, 'lawyer_id')->widget(Select2::class, [
                    'data' => $lawyers, 'options' => ['placeholder' => 'الكل'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownAutoWidth' => true],
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
            <div class="jud-search-actions">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fa fa-times"></i>', ['index'], ['class' => 'btn btn-default', 'title' => 'مسح الفلاتر']) ?>
            </div>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</div>
