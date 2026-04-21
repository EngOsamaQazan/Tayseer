<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $cert \backend\modules\followUp\models\ClearanceCertificate */
/* @var $snapshot array */
/* @var $isExpired bool */
/* @var $verifyUrl string */
/* @var $canRevoke bool */

$this->title = 'شهادة براءة ذمة — ' . $cert->cert_number;
$this->registerCssFile(Yii::getAlias('@web') . '/css/follow-up-statement.css', ['depends' => ['yii\web\YiiAsset']]);

if (!function_exists('clrIssuedNum')) {
    function clrIssuedNum($n) {
        if ($n === null || $n === '' || $n === '—' || $n === 'لا يوجد') return $n;
        if (!is_numeric($n)) return $n;
        return number_format((float) $n, 2, '.', ',');
    }
}

$companyName    = $snapshot['companyName']    ?? '';
$companyPhone   = $snapshot['companyPhone']   ?? '';
$clientNames    = $snapshot['clientNames']    ?? [];
$guarantorNames = $snapshot['guarantorNames'] ?? [];
$totalValue     = $snapshot['totalValue']     ?? 0;
$paidAmount     = $snapshot['paidAmount']     ?? 0;
$remaining      = $snapshot['remainingBalance']?? 0;
$dateSale       = $snapshot['dateSale']       ?? '—';
$firstInstDate  = $snapshot['firstInstDate']  ?? '—';
$lastIncomeDate = $snapshot['lastIncomeDate'] ?? null;
$monthlyInst    = $snapshot['monthlyInst']    ?? null;
$cases          = $snapshot['judiciaryCases'] ?? [];

$issuedDate     = substr((string) $cert->issued_at, 0, 10);
$issuedTime     = substr((string) $cert->issued_at, 11, 5);
$statusKey      = $isExpired ? 'expired' : 'active';
$sigShort       = strtoupper(substr((string) $cert->signature, 0, 4) . '-'
                 . substr((string) $cert->signature, 4, 4) . '-'
                 . substr((string) $cert->signature, 8, 4));

$issuedBy = null;
if ($cert->issued_by) {
    $u = \common\models\User::findOne($cert->issued_by);
    $issuedBy = $u ? ($u->username ?? ($u->id ?? null)) : null;
}

// Generate a LARGE scannable QR. Prefer a local PNG via chillerlan (base64
// data URL, 320x320), fall back to qrserver.com @ 320px.
$qrImageSrc = null;
if (class_exists(\chillerlan\QRCode\QRCode::class)) {
    try {
        $opts = new \chillerlan\QRCode\QROptions([
            'version'    => 5,
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => \chillerlan\QRCode\QRCode::ECC_M,
            'scale'      => 8,
            'imageBase64'=> true,
            'imageTransparent' => false,
        ]);
        $qrImageSrc = (new \chillerlan\QRCode\QRCode($opts))->render($verifyUrl);
    } catch (\Throwable $e) {
        $qrImageSrc = null;
    }
}
if ($qrImageSrc === null) {
    $qrImageSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=8&data=' . urlencode($verifyUrl);
}

$revokeUrl = Url::to(['revoke-clearance', 'id' => $cert->id]);
$pdfUrl    = Url::to(['download-clearance-pdf', 'id' => $cert->id]);
?>

<style>
/* ═══════════════════════════════════════════════════════════
   شهادة براءة ذمة — Professional Certificate Layout v2
   (single-column, print-ready, scannable QR)
   ═══════════════════════════════════════════════════════════ */
