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

<style>
.jud-search { background:#fff; border:1px solid #E2E8F0; border-radius:10px; margin-bottom:14px; }
.jud-search-header {
    display:flex; align-items:center; justify-content:space-between; padding:10px 16px;
    cursor:pointer; user-select:none;
}
.jud-search-header h4 { margin:0; font-size:13px; font-weight:700; color:#475569; display:flex; align-items:center; gap:8px; }
.jud-search-header h4 i { color:#800020; }
.jud-search-toggle { color:#94A3B8; font-size:12px; transition:transform .2s; }
.jud-search.collapsed .jud-search-toggle { transform:rotate(-90deg); }
.jud-search-body { padding:0 16px 14px; }
.jud-search.collapsed .jud-search-body { display:none; }

.jud-filter-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
.jud-filter-row:last-child { margin-bottom:0; }
.jud-filter-col { flex:1; min-width:120px; max-width:220px; }
.jud-filter-col-wide { flex:1.5; min-width:160px; max-width:280px; }
.jud-filter-col .form-group,
.jud-filter-col-wide .form-group { margin-bottom:0; }
.jud-filter-col label,
.jud-filter-col-wide label { font-size:10px; color:#64748B; font-weight:600; margin-bottom:2px; letter-spacing:.3px; }
.jud-filter-col .form-control,
.jud-filter-col-wide .form-control { font-size:12px !important; height:32px; padding:4px 8px; border-radius:6px; }
.jud-filter-col .select2-container,
.jud-filter-col-wide .select2-container { font-size:12px !important; }
.jud-filter-col .select2-container .select2-selection--single,
.jud-filter-col-wide .select2-container .select2-selection--single { height:32px !important; min-height:32px !important; border-radius:6px !important; }
.jud-filter-col .select2-container .select2-selection--single .select2-selection__rendered,
.jud-filter-col-wide .select2-container .select2-selection--single .select2-selection__rendered { line-height:30px !important; font-size:12px !important; padding-right:8px !important; }

.jud-filter-sep { width:100%; height:1px; background:#F1F5F9; margin:4px 0 6px; }

.jud-search-actions { display:flex; gap:8px; align-items:flex-end; min-width:130px; }
.jud-search-actions .btn { height:32px; font-size:12px; padding:0 16px; border-radius:6px; display:flex; align-items:center; gap:5px; font-weight:600; white-space:nowrap; }
.jud-search-actions .btn-primary { background:#800020; border-color:#800020; }
.jud-search-actions .btn-primary:hover { background:#650019; border-color:#650019; }

/* ═══ Responsive ═══ */
@media (max-width:767px) {
    .jud-search { margin-bottom:10px; border-radius:8px; overflow:hidden; }
    .jud-search-header { padding:8px 12px; }
    .jud-search-header h4 { font-size:12px; }
    .jud-search-body { padding:0 10px 10px; }
    .jud-filter-row { flex-direction:column; gap:6px; }
    .jud-filter-col, .jud-filter-col-wide { flex:none; width:100%; min-width:0; max-width:none; }
    .jud-filter-col .form-control,
    .jud-filter-col-wide .form-control { width:100% !important; }
    .jud-filter-col .select2-container,
    .jud-filter-col-wide .select2-container { width:100% !important; }
    .jud-search-actions { width:100%; justify-content:stretch; }
    .jud-search-actions .btn { flex:1; justify-content:center; min-height:40px; }
}
@media (max-width:480px) {
    .jud-filter-col label, .jud-filter-col-wide label { font-size:9px; }
    .jud-search-actions .btn { font-size:11px; }
}
</style>

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
