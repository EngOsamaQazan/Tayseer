<?php
/**
 * نموذج المتابعة - بناء من الصفر
 * يشمل: معلومات المتابعة + حالة العقد + الملخص المالي
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use backend\modules\contracts\models\Contracts;
use backend\modules\followUp\helper\ContractCalculations;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$calc = new ContractCalculations($contract_id);
$isNew = $model->isNewRecord;

/* حساب مدة التعديل المسموحة */
$canEdit = true;
if (!$isNew) {
    $created = new DateTime($model->date_time);
    $now = new DateTime();
    $canEdit = ($created->diff($now)->h + ($created->diff($now)->days * 24)) < 2;
}

?>

<!-- ═══ ملخص العقد ═══ -->
<?= $this->render('partial/tabs.php', [
    'model' => $model,
    'contract_id' => $contract_id,
    'contractCalculations' => $calc,
    'modelsPhoneNumbersFollwUps' => $modelsPhoneNumbersFollwUps,
]) ?>

<!-- حالة العقد -->
<div class="text-center" style="margin:15px 0">
    <?php
    $statusBadgeClass = ['active' => 'bg-success', 'pending' => 'bg-warning text-dark', 'judiciary' => 'bg-danger', 'legal_department' => 'bg-info', 'finished' => 'bg-secondary', 'canceled' => 'bg-secondary'];
    $statusLabels = ['active' => 'نشط', 'pending' => 'معلّق', 'judiciary' => 'قضاء', 'legal_department' => 'قانوني', 'finished' => 'منتهي', 'canceled' => 'ملغي', 'settlement' => 'تسوية'];
    $st = $calc->contract_model->status;
    ?>
    <span class="badge <?= $statusBadgeClass[$st] ?? 'bg-secondary' ?>" style="font-size:16px;padding:8px 20px">
        حالة العقد: <?= $statusLabels[$st] ?? $st ?>
    </span>
    <?php if ($calc->contract_model->is_can_not_contact == 1): ?>
        <p class="text-danger" style="margin-top:8px"><i class="fa fa-exclamation-triangle"></i> لا يوجد أرقام تواصل</p>
    <?php endif ?>
</div>

<!-- ملاحظات العقد -->
<?php if (!empty($calc->contract_model->notes)): ?>
    <div class="alert alert-info">
        <i class="fa fa-sticky-note"></i> <strong>ملاحظات العقد:</strong> <?= Html::encode($calc->contract_model->notes) ?>
    </div>
<?php endif ?>

<!-- ═══ نموذج المتابعة ═══ -->
<?php
$result = Contracts::findOne($contract_id);
$formConfig = ['id' => 'dynamic-form'];
if ($isNew) {
    $formConfig['action'] = Url::to(['/followUp/follow-up/create', 'contract_id' => $contract_id]);
} else {
    $formConfig['action'] = Url::to(['/followUp/follow-up/update', 'contract_id' => $contract_id, 'id' => Yii::$app->getRequest()->getQueryParam('id')]);
}
$form = ActiveForm::begin($formConfig);
?>

<?= $form->field($model, 'contract_id')->hiddenInput(['value' => $contract_id])->label(false) ?>
<?= $form->field($model, 'created_by')->hiddenInput(['value' => Yii::$app->user->id])->label(false) ?>

