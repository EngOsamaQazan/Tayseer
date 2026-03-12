<?php
/**
 * نموذج أمر الشراء v2 — متكامل مع إضافة سريعة
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\inventoryInvoices\models\InventoryInvoices;

$csrfToken = Yii::$app->request->csrfToken;
$quickAddSupplierUrl = Url::to(['/inventoryItems/inventory-items/quick-add-supplier']);
?>

<style>
.po-form { padding: 16px; }
.po-form .form-group { margin-bottom: 14px; }
.po-section { margin-bottom: 20px; }
.po-section-title { font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }
.po-inline-add { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700; color: #0369a1; background: #e0f2fe; border: none; cursor: pointer; margin-right: 8px; }
.po-inline-add:hover { background: #bae6fd; }
</style>

<div class="po-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']); ?>

    <!-- ═══ معلومات أمر الشراء ═══ -->
    <div class="po-section">
        <div class="po-section-title">
            <i class="fa fa-info-circle" style="color:#0369a1"></i> معلومات أمر الشراء
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <?= $form->field($model, 'suppliers_id')->widget(Select2::class, [
                    'data' => ArrayHelper::map(InventorySuppliers::find()->all(), 'id', 'name'),
                    'options' => ['placeholder' => '— اختر المورد —', 'id' => 'supplier-select'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label('المورد <span style="color:red">*</span> <button type="button" class="po-inline-add" id="btn-inline-supplier"><i class="fa fa-plus"></i> جديد</button>') ?>
            </div>
            <div class="col-lg-4 col-md-6">
                <?= $form->field($model, 'company_id')->widget(Select2::class, [
                    'data' => ArrayHelper::map(\backend\modules\companies\models\Companies::find()->all(), 'id', 'name'),
                    'options' => ['placeholder' => '— اختر الشركة —'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label('الشركة') ?>
            </div>
            <div class="col-lg-2 col-md-6">
                <?= $form->field($model, 'type')->dropDownList(InventoryInvoices::getTypeList())->label('طريقة الدفع') ?>
            </div>
            <div class="col-lg-2 col-md-6">
                <?= $form->field($model, 'date')->textInput([
                    'type' => 'date',
                    'value' => $model->date ?: date('Y-m-d'),
                ])->label('التاريخ') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <?= $form->field($model, 'invoice_notes')->textarea([
                    'rows' => 2,
                    'placeholder' => 'ملاحظات إضافية على أمر الشراء...',
                ])->label('ملاحظات') ?>
            </div>
            <div class="col-lg-4">
                <?= $form->field($model, 'discount_amount')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => 0,
                    'placeholder' => '0',
                    'style' => 'direction:ltr',
                ])->label('الخصم (مبلغ)') ?>
            </div>
        </div>
    </div>

    <!-- ═══ بنود الأصناف ═══ -->
    <div class="po-section">
        <div class="po-section-title">
            <i class="fa fa-cube" style="color:#7c3aed"></i> بنود الأصناف
        </div>

        <?= $this->render('_items_inventory_invoices', [
            'itemsInventoryInvoices' => $itemsInventoryInvoices,
            'form' => $form,
        ]) ?>
    </div>

    <?php if (!Yii::$app->request->isAjax): ?>
    <div class="form-group" style="margin-top:20px; padding-top:14px; border-top:1px solid #e2e8f0">
        <?= Html::submitButton($model->isNewRecord
            ? '<i class="fa fa-check"></i> إنشاء أمر الشراء'
            : '<i class="fa fa-check"></i> تحديث أمر الشراء',
            ['class' => 'btn btn-success btn-lg', 'style' => 'font-weight:700;padding:10px 30px;border-radius:8px']
        ) ?>
        <?= Html::a('إلغاء', ['index'], ['class' => 'btn btn-default btn-lg', 'style' => 'margin-right:10px;border-radius:8px']) ?>
    </div>
    <?php endif ?>

    <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<JS
/* ── إضافة مورد inline ── */
$('#btn-inline-supplier').click(function(e){
    e.preventDefault();
    var name = prompt('اسم المورد:');
    if (!name) return;
    var phone = prompt('رقم الهاتف:');
    if (!phone) return;

    $.post('$quickAddSupplierUrl', {
        name: name, phone: phone, _csrf: '$csrfToken'
    }, function(resp) {
        if (resp.success) {
            var newOption = new Option(resp.name, resp.id, true, true);
            $('#supplier-select').append(newOption).trigger('change');
        } else {
            alert(resp.message);
        }
    }, 'json');
});
JS;
$this->registerJs($js);
?>
