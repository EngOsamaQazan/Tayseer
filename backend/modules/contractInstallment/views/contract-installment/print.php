<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\CompanyChecked;

/* @var $this \yii\web\View */
/* @var $model \backend\modules\contractInstallment\models\ContractInstallment */

$this->registerCssFile(Yii::getAlias('@web') . '/css/receipt-print.css', ['depends' => ['yii\web\YiiAsset']]);

function rcNum($n) {
    if ($n === null || $n === '') return $n;
    if (!is_numeric($n)) return $n;
    return number_format((float) $n, 2, '.', ',');
}

$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $companyName = Yii::$app->params['companies_logo'] ?? '';
    $companyPhone = '';
    $compay_banks = '';
} else {
    $companyName = $primary_company->name;
    $companyPhone = $primary_company->phone ?? '';
    $compay_banks = $CompanyChecked->findPrimaryCompanyBancks();
}

$contract = $model->contract;
$customer = $contract->customer ?? null;
$customerName = $customer ? $customer->name : '';
$phoneNumbers = $customer ? $customer->phoneNumbers : [];

$contractCustomers = $contract->contractsCustomers ?? [];
$customersList = [];
foreach ($contractCustomers as $cc) {
    $cust = \backend\modules\customers\models\Customers::findOne($cc->customer_id);
    if ($cust) {
        $phones = [];
        foreach ($cust->phoneNumbers as $pn) {
            $phones[] = $pn->phone_number;
        }
        $customersList[] = [
            'name'  => $cust->name,
            'type'  => $cc->customer_type === 'client' ? 'عميل' : 'كفيل',
            'phones' => $phones,
        ];
    }
}

$payment_type = \backend\modules\paymentType\models\PaymentType::findOne(['id' => $model->payment_type]);
$paymentTypeName = !empty($payment_type) ? $payment_type->name : $model->payment_type;

$receiverName = Yii::$app->user->identity->name ?? '';

$amount = $model->amount;
$receiptDate = $model->date;
$receiptId = $model->id;

// ─── Verification (HMAC signature + QR) ───
$secret = Yii::$app->params['statementVerifySecret'] ?? 'tayseer-statement-verify-default';
$payload = $receiptId . '|' . $receiptDate;
$signature = hash_hmac('sha256', $payload, $secret);
$verifyCode = strtoupper(substr($signature, 0, 4) . '-' . substr($signature, 4, 4) . '-' . substr($signature, 8, 4));
$verifyUrl = Url::to(['/contractInstallment/contract-installment/verify-receipt', 'rid' => $receiptId, 'd' => $receiptDate, 's' => $signature], true);

$qrImageSrc = null;
if (class_exists(\chillerlan\QRCode\QRCode::class)) {
    try {
        $qrImageSrc = (new \chillerlan\QRCode\QRCode())->render($verifyUrl);
    } catch (\Throwable $e) {
        $qrImageSrc = null;
    }
}
if ($qrImageSrc === null) {
    $qrImageSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($verifyUrl);
}
?>

