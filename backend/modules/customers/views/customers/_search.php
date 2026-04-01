<?php
/**
 * بحث متقدم - العملاء (V4)
 * حقول بحث مستقلة مع اقتراحات AJAX — مطابق لتصميم شاشة العقود
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\web\View;

$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];

$city = $cache->getOrSet($p['key_city'], fn() => Yii::$app->db->createCommand($p['city_query'])->queryAll(), $d);
$jobType = $cache->getOrSet($p['key_job_type'], fn() => Yii::$app->db->createCommand($p['job_type_query'])->queryAll(), $d);
$jobTypeMap = ArrayHelper::map($jobType, 'id', 'name');

$contractStatusList = [
    '' => '-- حالة العقد --',
    'active' => 'نشط',
    'judiciary_active' => 'قضائي فعّال',
    'judiciary_paid' => 'قضائي مسدد',
    'judiciary' => 'قضائي (الكل)',
    'legal_department' => 'قانوني',
    'settlement' => 'تسوية',
    'finished' => 'منتهي',
    'canceled' => 'ملغي',
];

$baseUrl = Yii::$app->request->baseUrl;
$v = Yii::$app->params['assetVersion'];
$this->registerCssFile("$baseUrl/css/unified-search.css?v=$v");
$this->registerJsFile("$baseUrl/js/unified-search.js?v=$v", ['position' => View::POS_HEAD]);

$suggestUrl = Url::to(['field-suggest']);
$this->registerJs(<<<JS
if (typeof UnifiedSearch !== 'undefined') {
    UnifiedSearch.init({inputId:'cuf-name',  url:'{$suggestUrl}?field=customer_name', minChars:2, delay:300, formSelector:'#customers-search'});
    UnifiedSearch.init({inputId:'cuf-id',    url:'{$suggestUrl}?field=id',            minChars:1, delay:300, formSelector:'#customers-search'});
    UnifiedSearch.init({inputId:'cuf-idn',   url:'{$suggestUrl}?field=id_number',     minChars:2, delay:300, formSelector:'#customers-search'});
    UnifiedSearch.init({inputId:'cuf-phone', url:'{$suggestUrl}?field=phone_number',  minChars:2, delay:300, formSelector:'#customers-search'});
}
JS, View::POS_READY);
?>

<?php $form = ActiveForm::begin([
    'id' => 'customers-search',
    'method' => 'get',
    'action' => ['index'],
    'options' => ['class' => 'ct-search-form'],
]) ?>

<div class="ct-filter-rows">
    <div class="ct-filter-col-wide ct-filter-search">
        <label><i class="fa fa-user"></i> اسم العميل</label>
        <div class="us-wrap" id="cuf-name-wrap">
            <?= Html::activeTextInput($model, 'customer_name', [
                'id' => 'cuf-name',
                'class' => 'form-control us-input',
                'placeholder' => 'ابحث باسم العميل...',
                'aria-label' => 'بحث باسم العميل',
                'autocomplete' => 'off',
            ]) ?>
            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
            <div class="us-dropdown" style="display:none"></div>
        </div>
    </div>
    <div class="ct-filter-col ct-filter-search">
        <label><i class="fa fa-hashtag"></i> رقم العميل</label>
        <div class="us-wrap" id="cuf-id-wrap">
            <?= Html::activeTextInput($model, 'id', [
                'id' => 'cuf-id',
                'class' => 'form-control us-input',
                'placeholder' => 'رقم العميل...',
                'aria-label' => 'بحث برقم العميل',
                'autocomplete' => 'off',
            ]) ?>
            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
            <div class="us-dropdown" style="display:none"></div>
        </div>
    </div>
    <div class="ct-filter-col ct-filter-search">
        <label><i class="fa fa-id-card"></i> الرقم الوطني</label>
        <div class="us-wrap" id="cuf-idn-wrap">
            <?= Html::activeTextInput($model, 'id_number', [
                'id' => 'cuf-idn',
                'class' => 'form-control us-input',
                'placeholder' => 'الرقم الوطني...',
                'aria-label' => 'بحث بالرقم الوطني',
                'autocomplete' => 'off',
            ]) ?>
            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
            <div class="us-dropdown" style="display:none"></div>
        </div>
    </div>
    <div class="ct-filter-col ct-filter-search">
        <label><i class="fa fa-phone"></i> رقم الهاتف</label>
        <div class="us-wrap" id="cuf-phone-wrap">
            <?= Html::activeTextInput($model, 'phone_number', [
                'id' => 'cuf-phone',
                'class' => 'form-control us-input',
                'placeholder' => 'رقم الهاتف...',
                'aria-label' => 'بحث برقم الهاتف',
                'autocomplete' => 'off',
            ]) ?>
            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
            <div class="us-dropdown" style="display:none"></div>
        </div>
    </div>
    <div class="ct-filter-col">
        <label>المدينة</label>
        <?= $form->field($model, 'city', ['template' => '{input}'])->dropDownList(
            ArrayHelper::map($city, 'id', 'name'),
            ['prompt' => '-- المدينة --', 'class' => 'form-control', 'aria-label' => 'المدينة']
        ) ?>
    </div>
    <div class="ct-filter-col">
        <label>نوع الوظيفة</label>
        <?= $form->field($model, 'job_Type', ['template' => '{input}'])->dropDownList($jobTypeMap, [
            'prompt' => '-- الوظيفة --',
            'class' => 'form-control',
            'aria-label' => 'نوع الوظيفة',
        ]) ?>
    </div>
    <div class="ct-filter-col">
        <label>حالة العقد</label>
        <?= $form->field($model, 'contract_type', ['template' => '{input}'])->dropDownList(
            $contractStatusList,
            ['class' => 'form-control', 'aria-label' => 'حالة العقد']
        ) ?>
    </div>
    <div class="ct-filter-col">
        <label>نتائج/صفحة</label>
        <?= $form->field($model, 'number_row', ['template' => '{input}'])->textInput([
            'placeholder' => '20',
            'type' => 'number',
            'class' => 'form-control',
            'min' => 5,
            'max' => 200,
            'aria-label' => 'عدد النتائج في الصفحة',
        ]) ?>
    </div>
    <div class="ct-filter-actions">
        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
            'class' => 'ct-btn ct-btn-primary',
        ]) ?>
        <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline">
            <i class="fa fa-refresh"></i> <span class="ct-hide-xs">مسح</span>
        </a>
    </div>
</div>

<?php ActiveForm::end() ?>
