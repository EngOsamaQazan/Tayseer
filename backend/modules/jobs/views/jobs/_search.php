<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use backend\modules\jobs\models\JobsType;

/* @var $model backend\modules\jobs\models\JobsSearch */
?>

<div class="fin-filter" style="margin-bottom:18px">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => ['data-pjax' => 1],
    ]); ?>
    <div class="fin-filter-main" style="padding:16px 20px">
        <div class="fin-f-field fin-f--grow">
            <label><i class="fa fa-building"></i> اسم جهة العمل</label>
            <?= Html::activeTextInput($model, 'name', ['class' => 'form-control fin-f-input', 'placeholder' => 'اسم جهة العمل']) ?>
        </div>
        <div class="fin-f-field fin-f--grow">
            <label><i class="fa fa-tag"></i> النوع</label>
            <?= Html::activeDropDownList($model, 'job_type',
                ArrayHelper::map(JobsType::find()->all(), 'id', 'name'),
                ['class' => 'form-control fin-f-input', 'prompt' => 'جميع الأنواع']
            ) ?>
        </div>
        <div class="fin-f-field fin-f--sm">
            <label><i class="fa fa-map-marker"></i> المدينة</label>
            <?= Html::activeTextInput($model, 'address_city', ['class' => 'form-control fin-f-input', 'placeholder' => 'المدينة']) ?>
        </div>
        <div class="fin-f-field fin-f--xs">
            <label><i class="fa fa-toggle-on"></i> الحالة</label>
            <?= Html::activeDropDownList($model, 'status', [1 => 'فعال', 0 => 'غير فعال'], ['class' => 'form-control fin-f-input', 'prompt' => 'الكل']) ?>
        </div>
        <div class="fin-f-field" style="display:flex;gap:8px;align-items:flex-end">
            <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'fin-btn fin-btn--search', 'style' => 'height:42px;padding:0 18px;border-radius:8px;background:#800020;color:#fff;font-weight:700;border:none;font-size:13px;cursor:pointer']) ?>
            <?= Html::a('<i class="fa fa-times"></i>', ['index'], ['class' => 'fin-btn fin-btn--reset']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
