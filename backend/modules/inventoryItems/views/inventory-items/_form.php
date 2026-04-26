<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  Inventory Item — Pro Form (Create/Update)
 *  Tayseer ERP — نظام تيسير
 *  Replaces Bootstrap 3 form with .inv-form-pro design
 * ═══════════════════════════════════════════════════════════════
 */
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\inventoryItems\models\InventoryItems;

/** @var $this yii\web\View */
/** @var $model InventoryItems */
/** @var $form ActiveForm */

$existingCategories = InventoryItems::find()
    ->select('category')
    ->distinct()
    ->andWhere(['not', ['category' => null]])
    ->andWhere(['!=', 'category', ''])
    ->orderBy(['category' => SORT_ASC])
    ->column();
$categoryList = array_combine($existingCategories, $existingCategories);
$categoryList['__new__'] = '＋ إضافة تصنيف جديد...';

$baseUrl = Yii::$app->request->baseUrl;
$this->registerCssFile($baseUrl . '/css/inv-items-pro.css?v=3');
?>

<div class="inv-form-pro">
    <?php $form = ActiveForm::begin([
        'options' => ['autocomplete' => 'off'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'errorOptions' => ['class' => 'help-block help-block-error'],
        ],
    ]); ?>

    <div class="inv-form-section">
        <h4 class="inv-form-section-title">
            <i class="fa fa-info-circle"></i> المعلومات الأساسية
        </h4>

        <div class="inv-form-row inv-form-row--2">
            <?= $form->field($model, 'item_name')->textInput([
                'maxlength'  => true,
                'placeholder'=> 'مثال: آيفون 15 برو 256GB',
                'autofocus'  => $model->isNewRecord,
            ])->label('اسم الصنف') ?>

            <?= $form->field($model, 'item_barcode')->textInput([
                'maxlength' => true,
                'placeholder' => 'الباركود الفريد',
                'style' => 'direction:ltr; font-family:Courier New,monospace; font-weight:700',
            ])->label('الباركود') ?>
        </div>

        <div class="inv-form-row inv-form-row--2">
            <?= $form->field($model, 'category')->dropDownList($categoryList, [
                'prompt' => '— اختر التصنيف —',
                'id' => 'item-category-select',
                'class' => 'no-select2',
                'options' => ['__new__' => ['style' => 'font-weight:700; color:var(--inv-pro-info); border-top:1px solid var(--inv-pro-border);']],
            ])->label('التصنيف') ?>

            <?= $form->field($model, 'unit')->textInput([
                'placeholder' => 'قطعة، كرتون، علبة...',
                'maxlength' => true,
            ])->label('وحدة القياس') ?>
        </div>

        <?= $form->field($model, 'description')->textarea([
            'rows' => 3,
            'placeholder' => 'وصف إضافي عن الصنف (اختياري)...',
        ])->label('الوصف') ?>
    </div>

    <div class="inv-form-section">
        <h4 class="inv-form-section-title">
            <i class="fa fa-money"></i> التسعير ومستويات المخزون
        </h4>

        <div class="inv-form-row inv-form-row--3">
            <?= $form->field($model, 'unit_price')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'placeholder' => '0.00',
                'style' => 'font-variant-numeric:tabular-nums; font-weight:700',
            ])->label('سعر الوحدة (د.أ)') ?>

            <?= $form->field($model, 'min_stock_level')->textInput([
                'type' => 'number',
                'step' => '1',
                'min' => '0',
                'placeholder' => '0',
                'style' => 'font-variant-numeric:tabular-nums; font-weight:700',
            ])->label('الحد الأدنى للمخزون') ?>

            <?= $form->field($model, 'max_stock_level')->textInput([
                'type' => 'number',
                'step' => '1',
                'min' => '0',
                'placeholder' => 'بدون حد',
                'style' => 'font-variant-numeric:tabular-nums; font-weight:700',
            ])->label('الحد الأقصى (اختياري)') ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax): ?>
    <div class="inv-form-footer">
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['items'], [
            'class' => 'inv-pro-btn inv-pro-btn--ghost',
        ]) ?>
        <?= Html::submitButton(
            ($model->isNewRecord ? '<i class="fa fa-plus"></i> إضافة الصنف' : '<i class="fa fa-check"></i> حفظ التعديلات'),
            ['class' => $model->isNewRecord ? 'inv-pro-btn inv-pro-btn--primary' : 'inv-pro-btn inv-pro-btn--success']
        ) ?>
    </div>
    <?php else: ?>
    <div class="form-group inv-form-footer">
        <button type="button" class="inv-pro-btn inv-pro-btn--ghost" data-modal-close>
            <i class="fa fa-times"></i> إلغاء
        </button>
        <?= Html::submitButton(
            ($model->isNewRecord ? '<i class="fa fa-plus"></i> إضافة' : '<i class="fa fa-check"></i> حفظ'),
            ['class' => $model->isNewRecord ? 'inv-pro-btn inv-pro-btn--primary' : 'inv-pro-btn inv-pro-btn--success']
        ) ?>
    </div>
    <?php endif ?>

    <?php ActiveForm::end(); ?>
</div>

<script>
(function(){
    function initCategorySelect(sel) {
        if (!sel) return;
        sel.addEventListener('change', function() {
            if (this.value === '__new__') {
                var newCat = prompt('أدخل اسم التصنيف الجديد (مثال: أجهزة خلوية، أجهزة كهربائية، أثاث):');
                if (newCat && newCat.trim()) {
                    newCat = newCat.trim();
                    var opt = document.createElement('option');
                    opt.value = newCat;
                    opt.textContent = newCat;
                    opt.selected = true;
                    var newOpt = this.querySelector('option[value="__new__"]');
                    this.insertBefore(opt, newOpt);
                } else {
                    this.value = '';
                }
            }
        });
    }
    var sel = document.getElementById('item-category-select');
    if (sel) initCategorySelect(sel);
})();
</script>
