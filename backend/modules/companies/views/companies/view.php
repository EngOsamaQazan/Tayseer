<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\companies\models\Companies $model */

$this->title = 'عرض المُستثمر: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المُستثمرين', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;
?>

<style>
.inv-view { max-width: 900px; margin: 0 auto; padding: 20px; }
.inv-view-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); border: 1px solid #e2e8f0; margin-bottom: 18px; overflow: hidden; }
.inv-view-header { display: flex; align-items: center; gap: 20px; padding: 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.inv-view-logo { width: 80px; height: 80px; border-radius: 12px; object-fit: contain; background: #fff; border: 1px solid #e2e8f0; }
.inv-view-name { font-size: 20px; font-weight: 700; color: #1e293b; }
.inv-view-badge { display: inline-block; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; margin-right: 8px; }
.inv-view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.inv-view-item { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.inv-view-item:nth-child(odd) { border-left: 1px solid #f1f5f9; }
.inv-view-label { font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
.inv-view-value { font-size: 14px; color: #1e293b; font-weight: 500; }
.inv-view-section { padding: 16px 20px; font-size: 14px; font-weight: 700; color: #475569; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.inv-view-docs { padding: 16px 20px; }
.inv-view-doc { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f8fafc; border-radius: 8px; margin: 4px; font-size: 13px; color: #334155; text-decoration: none; border: 1px solid #e2e8f0; }
.inv-view-doc:hover { background: #ede9fe; border-color: #7c3aed; color: #7c3aed; text-decoration: none; }
.inv-view-actions { padding: 16px 20px; display: flex; gap: 8px; justify-content: flex-end; background: #f8fafc; border-top: 1px solid #e2e8f0; }
.inv-view-actions .btn { border-radius: 8px; font-size: 13px; font-weight: 600; }
@media (max-width: 600px) { .inv-view-grid { grid-template-columns: 1fr; } }
</style>

<div class="inv-view">
    <div class="inv-view-card">
        <div class="inv-view-header">
            <?php
            $logo = !empty($model->logo) ? Url::to(['/' . $model->logo]) : Url::to([Yii::$app->params['companies_logo'] ?? '/images/default-company.png']);
            ?>
            <img src="<?= $logo ?>" class="inv-view-logo" alt="">
            <div>
                <div class="inv-view-name">
                    <?= Html::encode($model->name) ?>
                    <?php if ($model->is_primary_company): ?>
                        <span class="inv-view-badge">رئيسي</span>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="inv-view-grid">
            <div class="inv-view-item">
                <div class="inv-view-label">رقم الهاتف</div>
                <div class="inv-view-value" dir="ltr" style="text-align:right"><?= Html::encode($model->phone_number) ?></div>
            </div>
            <div class="inv-view-item">
                <div class="inv-view-label">البريد الإلكتروني</div>
                <div class="inv-view-value"><?= Html::encode($model->company_email ?: '—') ?></div>
            </div>
            <div class="inv-view-item">
                <div class="inv-view-label">العنوان</div>
                <div class="inv-view-value"><?= Html::encode($model->company_address ?: '—') ?></div>
            </div>
            <div class="inv-view-item">
                <div class="inv-view-label">رقم الضمان الاجتماعي</div>
                <div class="inv-view-value"><?= Html::encode($model->company_social_security_number ?: '—') ?></div>
            </div>
            <div class="inv-view-item">
                <div class="inv-view-label">الرقم الضريبي</div>
                <div class="inv-view-value"><?= Html::encode($model->company_tax_number ?: '—') ?></div>
            </div>
            <div class="inv-view-item">
                <div class="inv-view-label">أنشئ بواسطة</div>
                <div class="inv-view-value"><?= Html::encode($model->createdBy->username ?? '—') ?></div>
            </div>
        </div>

        <?php $regDocs = $model->getCommercialRegisterList(); ?>
        <?php if (!empty($regDocs)): ?>
            <div class="inv-view-section"><i class="fa fa-file-text"></i> السجل التجاري</div>
            <div class="inv-view-docs">
                <?php foreach ($regDocs as $doc): ?>
                    <a href="<?= Url::to(['/' . $doc['path']]) ?>" target="_blank" class="inv-view-doc">
                        <i class="fa fa-file-pdf-o"></i> <?= Html::encode($doc['name']) ?>
                    </a>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php $licDocs = $model->getTradeLicenseList(); ?>
        <?php if (!empty($licDocs)): ?>
            <div class="inv-view-section"><i class="fa fa-id-card"></i> رخص المهن</div>
            <div class="inv-view-docs">
                <?php foreach ($licDocs as $doc): ?>
                    <a href="<?= Url::to(['/' . $doc['path']]) ?>" target="_blank" class="inv-view-doc">
                        <i class="fa fa-file-pdf-o"></i> <?= Html::encode($doc['name']) ?>
                    </a>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <div class="inv-view-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'btn btn-default']) ?>
            <?php if (Permissions::can(Permissions::COMP_UPDATE)): ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?php endif ?>
        </div>
    </div>
</div>
