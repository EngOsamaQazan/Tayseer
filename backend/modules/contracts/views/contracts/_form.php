<?php
/**
 * Contract Create/Update Form
 *
 * @var yii\web\View $this
 * @var backend\modules\contracts\models\Contracts $model
 * @var array $companies
 * @var array $inventoryItems
 * @var array $scannedSerials
 * @var array $existingCustomers
 * @var array $existingGuarantors
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

$isNew = $model->isNewRecord;
$existingCustomers  = $existingCustomers ?? [];
$existingGuarantors = $existingGuarantors ?? [];
$scannedSerials     = $scannedSerials ?? [];

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contract-form.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/contract-form.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);
?>

<div class="cf">
<?php
$flashes = Yii::$app->session->getAllFlashes(true);
foreach ($flashes as $type => $msg): ?>
    <div class="cf-flash cf-flash-<?= $type === 'error' ? 'err' : ($type === 'success' ? 'ok' : 'warn') ?>">
        <i class="fa fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
        <?= Html::encode(is_array($msg) ? implode(' ', $msg) : $msg) ?>
    </div>
<?php endforeach; ?>
<?php $form = ActiveForm::begin(['id' => 'contract-form', 'options' => ['autocomplete' => 'off']]) ?>
<?= Html::hiddenInput('Contracts[is_legal_department]', $model->is_legal_department ?? 0, ['id' => 'cf-is-legal']) ?>

<!-- ═══ Section Nav ═══ -->
<nav class="cf-nav">
    <a class="cf-nav-pill active" href="#cf-sec-customer"><i class="fa fa-user"></i> <span>العميل</span></a>
    <a class="cf-nav-pill" href="#cf-sec-contract"><i class="fa fa-file-text-o"></i> <span>العقد</span></a>
    <a class="cf-nav-pill" href="#cf-sec-devices"><i class="fa fa-barcode"></i> <span>الأجهزة</span> <em class="cf-nav-badge" id="cf-dev-count" style="display:none">0</em></a>
    <a class="cf-nav-pill" href="#cf-sec-finance"><i class="fa fa-money"></i> <span>المالية</span></a>
    <a class="cf-nav-pill" href="#cf-sec-schedule"><i class="fa fa-calendar"></i> <span>الأقساط</span></a>
</nav>

<div class="cf-layout">
<!-- ══════════════════════ Main Column ══════════════════════ -->
<div class="cf-main">

<!-- ─── 1. Customer Section ─── -->
<div class="cf-card" id="cf-sec-customer">
    <div class="cf-card-hd"><i class="fa fa-user cf-ic-customer"></i><span class="cf-card-title">العميل والكفلاء</span></div>
    <div class="cf-card-bd">
        <div class="row">
            <!-- Normal: Single Customer -->
            <div class="col-md-5" id="cf-normal-cust">
                <label class="cf-label">العميل <span style="color:var(--cf-err)">*</span></label>
                <div class="cf-search-wrap">
                    <i class="fa fa-search cf-search-icon"></i>
                    <input type="text" id="cf-cust-search" class="cf-search-input" placeholder="ابحث بالاسم أو الرقم الوطني أو الهاتف..." autocomplete="off">
                    <div id="cf-cust-results" class="cf-dropdown"></div>
                </div>
                <div class="cf-chips" id="cf-cust-chips"></div>
            </div>

            <!-- Solidarity: Multiple Customers -->
            <div class="col-md-5" id="cf-sol-cust" style="display:none">
                <label class="cf-label">العملاء (تضامني) <span style="color:var(--cf-err)">*</span></label>
                <div class="cf-search-wrap">
                    <i class="fa fa-search cf-search-icon"></i>
                    <input type="text" id="cf-sol-search" class="cf-search-input" placeholder="ابحث واختر العملاء..." autocomplete="off">
                    <div id="cf-sol-results" class="cf-dropdown"></div>
                </div>
                <div class="cf-chips" id="cf-sol-chips"></div>
            </div>

            <!-- Type -->
            <div class="col-md-2">
                <?= $form->field($model, 'type', ['inputOptions' => ['id' => 'cf-type']])->dropDownList(
                    \backend\modules\contracts\models\Contracts::getTypeLabels(),
                    ['class' => 'form-control']
                )->label('النوع') ?>
            </div>

            <!-- Guarantors -->
            <div class="col-md-5">
                <label class="cf-label">الكفلاء</label>
                <div class="cf-search-wrap">
                    <i class="fa fa-search cf-search-icon"></i>
                    <input type="text" id="cf-guar-search" class="cf-search-input" placeholder="ابحث واختر الكفلاء..." autocomplete="off">
                    <div id="cf-guar-results" class="cf-dropdown"></div>
                </div>
                <div class="cf-chips" id="cf-guar-chips"></div>
            </div>
        </div>
    </div>
    <div class="cf-cust-bar" id="cf-cust-bar">
        <div class="cf-cust-chip"><small>ID</small><b id="cf-nc-dbid" style="color:var(--cf-teal)">—</b></div>
        <div class="cf-cust-chip"><small>الاسم</small><b id="cf-nc-name">—</b></div>
        <div class="cf-cust-chip"><small>الوطني</small><b id="cf-nc-id">—</b></div>
        <div class="cf-cust-chip"><small>الميلاد</small><b id="cf-nc-birth">—</b></div>
        <div class="cf-cust-chip"><small>الوظيفة</small><b id="cf-nc-job">—</b></div>
        <div class="cf-cust-chip"><small>العقود</small><b id="cf-nc-cnt" style="color:var(--cf-teal)">—</b></div>
    </div>
</div>

<!-- ─── 2. Contract Info ─── -->
<div class="cf-card" id="cf-sec-contract">
    <div class="cf-card-hd"><i class="fa fa-file-text-o cf-ic-contract"></i><span class="cf-card-title">معلومات العقد</span></div>
    <div class="cf-card-bd">
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'company_id')->dropDownList($companies, [
                    'prompt' => '— اختر الشركة —', 'class' => 'form-control',
                ])->label('الشركة') ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'Date_of_sale')->input('date', ['class' => 'form-control'])->label('تاريخ البيع') ?>
            </div>
        </div>
    </div>
</div>

<!-- ─── 3. Devices ─── -->
<div class="cf-card" id="cf-sec-devices">
    <div class="cf-card-hd"><i class="fa fa-barcode cf-ic-device"></i><span class="cf-card-title">الأجهزة</span></div>
    <div class="cf-card-bd">
        <div class="cf-scanner">
            <input type="text" id="cf-serial-in" class="form-control cf-scanner-input" placeholder="امسح أو اكتب الرقم التسلسلي..." autocomplete="off">
            <button type="button" id="cf-scan-btn" class="cf-scan-btn"><i class="fa fa-bolt"></i> مسح</button>
        </div>
        <div class="cf-scan-hint"><kbd>Enter</kbd> للمسح السريع · السكانر يعمل تلقائياً</div>
        <div id="cf-scan-msg" class="cf-scan-msg"></div>

        <table class="cf-dev-table" id="cf-dev-table" style="display:none">
            <thead><tr><th>#</th><th>الجهاز</th><th>الرقم التسلسلي</th><th>النوع</th><th></th></tr></thead>
            <tbody id="cf-dev-body"></tbody>
        </table>
        <div class="cf-dev-empty" id="cf-dev-empty">
            <i class="fa fa-mobile"></i>
            امسح الرقم التسلسلي لإضافة جهاز للعقد
        </div>

        <?php foreach ($scannedSerials as $s): ?>
            <input type="hidden" name="serial_ids[]" value="<?= $s['id'] ?>" class="cf-sh" data-sid="<?= $s['id'] ?>" data-sn="<?= Html::encode($s['serial_number']) ?>">
        <?php endforeach ?>

        <span class="cf-manual-link" id="cf-manual-link"><i class="fa fa-list-ul"></i> إضافة يدوية بدون سيريال</span>
        <div class="cf-manual-box" id="cf-manual-box">
            <select id="cf-manual-sel" class="form-control" style="flex:1">
                <option value="">— اختر الصنف —</option>
                <?php foreach ($inventoryItems as $id => $name): ?>
                    <option value="<?= $id ?>"><?= Html::encode($name) ?></option>
                <?php endforeach ?>
            </select>
            <button type="button" id="cf-manual-add" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> أضف</button>
        </div>
    </div>
</div>

<!-- ─── 4. Finance ─── -->
<div class="cf-card" id="cf-sec-finance">
    <div class="cf-card-hd"><i class="fa fa-money cf-ic-finance"></i><span class="cf-card-title">المعلومات المالية</span></div>
    <div class="cf-card-bd">
        <div class="cf-money-row">
            <div class="cf-money-card">
                <label>الدفعة الأولى</label>
                <?= Html::activeTextInput($model, 'first_installment_value', [
                    'type' => 'number', 'step' => '1', 'min' => '0', 'placeholder' => '0',
                    'class' => 'form-control', 'id' => 'cf-fv',
                ]) ?>
                <div class="cf-currency">د.أ</div>
            </div>
            <div class="cf-money-card">
                <label>إجمالي العقد</label>
                <?= Html::activeTextInput($model, 'total_value', [
                    'type' => 'number', 'step' => '1', 'min' => '0', 'placeholder' => '0',
                    'class' => 'form-control', 'id' => 'cf-tv',
                ]) ?>
                <div class="cf-currency">د.أ</div>
            </div>
            <div class="cf-money-card">
                <label>القسط الشهري</label>
                <?= Html::activeTextInput($model, 'monthly_installment_value', [
                    'type' => 'number', 'step' => '1', 'min' => '0', 'placeholder' => '0',
                    'class' => 'form-control', 'id' => 'cf-mv',
                ]) ?>
                <div class="cf-currency">د.أ</div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'first_installment_date')->input('date', [
                    'class' => 'form-control', 'id' => 'cf-fd',
                ])->label('تاريخ أول قسط') ?>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label">خصم الالتزام</label>
                    <div class="input-group">
                        <?= Html::activeTextInput($model, 'commitment_discount', [
                            'type' => 'number', 'step' => '1', 'min' => '0', 'placeholder' => '0',
                            'class' => 'form-control',
                        ]) ?>
                        <span class="input-group-addon" style="background:var(--cf-bg);border:1.5px solid var(--cf-border);color:var(--cf-text2);font-size:12px;font-weight:700">د.أ</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'loss_commitment')->textInput([
                    'type' => 'number', 'placeholder' => '0', 'class' => 'form-control',
                ])->label('التزام الخسارة') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'notes')->textarea([
                    'rows' => 2, 'placeholder' => 'ملاحظات إضافية...', 'class' => 'form-control',
                ])->label('ملاحظات') ?>
            </div>
        </div>
    </div>
</div>

<!-- ─── 5. Schedule ─── -->
<div class="cf-card" id="cf-sec-schedule" style="display:none">
    <div class="cf-card-hd"><i class="fa fa-calendar cf-ic-schedule"></i><span class="cf-card-title">جدول الأقساط المتوقع</span></div>
    <div class="cf-card-bd" style="padding:0">
        <div class="cf-inst-table">
            <table><thead><tr><th>#</th><th>المبلغ</th><th>الشهر</th><th>السنة</th></tr></thead>
            <tbody id="cf-inst-body"></tbody></table>
        </div>
    </div>
</div>

</div><!-- /cf-main -->

<!-- ══════════════════════ Sidebar Summary ══════════════════════ -->
<aside class="cf-sidebar">
    <div class="cf-summary">
        <div class="cf-sum-hd"><h4><i class="fa fa-calculator"></i> ملخص العقد</h4></div>
        <div class="cf-sum-bd">
            <div class="cf-sum-devices" id="cf-sum-devs"><i class="fa fa-mobile"></i> الأجهزة: <b>0</b></div>
            <div class="cf-sum-row"><span class="cf-sum-label">إجمالي العقد</span><span class="cf-sum-val big" id="cf-ns-total">0 د.أ</span></div>
            <div class="cf-sum-row"><span class="cf-sum-label">الدفعة الأولى</span><span class="cf-sum-val" id="cf-ns-first">0 د.أ</span></div>
            <div class="cf-sum-row"><span class="cf-sum-label">المتبقي بالتقسيط</span><span class="cf-sum-val teal" id="cf-ns-remaining">0 د.أ</span></div>
            <div class="cf-sum-divider"></div>
            <div class="cf-sum-row"><span class="cf-sum-label">القسط الشهري</span><span class="cf-sum-val ok" id="cf-ns-monthly">0 د.أ</span></div>
            <div class="cf-sum-row"><span class="cf-sum-label">عدد الأقساط</span><span class="cf-sum-val" id="cf-ns-count">—</span></div>
            <div class="cf-sum-row"><span class="cf-sum-label">تاريخ أول قسط</span><span class="cf-sum-val" id="cf-ns-date">—</span></div>
        </div>
        <div class="cf-sum-actions">
            <?= Html::submitButton('<i class="fa fa-print"></i> ' . ($isNew ? 'إنشاء وطباعة' : 'حفظ وطباعة'), [
                'name' => 'print', 'class' => 'btn cf-btn-print',
            ]) ?>
            <?= Html::submitButton('<i class="fa ' . ($isNew ? 'fa-plus' : 'fa-save') . '"></i> ' . ($isNew ? 'إنشاء العقد' : 'حفظ التعديلات'), [
                'class' => 'btn cf-btn-save',
            ]) ?>
        </div>
    </div>
</aside>

</div><!-- /cf-layout -->

<!-- Mobile Actions -->
<div class="cf-mobile-actions">
    <?= Html::submitButton('<i class="fa fa-print"></i> ' . ($isNew ? 'إنشاء وطباعة' : 'حفظ وطباعة'), [
        'name' => 'print', 'class' => 'btn cf-btn-print',
    ]) ?>
    <?= Html::submitButton('<i class="fa ' . ($isNew ? 'fa-plus' : 'fa-save') . '"></i> ' . ($isNew ? 'حفظ' : 'حفظ'), [
        'class' => 'btn cf-btn-save',
    ]) ?>
</div>

<?php ActiveForm::end() ?>
</div>

<?php
$this->registerJs('ContractForm.init(' . Json::encode([
    'searchUrl'       => Url::to(['/customers/search-customers']),
    'customerDataUrl' => Url::to(['/customers/customer-data']),
    'lookupSerialUrl' => Url::to(['/contracts/contracts/lookup-serial']),
    'type'            => $model->type ?: 'normal',
    'existingCustomers'  => $existingCustomers,
    'existingGuarantors' => $existingGuarantors,
    'preloadedSerials'   => $scannedSerials,
]) . ');', \yii\web\View::POS_READY);
?>
