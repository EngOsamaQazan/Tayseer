<?php
/**
 * بحث متقدم - العملاء
 * مربع بحث موحد + فلاتر منسدلة (تصميم ct-*)
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\widgets\UnifiedSearchWidget;

$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];

$city = $cache->getOrSet($p['key_city'], fn() => Yii::$app->db->createCommand($p['city_query'])->queryAll(), $d);
$jobType = $cache->getOrSet($p['key_job_type'], fn() => Yii::$app->db->createCommand($p['job_type_query'])->queryAll(), $d);

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
?>

<div class="ct-filter-wrap" id="ctFilterWrap">
    <div class="ct-filter-backdrop" onclick="document.getElementById('ctFilterWrap').classList.remove('open')"></div>
    <div class="ct-filter-panel" id="ctFilterPanel">
        <div class="ct-filter-hdr" onclick="this.parentElement.classList.toggle('collapsed')">
            <h3><i class="fa fa-search"></i> بحث وفلترة</h3>
            <span class="ct-filter-toggle-icon"><i class="fa fa-chevron-up"></i></span>
        </div>
        <div class="ct-filter-body">
            <?php $form = ActiveForm::begin([
                'id' => 'customers-search',
                'method' => 'get',
                'action' => ['index'],
                'options' => ['class' => 'ct-filter-form'],
            ]) ?>

            <div class="ct-filter-grid">
                <div class="ct-filter-group ct-filter-wide">
                    <label><i class="fa fa-search"></i> بحث</label>
                    <?= UnifiedSearchWidget::widget([
                        'name'         => 'CustomersSearch[q]',
                        'value'        => $model->q,
                        'searchUrl'    => Url::to(['search-suggest']),
                        'placeholder'  => 'رقم العميل، الاسم، الرقم الوطني، رقم الهاتف، الوظيفة...',
                        'formSelector' => '#customers-search',
                    ]) ?>
                </div>

                <div class="ct-filter-group">
                    <label>المدينة</label>
                    <?= $form->field($model, 'city', ['template' => '{input}'])->dropDownList(
                        ArrayHelper::map($city, 'id', 'name'),
                        ['prompt' => '-- المدينة --', 'class' => 'form-control']
                    ) ?>
                </div>

                <div class="ct-filter-group">
                    <label>نوع الوظيفة</label>
                    <?= $form->field($model, 'job_Type', ['template' => '{input}'])->widget(Select2::class, [
                        'data' => ArrayHelper::map($jobType, 'id', 'name'),
                        'options' => ['placeholder' => 'نوع الوظيفة'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>

                <div class="ct-filter-group">
                    <label>حالة العقد</label>
                    <?= $form->field($model, 'contract_type', ['template' => '{input}'])->dropDownList(
                        $contractStatusList,
                        ['class' => 'form-control']
                    ) ?>
                </div>

                <div class="ct-filter-group">
                    <label>نتائج/صفحة</label>
                    <?= $form->field($model, 'number_row', ['template' => '{input}'])->textInput([
                        'placeholder' => '20',
                        'type' => 'number',
                        'class' => 'form-control',
                        'min' => 5,
                        'max' => 200,
                    ]) ?>
                </div>
            </div>

            <div class="ct-filter-actions">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
                    'class' => 'ct-btn ct-btn-primary',
                ]) ?>
                <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline">
                    <i class="fa fa-refresh"></i> إعادة تعيين
                </a>
            </div>

            <?php ActiveForm::end() ?>
        </div>
    </div>
</div>
