<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\LoanContract;
use backend\modules\contractInstallment\models\ContractInstallment;
use common\components\CompanyChecked;

/* @var $this \yii\web\View */
/* @var $contract_id int */

$this->registerCssFile(Yii::getAlias('@web') . '/css/follow-up-statement.css', ['depends' => ['yii\web\YiiAsset']]);

function clrNum($n) {
    if ($n === null || $n === '' || $n === '—' || $n === 'لا يوجد') return $n;
    if (!is_numeric($n)) return $n;
    return number_format((float) $n, 2, '.', ',');
}

// ─── Company ───
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $companyName = Yii::$app->params['companies_logo'] ?? '';
    $compay_banks = '';
    $companyPhone = '';
} else {
    $companyName = $primary_company->name;
    $compay_banks = $CompanyChecked->findPrimaryCompanyBancks();
    $companyPhone = $primary_company->phone ?? '';
}

// ─── Contract & customers ───
$clientInContract = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['customer_type' => 'client', 'contract_id' => $contract_id])->all();
$guarantorInContract = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['customer_type' => 'guarantor', 'contract_id' => $contract_id])->all();

$modelf = new LoanContract;
$contractModel = $modelf->findContract($contract_id);
if (!$contractModel) {
    echo '<div style="text-align:center;padding:60px 20px;font-family:sans-serif;direction:rtl"><h2>العقد غير موجود</h2><p>رقم العقد المطلوب (' . (int)$contract_id . ') غير موجود في النظام.</p></div>';
    return;
}

$vb = \backend\modules\followUp\helper\ContractCalculations::fromView($contractModel->id);
$total = $vb ? $vb['totalDebt'] : (float)$contractModel->total_value;
$contractModel->total_value = $total;

$clientNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $clientInContract);
$guarantorNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $guarantorInContract);

$paid_amount = $vb ? $vb['paid'] : 0;
$remaining_balance = $vb ? $vb['remaining'] : 0;

$lastIncomeDate = ContractInstallment::find()
    ->where(['contract_id' => $contract_id])->orderBy(['date' => SORT_DESC])->one();

// ─── Payment rate ───
$totalForRate = $contractModel->total_value > 0 ? $contractModel->total_value : 1;
$paymentRate = min(100, round(($paid_amount / $totalForRate) * 100, 1));

// ─── Verification ───
$statementDate = date('Y-m-d');
$secret = Yii::$app->params['statementVerifySecret'] ?? 'tayseer-statement-verify-default';
$payload = $contract_id . '|clearance|' . $statementDate;
$signature = hash_hmac('sha256', $payload, $secret);
$verifyCode = strtoupper(substr($signature, 0, 4) . '-' . substr($signature, 4, 4) . '-' . substr($signature, 8, 4));
$verifyUrl = Url::to(['/followUp/follow-up/verify-statement', 'c' => $contract_id, 'd' => $statementDate, 't' => $statementDate, 's' => $signature], true);

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

// ─── Contract info ───
$infoClient = [
    ['label' => 'اسم المدين',      'value' => implode(' ، ', $clientNames)],
    ['label' => 'أسماء الكفلاء',    'value' => implode(' ، ', $guarantorNames) ?: 'لا يوجد'],
    ['label' => 'رقم العقد',        'value' => $contract_id],
];

$infoFinancial = [
    ['label' => 'تاريخ البيع',      'value' => $contractModel->Date_of_sale ?? '—'],
    ['label' => 'تاريخ أول قسط',    'value' => $contractModel->first_installment_date ?? '—'],
    ['label' => 'آخر دفعة',        'value' => $lastIncomeDate ? $lastIncomeDate->date : 'لا يوجد'],
    ['label' => 'القسط الشهري',    'value' => clrNum($contractModel->monthly_installment_value)],
];
?>

<!-- ══════════════════════════════════════════════════════════
     براءة ذمة — Premium FinTech Clearance Certificate
     ══════════════════════════════════════════════════════════ -->
