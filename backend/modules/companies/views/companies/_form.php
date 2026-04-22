<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use common\components\CompanyChecked;
use common\helper\Permissions;
use backend\assets\MediaUploaderAsset;

/** @var yii\web\View $this */
/** @var backend\modules\companies\models\Companies $model */
/** @var yii\widgets\ActiveForm $form */

MediaUploaderAsset::register($this);
$companyId = $model->isNewRecord ? 0 : (int)$model->id;
?>

<style>
:root {
    --inv-primary: #7c3aed;
    --inv-primary-light: #ede9fe;
    --inv-border: #e2e8f0;
    --inv-r: 12px;
    --inv-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.inv-form-page { max-width: 1100px; margin: 0 auto; }
.inv-card { background: #fff; border-radius: var(--inv-r); box-shadow: var(--inv-shadow); border: 1px solid var(--inv-border); margin-bottom: 18px; overflow: hidden; }
.inv-card-title { font-size: 15px; font-weight: 700; color: #1e293b; padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid var(--inv-border); display: flex; align-items: center; gap: 8px; }
.inv-card-title i { color: var(--inv-primary); }
.inv-card-body { padding: 20px; }

.inv-logo-zone { text-align: center; padding: 20px; }
.inv-logo-preview { width: 180px; height: 180px; border-radius: 12px; object-fit: contain; border: 2px dashed var(--inv-border); background: #f8fafc; margin: 0 auto 12px; display: block; }
.inv-logo-hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }

.inv-upload-zone { border: 2px dashed var(--inv-border); border-radius: 10px; padding: 24px; text-align: center; background: #fafbfc; transition: all .2s; cursor: pointer; margin-bottom: 10px; }
.inv-upload-zone:hover { border-color: var(--inv-primary); background: var(--inv-primary-light); }
.inv-upload-zone-active { border-color: #22c55e !important; background: #f0fdf4 !important; }
.inv-upload-zone-active i { color: #22c55e !important; }
.inv-upload-zone-active span { color: #15803d !important; font-weight: 600; }
.inv-upload-zone i { font-size: 28px; color: #94a3b8; margin-bottom: 6px; display: block; }
.inv-upload-zone span { font-size: 13px; color: #64748b; }
.inv-upload-zone input[type="file"] { position: absolute; opacity: 0; width: 0; height: 0; }

.inv-doc-list { list-style: none; padding: 0; margin: 10px 0 0; }
.inv-doc-list li { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 6px; font-size: 13px; }
.inv-doc-list li i { color: var(--inv-primary); }
.inv-doc-list li a { color: #334155; text-decoration: none; flex: 1; }
.inv-doc-list li a:hover { color: var(--inv-primary); }
.inv-doc-list li .inv-doc-del { color: #ef4444; font-size: 12px; cursor: pointer; }

.inv-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 0; }
.inv-form-row .form-group { margin-bottom: 14px; }
.inv-form-row .form-group label { font-size: 13px; color: #475569; font-weight: 600; margin-bottom: 4px; }
.inv-form-row .form-control { border-radius: 8px; border: 1px solid var(--inv-border); }

.inv-submit-bar { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; background: #f8fafc; border-top: 1px solid var(--inv-border); }
.inv-submit-bar .btn { border-radius: 8px; padding: 9px 28px; font-weight: 600; font-size: 14px; }
</style>

<div class="inv-form-page">
    <?php
    if (isset($id)) {
        $form = ActiveForm::begin(['action' => Url::to(['update', 'id' => $id]), 'options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    } else {
        $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    }
    ?>
    <?= $form->errorSummary($model) ?>

    <div class="row">
        <!-- Left: Logo + Documents -->
        <div class="col-sm-4">
            <!-- Logo -->
            <div class="inv-card">
                <div class="inv-card-title"><i class="fa fa-image"></i> الشعار</div>
                <div class="inv-card-body inv-logo-zone">
                    <?php
                    $logo = Yii::$app->params['companies_logo'] ?? '/images/default-company.png';
                    $logoSrc = $model->isNewRecord ? Url::to([$logo]) : Url::to(['/' . $model->logo]);
                    ?>
                    <img src="<?= $logoSrc ?>" class="inv-logo-preview" id="logoPreview" alt="الشعار">
                    <div id="logoHost"
                         data-media-uploader
                         data-entity-type="company"
                         data-entity-id="<?= $companyId ?>"
                         data-group-name="company_logo"
                         data-uploaded-via="company_form"
                         data-accept=".png,.jpg,.jpeg,.gif,.bmp,.webp,.svg"
                         data-max-mb="3"
                         data-target-name="Companies[adopted_logo_id]">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-media-pick>
                            <i class="fa fa-upload"></i> اختر شعاراً
                        </button>
                    </div>
                    <div class="inv-logo-hint">PNG, JPG, JPEG, GIF, WebP, SVG</div>
                </div>
            </div>

            <!-- Commercial Register -->
            <div class="inv-card">
                <div class="inv-card-title"><i class="fa fa-file-text"></i> السجل التجاري</div>
                <div class="inv-card-body">
                    <div class="inv-upload-zone" id="regDropZone"
                         data-media-uploader
                         data-entity-type="company"
                         data-entity-id="<?= $companyId ?>"
                         data-group-name="commercial_register"
                         data-uploaded-via="company_form"
                         data-multiple="1"
                         data-accept=".png,.jpg,.jpeg,.gif,.bmp,.webp,.pdf"
                         data-max-mb="20"
                         data-target-name="Companies[adopted_register_ids][]">
                        <button type="button" class="inv-pick-btn" data-media-pick style="background:none;border:0;padding:0;cursor:pointer">
                            <i class="fa fa-cloud-upload"></i>
                            <span>اضغط لرفع ملفات السجل التجاري</span>
                        </button>
                    </div>
                    <ul class="inv-doc-list" id="regFileList"></ul>
                    <div class="inv-logo-hint">PDF, PNG, JPG — يمكنك رفع عدة ملفات</div>
                    <?php $regDocs = $model->getCommercialRegisterList(); ?>
                    <?php if (!empty($regDocs)): ?>
                        <ul class="inv-doc-list">
                            <?php foreach ($regDocs as $doc): ?>
                                <li>
                                    <i class="fa fa-file-pdf-o"></i>
                                    <a href="<?= Url::to(['/' . $doc['path']]) ?>" target="_blank"><?= Html::encode($doc['name']) ?></a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>

            <!-- Trade License -->
            <div class="inv-card">
                <div class="inv-card-title"><i class="fa fa-id-card"></i> رخص المهن</div>
                <div class="inv-card-body">
                    <div class="inv-upload-zone" id="licDropZone"
                         data-media-uploader
                         data-entity-type="company"
                         data-entity-id="<?= $companyId ?>"
                         data-group-name="trade_license"
                         data-uploaded-via="company_form"
                         data-multiple="1"
                         data-accept=".png,.jpg,.jpeg,.gif,.bmp,.webp,.pdf"
                         data-max-mb="20"
                         data-target-name="Companies[adopted_license_ids][]">
                        <button type="button" class="inv-pick-btn" data-media-pick style="background:none;border:0;padding:0;cursor:pointer">
                            <i class="fa fa-cloud-upload"></i>
                            <span>اضغط لرفع ملفات رخص المهن</span>
                        </button>
                    </div>
                    <ul class="inv-doc-list" id="licFileList"></ul>
                    <div class="inv-logo-hint">PDF, PNG, JPG — يمكنك رفع عدة ملفات</div>
                    <?php $licDocs = $model->getTradeLicenseList(); ?>
                    <?php if (!empty($licDocs)): ?>
                        <ul class="inv-doc-list">
                            <?php foreach ($licDocs as $doc): ?>
                                <li>
                                    <i class="fa fa-file-pdf-o"></i>
                                    <a href="<?= Url::to(['/' . $doc['path']]) ?>" target="_blank"><?= Html::encode($doc['name']) ?></a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- Right: Fields -->
        <div class="col-sm-8">
            <div class="inv-card">
                <div class="inv-card-title"><i class="fa fa-building"></i> بيانات المُستثمر</div>
                <div class="inv-card-body">
                    <div class="inv-form-row">
                        <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'أدخل اسم المُستثمر']) ?>
                        <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true, 'placeholder' => 'أدخل رقم الهاتف']) ?>
                    </div>
                    <div class="inv-form-row">
                        <?= $form->field($model, 'company_social_security_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الوطني للمنشأة']) ?>
                        <?= $form->field($model, 'company_tax_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الضريبي']) ?>
                    </div>
                    <div class="inv-form-row">
                        <?= $form->field($model, 'company_address')->textInput(['maxlength' => true, 'placeholder' => 'العنوان']) ?>
                        <?= $form->field($model, 'company_email')->textInput(['maxlength' => true, 'placeholder' => 'البريد الإلكتروني']) ?>
                    </div>
                    <div class="inv-form-row">
                        <div class="form-group">
                            <?php
                            $CompanyChecked = new CompanyChecked();
                            $primary_company = $CompanyChecked->findPrimaryCompany();
                            $showCheckbox = false;
                            if ($primary_company == '') {
                                $showCheckbox = true;
                            } elseif (!empty($primary_company->id) && $primary_company->id == $model->id) {
                                $showCheckbox = true;
                            }
                            if ($showCheckbox) {
                                echo $form->field($model, 'is_primary_company')->checkbox();
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Investment Settings -->
            <?php
            $CompanyCheckedForPrimary = new CompanyChecked();
            $isPrimary = !$model->isNewRecord && $model->is_primary_company;
            ?>
            <?php if (!$isPrimary): ?>
            <div class="inv-card">
                <div class="inv-card-title"><i class="fa fa-line-chart"></i> إعدادات المحفظة الاستثمارية</div>
                <div class="inv-card-body">
                    <div class="inv-form-row">
                        <?= $form->field($model, 'profit_share_ratio')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '100', 'placeholder' => 'مثلاً 50']) ?>
                        <?= $form->field($model, 'parent_share_ratio')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '100', 'placeholder' => 'مثلاً 50']) ?>
                    </div>
                    <div class="inv-form-row">
                        <?= $form->field($model, 'portfolio_status')->dropDownList([
                            'نشط' => 'نشط',
                            'مجمّد' => 'مجمّد',
                            'مُغلق' => 'مُغلق',
                        ]) ?>
                        <?= $form->field($model, 'agreement_date')->input('date') ?>
                    </div>
                    <div class="inv-form-row">
                        <?= $form->field($model, 'capital_refundable')->checkbox() ?>
                    </div>
                    <div class="inv-form-row" style="grid-template-columns:1fr">
                        <?= $form->field($model, 'agreement_notes')->textarea(['rows' => 3, 'placeholder' => 'شروط الاتفاق بين الشركة الأم والمُستثمر']) ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="inv-card">
                <div class="inv-card-title"><i class="fa fa-pie-chart"></i> إعدادات الأسهم</div>
                <div class="inv-card-body">
                    <div class="inv-form-row">
                        <?= $form->field($model, 'total_shares')->textInput(['type' => 'number', 'min' => '1', 'placeholder' => 'إجمالي أسهم الشركة']) ?>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <!-- Bank Accounts -->
            <div class="inv-card">
                <div class="inv-card-title"><i class="fa fa-university"></i> الحسابات البنكية</div>
                <div class="inv-card-body">
                    <?= $this->render('_parital/company_banks', [
                        'modelsCompanieBanks' => $modelsCompanieBanks,
                        'form' => $form
                    ]) ?>
                </div>
            </div>

            <?php if (!Yii::$app->request->isAjax): ?>
                <?php
                $canSubmit = $model->isNewRecord ? Permissions::can(Permissions::COMP_CREATE) : Permissions::can(Permissions::COMP_UPDATE);
                ?>
                <?php if ($canSubmit): ?>
                    <div class="inv-card">
                        <div class="inv-submit-bar">
                            <?= Html::a('إلغاء', ['index'], ['class' => 'btn btn-secondary']) ?>
                            <?= Html::submitButton($model->isNewRecord ? 'إضافة مُستثمر' : 'حفظ التعديلات', [
                                'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'
                            ]) ?>
                        </div>
                    </div>
                <?php endif ?>
            <?php endif ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
/**
 * Companies form — slim glue script (Phase 6.2).
 * Drag/drop, file picker, progress, and CSRF are owned by the unified
 * MediaUploader. This script only mirrors successfully-uploaded files
 * into the visual list and updates the logo preview.
 */
(function() {
    if (!window.MediaUploader) return;

    function attachListAppender(hostId, listId, zoneId) {
        var list = document.getElementById(listId);
        var zone = document.getElementById(zoneId);
        MediaUploader.attach('#' + hostId, {
            onSuccess: function(resp) {
                var f = resp && resp.file;
                if (!f || !list) return;
                var name = f.fileName || f.name || 'file';
                var ext = (name.split('.').pop() || '').toLowerCase();
                var icon = (ext === 'pdf') ? 'fa-file-pdf-o' : 'fa-file-image-o';
                var li = document.createElement('li');
                li.innerHTML = '<i class="fa ' + icon + '"></i>'
                    + '<a href="' + f.url + '" target="_blank" style="flex:1;color:#334155">' + name + '</a>'
                    + '<span style="font-size:11px;color:#94a3b8">جديد</span>';
                list.appendChild(li);
                if (zone) zone.classList.add('inv-upload-zone-active');
            }
        });
    }

    attachListAppender('regDropZone', 'regFileList', 'regDropZone');
    attachListAppender('licDropZone', 'licFileList', 'licDropZone');

    MediaUploader.attach('#logoHost', {
        onSuccess: function(resp) {
            var url = resp && resp.file && resp.file.url;
            if (url) {
                var prev = document.getElementById('logoPreview');
                if (prev) prev.src = url;
            }
        }
    });
})();
</script>
