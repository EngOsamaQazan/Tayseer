<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\CompanyChecked;

/* @var $this \yii\web\View */
/* @var $contract_id int */
/* @var $contractModel \backend\modules\contracts\models\Contracts */
/* @var $calc array|null */
/* @var $remaining float */
/* @var $judiciaryCases array */
/* @var $previousRevoked \backend\modules\followUp\models\ClearanceCertificate|null */

$this->title = 'معاينة براءة الذمة';
$this->registerCssFile(Yii::getAlias('@web') . '/css/follow-up-statement.css', ['depends' => ['yii\web\YiiAsset']]);

if (!function_exists('clrNum')) {
    function clrNum($n) {
        if ($n === null || $n === '' || $n === '—' || $n === 'لا يوجد') return $n;
        if (!is_numeric($n)) return $n;
        return number_format((float) $n, 2, '.', ',');
    }
}

$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
$companyName = $primary_company ? $primary_company->name : (Yii::$app->params['companies_logo'] ?? '');

$clientInContract = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['customer_type' => 'client', 'contract_id' => $contract_id])->all();
$guarantorInContract = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['customer_type' => 'guarantor', 'contract_id' => $contract_id])->all();

$clientNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $clientInContract);
$guarantorNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $guarantorInContract);

$totalDebt = $calc ? (float) $calc['totalDebt'] : (float) $contractModel->total_value;
$paid      = $calc ? (float) $calc['paid'] : 0.0;

$totalForRate = $totalDebt > 0 ? $totalDebt : 1;
$paymentRate  = min(100, round(($paid / $totalForRate) * 100, 1));

$hasCases     = !empty($judiciaryCases);
$canIssue     = round($remaining, 2) <= 0;
$issueFormUrl = Url::to(['issue-clearance', 'contract_id' => $contract_id]);
?>
<style>
/* Preview-specific additions on top of follow-up-statement.css */
.fs-preview-banner {
    margin-bottom: 18px;
    padding: 14px 18px;
    border-radius: 14px;
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    border: 1px solid #fdba74;
    color: #9a3412;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}
.fs-preview-banner svg { flex-shrink: 0; }

.fs-cases {
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 10px;
}
.fs-case {
    border: 1px solid #fecaca;
    background: #fef2f2;
    border-radius: 12px;
    padding: 12px 14px;
}
.fs-case__num {
    font-weight: 700;
    color: #991b1b;
    font-size: 16px;
    margin-bottom: 4px;
}
.fs-case__meta {
    color: #7f1d1d;
    font-size: 13px;
    line-height: 1.7;
}
.fs-case__meta span.en { margin-inline: 4px; }

.fs-issue-bar {
    position: sticky;
    bottom: 0;
    margin-top: 24px;
    padding: 16px;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    box-shadow: 0 -4px 16px rgba(0,0,0,0.05);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    z-index: 10;
}
.fs-issue-bar__left {
    display: flex;
    flex-direction: column;
    gap: 2px;
    max-width: 60%;
}
.fs-issue-bar__title {
    font-weight: 700;
    font-size: 15px;
    color: var(--c-text);
}
.fs-issue-bar__hint {
    font-size: 13px;
    color: var(--c-text-2);
}
.fs-issue-bar__hint--error { color: var(--c-danger); font-weight: 600; }

.fs-btn-issue {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #fff !important;
    border: none;
    border-radius: 12px;
    font-family: var(--font-ar);
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(5,150,105,0.3);
    transition: transform 0.15s, box-shadow 0.15s;
}
.fs-btn-issue:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(5,150,105,0.4); }
.fs-btn-issue:disabled, .fs-btn-issue[disabled] {
    background: #d1d5db;
    color: #6b7280 !important;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}
.fs-btn-issue svg { flex-shrink: 0; }

.fs-flash {
    margin-bottom: 14px;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 600;
}
.fs-flash--error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.fs-flash--warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.fs-flash--success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }

