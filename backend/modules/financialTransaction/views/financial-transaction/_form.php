<?php
/**
 * نموذج الحركة المالية - بناء من الصفر
 * يشمل: المبلغ، الشركة، النوع، التصنيف، نوع الدخل، العقد، الوصف
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

use backend\modules\expenseCategories\models\ExpenseCategories;
use backend\modules\incomeCategory\models\IncomeCategory;
use backend\modules\contracts\models\Contracts;
use backend\modules\companies\models\Companies;
use backend\modules\financialTransaction\models\FinancialTransaction;
use backend\modules\accounting\models\Account;

$isNew = $model->isNewRecord;
$companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
$categories = ArrayHelper::map(ExpenseCategories::find()->asArray()->all(), 'id', 'name');
$incomeTypes = ArrayHelper::map(IncomeCategory::find()->asArray()->all(), 'id', 'name');
$contractIds = ArrayHelper::map(Contracts::find()->select(['id'])->asArray()->all(), 'id', 'id');
$hasCashField = $model->hasAttribute('cash_account_id');
$cashFunds = $hasCashField ? Account::getCashFundAccounts() : [];
?>

<div class="financial-transaction-form"
     x-data="{ txType: '<?= $model->type ?>', incomeType: '<?= $model->income_type ?>' }"
     @income-type-change.window="incomeType = $event.detail">
    <?php $form = ActiveForm::begin() ?>

    <fieldset>
        <legend><i class="fa fa-bank"></i> بيانات الحركة المالية</legend>
        <div class="row">
            <div class="<?= $hasCashField ? 'col-md-3' : 'col-md-4' ?>">
                <?= $form->field($model, 'amount')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('المبلغ') ?>
            </div>
            <div class="<?= $hasCashField ? 'col-md-3' : 'col-md-4' ?>">
                <?= $form->field($model, 'company_id')->dropDownList($companies, ['prompt' => '-- اختر الشركة --', 'class' => 'form-control'])->label('الشركة') ?>
            </div>
            <?php if ($hasCashField): ?>
            <div class="col-md-3">
                <?= $form->field($model, 'cash_account_id')->dropDownList($cashFunds, ['prompt' => '-- اختر الصندوق --', 'class' => 'form-control'])->label('الصندوق') ?>
            </div>
            <?php endif; ?>
            <div class="<?= $hasCashField ? 'col-md-3' : 'col-md-4' ?>">
                <?= $form->field($model, 'receiver_number')->textInput(['placeholder' => 'رقم المستلم'])->label('رقم المستلم') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'type', ['inputOptions' => ['id' => 'ft-type', 'x-model' => 'txType']])->dropDownList(['' => '-- النوع --', 1 => 'دائنة (دخل)', 2 => 'مدينة (مصاريف)'])->label('النوع') ?>
            </div>
            <div class="col-md-4 js-category-section"
                 x-show="txType == <?= FinancialTransaction::TYPE_OUTCOME ?>" x-transition x-cloak>
                <?= $form->field($model, 'category_id')->dropDownList($categories, ['prompt' => '-- تصنيف المصاريف --', 'class' => 'form-control'])->label('تصنيف المصاريف') ?>
            </div>
            <div class="col-md-4 js-income-section"
                 x-show="txType == <?= FinancialTransaction::TYPE_INCOME ?>" x-transition x-cloak>
                <?= $form->field($model, 'income_type', ['inputOptions' => ['id' => 'ft-income-type', 'x-model' => 'incomeType']])->dropDownList($incomeTypes, ['prompt' => '-- نوع الدخل --', 'class' => 'form-control', 'id' => 'ft-income-type'])->label('نوع الدخل') ?>
            </div>
            <div class="col-md-4 js-contract-section"
                 x-show="txType == <?= FinancialTransaction::TYPE_INCOME ?> && incomeType == <?= FinancialTransaction::TYPE_INCOME_MONTHLY ?>" x-transition x-cloak>
                <?= $form->field($model, 'contract_id')->dropDownList($contractIds, ['prompt' => '-- رقم العقد --', 'class' => 'form-control'])->label('العقد') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'description')->textarea(['rows' => 3, 'placeholder' => 'وصف الحركة المالية'])->label('الوصف') ?>
            </div>
        </div>
    </fieldset>

    <!-- زر الحفظ -->
    <?php if (!Yii::$app->request->isAjax): ?>
        <div class="jadal-form-actions">
            <?= Html::submitButton(
                $isNew ? '<i class="fa fa-plus"></i> إضافة حركة' : '<i class="fa fa-save"></i> حفظ التعديلات',
                ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
            ) ?>
        </div>
    <?php endif ?>

    <?php ActiveForm::end() ?>
</div>

<?php
$typeIncome = FinancialTransaction::TYPE_INCOME;
$typeOutcome = FinancialTransaction::TYPE_OUTCOME;
$monthlyType = FinancialTransaction::TYPE_INCOME_MONTHLY;

$this->registerJs(<<<JS
/* OLD jQuery - replaced by Alpine.js
$(document).on('change', '#ft-type', function(){
    var val = $(this).val();
    if (val == {$typeIncome}) {
        $('.js-income-section').show();
        $('.js-category-section').hide();
    } else if (val == {$typeOutcome}) {
        $('.js-income-section').hide();
        $('.js-contract-section').hide();
        $('.js-category-section').show();
    } else {
        $('.js-income-section, .js-category-section, .js-contract-section').hide();
    }
});
$(document).on('change', '#ft-income-type', function(){
    var val = $(this).val();
    if (val == {$monthlyType}) {
        $('.js-contract-section').show();
    } else {
        $('.js-contract-section').hide();
    }
});
*/

/* Bridge Select2 change event to Alpine */
$('#ft-income-type').on('change', function(){
    window.dispatchEvent(new CustomEvent('income-type-change', { detail: $(this).val() }));
});

JS
);
?>
