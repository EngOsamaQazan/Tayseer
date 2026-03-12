<?php
/**
 * ملخص إضافة العميل — يعرض بعد إنشاء عميل جديد
 * يحتوي على زر إنشاء عقد
 */
use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\Permissions;

$this->title = 'تم إضافة العميل بنجاح';
$this->params['breadcrumbs'][] = ['label' => 'العملاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('@web/css/smart-onboarding.css', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
<div class="so-page">
    <div class="so-header">
        <h1><i class="fa fa-check-circle text-success"></i> <?= Html::encode($this->title) ?></h1>
        <div class="so-header-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة للقائمة', ['index'], ['class' => 'so-back-btn']) ?>
        </div>
    </div>

    <div class="so-body" style="max-width: 800px; margin: 0 auto;">
        <div class="so-fieldset" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #86efac; border-radius: 12px; padding: 24px;">
            <h3 class="so-fieldset-title" style="color: #166534;"><i class="fa fa-user"></i> ملخص بيانات العميل</h3>

            <div class="so-grid so-grid-2" style="gap: 16px; margin-top: 16px;">
                <div class="cs-summary-item">
                    <span class="cs-label">الاسم</span>
                    <span class="cs-value"><?= Html::encode($model->name) ?></span>
                </div>
                <div class="cs-summary-item">
                    <span class="cs-label">الرقم الوطني</span>
                    <span class="cs-value"><?= Html::encode($model->id_number) ?></span>
                </div>
                <div class="cs-summary-item">
                    <span class="cs-label">الهاتف</span>
                    <span class="cs-value"><?= Html::encode(\backend\helpers\PhoneHelper::toLocal($model->primary_phone_number)) ?></span>
                </div>
                <div class="cs-summary-item">
                    <span class="cs-label">البريد الإلكتروني</span>
                    <span class="cs-value"><?= Html::encode($model->email ?: '—') ?></span>
                </div>
            </div>

            <?php if (!empty($modelsAddress)): ?>
            <div style="margin-top: 20px;">
                <h4 style="font-size: 14px; color: #166534; margin-bottom: 8px;"><i class="fa fa-map-marker"></i> العناوين</h4>
                <ul style="margin: 0; padding-right: 20px;">
                    <?php foreach ($modelsAddress as $addr): ?>
                    <li><?= Html::encode($addr->address) ?> (<?= $addr->address_type == 1 ? 'عمل' : 'سكن' ?>)</li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php endif ?>

            <?php if (!empty($modelsPhoneNumbers)): ?>
            <div style="margin-top: 20px;">
                <h4 style="font-size: 14px; color: #166534; margin-bottom: 8px;"><i class="fa fa-address-book"></i> المعرّفون</h4>
                <ul style="margin: 0; padding-right: 20px;">
                    <?php foreach ($modelsPhoneNumbers as $pn): ?>
                    <li><?= Html::encode(\backend\helpers\PhoneHelper::toLocal($pn->phone_number)) ?> — <?= Html::encode($pn->owner_name) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php endif ?>

            <?php if (!empty($modelRealEstate)): ?>
            <div style="margin-top: 20px;">
                <h4 style="font-size: 14px; color: #166534; margin-bottom: 8px;"><i class="fa fa-building"></i> العقارات</h4>
                <ul style="margin: 0; padding-right: 20px;">
                    <?php foreach ($modelRealEstate as $re): ?>
                    <li><?= Html::encode($re->property_type) ?> — <?= Html::encode($re->property_number) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php endif ?>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; justify-content: center;">
            <?= Html::a('<i class="fa fa-file-text-o"></i> إنشاء عقد جديد', ['/contracts/contracts/create', 'id' => $model->id], ['class' => 'so-btn so-btn-success', 'style' => 'padding: 12px 24px; font-size: 16px;']) ?>
            <?php if (Permissions::can(Permissions::CUST_UPDATE)): ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل العميل', ['update', 'id' => $model->id], ['class' => 'so-btn so-btn-outline']) ?>
            <?php endif ?>
            <?= Html::a('<i class="fa fa-eye"></i> عرض التفاصيل', ['view', 'id' => $model->id], ['class' => 'so-btn so-btn-outline', 'role' => 'modal-remote']) ?>
        </div>
    </div>
</div>
<style>
.cs-summary-item { display: flex; flex-direction: column; gap: 4px; }
.cs-label { font-size: 12px; color: #6b7280; }
.cs-value { font-size: 15px; font-weight: 600; color: #111827; }
</style>
