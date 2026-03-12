<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $status string valid|invalid */
/* @var $label string */
/* @var $message string */
/* @var $receipt_id int|null */
/* @var $amount float|null */
/* @var $date string|null */
/* @var $contract_id int|null */

$this->title = 'تحقق من الإيصال';
$this->registerCssFile(Yii::getAlias('@web') . '/css/receipt-print.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<div class="rc" style="max-width:480px">
    <div class="rc-verify-page rc-verify-page--<?= Html::encode($status) ?>">
        <div class="rc-verify-page__box">
            <div class="rc-verify-page__icon">
                <?php if ($status === 'valid'): ?>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#0F7B3D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?php else: ?>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#B42318" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?php endif; ?>
            </div>

            <h2 class="rc-verify-page__label"><?= Html::encode($label) ?></h2>
            <p class="rc-verify-page__message"><?= Html::encode($message) ?></p>

            <?php if ($status === 'valid' && isset($receipt_id)): ?>
            <div class="rc-verify-page__details">
                <div class="rc-verify-page__detail-row">
                    <span class="rc-verify-page__detail-label">رقم الإيصال</span>
                    <span class="rc-verify-page__detail-value en">#<?= (int) $receipt_id ?></span>
                </div>
                <div class="rc-verify-page__detail-row">
                    <span class="rc-verify-page__detail-label">المبلغ</span>
                    <span class="rc-verify-page__detail-value en"><?= number_format((float)$amount, 2, '.', ',') ?> د.أ</span>
                </div>
                <div class="rc-verify-page__detail-row">
                    <span class="rc-verify-page__detail-label">التاريخ</span>
                    <span class="rc-verify-page__detail-value en"><?= Html::encode($date) ?></span>
                </div>
                <div class="rc-verify-page__detail-row">
                    <span class="rc-verify-page__detail-label">رقم العقد</span>
                    <span class="rc-verify-page__detail-value en">#<?= (int) $contract_id ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <p class="rc-verify-page__footer">نظام تيسير لإدارة شركات التقسيط</p>
    </div>
</div>