<div class="fs" id="clearance-certificate">

    <!-- ═══════════════════════════════════════
         1) EXECUTIVE HEADER
         ═══════════════════════════════════════ -->
    <header class="fs-header">
        <div class="fs-header__row">
            <div class="fs-header__brand">
                <div class="fs-header__logo">
                    <svg viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="7" fill="rgba(255,255,255,0.12)"/><path d="M10 26V13l8-4 8 4v13l-8 4-8-4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 13l8 4 8-4M18 17v13" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h1 class="fs-header__company"><?= Html::encode($companyName) ?></h1>
                    <p class="fs-header__subtitle">براءة ذمة</p>
                </div>
            </div>
            <div class="fs-header__badge-wrap">
                <div class="fc-status-badge">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    بريء الذمة
                </div>
            </div>
        </div>

        <div class="fs-header__meta-row">
            <div class="fs-header__meta-item">
                <span class="fs-header__meta-label">رقم العقد</span>
                <span class="fs-header__meta-value en"><?= Html::encode($contract_id) ?></span>
            </div>
            <span class="fs-header__meta-dot"></span>
            <div class="fs-header__meta-item">
                <span class="fs-header__meta-label">تاريخ الإصدار</span>
                <span class="fs-header__meta-value en"><?= $statementDate ?></span>
            </div>
        </div>

        <div class="fs-header__verify-strip">
            <div class="fs-header__qr-box">
                <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR" />
            </div>
            <div class="fs-header__verify-info">
                <span class="fs-header__verify-hint">رقم التحقق</span>
                <span class="fs-header__verify-code en"><?= $verifyCode ?></span>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════
         2) CLEARANCE STATUS BANNER
         ═══════════════════════════════════════ -->
    <section class="fc-banner">
        <div class="fc-banner__icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="fc-banner__content">
            <h2 class="fc-banner__title">شهادة براءة ذمة</h2>
            <p class="fc-banner__subtitle">تم تسديد كافة المستحقات المالية بالكامل</p>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         3) FINANCIAL SUMMARY — 3 Cards
         ═══════════════════════════════════════ -->
    <section class="fs-cards">
        <div class="fs-cards__grid">
            <div class="fs-card fs-card--neutral">
                <span class="fs-card__label">إجمالي العقد</span>
                <span class="fs-card__amount en"><?= clrNum($contractModel->total_value) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <div class="fs-card fs-card--success">
                <span class="fs-card__label">المدفوع</span>
                <span class="fs-card__amount en"><?= clrNum($paid_amount) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <div class="fs-card fs-card--danger">
                <span class="fs-card__label">المتبقي</span>
                <span class="fs-card__amount en"><?= clrNum($remaining_balance) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
        </div>

        <div class="fs-progress-card">
            <div class="fs-progress-card__top">
                <span class="fs-progress-card__label">نسبة السداد</span>
                <span class="fs-progress-card__percent en"><?= $paymentRate ?>%</span>
            </div>
            <div class="fs-progress-card__bar">
                <div class="fs-progress-card__fill" style="width: <?= $paymentRate ?>%"></div>
            </div>
            <p class="fs-progress-card__text">تم سداد <strong class="en"><?= clrNum($paid_amount) ?></strong> من أصل <strong class="en"><?= clrNum($contractModel->total_value) ?></strong> دينار</p>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         4) CONTRACT INFORMATION
         ═══════════════════════════════════════ -->
    <section class="fs-section">
        <h3 class="fs-section__title">معلومات العقد</h3>
        <div class="fs-info">
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات العميل</h4>
                <?php foreach ($infoClient as $row): ?>
                <div class="fs-info__row">
                    <span class="fs-info__label"><?= Html::encode($row['label']) ?></span>
                    <span class="fs-info__value en"><?= Html::encode($row['value']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات مالية</h4>
                <?php foreach ($infoFinancial as $row): ?>
                <div class="fs-info__row">
                    <span class="fs-info__label"><?= Html::encode($row['label']) ?></span>
                    <span class="fs-info__value en"><?= Html::encode($row['value']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         5) CLEARANCE STATEMENT — لمن يهمه الأمر
         ═══════════════════════════════════════ -->
    <section class="fs-section">
        <h3 class="fs-section__title">نص براءة الذمة</h3>
        <div class="fc-statement">
            <div class="fc-statement__header">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>لمن يهمه الأمر</span>
            </div>
            <div class="fc-statement__body">
                <p>
                    تشهد <strong><?= Html::encode($companyName) ?></strong> أن المدين / المدينين المذكورين أعلاه
                    <strong>بريئ الذمة المالية</strong> في العقد رقم
                    <strong class="en"><?= Html::encode($contract_id) ?></strong>
                    الموقّع بتاريخ البيع
                    <strong class="en"><?= Html::encode($contractModel->Date_of_sale ?? '—') ?></strong>
                    وأن كافة الشيكات والسندات الموقعة من قبله بتاريخ هذا العقد <strong>ملغية</strong>.
                </p>
            </div>
            <div class="fc-statement__seal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                هذه الشهادة صادرة إلكترونياً عبر نظام تيسير ERP ولا تحتاج إلى توقيع أو ختم.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         6) COPIES DISTRIBUTION
         ═══════════════════════════════════════ -->
    <section class="fs-section">
        <h3 class="fs-section__title">توزيع النسخ</h3>
        <div class="fc-copies">
            <div class="fc-copies__item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>نسخة ملف العميل</span>
            </div>
            <div class="fc-copies__item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>نسخة العميل</span>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         7) VERIFICATION SECTION
         ═══════════════════════════════════════ -->
    <section class="fs-section">
        <h3 class="fs-section__title">تحقق من صحة هذه الشهادة</h3>
        <div class="fs-verify">
            <div class="fs-verify__main">
                <div class="fs-verify__code-block">
                    <span class="fs-verify__code-label">رقم التحقق الفريد</span>
                    <span class="fs-verify__code-value en"><?= $verifyCode ?></span>
                </div>
                <div class="fs-verify__link-block">
                    <span class="fs-verify__link-label">رابط التحقق</span>
                    <a href="<?= Html::encode($verifyUrl) ?>" class="fs-verify__link en" target="_blank"><?= Html::encode($verifyUrl) ?></a>
                </div>
            </div>
            <div class="fs-verify__qr">
                <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR التحقق" />
            </div>
        </div>
        <div class="fs-verify__stamp">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            هذه الشهادة موثقة إلكترونياً عبر نظام تيسير ERP ولا تحتاج توقيع.
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         8) FOOTER
         ═══════════════════════════════════════ -->
    <footer class="fs-footer">
        <div class="fs-footer__top">
            <strong><?= Html::encode($companyName) ?></strong>
            <?php if (!empty($companyPhone)): ?>
            <span class="fs-footer__sep">|</span>
            <span class="en"><?= Html::encode($companyPhone) ?></span>
            <?php endif; ?>
        </div>
        <div class="fs-footer__legal">
            <p><?= Html::encode($companyName) ?> مسؤولة عن صحة بيانات هذه الشهادة حتى تاريخها.</p>
            <p>هذه الشهادة لا تعفي العميل من أي التزامات أخرى خارج نطاق هذا العقد.</p>
            <?php if (!empty($compay_banks)): ?>
            <p>الشركة غير مسؤولة عن أي دفعة مدفوعة في أي حساب غير حسابها في <?= Html::encode($compay_banks) ?>.</p>
            <?php endif; ?>
        </div>
        <div class="fs-footer__copy en">&copy; <?= date('Y') ?> <?= Html::encode($companyName) ?></div>
    </footer>

</div>
