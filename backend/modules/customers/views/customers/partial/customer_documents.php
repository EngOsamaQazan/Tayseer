<?php
/**
 * نموذج ديناميكي - مستندات العميل
 * يستخدم تقنية الرفع الذكية (سحب وإفلات / كاميرا)
 */
use yii\helpers\Html;
use yii\helpers\Url;
use wbraganca\dynamicform\DynamicFormWidget;

$docTypes = [0 => 'هوية', 1 => 'جواز سفر', 2 => 'رخصة', 3 => 'شهادة ميلاد', 4 => 'شهادة تعيين'];

DynamicFormWidget::begin([
    'widgetContainer' => 'dynamicform_wrapper3',
    'widgetBody' => '.container-items3',
    'widgetItem' => '.customer-documents-item',
    'limit' => 20,
    'min' => 1,
    'insertButton' => '.customer-documents-add-item',
    'deleteButton' => '.customer-documents-remove-item',
    'model' => $customerDocumentsModel[0],
    'formId' => 'smart-onboarding-form',
    'formFields' => ['document_number', 'document_type', 'document_image'],
]);
?>

<div class="container-items3">
    <?php foreach ($customerDocumentsModel as $i => $doc): ?>
        <div class="customer-documents-item panel panel-default">
            <div class="panel-body">
                <?php if (!$doc->isNewRecord) echo Html::activeHiddenInput($doc, "[{$i}]id") ?>
                <div class="row">
                    <div class="col-md-3">
                        <?= $form->field($doc, "[{$i}]document_type")->dropDownList($docTypes)->label('النوع') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($doc, "[{$i}]document_number")->textInput(['maxlength' => true, 'placeholder' => 'رقم المستند'])->label('الرقم') ?>
                    </div>
                    <div class="col-md-5">
                        <div class="sm-doc-zone" data-index="<?= $i ?>">
                            <input type="file" accept="image/*,.pdf" style="display:none">
                            <?php
                            $docPath = $doc->document_image ?? $doc->images ?? '';
                            if ($docPath === 'null' || $docPath === null) $docPath = '';
                            ?>
                            <input type="hidden" name="CustomersDocument[<?= $i ?>][document_image]" value="<?= Html::encode($docPath) ?>" class="sm-doc-path-input">
                            <div class="sm-doc-placeholder">
                                <i class="fa fa-cloud-upload"></i>
                                <span>اسحب الصورة هنا أو اضغط للرفع</span>
                            </div>
                            <?php $isPdf = !empty($docPath) && (strtolower(substr($docPath, -4)) === '.pdf'); ?>
                            <div class="sm-doc-preview <?= $isPdf ? 'is-pdf' : '' ?>" style="display:<?= !empty($docPath) ? 'flex' : 'none' ?>">
                                <?php if ($isPdf): ?>
                                <div class="sm-doc-pdf-label"><i class="fa fa-file-pdf-o"></i> PDF</div>
                                <?php endif ?>
                                <img src="<?= !$isPdf && !empty($docPath) ? Url::to('@web/' . ltrim($docPath, '/')) : '' ?>" alt="" style="<?= $isPdf ? 'display:none' : '' ?>">
                                <button type="button" class="sm-doc-remove" title="إزالة"><i class="fa fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div style="margin-top:26px">
                            <button type="button" class="customer-documents-remove-item btn btn-danger btn-xs" title="حذف"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<button type="button" class="customer-documents-add-item btn btn-success btn-xs"><i class="fa fa-plus"></i> إضافة مستند</button>

<?php DynamicFormWidget::end() ?>
