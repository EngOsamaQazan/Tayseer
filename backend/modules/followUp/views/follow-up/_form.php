<?php
/**
 * نموذج المتابعة - بناء من الصفر
 * يشمل: معلومات المتابعة + حالة العقد + الملخص المالي
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Modal;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use johnitvn\ajaxcrud\CrudAsset;
use backend\modules\contracts\models\Contracts;
use backend\modules\followUp\helper\ContractCalculations;

CrudAsset::register($this);
$calc = new ContractCalculations($contract_id);
$isNew = $model->isNewRecord;

/* حساب مدة التعديل المسموحة */
$canEdit = true;
if (!$isNew) {
    $created = new DateTime($model->date_time);
    $now = new DateTime();
    $canEdit = ($created->diff($now)->h + ($created->diff($now)->days * 24)) < 2;
}

/* بيانات مرجعية */
$feelings = ArrayHelper::map(\backend\modules\feelings\models\Feelings::find()->asArray()->all(), 'id', 'name');
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
    $statusColors = ['active' => 'success', 'pending' => 'warning', 'judiciary' => 'danger', 'legal_department' => 'info', 'finished' => 'default', 'canceled' => 'default'];
    $statusLabels = ['active' => 'نشط', 'pending' => 'معلّق', 'judiciary' => 'قضاء', 'legal_department' => 'قانوني', 'finished' => 'منتهي', 'canceled' => 'ملغي', 'settlement' => 'تسوية'];
    $st = $calc->contract_model->status;
    ?>
    <span class="label label-<?= $statusColors[$st] ?? 'default' ?>" style="font-size:16px;padding:8px 20px">
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
        <div class="col-md-3">
            <?= $form->field($model, 'connection_goal')->dropDownList([1 => 'تحصيل', 2 => 'مصالحة', 3 => 'إنهاء عقد'], ['prompt' => '-- الهدف --'])->label('هدف الاتصال') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'reminder')->widget(FlatpickrWidget::class, [
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ])->label('تاريخ التذكير') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'promise_to_pay_at')->widget(FlatpickrWidget::class, [
                'options' => ['placeholder' => 'تاريخ الوعد بالدفع'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ])->label('وعد بالدفع') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'feeling')->dropDownList($feelings, ['prompt' => '-- الانطباع --'])->label('الانطباع') ?>
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

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>

<?= $this->render('modals.php', ['contractCalculations' => $calc, 'contract_id' => $contract_id]) ?>

<?php
$this->registerJsVar('is_loan', $calc->contract_model->is_loan ?? 0, yii\web\View::POS_HEAD);
$this->registerJsVar('change_status_url', Url::to(['/followUp/follow-up/change-status']), yii\web\View::POS_HEAD);
$this->registerJsVar('send_sms', Url::to(['/followUp/follow-up/send-sms']), yii\web\View::POS_HEAD);
$this->registerJsVar('customer_info_url', Url::to(['/followUp/follow-up/custamer-info']), yii\web\View::POS_HEAD);
$this->registerJsVar('quick_update_customer_url', Url::to(['/followUp/follow-up/quick-update-customer']), yii\web\View::POS_HEAD);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/follow-up.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
