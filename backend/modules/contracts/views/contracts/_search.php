<?php
/**
 * بحث متقدم — العقود — V2
 * Advanced Search — Contracts — V2 (Grid layout)
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use backend\widgets\UnifiedSearchWidget;
use kartik\select2\Select2;

$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

$users   = $db->createCommand(
    "SELECT DISTINCT u.id, u.username FROM {{%user}} u
     INNER JOIN {{%auth_assignment}} a ON a.user_id = u.id
     WHERE u.blocked_at IS NULL AND u.employee_type = 'Active'
     ORDER BY u.username"
)->queryAll();
$jobType = $cache->getOrSet($p['key_job_type'], fn() => $db->createCommand($p['job_type_query'])->queryAll(), $d);

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
?>

<?php $form = ActiveForm::begin([
    'id'      => 'contracts-search',
    'method'  => 'get',
    'action'  => ['index'],
    'options' => ['class' => 'ct-search-form'],
]) ?>

<div class="ct-filter-grid">

    <!-- بحث موحّد -->
    <div class="ct-filter-group ct-filter-wide">
        <label><i class="fa fa-search"></i> بحث</label>
        <?= UnifiedSearchWidget::widget([
            'name'        => 'ContractsSearch[q]',
            'value'       => $model->q,
            'searchUrl'   => Url::to(['search-suggest']),
            'placeholder' => 'رقم العقد، اسم العميل، رقم الهوية، رقم الهاتف...',
            'formSelector'=> '#contracts-search',
        ]) ?>
    </div>

    <!-- الحالة -->
    <div class="ct-filter-group">
        <label for="contractssearch-status">الحالة</label>
        <?= $form->field($model, 'status', ['template' => '{input}'])->dropDownList($statusList, [
            'class' => 'form-control',
            'aria-label' => 'الحالة',
        ]) ?>
    </div>

    <!-- من تاريخ -->
    <div class="ct-filter-group">
        <label>من تاريخ</label>
        <?= $form->field($model, 'from_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
            'options' => ['placeholder' => 'من تاريخ', 'aria-label' => 'من تاريخ', 'autocomplete' => 'off'],
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
        ]) ?>
    </div>

    <!-- إلى تاريخ -->
    <div class="ct-filter-group">
        <label>إلى تاريخ</label>
        <?= $form->field($model, 'to_date', ['template' => '{input}'])->widget(FlatpickrWidget::class, [
            'options' => ['placeholder' => 'إلى تاريخ', 'aria-label' => 'إلى تاريخ', 'autocomplete' => 'off'],
            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
        ]) ?>
    </div>

    <!-- البائع -->
    <div class="ct-filter-group">
        <label>البائع</label>
        <?= $form->field($model, 'seller_id', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($users, 'id', 'username'),
            'options' => ['placeholder' => 'اختر البائع', 'aria-label' => 'البائع'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- المتابع -->
    <div class="ct-filter-group">
        <label>المتابع</label>
        <?= $form->field($model, 'followed_by', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($users, 'id', 'username'),
            'options' => ['placeholder' => 'اختر المتابع', 'aria-label' => 'المتابع'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- نوع الوظيفة -->
    <div class="ct-filter-group">
        <label>نوع الوظيفة</label>
        <?= $form->field($model, 'job_Type', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($jobType, 'id', 'name'),
            'options' => ['placeholder' => 'نوع الوظيفة', 'aria-label' => 'نوع الوظيفة'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- عدد النتائج -->
    <div class="ct-filter-group">
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

    <!-- Actions -->
    <div class="ct-filter-actions">
        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
            'class' => 'ct-btn ct-btn-primary',
        ]) ?>
        <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline">
            <i class="fa fa-refresh"></i> <span class="ct-hide-xs">إعادة تعيين</span>
        </a>
    </div>
</div>

<?php ActiveForm::end() ?>
