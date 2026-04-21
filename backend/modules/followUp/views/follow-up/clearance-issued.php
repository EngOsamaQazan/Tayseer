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
$statusKey      = $isExpired ? 'expired' : 'active';
$sigShort       = strtoupper(substr((string) $cert->signature, 0, 4) . '-'
                 . substr((string) $cert->signature, 4, 4) . '-'
                 . substr((string) $cert->signature, 8, 4));

$issuedBy = null;
if ($cert->issued_by) {
    $u = \common\models\User::findOne($cert->issued_by);
    $issuedBy = $u ? ($u->username ?? ($u->id ?? null)) : null;
}

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

$revokeUrl = Url::to(['revoke-clearance', 'id' => $cert->id]);
?>
<style>
.fc-status-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 999px;
    font-family: var(--font-ar); font-weight: 700; font-size: 13px;
    border: 1px solid transparent;
}
.fc-status-chip--active  { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.fc-status-chip--expired { background: #fffbeb; color: #92400e; border-color: #fde68a; }

.fc-issue-meta {
    margin: 14px 0;
    padding: 14px 16px;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 12px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px 20px;
    font-size: 13px;
}
.fc-issue-meta__item { display: flex; flex-direction: column; gap: 2px; }
.fc-issue-meta__label { color: var(--c-text-3); font-size: 12px; }
.fc-issue-meta__value { color: var(--c-text); font-weight: 600; }

.fc-cases-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 10px; }
.fc-case-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; background: #fafafa; }
.fc-case-item__num { font-weight: 700; font-size: 14px; color: var(--c-text); }
.fc-case-item__meta { color: var(--c-text-2); font-size: 13px; margin-top: 2px; }

.fc-actions-bar {
    margin-top: 20px;
    padding: 14px;
    border: 1px solid var(--c-border);
    border-radius: 12px;
    display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;
    background: var(--c-surface);
}
.fc-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 18px; border-radius: 10px; font-family: var(--font-ar);
    font-weight: 600; font-size: 14px; cursor: pointer; border: none; text-decoration: none;
}
.fc-btn--print  { background: #0f172a; color: #fff !important; }
.fc-btn--revoke { background: #fee2e2; color: #991b1b !important; border: 1px solid #fecaca; }
.fc-btn--revoke:hover { background: #fecaca; }

.fc-expired-banner {
    margin-bottom: 18px; padding: 14px 18px; border-radius: 14px;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1px solid #fcd34d; color: #92400e; display: flex; gap: 10px; align-items: center; font-weight: 600;
}

@media print {
    .fc-actions-bar, .fc-expired-banner { display: none !important; }
}
</style>

<div class="fs" id="clearance-issued">

    <?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="fs-flash fs-flash--success" style="margin-bottom:14px;padding:12px 16px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;font-weight:600">
        <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
    </div>
    <?php endif; ?>

    <?php if ($isExpired): ?>
    <div class="fc-expired-banner">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>هذه الشهادة أصبحت <strong>منتهية الصلاحية</strong> بسبب تسجيل حركة جديدة على العقد بعد تاريخ الإصدار. لإصدار شهادة جديدة يجب إلغاء هذه الشهادة أولاً.</span>
    </div>
    <?php endif; ?>

    <header class="fs-header">
        <div class="fs-header__row">
            <div class="fs-header__brand">
                <div class="fs-header__logo">
                    <svg viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="7" fill="rgba(255,255,255,0.12)"/><path d="M10 26V13l8-4 8 4v13l-8 4-8-4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 13l8 4 8-4M18 17v13" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h1 class="fs-header__company"><?= Html::encode($companyName) ?></h1>
                    <p class="fs-header__subtitle">شهادة براءة ذمة</p>
                </div>
            </div>
            <div class="fs-header__badge-wrap">
                <span class="fc-status-chip fc-status-chip--<?= $statusKey ?>">
                    <?php if ($isExpired): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        منتهية الصلاحية
                    <?php else: ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        شهادة فعّالة
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="fs-header__meta-row">
            <div class="fs-header__meta-item">
                <span class="fs-header__meta-label">رقم الشهادة</span>
                <span class="fs-header__meta-value en"><?= Html::encode($cert->cert_number) ?></span>
            </div>
            <span class="fs-header__meta-dot"></span>
            <div class="fs-header__meta-item">
                <span class="fs-header__meta-label">رقم العقد</span>
                <span class="fs-header__meta-value en"><?= (int) $cert->contract_id ?></span>
            </div>
            <span class="fs-header__meta-dot"></span>
            <div class="fs-header__meta-item">
                <span class="fs-header__meta-label">تاريخ الإصدار</span>
                <span class="fs-header__meta-value en"><?= Html::encode($issuedDate) ?></span>
            </div>
        </div>

        <div class="fs-header__verify-strip">
            <div class="fs-header__qr-box">
                <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR" />
            </div>
            <div class="fs-header__verify-info">
                <span class="fs-header__verify-hint">رقم التحقق</span>
                <span class="fs-header__verify-code en"><?= Html::encode($sigShort) ?></span>
            </div>
        </div>
    </header>

    <section class="fc-banner">
        <div class="fc-banner__icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="fc-banner__content">
            <h2 class="fc-banner__title">شهادة براءة ذمة</h2>
            <p class="fc-banner__subtitle">تم تسديد كافة المستحقات المالية بالكامل</p>
        </div>
    </section>

    <div class="fc-issue-meta">
        <div class="fc-issue-meta__item">
            <span class="fc-issue-meta__label">رقم الشهادة</span>
            <span class="fc-issue-meta__value en"><?= Html::encode($cert->cert_number) ?></span>
        </div>
        <div class="fc-issue-meta__item">
            <span class="fc-issue-meta__label">تاريخ ووقت الإصدار</span>
            <span class="fc-issue-meta__value en"><?= Html::encode($cert->issued_at) ?></span>
        </div>
        <?php if ($issuedBy !== null): ?>
        <div class="fc-issue-meta__item">
            <span class="fc-issue-meta__label">أصدرها</span>
            <span class="fc-issue-meta__value"><?= Html::encode($issuedBy) ?></span>
        </div>
        <?php endif; ?>
        <div class="fc-issue-meta__item">
            <span class="fc-issue-meta__label">التوقيع الرقمي</span>
            <span class="fc-issue-meta__value en" style="font-size:12px"><?= Html::encode(substr((string) $cert->signature, 0, 24)) ?>…</span>
        </div>
    </div>

    <section class="fs-cards">
        <div class="fs-cards__grid">
            <div class="fs-card fs-card--neutral">
                <span class="fs-card__label">إجمالي العقد</span>
                <span class="fs-card__amount en"><?= clrIssuedNum($totalValue) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <div class="fs-card fs-card--success">
                <span class="fs-card__label">المدفوع</span>
                <span class="fs-card__amount en"><?= clrIssuedNum($paidAmount) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <div class="fs-card fs-card--danger">
                <span class="fs-card__label">المتبقي</span>
                <span class="fs-card__amount en"><?= clrIssuedNum($remaining) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
        </div>
    </section>

    <section class="fs-section">
        <h3 class="fs-section__title">معلومات العقد</h3>
        <div class="fs-info">
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات العميل</h4>
                <div class="fs-info__row">
                    <span class="fs-info__label">اسم المدين</span>
                    <span class="fs-info__value"><?= Html::encode(implode(' ، ', (array) $clientNames)) ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">أسماء الكفلاء</span>
                    <span class="fs-info__value"><?= Html::encode(implode(' ، ', (array) $guarantorNames) ?: 'لا يوجد') ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">رقم العقد</span>
                    <span class="fs-info__value en"><?= (int) $cert->contract_id ?></span>
                </div>
            </div>
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات مالية</h4>
                <div class="fs-info__row">
                    <span class="fs-info__label">تاريخ البيع</span>
                    <span class="fs-info__value en"><?= Html::encode($dateSale) ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">تاريخ أول قسط</span>
                    <span class="fs-info__value en"><?= Html::encode($firstInstDate) ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">آخر دفعة</span>
                    <span class="fs-info__value en"><?= Html::encode($lastIncomeDate ?: 'لا يوجد') ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">القسط الشهري</span>
                    <span class="fs-info__value en"><?= clrIssuedNum($monthlyInst) ?></span>
                </div>
            </div>
        </div>
    </section>

    <section class="fs-section">
        <h3 class="fs-section__title">نص براءة الذمة</h3>
        <div class="fc-statement">
            <div class="fc-statement__header">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>لمن يهمه الأمر</span>
            </div>
            <div class="fc-statement__body">
                <p>
                    تشهد <strong><?= Html::encode($companyName) ?></strong> أن المدين / المدينين المذكورين أعلاه
                    <strong>بريئ الذمة المالية</strong> في العقد رقم
                    <strong class="en"><?= (int) $cert->contract_id ?></strong>
                    الموقّع بتاريخ البيع
                    <strong class="en"><?= Html::encode($dateSale) ?></strong>
                    وأن كافة الشيكات والسندات الموقعة من قبله بتاريخ هذا العقد <strong>ملغية</strong>.
                </p>
                <?php if (!empty($cases)): ?>
                <p style="margin-top:10px">
                    مع الإشارة إلى أن هناك قضايا قضائية مسجلة مرتبطة بهذا العقد موضحة أدناه،
                    ولا يُعتبر هذا إقراراً بأي التزام قائم خلاف تسوية الالتزامات المالية الناشئة عن العقد.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($cases)): ?>
    <section class="fs-section">
        <h3 class="fs-section__title">القضايا المسجلة</h3>
        <div class="fc-cases-list">
            <?php foreach ($cases as $case): ?>
            <div class="fc-case-item">
                <div class="fc-case-item__num">
                    قضية رقم
                    <span class="en"><?= Html::encode($case['judiciary_number'] ?: '—') ?></span>
                    <?php if (!empty($case['year'])): ?> / <span class="en"><?= Html::encode($case['year']) ?></span><?php endif; ?>
                </div>
                <div class="fc-case-item__meta">
                    المحكمة: <?= Html::encode($case['court_name'] ?: '—') ?>
                    <?php if (!empty($case['case_status'])): ?>
                    &nbsp;•&nbsp; الحالة: <?= Html::encode($case['case_status']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="fs-section">
        <h3 class="fs-section__title">تحقق من صحة هذه الشهادة</h3>
        <div class="fs-verify">
            <div class="fs-verify__main">
                <div class="fs-verify__code-block">
                    <span class="fs-verify__code-label">رقم التحقق الفريد</span>
                    <span class="fs-verify__code-value en"><?= Html::encode($sigShort) ?></span>
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
        </div>
        <div class="fs-footer__copy en">&copy; <?= date('Y') ?> <?= Html::encode($companyName) ?></div>
    </footer>

    <div class="fc-actions-bar">
        <button type="button" class="fc-btn fc-btn--print" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            طباعة / تنزيل PDF
        </button>

        <?php if ($canRevoke): ?>
        <form method="post" action="<?= Html::encode($revokeUrl) ?>" style="margin:0"
              onsubmit="return confirm('هل أنت متأكد من إلغاء هذه الشهادة؟ بعد الإلغاء يمكن إصدار شهادة جديدة.');">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
            <button type="submit" class="fc-btn fc-btn--revoke">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                إلغاء الشهادة
            </button>
        </form>
        <?php endif; ?>
    </div>

</div>
