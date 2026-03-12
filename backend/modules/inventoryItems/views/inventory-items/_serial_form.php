<?php
/**
 * فورم إضافة/تعديل رقم تسلسلي
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\modules\inventoryItems\models\InventoryItems;
use backend\modules\inventoryItems\models\InventorySerialNumber;
use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
?>

<div class="serial-number-form" style="padding:10px">
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'serial_number')->textInput([
                'maxlength' => 50,
                'placeholder' => 'مثال: 350000000000001',
                'class' => 'form-control',
                'style' => 'direction:ltr; font-family:monospace; font-weight:700; font-size:15px; letter-spacing:1px',
            ])->label('الرقم التسلسلي / IMEI <span style="color:red">*</span>') ?>
        </div>
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'item_id')->dropDownList(
                ArrayHelper::map(
                    InventoryItems::find()->andWhere(['is_deleted' => 0])->orderBy(['item_name' => SORT_ASC])->all(),
                    'id',
                    function($item) { return $item->item_name . ' (' . $item->item_barcode . ')'; }
                ),
                ['prompt' => '— اختر الصنف —', 'class' => 'form-control']
            )->label('الصنف <span style="color:red">*</span>') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-4">
            <?= $form->field($model, 'status')->dropDownList(
                InventorySerialNumber::getStatusList(),
                ['class' => 'form-control']
            )->label('الحالة') ?>
        </div>
        <div class="col-lg-4 col-md-4">
            <?= $form->field($model, 'supplier_id')->dropDownList(
                ArrayHelper::map(
                    InventorySuppliers::find()->andWhere(['is_deleted' => 0])->all(),
                    'id', 'name'
                ),
                ['prompt' => '— اختر المورد —', 'class' => 'form-control']
            )->label('المورد') ?>
        </div>
        <div class="col-lg-4 col-md-4">
            <?= $form->field($model, 'location_id')->dropDownList(
                ArrayHelper::map(
                    InventoryStockLocations::find()->andWhere(['is_deleted' => 0])->all(),
                    'id', 'locations_name'
                ),
                ['prompt' => '— اختر الموقع —', 'class' => 'form-control']
            )->label('موقع التخزين') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-4">
            <?= $form->field($model, 'company_id')->textInput([
                'type' => 'number',
                'placeholder' => 'رقم الشركة',
            ])->label('الشركة <span style="color:red">*</span>') ?>
        </div>
        <div class="col-lg-4 col-md-4">
            <?= $form->field($model, 'contract_id')->textInput([
                'type' => 'number',
                'placeholder' => 'رقم العقد (اختياري)',
            ])->label('رقم العقد') ?>
        </div>
        <div class="col-lg-4 col-md-4">
            <?= $form->field($model, 'received_at')->textInput([
                'type' => 'date',
                'value' => $model->received_at ? date('Y-m-d', $model->received_at) : '',
                'class' => 'form-control',
            ])->label('تاريخ الاستلام') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <?= $form->field($model, 'note')->textarea([
                'rows' => 2,
                'placeholder' => 'ملاحظات إضافية...',
            ])->label('ملاحظات') ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax): ?>
    <div class="form-group" style="margin-top:15px">
        <?= Html::submitButton($model->isNewRecord ? '<i class="fa fa-plus"></i> إضافة' : '<i class="fa fa-check"></i> تحديث', [
            'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
        ]) ?>
    </div>
    <?php endif ?>

    <?php ActiveForm::end(); ?>
</div>

<?php
/* تحويل تاريخ الاستلام من date input إلى timestamp قبل الإرسال */
$js = <<<JS
$('.serial-number-form form').on('beforeSubmit', function(){
    var dateInput = $(this).find('input[type="date"]');
    if (dateInput.val()) {
        var ts = Math.floor(new Date(dateInput.val()).getTime() / 1000);
        dateInput.val(ts);
    }
    return true;
});
JS;
$this->registerJs($js);
?>
