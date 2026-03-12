<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\helpers\PhoneInputWidget;

/* @var $this yii\web\View */
/* @var $model backend\modules\phoneNumbers\models\PhoneNumbers */
/* @var $form yii\widgets\ActiveForm */

$isNew = $model->isNewRecord;
$relations = ArrayHelper::map(\backend\modules\cousins\models\Cousins::find()->all(), 'id', 'name');
?>

<style>
.pnf{font-family:inherit;direction:rtl}
.pnf-section{margin-bottom:18px}
.pnf-section-head{display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #E2E8F0}
.pnf-section-head i{font-size:15px}
.pnf-section-head span{font-size:13px;font-weight:700;color:#1E293B}
.pnf-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.pnf-field{display:flex;flex-direction:column;gap:4px}
.pnf-field.full{grid-column:1/-1}
.pnf-label{font-size:11px;font-weight:700;color:#64748B;display:flex;align-items:center;gap:5px}
.pnf-label i{font-size:11px;color:#94A3B8}
.pnf .form-group{margin-bottom:0}
.pnf .form-control{border:1px solid #D1D5DB;border-radius:8px;font-size:13px;padding:8px 12px;transition:border-color .2s,box-shadow .2s;background:#fff}
.pnf .form-control:focus{border-color:#6B1D3D;box-shadow:0 0 0 3px rgba(107,29,61,.08)}
.pnf select.form-control{appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748B' d='M2.5 4.5L6 8l3.5-3.5'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 10px center;padding-left:28px}
.pnf .iti{width:100%;direction:ltr}
.pnf .iti input{border:1px solid #D1D5DB !important;border-radius:8px !important;font-size:13px !important;padding:8px 12px 8px 90px !important;width:100% !important;transition:border-color .2s,box-shadow .2s}
.pnf .iti input:focus{border-color:#6B1D3D !important;box-shadow:0 0 0 3px rgba(107,29,61,.08) !important}
.pnf .iti__search-input{border-radius:6px !important;font-size:13px !important;padding:6px 10px !important}
.pnf .help-block{font-size:10px;color:#EF4444;margin:2px 0 0}
.pnf-hint{font-size:10px;color:#94A3B8;margin-top:2px}
.pnf-submit{display:flex;justify-content:flex-end;margin-top:8px;padding-top:14px;border-top:1px solid #F1F5F9}
.pnf-submit-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
.pnf-submit-btn.primary{background:#6B1D3D;color:#fff}
.pnf-submit-btn.primary:hover{filter:brightness(.9)}
@media(max-width:600px){.pnf-grid{grid-template-columns:1fr}}
</style>

<div class="pnf">
<?php $form = ActiveForm::begin(['options' => ['class' => 'pnf-form']]); ?>
<?= $form->field($model, 'customers_id')->hiddenInput()->label(false) ?>

<!-- قسم معلومات الاتصال -->
<div class="pnf-section">
    <div class="pnf-section-head">
        <i class="fa fa-phone" style="color:#2563EB"></i>
        <span>معلومات الاتصال</span>
    </div>
    <div class="pnf-grid">
        <div class="pnf-field">
            <label class="pnf-label"><i class="fa fa-phone"></i> رقم الهاتف</label>
            <?= $form->field($model, 'phone_number', ['options' => ['tag' => 'div', 'style' => 'margin:0']])->widget(PhoneInputWidget::class, [
                'options' => ['class' => 'form-control'],
            ])->label(false); ?>
        </div>
        <div class="pnf-field">
            <label class="pnf-label"><i class="fa fa-facebook-square"></i> حساب فيس بوك</label>
            <?= $form->field($model, 'fb_account', ['options' => ['tag' => 'div', 'style' => 'margin:0']])->textInput([
                'maxlength' => true,
                'placeholder' => 'رابط أو اسم الحساب',
                'class' => 'form-control',
            ])->label(false) ?>
            <span class="pnf-hint">رابط الملف الشخصي أو اسم المستخدم</span>
        </div>
    </div>
</div>

<!-- قسم معلومات المالك -->
<div class="pnf-section">
    <div class="pnf-section-head">
        <i class="fa fa-user" style="color:#6B1D3D"></i>
        <span>صاحب الرقم</span>
    </div>
    <div class="pnf-grid">
        <div class="pnf-field">
            <label class="pnf-label"><i class="fa fa-users"></i> صلة القرابة</label>
            <?= $form->field($model, 'phone_number_owner', ['options' => ['tag' => 'div', 'style' => 'margin:0']])->dropDownList($relations, [
                'prompt' => '— اختر صلة القرابة —',
                'class' => 'form-control',
            ])->label(false) ?>
        </div>
        <div class="pnf-field">
            <label class="pnf-label"><i class="fa fa-id-card-o"></i> اسم المالك</label>
            <?= $form->field($model, 'owner_name', ['options' => ['tag' => 'div', 'style' => 'margin:0']])->textInput([
                'maxlength' => true,
                'placeholder' => 'الاسم الكامل لصاحب الرقم',
                'class' => 'form-control',
            ])->label(false) ?>
        </div>
    </div>
</div>

<?php if (!Yii::$app->request->isAjax): ?>
<div class="pnf-submit">
    <button type="submit" class="pnf-submit-btn primary">
        <i class="fa <?= $isNew ? 'fa-plus' : 'fa-check' ?>"></i>
        <?= $isNew ? 'إضافة الرقم' : 'حفظ التعديلات' ?>
    </button>
</div>
<?php endif; ?>

<?php ActiveForm::end(); ?>
</div>