.cc {
    --c-primary:      #7A000C;
    --c-primary-dark: #4A0006;
    --c-success:      #0F7B3D;
    --c-success-soft: #eefbf3;
    --c-warn:         #92400e;
    --c-warn-soft:    #fffbeb;
    --c-text:         #1a1d21;
    --c-text-2:       #525866;
    --c-text-3:       #868c98;
    --c-bg:           #f4f5f7;
    --c-surface:      #ffffff;
    --c-border:       #e4e5e7;
    --c-border-2:     #f0f0f1;

    --font-ar: 'IBM Plex Sans Arabic','Cairo','Segoe UI',Tahoma,sans-serif;
    --font-en: 'Inter','Segoe UI',sans-serif;

    font-family: var(--font-ar);
    direction: rtl;
    color: var(--c-text);
    max-width: 860px;
    margin: 0 auto;
    padding: 24px 20px 40px;
    background: var(--c-bg);
    line-height: 1.7;
    -webkit-font-smoothing: antialiased;
}
.cc .en {
    font-family: var(--font-en);
    font-variant-numeric: tabular-nums;
    direction: ltr;
    unicode-bidi: isolate;
    display: inline-block;
}

/* ── Flash ── */
.cc-flash {
    margin-bottom: 16px;
    padding: 12px 16px;
    border-radius: 12px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    font-weight: 600;
    display: flex; align-items: center; gap: 8px;
}

/* ── Expired banner ── */
.cc-expired {
    margin-bottom: 18px;
    padding: 14px 18px;
    border-radius: 14px;
    background: linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);
    border: 1px solid #fcd34d;
    color: var(--c-warn);
    display: flex; gap: 12px; align-items: center; font-weight: 600;
}

/* ── Certificate container (the “paper”) ── */
.cc-doc {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 14px rgba(0,0,0,0.05);
}

/* ── Header ── */
.cc-header {
    background: linear-gradient(135deg,#7A000C 0%,#5C0008 55%,#4A0006 100%);
    color: #fff;
    padding: 28px 32px 24px;
    position: relative;
}
.cc-header::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 3px;
    background: linear-gradient(90deg,#B8860B 0%,#d4a640 50%,#B8860B 100%);
}
.cc-header__row {
    display: flex; align-items: center; justify-content: space-between; gap: 18px;
    flex-wrap: wrap;
}
.cc-brand { display: flex; align-items: center; gap: 14px; }
.cc-brand__logo {
    width: 52px; height: 52px; border-radius: 10px;
    background: rgba(255,255,255,0.12);
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,0.18);
}
.cc-brand__title { font-size: 13px; opacity: 0.78; letter-spacing: 0.02em; margin: 0 0 2px; font-weight: 500; }
.cc-brand__name  { font-size: 22px; font-weight: 700; margin: 0; line-height: 1.2; }

