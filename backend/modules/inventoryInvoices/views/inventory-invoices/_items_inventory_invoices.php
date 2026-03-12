<?php
/**
 * بنود أصناف الفاتورة v2 — مع إضافة صنف سريعة
 */
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use backend\modules\inventoryItems\models\InventoryItems;

$csrfToken = Yii::$app->request->csrfToken;
$quickAddItemUrl = Url::to(['/inventoryItems/inventory-items/quick-add-item']);
$itemsList = ArrayHelper::map(InventoryItems::find()->andWhere(['status' => 'approved'])->orderBy('item_name')->all(), 'id', 'item_name');
?>

<style>
.po-items-panel { background: #fafbfc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
.po-line-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 8px; }
.po-line-item .row { align-items: flex-end; }
.po-line-item .form-group { margin-bottom: 6px; }
.po-line-item label { font-size: 12px; font-weight: 600; color: #64748b; }
.po-line-total { font-weight: 800; color: #0369a1; font-size: 15px; margin-top: 28px; display: block; }
.po-add-btns { display: flex; gap: 8px; margin-top: 10px; }
.po-add-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; }
.po-add-line { background: #15803d; color: #fff; }
.po-add-line:hover { background: #166534; }
.po-add-quick { background: #e0f2fe; color: #0369a1; }
.po-add-quick:hover { background: #bae6fd; }
.po-remove { background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; padding: 6px 10px; cursor: pointer; font-size: 12px; font-weight: 700; margin-top: 28px; }
.po-remove:hover { background: #fecaca; }
</style>

<div class="po-items-panel">
    <?php
    DynamicFormWidget::begin([
        'widgetContainer' => 'dynamicform_wrapper90',
        'widgetBody' => '.container-items90',
        'widgetItem' => '.itemsInventoryInvoice-item',
        'limit' => 100,
        'min' => 1,
        'insertButton' => '.itemsInventoryInvoice-add-item',
        'deleteButton' => '.itemsInventoryInvoice-remove-item',
        'model' => $itemsInventoryInvoices[0],
        'formId' => 'dynamic-form',
        'formFields' => ['number', 'single_price', 'inventory_items_id'],
    ]);
    ?>

    <div class="container-items90">
        <?php foreach ($itemsInventoryInvoices as $i => $lineItem): ?>
            <div class="itemsInventoryInvoice-item po-line-item">
                <?php if (!$lineItem->isNewRecord) echo Html::activeHiddenInput($lineItem, "[{$i}]id"); ?>
                <div class="row">
                    <div class="col-sm-4">
                        <?= $form->field($lineItem, "[{$i}]inventory_items_id")->dropDownList(
                            $itemsList,
                            ['prompt' => '— اختر الصنف —', 'class' => 'form-control item-select']
                        )->label('الصنف') ?>
                    </div>
                    <div class="col-sm-2">
                        <?= $form->field($lineItem, "[{$i}]number")->textInput([
                            'type' => 'number', 'min' => 1, 'placeholder' => '0',
                            'class' => 'form-control line-qty',
                        ])->label('الكمية') ?>
                    </div>
                    <div class="col-sm-2">
                        <?= $form->field($lineItem, "[{$i}]single_price")->textInput([
                            'type' => 'number', 'step' => '0.01', 'min' => 0, 'placeholder' => '0.00',
                            'class' => 'form-control line-price', 'style' => 'direction:ltr',
                        ])->label('سعر الوحدة') ?>
                    </div>
                    <div class="col-sm-2">
                        <span class="po-line-total line-total">0.00</span>
                    </div>
                    <div class="col-sm-2">
                        <button type="button" class="itemsInventoryInvoice-remove-item po-remove">
                            <i class="fa fa-trash"></i> حذف
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="po-add-btns">
        <button type="button" class="itemsInventoryInvoice-add-item po-add-btn po-add-line">
            <i class="fa fa-plus"></i> إضافة بند
        </button>
        <button type="button" class="po-add-btn po-add-quick" id="btn-quick-item">
            <i class="fa fa-bolt"></i> إضافة صنف جديد سريع
        </button>
    </div>

    <?php DynamicFormWidget::end(); ?>
</div>

<?php
$js = <<<JS
/* حساب الإجمالي لكل بند */
function calcLineTotals() {
    $('.itemsInventoryInvoice-item').each(function(){
        var qty = parseFloat($(this).find('.line-qty').val()) || 0;
        var price = parseFloat($(this).find('.line-price').val()) || 0;
        $(this).find('.line-total').text((qty * price).toFixed(2));
    });
}
$(document).on('input', '.line-qty, .line-price', calcLineTotals);
$(document).on('afterInsert', '.dynamicform_wrapper90', calcLineTotals);
calcLineTotals();

/* إضافة صنف سريعة */
$('#btn-quick-item').click(function(e){
    e.preventDefault();
    var name = prompt('اسم الصنف الجديد:');
    if (!name) return;

    $.post('$quickAddItemUrl', {
        name: name, _csrf: '$csrfToken'
    }, function(resp) {
        if (resp.success) {
            // إضافة الصنف لكل dropdowns الأصناف
            $('select.item-select').each(function(){
                if ($(this).find('option[value="'+resp.id+'"]').length === 0) {
                    $(this).append(new Option(resp.name, resp.id));
                }
            });
            // تحديد الصنف في آخر بند
            var lastSelect = $('.itemsInventoryInvoice-item:last select.item-select');
            lastSelect.val(resp.id);
            alert('تم إضافة "' + resp.name + '" بنجاح');
        } else {
            alert(resp.message);
        }
    }, 'json');
});
JS;
$this->registerJs($js);
?>
