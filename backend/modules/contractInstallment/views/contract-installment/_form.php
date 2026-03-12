<?php
/**
 * نموذج إدخال/تعديل دفعة (قسط)
 * ================================
 * يحتوي على حقول: التاريخ، المبلغ، نوع الدفع، البنك، المدفوع بواسطة، الغرض، التصنيف
 * يعرض ملخصاً مالياً سريعاً للعقد في الأعلى
 * 
 * @var yii\web\View $this
 * @var backend\modules\contractInstallment\models\ContractInstallment $model نموذج الدفعة
 * @var backend\modules\contracts\models\Contracts $contract_model نموذج العقد
 * @var int $contract_id رقم العقد
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\incomeCategory\models\IncomeCategory;

/* === حسابات مالية سريعة === */
$origin = new DateTime($contract_model->first_installment_date);
$target = new DateTime(date('Y-m-d'));
$interval = $origin->diff($target);
$batchesShouldBePaid = $interval->format('%R%m') + 1;
$amountShouldBePaid = $batchesShouldBePaid * $contract_model->monthly_installment_value;
$paidAmount = ContractInstallment::find()
    ->where(['contract_id' => $contract_model->id])
    ->sum('amount') ?? 0;
$deservedAmount = $amountShouldBePaid - $paidAmount;
?>

<div class="contract-installment-form">

    <!-- === ملخص مالي سريع === -->
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h4><?= number_format($contract_model->total_value, 2) ?></h4>
                    <p><?= Yii::t('app', 'القيمة الإجمالية') ?></p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h4><?= number_format($paidAmount, 2) ?></h4>
                    <p><?= Yii::t('app', 'المدفوع') ?></p>
                </div>
                <div class="icon"><i class="fa fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h4><?= number_format($deservedAmount, 2) ?></h4>
                    <p><?= Yii::t('app', 'المستحق') ?></p>
                </div>
                <div class="icon"><i class="fa fa-clock-o"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h4><?= number_format($contract_model->total_value - $paidAmount, 2) ?></h4>
                    <p><?= Yii::t('app', 'المتبقي') ?></p>
                </div>
                <div class="icon"><i class="fa fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <!-- === بداية النموذج === -->
    <?php $form = ActiveForm::begin() ?>

    <!-- حقل مخفي لرقم العقد -->
    <?= $form->field($model, 'contract_id')->hiddenInput(['value' => $contract_id])->label(false) ?>

    <fieldset>
        <legend>
            <i class="fa fa-money"></i> <?= Yii::t('app', 'بيانات الدفعة') ?>
        </legend>

        <!-- التاريخ والمبلغ -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => Yii::t('app', 'اختر تاريخ الدفع ...')],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label(Yii::t('app', 'تاريخ الدفع')) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'amount')
                    ->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])
                    ->label(Yii::t('app', 'المبلغ')) ?>
            </div>
        </div>

        <!-- نوع الدفع وبنك الإيصال -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'payment_type')
                    ->dropDownList(
                        ArrayHelper::map(
                            \backend\modules\paymentType\models\PaymentType::find()->all(),
                            'id',
                            'name'
                        ),
                        ['prompt' => Yii::t('app', '-- اختر نوع الدفع --')]
                    )
                    ->label(Yii::t('app', 'نوع الدفع')) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'receipt_bank')
                    ->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'اسم البنك')])
                    ->label(Yii::t('app', 'بنك الإيصال')) ?>
            </div>
        </div>

        <!-- المدفوع بواسطة والغرض -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, '_by')
                    ->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'اسم الدافع')])
                    ->label(Yii::t('app', 'بواسطة')) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'payment_purpose')
                    ->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'غرض الدفعة')])
                    ->label(Yii::t('app', 'غرض الدفع')) ?>
            </div>
        </div>

        <!-- تصنيف الدفعة -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'type')
                    ->dropDownList(
                        ArrayHelper::map(IncomeCategory::find()->all(), 'id', 'name'),
                        ['prompt' => Yii::t('app', '-- اختر التصنيف --')]
                    )
                    ->label(Yii::t('app', 'تصنيف الدفعة')) ?>
            </div>
        </div>
    </fieldset>

    <!-- === أزرار الإرسال === -->
    <div class="form-group jadal-form-actions" style="margin-top: 20px;">
        <?= Html::submitButton(
            $model->isNewRecord
                ? '<i class="fa fa-print"></i> ' . Yii::t('app', 'حفظ وطباعة')
                : '<i class="fa fa-print"></i> ' . Yii::t('app', 'حفظ وطباعة'),
            ['name' => 'print', 'class' => 'btn btn-success btn-lg']
        ) ?>

        <?php if (!Yii::$app->request->isAjax) : ?>
            <?= Html::submitButton(
                $model->isNewRecord
                    ? '<i class="fa fa-plus"></i> ' . Yii::t('app', 'حفظ')
                    : '<i class="fa fa-save"></i> ' . Yii::t('app', 'حفظ التعديلات'),
                ['class' => 'btn btn-primary btn-lg']
            ) ?>
        <?php endif ?>
    </div>

    <?php ActiveForm::end() ?>
</div>
