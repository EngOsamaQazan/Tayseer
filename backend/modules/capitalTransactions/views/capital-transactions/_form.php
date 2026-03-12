<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\modules\companies\models\Companies;
use backend\modules\capitalTransactions\models\CapitalTransactions;

/** @var yii\web\View $this */
/** @var backend\modules\capitalTransactions\models\CapitalTransactions $model */
/** @var backend\modules\companies\models\Companies|null $company */
/** @var yii\widgets\ActiveForm $form */
?>

<style>
:root {
    --ct-primary: #f59e0b;
    --ct-primary-dark: #d97706;
    --ct-primary-light: #fef3c7;
    --ct-border: #e2e8f0;
    --ct-bg: #f8fafc;
    --ct-r: 12px;
    --ct-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.ct-form-page { max-width: 900px; margin: 0 auto; }
.ct-card { background: #fff; border-radius: var(--ct-r); box-shadow: var(--ct-shadow); border: 1px solid var(--ct-border); margin-bottom: 18px; overflow: hidden; }
.ct-card-title { font-size: 15px; font-weight: 700; color: #1e293b; padding: 16px 20px; background: var(--ct-bg); border-bottom: 1px solid var(--ct-border); display: flex; align-items: center; gap: 8px; }
.ct-card-title i { color: var(--ct-primary); }
.ct-card-body { padding: 20px; }
.ct-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 0; }
.ct-form-row .form-group { margin-bottom: 14px; }
.ct-form-row .form-group label { font-size: 13px; color: #475569; font-weight: 600; margin-bottom: 4px; }
.ct-form-row .form-control { border-radius: 8px; border: 1px solid var(--ct-border); }
.ct-submit-bar { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; background: var(--ct-bg); border-top: 1px solid var(--ct-border); }
.ct-submit-bar .btn { border-radius: 8px; padding: 9px 28px; font-weight: 600; font-size: 14px; }
.ct-company-banner { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; padding: 16px 20px; border-radius: var(--ct-r); margin-bottom: 18px; display: flex; align-items: center; gap: 12px; }
.ct-company-banner i { font-size: 24px; opacity: .8; }
.ct-company-banner .ct-cb-name { font-size: 17px; font-weight: 700; }
.ct-company-banner .ct-cb-label { font-size: 12px; opacity: .85; }
</style>

<div class="ct-form-page">
    <?php if (isset($company) && $company): ?>
        <div class="ct-company-banner">
            <i class="fa fa-building"></i>
            <div>
                <div class="ct-cb-label">حركة رأس مال للمحفظة</div>
                <div class="ct-cb-name"><?= Html::encode($company->name) ?></div>
            </div>
        </div>
    <?php endif ?>

    <?php $form = ActiveForm::begin(['id' => 'capital-transactions-form']); ?>
    <?= $form->errorSummary($model) ?>

    <div class="ct-card">
        <div class="ct-card-title"><i class="fa fa-exchange"></i> بيانات الحركة</div>
        <div class="ct-card-body">
            <div class="ct-form-row">
                <?php if (isset($company) && $company): ?>
                    <?= $form->field($model, 'company_id')->hiddenInput()->label(false) ?>
                <?php else: ?>
                    <?= $form->field($model, 'company_id')->dropDownList(
                        ArrayHelper::map(Companies::find()->all(), 'id', 'name'),
                        ['prompt' => 'اختر المحفظة...']
                    ) ?>
                <?php endif ?>
                <?= $form->field($model, 'transaction_type')->dropDownList(
                    CapitalTransactions::getTransactionTypes(),
                    ['prompt' => 'اختر نوع العملية...']
                ) ?>
            </div>
            <div class="ct-form-row">
                <?= $form->field($model, 'amount')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'placeholder' => '0.00']) ?>
                <?= $form->field($model, 'transaction_date')->input('date') ?>
            </div>
        </div>
    </div>

    <div class="ct-card">
        <div class="ct-card-title"><i class="fa fa-credit-card"></i> معلومات الدفع</div>
        <div class="ct-card-body">
            <div class="ct-form-row">
                <?= $form->field($model, 'payment_method')->textInput(['maxlength' => true, 'placeholder' => 'مثال: تحويل بنكي، نقدي...']) ?>
                <?= $form->field($model, 'reference_number')->textInput(['maxlength' => true, 'placeholder' => 'رقم المرجع أو الحوالة']) ?>
            </div>
        </div>
    </div>

    <div class="ct-card">
        <div class="ct-card-title"><i class="fa fa-sticky-note"></i> ملاحظات</div>
        <div class="ct-card-body">
            <?= $form->field($model, 'notes')->textarea(['rows' => 4, 'placeholder' => 'أدخل أي ملاحظات إضافية...']) ?>
        </div>
    </div>

    <div class="ct-card">
        <div class="ct-submit-bar">
            <?php
            $cancelUrl = (isset($company) && $company)
                ? ['index', 'company_id' => $company->id]
                : ['index'];
            ?>
            <?= Html::a('إلغاء', $cancelUrl, ['class' => 'btn btn-default']) ?>
            <?= Html::submitButton($model->isNewRecord ? 'إضافة حركة' : 'حفظ التعديلات', [
                'class' => $model->isNewRecord ? 'btn btn-warning' : 'btn btn-primary',
                'style' => $model->isNewRecord ? 'background:#f59e0b;border-color:#f59e0b;color:#fff' : '',
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
