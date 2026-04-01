<?php
/**
 * بحث متقدم — العقود — V2
 * Advanced Search — Contracts — V2 (Grid layout + per-field autocomplete)
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\web\View;
use backend\helpers\FlatpickrWidget;

$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

if (!isset($users)) {
    $users = $db->createCommand(
        "SELECT DISTINCT u.id, u.username FROM {{%user}} u
         INNER JOIN {{%auth_assignment}} a ON a.user_id = u.id
         WHERE u.blocked_at IS NULL AND u.employee_type = 'Active'
         ORDER BY u.username"
    )->queryAll();
}
$userMap = ArrayHelper::map($users, 'id', 'username');
$jobType = $cache->getOrSet($p['key_job_type'], fn() => $db->createCommand($p['job_type_query'])->queryAll(), $d);
$jobTypeMap = ArrayHelper::map($jobType, 'id', 'name');

$statusList = [
    '' => '-- جميع الحالات --',
    'active' => 'نشط',
    'judiciary_active' => 'قضاء فعّال',
    'judiciary_paid' => 'قضاء مسدد',
    'judiciary' => 'قضاء (الكل)',
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
    UnifiedSearch.init({inputId:'ctf-name',  url:'{$suggestUrl}?field=customer_name', minChars:2, delay:300, formSelector:'#contracts-search'});
    UnifiedSearch.init({inputId:'ctf-id',    url:'{$suggestUrl}?field=id',            minChars:1, delay:300, formSelector:'#contracts-search'});
    UnifiedSearch.init({inputId:'ctf-idn',   url:'{$suggestUrl}?field=id_number',     minChars:2, delay:300, formSelector:'#contracts-search'});
    UnifiedSearch.init({inputId:'ctf-phone', url:'{$suggestUrl}?field=phone_number',  minChars:2, delay:300, formSelector:'#contracts-search'});
}
JS, View::POS_READY);
?>

<?php $form = ActiveForm::begin([
    'id'      => 'contracts-search',
    'method'  => 'get',
    'action'  => ['index'],
    'options' => ['class' => 'ct-search-form'],
]) ?>

<div class="ct-filter-rows">
    <div class="ct-filter-col-wide ct-filter-search">
        <label><i class="fa fa-user"></i> اسم الطرف</label>
        <div class="us-wrap" id="ctf-name-wrap">
            <?= Html::activeTextInput($model, 'customer_name', [
                'id' => 'ctf-name',
                'class' => 'form-control us-input',
                'placeholder' => 'ابحث باسم الطرف...',
                'aria-label' => 'بحث باسم الطرف',
                'autocomplete' => 'off',
            ]) ?>
            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
            <div class="us-dropdown" style="display:none"></div>
        </div>
    </div>
    <div class="ct-filter-col ct-filter-search">
        <label><i class="fa fa-file-text-o"></i> رقم العقد</label>
        <div class="us-wrap" id="ctf-id-wrap">
            <?= Html::activeTextInput($model, 'id', [
                'id' => 'ctf-id',
                'class' => 'form-control us-input',
                'placeholder' => 'رقم العقد...',
                'aria-label' => 'بحث برقم العقد',
                'autocomplete' => 'off',
            ]) ?>
            <span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>
            <div class="us-dropdown" style="display:none"></div>
        </div>
    </div>
    <div class="ct-filter-col ct-filter-search">
        <label><i class="fa fa-id-card"></i> الرقم الوطني</label>
        <div class="us-wrap" id="ctf-idn-wrap">
            <?= Html::activeTextInput($model, 'id_number', [
                'id' => 'ctf-idn',
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
        <div class="us-wrap" id="ctf-phone-wrap">
            <?= Html::activeTextInput($model, 'phone_number', [
                'id' => 'ctf-phone',
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
        <label>الحالة</label>
        <?= $form->field($model, 'status', ['template' => '{input}'])->dropDownList($statusList, [
            'class' => 'form-control',
            'aria-label' => 'الحالة',
        ]) ?>
    </div>
    <div class="ct-filter-col">
        <label>من تاريخ</label>
        <?= $form->field($model, 'from_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
            'options' => ['placeholder' => 'من', 'aria-label' => 'من تاريخ', 'autocomplete' => 'off', 'style' => 'font-size:12px'],
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
        ]) ?>
    </div>
    <div class="ct-filter-col">
        <label>إلى تاريخ</label>
        <?= $form->field($model, 'to_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
            'options' => ['placeholder' => 'إلى', 'aria-label' => 'إلى تاريخ', 'autocomplete' => 'off', 'style' => 'font-size:12px'],
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
        ]) ?>
    </div>
    <div class="ct-filter-col">
        <label>البائع</label>
        <?= $form->field($model, 'seller_id', ['template' => '{input}'])->dropDownList($userMap, [
            'prompt' => '-- البائع --',
            'class' => 'form-control',
            'aria-label' => 'البائع',
        ]) ?>
    </div>
    <div class="ct-filter-col">
        <label>المتابع</label>
        <?= $form->field($model, 'followed_by', ['template' => '{input}'])->dropDownList($userMap, [
            'prompt' => '-- المتابع --',
            'class' => 'form-control',
            'aria-label' => 'المتابع',
        ]) ?>
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
