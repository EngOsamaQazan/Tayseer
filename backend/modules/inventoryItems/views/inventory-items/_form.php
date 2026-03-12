<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\modules\inventoryItems\models\InventoryItems;

$existingCategories = InventoryItems::find()
    ->select('category')
    ->distinct()
    ->andWhere(['not', ['category' => null]])
    ->andWhere(['!=', 'category', ''])
    ->orderBy(['category' => SORT_ASC])
    ->column();
$categoryList = array_combine($existingCategories, $existingCategories);
$categoryList['__new__'] = '＋ إضافة تصنيف جديد...';
?>

<div class="inventory-items-form" style="padding:10px">
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'item_name')->textInput([
                'maxlength' => true,
                'placeholder' => 'مثال: آيفون 15 برو',
                'class' => 'form-control',
            ])->label('اسم الصنف') ?>
        </div>
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'item_barcode')->textInput([
                'maxlength' => true,
                'placeholder' => 'الباركود الفريد',
                'class' => 'form-control',
                'style' => 'direction:ltr; font-family:monospace',
            ])->label('الباركود') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'category')->dropDownList($categoryList, [
                'prompt' => '— اختر التصنيف —',
                'id' => 'item-category-select',
                'options' => ['__new__' => ['style' => 'font-weight:700; color:#0369a1; border-top:1px solid #e2e8f0;']],
            ])->label('التصنيف') ?>
        </div>
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'description')->textarea([
                'rows' => 3,
                'placeholder' => 'وصف إضافي عن الصنف...',
            ])->label('الوصف') ?>
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
    var readyFn = function() { initCategorySelect(document.getElementById('item-category-select')); };
    if (document.readyState !== 'loading') readyFn();
    else document.addEventListener('DOMContentLoaded', readyFn);
})();
</script>