<div class="rc" id="receipt-print">

    <!-- ═══════════════════════════════════════
         1) EXECUTIVE HEADER
         ═══════════════════════════════════════ -->
    <header class="rc-header">
        <div class="rc-header__row">
            <div class="rc-header__brand">
                <div class="rc-header__logo">
                    <svg viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="7" fill="rgba(255,255,255,0.12)"/><path d="M10 26V13l8-4 8 4v13l-8 4-8-4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 13l8 4 8-4M18 17v13" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h1 class="rc-header__company"><?= Html::encode($companyName) ?></h1>
                    <p class="rc-header__subtitle">إيصال استلام</p>
                </div>
            </div>
            <div class="rc-header__type-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                إيصال مالي
            </div>
        </div>

        <div class="rc-header__meta-strip">
            <div class="rc-header__meta-item">
                <span class="rc-header__meta-label">رقم الإيصال</span>
                <span class="rc-header__meta-value en">#<?= Html::encode($receiptId) ?></span>
            </div>
            <span class="rc-header__meta-dot"></span>
            <div class="rc-header__meta-item">
                <span class="rc-header__meta-label">التاريخ</span>
                <span class="rc-header__meta-value en"><?= Html::encode($receiptDate) ?></span>
            </div>
            <span class="rc-header__meta-dot"></span>
            <div class="rc-header__meta-item">
                <span class="rc-header__meta-label">رقم العقد</span>
                <span class="rc-header__meta-value en">#<?= Html::encode($model->contract_id) ?></span>
            </div>
        </div>

        <div class="rc-header__verify-strip">
            <div class="rc-header__qr-box">
                <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR" />
            </div>
            <div class="rc-header__verify-info">
                <span class="rc-header__verify-hint">رقم التحقق</span>
                <span class="rc-header__verify-code en"><?= $verifyCode ?></span>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════
         2) AMOUNT HERO
         ═══════════════════════════════════════ -->
    <div class="rc-amount-hero">
        <span class="rc-amount-hero__label">المبلغ المستلم</span>
        <div class="rc-amount-hero__number en"><?= rcNum($amount) ?></div>
        <span class="rc-amount-hero__currency">دينار أردني</span>
        <div class="rc-amount-hero__words" id="amount_words"></div>
    </div>

    <!-- ═══════════════════════════════════════
         3) RECEIPT DETAILS
         ═══════════════════════════════════════ -->
    <section class="rc-section">
        <h3 class="rc-section__title">تفاصيل الإيصال</h3>
        <div class="rc-details">
            <div class="rc-detail">
                <span class="rc-detail__label">اسم الدافع</span>
                <span class="rc-detail__value"><?= Html::encode($model->_by) ?></span>
            </div>
            <div class="rc-detail rc-detail--highlight">
                <span class="rc-detail__label">طريقة الدفع</span>
                <span class="rc-detail__value"><?= Html::encode($paymentTypeName) ?></span>
            </div>
            <?php if (!empty($model->receipt_bank)): ?>
            <div class="rc-detail">
                <span class="rc-detail__label">البنك</span>
                <span class="rc-detail__value"><?= Html::encode($model->receipt_bank) ?></span>
            </div>
            <?php endif; ?>
            <div class="rc-detail">
                <span class="rc-detail__label">اسم المستلم</span>
                <span class="rc-detail__value"><?= Html::encode($receiverName) ?></span>
            </div>
            <?php if (!empty($model->payment_purpose)): ?>
            <div class="rc-detail">
                <span class="rc-detail__label">وذلك مقابل</span>
                <span class="rc-detail__value"><?= Html::encode($model->payment_purpose) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         4) CONTRACT CUSTOMERS
         ═══════════════════════════════════════ -->
    <section class="rc-section">
        <h3 class="rc-section__title">أطراف العقد</h3>
        <div class="rc-customers">
            <?php foreach ($customersList as $c): ?>
            <div class="rc-customer-row">
                <div class="rc-customer-row__avatar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="rc-customer-row__info">
                    <div class="rc-customer-row__name"><?= Html::encode($c['name']) ?></div>
                    <div class="rc-customer-row__type"><?= Html::encode($c['type']) ?></div>
                </div>
                <?php if (!empty($c['phones'])): ?>
                <div class="rc-customer-row__phones en"><?= Html::encode(implode(' - ', $c['phones'])) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         5) SIGNATURE
         ═══════════════════════════════════════ -->
    <section class="rc-section">
        <h3 class="rc-section__title">التوقيع</h3>
        <div class="rc-signature">
            <div class="rc-signature__box">
                <div class="rc-signature__label">توقيع المستلم</div>
                <div class="rc-signature__name"><?= Html::encode($receiverName) ?></div>
                <div class="rc-signature__line"></div>
            </div>
            <div class="rc-signature__box">
                <div class="rc-signature__label">توقيع الدافع</div>
                <div class="rc-signature__name"><?= Html::encode($model->_by) ?></div>
                <div class="rc-signature__line"></div>
            </div>
        </div>

        <div class="rc-stamp">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            هذا الإيصال صادر إلكترونياً عبر نظام تيسير ولا يحتاج ختم.
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         6) VERIFICATION SECTION
         ═══════════════════════════════════════ -->
    <section class="rc-section">
        <h3 class="rc-section__title">تحقق من صحة هذا الإيصال</h3>
        <div class="rc-verify">
            <div class="rc-verify__main">
                <div class="rc-verify__code-block">
                    <span class="rc-verify__code-label">رقم التحقق الفريد</span>
                    <span class="rc-verify__code-value en"><?= $verifyCode ?></span>
                </div>
                <div class="rc-verify__link-block">
                    <span class="rc-verify__link-label">رابط التحقق</span>
                    <a href="<?= Html::encode($verifyUrl) ?>" class="rc-verify__link en" target="_blank"><?= Html::encode($verifyUrl) ?></a>
                </div>
            </div>
            <div class="rc-verify__qr">
                <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR التحقق" />
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         7) FOOTER
         ═══════════════════════════════════════ -->
    <footer class="rc-footer">
        <div class="rc-footer__top">
            <strong><?= Html::encode($companyName) ?></strong>
            <?php if (!empty($companyPhone)): ?>
            <span class="rc-footer__sep">|</span>
            <span class="en"><?= Html::encode($companyPhone) ?></span>
            <?php endif; ?>
        </div>
        <div class="rc-footer__legal">
            <p>هذا الإيصال يعتبر سنداً رسمياً بالمبلغ المذكور أعلاه.</p>
            <p><?= Html::encode($companyName) ?> غير مسؤولة عن أي دفعات غير مثبتة بإيصال رسمي.</p>
            <?php if (!empty($compay_banks)): ?>
            <p>الشركة غير مسؤولة عن أي دفعة مدفوعة في أي حساب غير حسابها في <?= Html::encode($compay_banks) ?>.</p>
            <?php endif; ?>
        </div>
        <div class="rc-footer__copy en">&copy; <?= date('Y') ?> <?= Html::encode($companyName) ?></div>
    </footer>

</div>

<?php
$amountVal = (float)$amount;
$script = <<< JS
$(document).ready(function(){
    if (typeof tafqeet === 'function') {
        $('#amount_words').text(tafqeet({$amountVal}) + ' دينار أردني فقط لا غير');
    } else {
        $('#amount_words').text('{$amountVal} دينار أردني');
    }
});
JS;
$this->registerJs($script, $this::POS_END);
$this->registerJsFile(Yii::getAlias('@web') . '/js/Tafqeet.js', ['depends' => ['yii\web\JqueryAsset']]);
?>
