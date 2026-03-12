<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var backend\modules\shareholders\models\Shareholders $model */
/** @var yii\widgets\ActiveForm $form */
?>

<style>
:root {
    --sh-primary: #0ea5e9;
    --sh-primary-light: #e0f2fe;
    --sh-border: #e2e8f0;
    --sh-bg: #f8fafc;
    --sh-r: 12px;
    --sh-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.sh-form-page { max-width: 900px; margin: 0 auto; }
.sh-card { background: #fff; border-radius: var(--sh-r); box-shadow: var(--sh-shadow); border: 1px solid var(--sh-border); margin-bottom: 18px; overflow: hidden; }
.sh-card-title { font-size: 15px; font-weight: 700; color: #1e293b; padding: 16px 20px; background: var(--sh-bg); border-bottom: 1px solid var(--sh-border); display: flex; align-items: center; gap: 8px; }
.sh-card-title i { color: var(--sh-primary); }
.sh-card-body { padding: 20px; }
.sh-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 0; }
.sh-form-row .form-group { margin-bottom: 14px; }
.sh-form-row .form-group label { font-size: 13px; color: #475569; font-weight: 600; margin-bottom: 4px; }
.sh-form-row .form-control { border-radius: 8px; border: 1px solid var(--sh-border); }
.sh-submit-bar { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; background: var(--sh-bg); border-top: 1px solid var(--sh-border); }
.sh-submit-bar .btn { border-radius: 8px; padding: 9px 28px; font-weight: 600; font-size: 14px; }
.sh-checkbox-wrap { display: flex; align-items: center; gap: 8px; padding-top: 8px; }
.sh-checkbox-wrap label { font-weight: 600; color: #475569; font-size: 13px; margin: 0; }
</style>

<div class="sh-form-page">
    <?php $form = ActiveForm::begin(['id' => 'shareholders-form']); ?>
    <?= $form->errorSummary($model) ?>

    <div class="sh-card">
        <div class="sh-card-title"><i class="fa fa-user"></i> بيانات المساهم</div>
        <div class="sh-card-body">
            <div class="sh-form-row">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'أدخل اسم المساهم']) ?>
                <?= $form->field($model, 'phone')->textInput(['maxlength' => true, 'placeholder' => 'أدخل رقم الهاتف']) ?>
            </div>
            <div class="sh-form-row">
                <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'أدخل البريد الإلكتروني']) ?>
                <?= $form->field($model, 'national_id')->textInput(['maxlength' => true, 'placeholder' => 'أدخل رقم الهوية']) ?>
            </div>
        </div>
    </div>

    <div class="sh-card">
        <div class="sh-card-title"><i class="fa fa-pie-chart"></i> الأسهم والعضوية</div>
        <div class="sh-card-body">
            <div class="sh-form-row">
                <?= $form->field($model, 'share_count')->textInput(['type' => 'number', 'min' => 0, 'placeholder' => 'عدد الأسهم']) ?>
                <?= $form->field($model, 'join_date')->input('date') ?>
            </div>
            <div class="sh-form-row">
                <div class="sh-checkbox-wrap">
                    <?= $form->field($model, 'is_active')->checkbox() ?>
                </div>
            </div>
        </div>
    </div>

    <div class="sh-card">
        <div class="sh-card-title"><i class="fa fa-sticky-note"></i> ملاحظات</div>
        <div class="sh-card-body">
            <?= $form->field($model, 'notes')->textarea(['rows' => 4, 'placeholder' => 'أدخل أي ملاحظات إضافية...']) ?>
        </div>
    </div>

    <div class="sh-card">
        <div class="sh-submit-bar">
            <?= Html::a('إلغاء', ['index'], ['class' => 'btn btn-default']) ?>
            <?= Html::submitButton($model->isNewRecord ? 'إضافة مساهم' : 'حفظ التعديلات', [
                'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