.cc-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 999px;
    font-weight: 700; font-size: 13px;
    border: 1.5px solid;
}
.cc-status--active  { background: rgba(16,185,129,0.15); color: #ecfdf5; border-color: rgba(167,243,208,0.5); }
.cc-status--expired { background: rgba(245,158,11,0.18); color: #fef3c7; border-color: rgba(253,224,71,0.5); }

.cc-title {
    text-align: center;
    margin: 22px 0 6px;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.cc-title__sub { text-align: center; font-size: 13px; opacity: 0.7; margin: 0; }

.cc-meta {
    margin-top: 22px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1px;
    background: rgba(255,255,255,0.12);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.14);
}
.cc-meta__item {
    background: rgba(0,0,0,0.14);
    padding: 12px 16px;
    display: flex; flex-direction: column; gap: 2px;
    min-width: 0;
}
.cc-meta__label { font-size: 11px; opacity: 0.65; font-weight: 500; }
.cc-meta__value { font-size: 15px; font-weight: 700; word-break: break-word; }

/* ── Body ── */
.cc-body { padding: 28px 32px; }

.cc-section { margin-bottom: 26px; }
.cc-section:last-child { margin-bottom: 0; }

.cc-section__title {
    display: flex; align-items: center; gap: 8px;
    font-size: 14px; font-weight: 700;
    color: var(--c-primary);
    margin: 0 0 12px;
    padding-bottom: 8px;
    border-bottom: 1.5px solid var(--c-border-2);
}
.cc-section__title svg { color: var(--c-primary); }

/* ── Statement text ── */
.cc-statement {
    padding: 18px 20px;
    background: #fdfcfa;
    border: 1px dashed #d9b98a;
    border-radius: 12px;
    font-size: 15px;
    line-height: 1.9;
    color: #2b2b2e;
}
.cc-statement strong { color: var(--c-primary-dark); font-weight: 700; }
.cc-statement p { margin: 0 0 10px; }
.cc-statement p:last-child { margin: 0; }

/* ── Two-column info ── */
.cc-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.cc-card {
    background: #fafbfc;
    border: 1px solid var(--c-border);
    border-radius: 12px;
    padding: 14px 16px;
}
.cc-card__title {
    font-size: 12px; font-weight: 700; color: var(--c-text-3);
    margin: 0 0 10px; letter-spacing: 0.02em;
    text-transform: uppercase;
}
.cc-kv { display: flex; justify-content: space-between; align-items: flex-start; padding: 6px 0; border-bottom: 1px dashed var(--c-border-2); gap: 12px; }
.cc-kv:last-child { border-bottom: 0; }
.cc-kv__k { color: var(--c-text-2); font-size: 13px; white-space: nowrap; }
.cc-kv__v { color: var(--c-text); font-size: 13px; font-weight: 600; text-align: left; word-break: break-word; }

/* ── Financial strip ── */
.cc-money {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 12px;
}
.cc-money__cell {
    padding: 14px 14px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid var(--c-border);
    background: #fff;
}
.cc-money__cell--paid    { background: #f0fdf4; border-color: #bbf7d0; }
.cc-money__cell--remain  { background: #fef2f2; border-color: #fecaca; }
.cc-money__label   { font-size: 12px; color: var(--c-text-2); margin: 0 0 4px; }
.cc-money__amount  { font-size: 22px; font-weight: 800; color: var(--c-text); margin: 0; }
.cc-money__cell--paid   .cc-money__amount { color: var(--c-success); }
.cc-money__cell--remain .cc-money__amount { color: #B42318; }
.cc-money__cur     { font-size: 12px; color: var(--c-text-3); margin-top: 2px; }

/* ── Cases ── */
.cc-cases { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap: 10px; }
.cc-case { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; background: #fafafa; }
.cc-case__n { font-weight: 700; font-size: 14px; color: var(--c-text); margin-bottom: 4px; }
.cc-case__m { color: var(--c-text-2); font-size: 13px; }

/* ── Verification block (LARGE, scannable QR) ── */
.cc-verify {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 22px;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg,#ffffff 0%,#f9f9fb 100%);
    border: 1.5px solid var(--c-border);
    border-radius: 14px;
}
.cc-verify__qr {
    width: 200px; height: 200px;
    background: #fff;
    border: 1px solid var(--c-border);
    border-radius: 12px;
    padding: 8px;
    display: flex; align-items: center; justify-content: center;
}
.cc-verify__qr img { width: 100%; height: 100%; object-fit: contain; display: block; }

.cc-verify__info { min-width: 0; }
.cc-verify__hint { font-size: 12px; color: var(--c-text-3); margin: 0 0 6px; }
.cc-verify__code {
    font-size: 20px; font-weight: 800; letter-spacing: 0.06em;
    color: var(--c-primary); margin-bottom: 14px;
    font-family: var(--font-en);
    direction: ltr; unicode-bidi: isolate;
}
.cc-verify__row { display: flex; gap: 8px; font-size: 13px; margin-bottom: 6px; }
.cc-verify__row b { color: var(--c-text-3); font-weight: 500; flex-shrink: 0; }
.cc-verify__url {
    display: inline-block;
    padding: 6px 10px; border-radius: 6px;
    background: #f4f5f7; border: 1px solid var(--c-border);
    color: var(--c-primary); text-decoration: none;
    font-family: var(--font-en); font-size: 12px;
    word-break: break-all; line-height: 1.4;
    max-width: 100%;
}
.cc-verify__url:hover { background: #eef0f3; }

.cc-trust {
    margin-top: 14px;
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px;
    background: var(--c-success-soft);
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    color: var(--c-success);
    font-weight: 600; font-size: 13px;
}

/* ── Footer ── */
.cc-footer {
    padding: 20px 32px 24px;
    border-top: 1px solid var(--c-border-2);
    text-align: center;
    color: var(--c-text-3);
    font-size: 12px;
    background: #fafafb;
}
.cc-footer__brand { color: var(--c-text); font-weight: 700; font-size: 13px; margin: 0 0 4px; }
.cc-footer__note  { margin: 2px 0; }

/* ── Actions bar (not printed) ── */
.cc-actions {
    margin-top: 20px;
    padding: 14px 18px;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    display: flex; flex-wrap: wrap; gap: 10px;
    justify-content: space-between; align-items: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.cc-actions__left { display: flex; flex-wrap: wrap; gap: 10px; }
.cc-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-family: var(--font-ar);
    font-weight: 600; font-size: 14px;
    cursor: pointer; border: 1px solid transparent;
    text-decoration: none; transition: all 0.15s;
    white-space: nowrap;
}
.cc-btn--primary { background: var(--c-primary); color: #fff !important; }
.cc-btn--primary:hover { background: var(--c-primary-dark); }
.cc-btn--outline { background: #fff; color: var(--c-text) !important; border-color: var(--c-border); }
.cc-btn--outline:hover { background: #f4f5f7; }
.cc-btn--danger  { background: #fff; color: #991b1b !important; border-color: #fecaca; }
.cc-btn--danger:hover  { background: #fee2e2; }

/* ── Responsive ── */
@media (max-width: 720px) {
    .cc { padding: 16px 12px 32px; }
    .cc-header { padding: 22px 20px 20px; }
    .cc-body   { padding: 22px 20px; }
    .cc-footer { padding: 18px 20px 20px; }
    .cc-title  { font-size: 22px; }
    .cc-meta   { grid-template-columns: 1fr; }
    .cc-grid   { grid-template-columns: 1fr; }
    .cc-money  { grid-template-columns: 1fr; }
    .cc-verify { grid-template-columns: 1fr; justify-items: center; text-align: center; }
    .cc-verify__qr { width: 220px; height: 220px; margin: 0 auto; }
    .cc-verify__row { justify-content: center; flex-wrap: wrap; }
}

/* ── Print ── */
@media print {
    body, html { background: #fff !important; }
    .cc-actions, .cc-expired, .cc-flash { display: none !important; }
    .cc { max-width: 100%; padding: 0; background: #fff; }
    .cc-doc { border: 1px solid #ddd; box-shadow: none; border-radius: 0; }
    .cc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .cc-status, .cc-money__cell--paid, .cc-money__cell--remain { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<div class="cc" id="clearance-issued">

    <?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="cc-flash">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
    </div>
    <?php endif; ?>

    <?php if ($isExpired): ?>
    <div class="cc-expired">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>هذه الشهادة أصبحت <strong>منتهية الصلاحية</strong> بسبب تسجيل حركة جديدة على العقد بعد تاريخ الإصدار. لإصدار شهادة جديدة يجب إلغاء هذه الشهادة أولاً.</span>
    </div>
    <?php endif; ?>

    <article class="cc-doc">

        <header class="cc-header">
            <div class="cc-header__row">
                <div class="cc-brand">
                    <div class="cc-brand__logo">
                        <svg width="26" height="26" viewBox="0 0 36 36" fill="none">
                            <path d="M10 26V13l8-4 8 4v13l-8 4-8-4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M10 13l8 4 8-4M18 17v13" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <p class="cc-brand__title">المُصدِر</p>
                        <h1 class="cc-brand__name"><?= Html::encode($companyName) ?></h1>
                    </div>
                </div>
                <span class="cc-status cc-status--<?= $statusKey ?>">
                    <?php if ($isExpired): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        منتهية الصلاحية
                    <?php else: ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        شهادة فعّالة
                    <?php endif; ?>
                </span>
            </div>

            <h2 class="cc-title">شهادة براءة ذمة</h2>
            <p class="cc-title__sub">Clearance Certificate</p>

            <div class="cc-meta">
                <div class="cc-meta__item">
                    <span class="cc-meta__label">رقم الشهادة</span>
                    <span class="cc-meta__value en"><?= Html::encode($cert->cert_number) ?></span>
                </div>
                <div class="cc-meta__item">
                    <span class="cc-meta__label">رقم العقد</span>
                    <span class="cc-meta__value en"><?= (int) $cert->contract_id ?></span>
                </div>
                <div class="cc-meta__item">
                    <span class="cc-meta__label">تاريخ الإصدار</span>
                    <span class="cc-meta__value en"><?= Html::encode($issuedDate) ?><?php if ($issuedTime): ?> <span style="opacity:.75"><?= Html::encode($issuedTime) ?></span><?php endif ?></span>
                </div>
            </div>
        </header>

        <div class="cc-body">

            <!-- 1) نص الشهادة -->
            <section class="cc-section">
                <h3 class="cc-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    لمن يهمه الأمر
                </h3>
                <div class="cc-statement">
                    <p>
                        تشهد <strong><?= Html::encode($companyName) ?></strong>
                        أن المدين / المدينين <strong><?= Html::encode(implode(' ، ', (array) $clientNames)) ?></strong>
                        <strong>بريء الذمة المالية</strong>
                        في العقد رقم <strong class="en"><?= (int) $cert->contract_id ?></strong>
                        الموقّع بتاريخ <strong class="en"><?= Html::encode($dateSale) ?></strong>،
                        وأن كافة الشيكات والسندات الموقعة من قبله بتاريخ هذا العقد <strong>ملغية</strong>.
                    </p>
                    <?php if (!empty($cases)): ?>
                    <p>
                        مع الإشارة إلى أن هناك قضايا قضائية مسجلة مرتبطة بهذا العقد موضحة أدناه،
                        ولا يُعتبر هذا إقراراً بأي التزام قائم خلاف تسوية الالتزامات المالية الناشئة عن العقد.
                    </p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 2) الحالة المالية -->
            <section class="cc-section">
                <h3 class="cc-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    الحالة المالية
                </h3>
                <div class="cc-money">
                    <div class="cc-money__cell">
                        <p class="cc-money__label">إجمالي العقد</p>
                        <p class="cc-money__amount en"><?= clrIssuedNum($totalValue) ?></p>
                        <p class="cc-money__cur">د.أ</p>
                    </div>
                    <div class="cc-money__cell cc-money__cell--paid">
                        <p class="cc-money__label">المدفوع</p>
                        <p class="cc-money__amount en"><?= clrIssuedNum($paidAmount) ?></p>
                        <p class="cc-money__cur">د.أ</p>
                    </div>
                    <div class="cc-money__cell cc-money__cell--remain">
                        <p class="cc-money__label">المتبقي</p>
                        <p class="cc-money__amount en"><?= clrIssuedNum($remaining) ?></p>
                        <p class="cc-money__cur">د.أ</p>
                    </div>
                </div>
            </section>

            <!-- 3) بيانات العقد -->
            <section class="cc-section">
                <h3 class="cc-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    بيانات العقد
                </h3>
                <div class="cc-grid">
                    <div class="cc-card">
                        <p class="cc-card__title">الأطراف</p>
                        <div class="cc-kv"><span class="cc-kv__k">المدين</span><span class="cc-kv__v"><?= Html::encode(implode(' ، ', (array) $clientNames)) ?></span></div>
                        <div class="cc-kv"><span class="cc-kv__k">الكفلاء</span><span class="cc-kv__v"><?= Html::encode(implode(' ، ', (array) $guarantorNames) ?: 'لا يوجد') ?></span></div>
                    </div>
                    <div class="cc-card">
                        <p class="cc-card__title">التواريخ والأقساط</p>
                        <div class="cc-kv"><span class="cc-kv__k">تاريخ البيع</span><span class="cc-kv__v en"><?= Html::encode($dateSale) ?></span></div>
                        <div class="cc-kv"><span class="cc-kv__k">أول قسط</span><span class="cc-kv__v en"><?= Html::encode($firstInstDate) ?></span></div>
                        <div class="cc-kv"><span class="cc-kv__k">آخر دفعة</span><span class="cc-kv__v en"><?= Html::encode($lastIncomeDate ?: 'لا يوجد') ?></span></div>
                        <div class="cc-kv"><span class="cc-kv__k">القسط الشهري</span><span class="cc-kv__v en"><?= clrIssuedNum($monthlyInst) ?></span></div>
                    </div>
                </div>
            </section>

            <!-- 4) القضايا -->
            <?php if (!empty($cases)): ?>
            <section class="cc-section">
                <h3 class="cc-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    القضايا القضائية المسجلة
                </h3>
                <div class="cc-cases">
                    <?php foreach ($cases as $case): ?>
                    <div class="cc-case">
                        <div class="cc-case__n">
                            قضية رقم
                            <span class="en"><?= Html::encode($case['judiciary_number'] ?: '—') ?></span>
                            <?php if (!empty($case['year'])): ?> / <span class="en"><?= Html::encode($case['year']) ?></span><?php endif; ?>
                        </div>
                        <div class="cc-case__m">
                            <?= Html::encode($case['court_name'] ?: 'محكمة غير محددة') ?>
                            <?php if (!empty($case['case_status'])): ?>
                            &nbsp;•&nbsp; الحالة: <?= Html::encode($case['case_status']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 5) التحقق الإلكتروني -->
            <section class="cc-section">
                <h3 class="cc-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    التحقق الإلكتروني
                </h3>
                <div class="cc-verify">
                    <div class="cc-verify__qr">
                        <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR Verification" />
                    </div>
                    <div class="cc-verify__info">
                        <p class="cc-verify__hint">رقم التحقق الفريد</p>
                        <div class="cc-verify__code"><?= Html::encode($sigShort) ?></div>

                        <?php if ($issuedBy !== null): ?>
                        <div class="cc-verify__row"><b>أصدرها:</b><span><?= Html::encode($issuedBy) ?></span></div>
                        <?php endif; ?>
                        <div class="cc-verify__row"><b>رابط التحقق:</b></div>
                        <a href="<?= Html::encode($verifyUrl) ?>" class="cc-verify__url en" target="_blank" rel="noopener"><?= Html::encode($verifyUrl) ?></a>
                    </div>
                </div>
                <div class="cc-trust">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    شهادة موثّقة إلكترونياً عبر نظام تيسير ERP — لا تحتاج توقيع يدوي.
                </div>
            </section>

        </div>

        <footer class="cc-footer">
            <p class="cc-footer__brand"><?= Html::encode($companyName) ?><?php if (!empty($companyPhone)): ?> <span style="opacity:.5">|</span> <span class="en"><?= Html::encode($companyPhone) ?></span><?php endif; ?></p>
            <p class="cc-footer__note"><?= Html::encode($companyName) ?> مسؤولة عن صحة بيانات هذه الشهادة حتى تاريخها.</p>
            <p class="cc-footer__note">هذه الشهادة لا تُعفي العميل من أي التزامات أخرى خارج نطاق هذا العقد.</p>
            <p class="cc-footer__note en" style="margin-top:6px">&copy; <?= date('Y') ?> <?= Html::encode($companyName) ?></p>
        </footer>

    </article>

    <div class="cc-actions">
        <div class="cc-actions__left">
            <a href="<?= Html::encode($pdfUrl) ?>" class="cc-btn cc-btn--primary" target="_blank" rel="noopener">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                تصدير PDF
            </a>
            <button type="button" class="cc-btn cc-btn--outline" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                طباعة
            </button>
        </div>

        <?php if ($canRevoke): ?>
        <form method="post" action="<?= Html::encode($revokeUrl) ?>" style="margin:0"
              onsubmit="return confirm('هل أنت متأكد من إلغاء هذه الشهادة؟ بعد الإلغاء يمكن إصدار شهادة جديدة.');">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
            <button type="submit" class="cc-btn cc-btn--danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                إلغاء الشهادة
            </button>
        </form>
        <?php endif; ?>
    </div>

</div>
