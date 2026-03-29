<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;
use kartik\select2\Select2;
use backend\modules\branch\models\Branch;

/* @var $this yii\web\View */
/* @var $model Branch */
/* @var $form ActiveForm */

$users = ArrayHelper::map(
    \common\models\User::find()->select(['id', 'username'])->where(['<>', 'employee_status', 'fired'])->orderBy('username')->asArray()->all(),
    'id', 'username'
);

$companies = ArrayHelper::map(
    \backend\modules\companies\models\Companies::find()->select(['id', 'name'])->orderBy('name')->asArray()->all(),
    'id', 'name'
);

$isModal = Yii::$app->request->isAjax;
$dropParent = $isModal ? new JsExpression('$("#ajaxCrudModal")') : null;
?>

<div class="branch-form">
    <?php $form = ActiveForm::begin(['id' => 'branch-form']); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'مثال: فرع عمان الغربي']) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'code')->textInput(['maxlength' => true, 'placeholder' => 'BR-001', 'dir' => 'ltr']) ?>
            <span class="help-block text-muted" style="font-size:12px">يُولّد تلقائيًا إن ترك فارغًا</span>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'branch_type')->widget(Select2::class, [
                'data' => Branch::getTypeLabels(),
                'options' => ['placeholder' => 'اختر النوع...', 'id' => 'branch-type-' . $model->id],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownParent' => $dropParent],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'company_id')->widget(Select2::class, [
                'data' => $companies,
                'options' => ['placeholder' => 'اختر الشركة...', 'id' => 'branch-company-' . $model->id],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownParent' => $dropParent],
            ]) ?>
        </div>
        <div class="col-md-5">
            <?= $form->field($model, 'address')->textInput(['maxlength' => true, 'placeholder' => 'العنوان الكامل']) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'phone')->textInput(['maxlength' => true, 'placeholder' => '07xxxxxxxx', 'dir' => 'ltr']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?= $form->field($model, 'description')->textarea(['rows' => 2, 'placeholder' => 'ملاحظات إضافية عن الفرع...']) ?>
        </div>
    </div>

    <hr style="margin:10px 0">
    <h6 style="color:var(--clr-primary,#800020);font-weight:700;margin-bottom:12px">
        <i class="fa fa-map-marker-alt"></i> الموقع الجغرافي (Geofence)
    </h6>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'latitude')->textInput(['type' => 'number', 'step' => '0.00000001', 'placeholder' => '31.95xxxx', 'dir' => 'ltr']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'longitude')->textInput(['type' => 'number', 'step' => '0.00000001', 'placeholder' => '35.91xxxx', 'dir' => 'ltr']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'radius_meters')->textInput(['type' => 'number', 'min' => 20, 'max' => 5000, 'placeholder' => '100']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'wifi_ssid')->textInput(['maxlength' => true, 'placeholder' => 'اسم الشبكة', 'dir' => 'ltr']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'wifi_bssid')->textInput(['maxlength' => true, 'placeholder' => 'AA:BB:CC:DD:EE:FF', 'dir' => 'ltr']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'manager_id')->widget(Select2::class, [
                'data' => $users,
                'options' => ['placeholder' => 'اختر مدير الفرع...', 'id' => 'branch-manager-' . $model->id],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownParent' => $dropParent],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <?= $form->field($model, 'is_active')->widget(Select2::class, [
                'data' => [1 => 'فعّال', 0 => 'معطّل'],
                'options' => ['id' => 'branch-active-' . $model->id],
                'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl', 'dropdownParent' => $dropParent],
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'sort_order')->textInput(['type' => 'number', 'min' => 0]) ?>
        </div>
    </div>

    <?php if (!$isModal): ?>
        <div class="form-group" style="margin-top:15px">
            <?= Html::submitButton($model->isNewRecord ? 'إنشاء' : 'حفظ التعديلات', [
                'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php ActiveForm::end(); ?>
</div>