/* Modal */
.fs-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,0.55);
    display: none;
    align-items: center; justify-content: center;
    z-index: 9999;
    padding: 20px;
}
.fs-modal-backdrop.is-open { display: flex; }
.fs-modal {
    background: #fff;
    max-width: 560px;
    width: 100%;
    border-radius: 16px;
    padding: 22px;
    direction: rtl;
    font-family: var(--font-ar);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.fs-modal h3 {
    margin: 0 0 8px;
    font-size: 18px;
    color: #991b1b;
    display: flex; align-items: center; gap: 8px;
}
.fs-modal p { color: #475569; margin: 0 0 14px; font-size: 14px; line-height: 1.7; }
.fs-modal__cases { max-height: 220px; overflow: auto; margin-bottom: 14px; }
.fs-modal__actions { display: flex; gap: 10px; justify-content: flex-end; }
.fs-modal__btn {
    padding: 10px 18px;
    border-radius: 10px;
    font-family: var(--font-ar);
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    border: none;
}
.fs-modal__btn--cancel { background: #f1f5f9; color: #475569; }
.fs-modal__btn--confirm { background: #059669; color: #fff; }
.fs-modal__btn--confirm:hover { background: #047857; }
</style>

<div class="fs" id="clearance-preview">

    <?php foreach (['error','warning','success'] as $flashType): ?>
        <?php if (Yii::$app->session->hasFlash($flashType)): ?>
        <div class="fs-flash fs-flash--<?= $flashType ?>"><?= Html::encode(Yii::$app->session->getFlash($flashType)) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($previousRevoked): ?>
    <div class="fs-flash fs-flash--warning">
        ملاحظة: كانت هناك شهادة سابقة (<span class="en"><?= Html::encode($previousRevoked->cert_number) ?></span>) تم إلغاؤها بتاريخ
        <span class="en"><?= Html::encode(substr((string) $previousRevoked->revoked_at, 0, 10)) ?></span>. يمكنك الآن إصدار شهادة جديدة.
    </div>
    <?php endif; ?>

    <div class="fs-preview-banner">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span>هذه معاينة قبل الإصدار الرسمي. لن يعمل رمز QR ولن تُعتمد الشهادة إلا بعد الضغط على زر "إصدار الشهادة".</span>
    </div>

    <header class="fs-header">
        <div class="fs-header__row">
            <div class="fs-header__brand">
                <div class="fs-header__logo">
                    <svg viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="7" fill="rgba(255,255,255,0.12)"/><path d="M10 26V13l8-4 8 4v13l-8 4-8-4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 13l8 4 8-4M18 17v13" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h1 class="fs-header__company"><?= Html::encode($companyName) ?></h1>
                    <p class="fs-header__subtitle">معاينة براءة ذمة</p>
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
                <span class="fs-header__meta-label">تاريخ المعاينة</span>
                <span class="fs-header__meta-value en"><?= date('Y-m-d') ?></span>
            </div>
        </div>
    </header>

    <section class="fs-cards">
        <div class="fs-cards__grid">
            <div class="fs-card fs-card--neutral">
                <span class="fs-card__label">إجمالي العقد</span>
                <span class="fs-card__amount en"><?= clrNum($totalDebt) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <div class="fs-card fs-card--success">
                <span class="fs-card__label">المدفوع</span>
                <span class="fs-card__amount en"><?= clrNum($paid) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <div class="fs-card fs-card--danger">
                <span class="fs-card__label">المتبقي</span>
                <span class="fs-card__amount en"><?= clrNum($remaining) ?></span>
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
        </div>
    </section>

    <section class="fs-section">
        <h3 class="fs-section__title">معلومات العقد</h3>
        <div class="fs-info">
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات العميل</h4>
                <div class="fs-info__row">
                    <span class="fs-info__label">اسم المدين</span>
                    <span class="fs-info__value"><?= Html::encode(implode(' ، ', $clientNames)) ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">أسماء الكفلاء</span>
                    <span class="fs-info__value"><?= Html::encode(implode(' ، ', $guarantorNames) ?: 'لا يوجد') ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">رقم العقد</span>
                    <span class="fs-info__value en"><?= Html::encode($contract_id) ?></span>
                </div>
            </div>
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات مالية</h4>
                <div class="fs-info__row">
                    <span class="fs-info__label">تاريخ البيع</span>
                    <span class="fs-info__value en"><?= Html::encode($contractModel->Date_of_sale ?? '—') ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">تاريخ أول قسط</span>
                    <span class="fs-info__value en"><?= Html::encode($contractModel->first_installment_date ?? '—') ?></span>
                </div>
                <div class="fs-info__row">
                    <span class="fs-info__label">القسط الشهري</span>
                    <span class="fs-info__value en"><?= clrNum($contractModel->monthly_installment_value) ?></span>
                </div>
            </div>
        </div>
    </section>

    <?php if ($hasCases): ?>
    <section class="fs-section">
        <h3 class="fs-section__title">القضايا المسجلة على العميل</h3>
        <p style="color:#7f1d1d; margin:0 0 10px; font-size:14px">
            يوجد <strong class="en"><?= count($judiciaryCases) ?></strong> قضية غير محذوفة على هذا العقد وسيتم ذكرها في الشهادة عند الإصدار.
        </p>
        <div class="fs-cases">
            <?php foreach ($judiciaryCases as $case): ?>
            <div class="fs-case">
                <div class="fs-case__num">
                    قضية رقم
                    <span class="en"><?= Html::encode($case['judiciary_number'] ?: '—') ?></span>
                    <?php if (!empty($case['year'])): ?>
                        / <span class="en"><?= Html::encode($case['year']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="fs-case__meta">
                    <div>المحكمة: <?= Html::encode($case['court_name'] ?: '—') ?></div>
                    <?php if (!empty($case['case_status'])): ?>
                    <div>الحالة: <?= Html::encode($case['case_status']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <form id="clearance-issue-form" method="post" action="<?= Html::encode($issueFormUrl) ?>">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
        <input type="hidden" name="confirm_cases" id="confirm_cases" value="<?= $hasCases ? '0' : '1' ?>">

        <div class="fs-issue-bar">
            <div class="fs-issue-bar__left">
                <span class="fs-issue-bar__title">إصدار شهادة براءة الذمة رسمياً</span>
                <?php if (!$canIssue): ?>
                <span class="fs-issue-bar__hint fs-issue-bar__hint--error">
                    لا يمكن الإصدار — يوجد رصيد متبقٍ: <span class="en"><?= clrNum($remaining) ?></span> د.أ
                </span>
                <?php elseif ($hasCases): ?>
                <span class="fs-issue-bar__hint">
                    سيتم إدراج بيانات القضايا المسجلة في الشهادة. ستظهر نافذة تأكيد قبل الإصدار.
                </span>
                <?php else: ?>
                <span class="fs-issue-bar__hint">
                    بعد الإصدار ستحصل الشهادة على رقم تسلسلي، توقيع رقمي، ورمز QR للتحقق.
                </span>
                <?php endif; ?>
            </div>

            <button type="<?= $hasCases ? 'button' : 'submit' ?>"
                    id="fs-issue-btn"
                    class="fs-btn-issue"
                    <?= $canIssue ? '' : 'disabled' ?>>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span>إصدار الشهادة</span>
            </button>
        </div>
    </form>

    <?php if ($hasCases && $canIssue): ?>
    <div class="fs-modal-backdrop" id="fs-cases-modal">
        <div class="fs-modal">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                تنبيه: يوجد قضايا مسجلة على العميل
            </h3>
            <p>
                يوجد <strong class="en"><?= count($judiciaryCases) ?></strong> قضية نشطة مرتبطة بهذا العقد. سيتم ذكر رقم كل قضية واسم المحكمة في شهادة براءة الذمة.
                هل تريد المتابعة بالإصدار؟
            </p>
            <div class="fs-modal__cases">
                <?php foreach ($judiciaryCases as $case): ?>
                <div class="fs-case" style="margin-bottom:6px">
                    <div class="fs-case__num">
                        قضية <span class="en"><?= Html::encode($case['judiciary_number'] ?: '—') ?></span>
                        <?php if (!empty($case['year'])): ?> / <span class="en"><?= Html::encode($case['year']) ?></span><?php endif; ?>
                    </div>
                    <div class="fs-case__meta">المحكمة: <?= Html::encode($case['court_name'] ?: '—') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="fs-modal__actions">
                <button type="button" class="fs-modal__btn fs-modal__btn--cancel" id="fs-cases-cancel">إلغاء</button>
                <button type="button" class="fs-modal__btn fs-modal__btn--confirm" id="fs-cases-confirm">متابعة الإصدار</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php if ($hasCases && $canIssue): ?>
<script>
(function(){
    var btn     = document.getElementById('fs-issue-btn');
    var modal   = document.getElementById('fs-cases-modal');
    var cancel  = document.getElementById('fs-cases-cancel');
    var confirm = document.getElementById('fs-cases-confirm');
    var form    = document.getElementById('clearance-issue-form');
    var flag    = document.getElementById('confirm_cases');

    if (!btn || !modal || !form) return;

    btn.addEventListener('click', function(e){
        e.preventDefault();
        modal.classList.add('is-open');
    });
    cancel.addEventListener('click', function(){ modal.classList.remove('is-open'); });
    modal.addEventListener('click', function(e){ if (e.target === modal) modal.classList.remove('is-open'); });
    confirm.addEventListener('click', function(){
        flag.value = '1';
        modal.classList.remove('is-open');
        form.submit();
    });
})();
</script>
<?php endif; ?>