<fieldset>
    <legend><i class="fa fa-phone"></i> بيانات المتابعة</legend>
    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'reminder')->widget(FlatpickrWidget::class, [
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ])->label('تاريخ التذكير') ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'promise_to_pay_at')->widget(FlatpickrWidget::class, [
                'options' => ['placeholder' => 'تاريخ الوعد بالدفع'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ])->label('وعد بالدفع') ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <?= $form->field($model, 'notes')->textarea(['rows' => 4, 'placeholder' => 'ملاحظات المتابعة'])->label('الملاحظات') ?>
        </div>
    </div>
</fieldset>

<!-- متابعة الأرقام -->
<fieldset>
    <legend><i class="fa fa-phone-square"></i> متابعة الأرقام</legend>
    <?= $this->render('partial/phone_numbers_follow_up', [
        'form' => $form,
        'model' => $result,
        'modelsPhoneNumbersFollwUps' => $modelsPhoneNumbersFollwUps,
    ]) ?>
</fieldset>

<!-- زر الحفظ -->
<?php if ($isNew || $canEdit): ?>
    <?php if (!Yii::$app->request->isAjax): ?>
        <div class="jadal-form-actions">
            <?= Html::submitButton(
                $isNew ? '<i class="fa fa-plus"></i> إضافة متابعة' : '<i class="fa fa-save"></i> حفظ التعديلات',
                ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
            ) ?>
        </div>
    <?php endif ?>
<?php endif ?>

<?php ActiveForm::end() ?>

<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--ty-clr-primary,#800020)"></i>
                </div>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>

<?= $this->render('modals.php', ['contractCalculations' => $calc, 'contract_id' => $contract_id]) ?>

<?php
$this->registerJsVar('is_loan', $calc->contract_model->is_loan ?? 0, yii\web\View::POS_HEAD);
$this->registerJsVar('change_status_url', Url::to(['/followUp/follow-up/change-status']), yii\web\View::POS_HEAD);
$this->registerJsVar('send_sms', Url::to(['/followUp/follow-up/send-sms']), yii\web\View::POS_HEAD);
$this->registerJsVar('customer_info_url', Url::to(['/followUp/follow-up/custamer-info']), yii\web\View::POS_HEAD);
$this->registerJsVar('quick_update_customer_url', Url::to(['/followUp/follow-up/quick-update-customer']), yii\web\View::POS_HEAD);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/follow-up.js', ['depends' => [\yii\web\JqueryAsset::class]]);

$_fCust = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['contract_id' => $contract_id, 'customer_type' => 'client'])->one();
$_fCustName = ($_fCust && $_fCust->customer) ? $_fCust->customer->name : 'غير محدد';
$_fAllParties = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['contract_id' => $contract_id])->all();
$_fPartyNames = [];
foreach ($_fAllParties as $_fcc) {
    if ($_fcc->customer) $_fPartyNames[] = $_fcc->customer->name;
}
$_fJudiciary = \backend\modules\judiciary\models\Judiciary::find()
    ->where(['contract_id' => $contract_id, 'is_deleted' => 0])->one();
$_fCourtName = ($_fJudiciary && $_fJudiciary->court) ? $_fJudiciary->court->name : '';
$_fCaseNum = $_fJudiciary ? (($_fJudiciary->judiciary_number ?: '') . ($_fJudiciary->year ? '/' . $_fJudiciary->year : '')) : '';
$_statusLabelsMap = ['active' => 'نشط', 'pending' => 'معلّق', 'judiciary' => 'قضاء', 'legal_department' => 'قانوني', 'finished' => 'منتهي', 'canceled' => 'ملغي', 'settlement' => 'تسوية'];
$_fSmsVars = [
    'اسم_العميل'      => $_fCustName,
    'أطراف_العقد'      => implode(' و ', $_fPartyNames) ?: 'غير محدد',
    'رقم_العقد'        => (string)$contract_id,
    'حالة_العقد'       => $_statusLabelsMap[$calc->contract_model->status] ?? $calc->contract_model->status,
    'المبلغ_الإجمالي'  => number_format($calc->totalDebt(), 2),
    'المدفوع'          => number_format($calc->paidAmount(), 2),
    'المتبقي'          => number_format($calc->remainingAmount(), 2),
    'المستحق'          => number_format($calc->amountShouldBePaid(), 2),
    'المتأخر'          => number_format($calc->deservedAmount(), 2),
    'القسط_الشهري'     => number_format($calc->effectiveInstallment(), 2),
    'أقساط_متأخرة'     => (string)$calc->overdueInstallments(),
    'أقساط_متبقية'     => (string)$calc->remainingInstallments(),
    'أتعاب_المحاماة'   => number_format($calc->allLawyerCosts(), 2),
];
if ($_fJudiciary) {
    $_fSmsVars['اسم_المحكمة'] = $_fCourtName;
    $_fSmsVars['رقم_القضية'] = $_fCaseNum;
}
$this->registerJs("window.SMS_VARS=" . \yii\helpers\Json::encode($_fSmsVars) . ";", yii\web\View::POS_HEAD);

$this->registerJs("if(typeof OCP_CONFIG==='undefined'){window.OCP_CONFIG={urls:{" .
    "smsDraftList:"  . \yii\helpers\Json::encode(Url::to(['/followUp/follow-up/sms-draft-list'])) . "," .
    "smsDraftSave:"  . \yii\helpers\Json::encode(Url::to(['/followUp/follow-up/sms-draft-save'])) . "," .
    "smsDraftDelete:" . \yii\helpers\Json::encode(Url::to(['/followUp/follow-up/sms-draft-delete'])) .
    "}};}", yii\web\View::POS_HEAD);
?>
