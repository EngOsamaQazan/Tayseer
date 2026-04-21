<?php

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $status string valid|expired|revoked|invalid */
/* @var $label string */
/* @var $message string */
/* @var $cert \backend\modules\followUp\models\ClearanceCertificate|null */
/* @var $snapshot array|null */
/* @var $contract_id int */

$this->title = 'تحقق من براءة الذمة';
$this->registerCssFile(Yii::getAlias('@web') . '/css/follow-up-statement.css', ['depends' => ['yii\web\YiiAsset']]);

$statusClass = [
    'valid'   => 'tayseer-verify--valid',
    'expired' => 'tayseer-verify--expired',
    'revoked' => 'tayseer-verify--invalid',
    'invalid' => 'tayseer-verify--invalid',
][$status] ?? 'tayseer-verify--invalid';

$statusIcon = [
    'valid'   => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0F7B3D" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'expired' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#b8860b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'revoked' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#B42318" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>',
    'invalid' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#B42318" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
][$status] ?? '';

$fmtNum = function ($n) {
    if ($n === null || $n === '' || !is_numeric($n)) return $n;
    return number_format((float) $n, 2, '.', ',');
};

$showSnapshot = !empty($snapshot) && in_array($status, ['valid', 'expired', 'revoked'], true);
?>
<div class="tayseer-statement tayseer-verify <?= $statusClass ?>">

    <div class="tv-banner">
        <div class="tv-banner__icon"><?= $statusIcon ?></div>
        <div class="tv-banner__content">
            <h1 class="tv-banner__title">تحقق من براءة الذمة</h1>
            <p class="tv-banner__label"><?= Html::encode($label) ?></p>
            <p class="tv-banner__message"><?= Html::encode($message) ?></p>
        </div>
    </div>

    <?php if ($showSnapshot): ?>

    <div class="tv-alert">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <p>
            <?php if ($status === 'valid'): ?>
                تحقق من مطابقة البيانات أدناه مع الشهادة المقدمة لك. في حال وجود أي اختلاف يُعتبر المستند مزوراً.
            <?php elseif ($status === 'expired'): ?>
                هذه الشهادة أُصدرت بنجاح، لكنها لم تعد سارية بسبب حدوث حركة جديدة على العقد بعد إصدارها.
            <?php elseif ($status === 'revoked'): ?>
                هذه الشهادة تم إلغاؤها من قبل الجهة المصدرة ولم تعد معتمدة.
            <?php endif; ?>
        </p>
    </div>

    <div class="tv-header">
        <div class="tv-header__company"><?= Html::encode($snapshot['companyName'] ?? '') ?></div>
        <div class="tv-header__meta">
            <?php if ($cert): ?>
            <span>رقم الشهادة: <strong class="en"><?= Html::encode($cert->cert_number) ?></strong></span>
            <span class="tv-header__sep">|</span>
            <?php endif; ?>
            <span>رقم العقد: <strong class="en"><?= (int) $contract_id ?></strong></span>
            <?php if ($cert): ?>
            <span class="tv-header__sep">|</span>
            <span>تاريخ الإصدار: <strong class="en"><?= Html::encode(substr((string) $cert->issued_at, 0, 10)) ?></strong></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="tv-info">
        <div class="tv-info__group">
            <div class="tv-info__row">
                <span class="tv-info__label">اسم العميل</span>
                <span class="tv-info__value"><?= Html::encode(implode(' ، ', (array) ($snapshot['clientNames'] ?? []))) ?></span>
            </div>
            <?php $guars = (array) ($snapshot['guarantorNames'] ?? []); if (!empty($guars) && implode('', $guars)): ?>
            <div class="tv-info__row">
                <span class="tv-info__label">أسماء الكفلاء</span>
                <span class="tv-info__value"><?= Html::encode(implode(' ، ', $guars)) ?></span>
            </div>
            <?php endif; ?>
            <div class="tv-info__row">
                <span class="tv-info__label">تاريخ البيع</span>
                <span class="tv-info__value en"><?= Html::encode($snapshot['dateSale'] ?? '—') ?></span>
            </div>
            <div class="tv-info__row">
                <span class="tv-info__label">القسط الشهري</span>
                <span class="tv-info__value en"><?= $fmtNum($snapshot['monthlyInst'] ?? null) ?></span>
            </div>
        </div>
    </div>

    <div class="tv-cards">
        <div class="tv-card tv-card--neutral">
            <span class="tv-card__label">إجمالي العقد</span>
            <span class="tv-card__amount en"><?= $fmtNum($snapshot['totalValue'] ?? 0) ?></span>
        </div>
        <div class="tv-card tv-card--success">
            <span class="tv-card__label">المدفوع</span>
            <span class="tv-card__amount en"><?= $fmtNum($snapshot['paidAmount'] ?? 0) ?></span>
        </div>
        <div class="tv-card tv-card--danger">
            <span class="tv-card__label">المتبقي</span>
            <span class="tv-card__amount en"><?= $fmtNum($snapshot['remainingBalance'] ?? 0) ?></span>
        </div>
    </div>

    <?php $cases = (array) ($snapshot['judiciaryCases'] ?? []); if (!empty($cases)): ?>
    <div class="tv-info" style="margin-top:16px">
        <h4 style="margin:0 0 10px; font-family:var(--font-ar); font-size:15px; color:#1a1d21">القضايا المسجلة على العميل</h4>
        <?php foreach ($cases as $case): ?>
        <div class="tv-info__row">
            <span class="tv-info__label">
                قضية <span class="en"><?= Html::encode($case['judiciary_number'] ?: '—') ?></span>
                <?php if (!empty($case['year'])): ?> / <span class="en"><?= Html::encode($case['year']) ?></span><?php endif; ?>
            </span>
            <span class="tv-info__value">
                <?= Html::encode($case['court_name'] ?: '—') ?>
                <?php if (!empty($case['case_status'])): ?>
                &nbsp;•&nbsp; <?= Html::encode($case['case_status']) ?>
                <?php endif; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; /* showSnapshot */ ?>

</div>
