<?php
/**
 * نموذج ديناميكي - عقارات العميل
 */
use yii\helpers\Html;
use wbraganca\dynamicform\DynamicFormWidget;

DynamicFormWidget::begin([
    'widgetContainer' => 'dynamicform_wrapper122',
    'widgetBody' => '.container-items111',
    'widgetItem' => '.real_estate-item',
    'limit' => 20,
    'min' => 1,
    'insertButton' => '.real-estate-add-item',
    'deleteButton' => '.real-estate-remove-item',
    'model' => $modelRealEstate[0],
    'formId' => 'smart-onboarding-form',
    'formFields' => ['property_type', 'property_number'],
]);
?>

<div class="container-items111">
    <?php foreach ($modelRealEstate as $i => $re): ?>
        <div class="real_estate-item panel panel-default">
            <div class="panel-body">
                <?php if (!$re->isNewRecord) echo Html::activeHiddenInput($re, "[{$i}]id") ?>
                <div class="row">
                    <div class="col-md-5">
                        <?= $form->field($re, "[{$i}]property_type")->textInput(['maxlength' => true, 'placeholder' => 'شقة، أرض، فيلا'])->label('نوع العقار') ?>
                    </div>
                    <div class="col-md-5">
                        <?= $form->field($re, "[{$i}]property_number")->textInput(['maxlength' => true, 'placeholder' => 'رقم العقار'])->label('رقم العقار') ?>
                    </div>
                    <div class="col-md-2">
                        <div style="margin-top:26px">
                            <button type="button" class="real-estate-remove-item btn btn-danger btn-xs" title="حذف"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<button type="button" class="real-estate-add-item btn btn-success btn-xs"><i class="fa fa-plus"></i> إضافة عقار</button>

<?php DynamicFormWidget::end() ?>
