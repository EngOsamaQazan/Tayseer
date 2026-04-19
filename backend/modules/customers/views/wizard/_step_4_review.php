<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use backend\models\Media;

/**
 * Step 4 — Review & finalize.
 *
 * Mental model:
 *   "Show the user EVERYTHING they entered in one scannable page, surface
 *    risk-relevant data they should sanity-check, then offer one big
 *    primary CTA to commit the customer."
 *
 * Sections:
 *   • Hero                — name, ID number, status pill
 *   • Identity recap      — Step 1 fields, with edit-jump buttons
 *   • Employment recap    — Step 2 fields
 *   • Guarantors recap    — Step 3 dynamic rows
 *   • Address recap       — Step 3 address
 *   • Real-estate recap   — Step 3 (only if customer owns property)
 *   • Documents hub       — scan thumbnails + missing-docs hint
 *   • Risk assessment     — quick badge based on data completeness + signals
 *   • Final CTA           — "اعتماد العميل" button
 *
 * @var array $payload
 * @var int   $step
 * @var array $lookups   ['cities','citizens','hearAboutUs','jobs','banks','cousins']
 */

$cust1 = $payload['step1']['Customers'] ?? [];
$cust2 = $payload['step2']['Customers'] ?? [];
$cust3 = $payload['step3']['Customers'] ?? [];
$cust  = array_merge((array)$cust1, (array)$cust2, (array)$cust3);

// Address shape — supports both the new `addresses[home|work]` layout
// and the legacy single `address` payload (mapped to "home"). This
// keeps the recap in lockstep with the editing partial.
$addresses = $payload['step3']['addresses'] ?? null;
if (!is_array($addresses) || empty($addresses)) {
    $legacy   = $payload['step3']['address'] ?? [];
    $addresses = ['home' => is_array($legacy) ? $legacy : []];
}
$homeAddress = is_array($addresses['home'] ?? null) ? $addresses['home'] : [];
$workAddress = is_array($addresses['work'] ?? null) ? $addresses['work'] : [];
$guarantors  = $payload['step3']['guarantors'] ?? [];
if (!is_array($guarantors)) $guarantors = [];

// "Address present" probe used to decide whether to render an
// optional block — rather than dump an empty card on the recap.
$addrHasContent = function (array $a): bool {
    foreach (['address_city', 'address_area', 'address_street',
              'address_building', 'postal_code', 'address',
              'latitude', 'longitude', 'plus_code'] as $k) {
        if (trim((string)($a[$k] ?? '')) !== '') return true;
    }
    return false;
};

$scanInfo = $payload['_scan'] ?? [];

// ── Customer-uploaded extras (personal photo + ad-hoc documents). ──
//
// These live under `_extras` in the draft, populated by the Step 1
// uploaders in `_step_1_extras.php`. The review needs them so the
// reviewer can:
//   • Confirm the headshot that will land on the contract print preview
//     (Customers.selected_image gets stamped on finish).
//   • See/open every supporting document (utility bills, salary letters,
//     vehicle registrations, …) without leaving the wizard.
$extras       = is_array($payload['_extras'] ?? null) ? $payload['_extras'] : [];
$personalPic  = (is_array($extras['photo'] ?? null) && !empty($extras['photo']['url']))
              ? $extras['photo'] : null;
$extraDocs    = (is_array($extras['docs']  ?? null)) ? array_values($extras['docs']) : [];

// ── Lookup name helpers (id → display label). ──
$cityMap   = ArrayHelper::map($lookups['cities']   ?? [], 'id', 'name');
$citizenMap= ArrayHelper::map($lookups['citizens'] ?? [], 'id', 'name');
$hearMap   = ArrayHelper::map($lookups['hearAboutUs'] ?? [], 'id', 'name');
$jobMap    = ArrayHelper::map($lookups['jobs']     ?? [], 'id', 'name');
$bankMap   = ArrayHelper::map($lookups['banks']    ?? [], 'id', 'name');

$lookupName = function ($id, array $map) {
    if ($id === null || $id === '' || $id === 0 || $id === '0') return null;
    return $map[$id] ?? null;
};

// Normalize a value for display: returns either the string or a
// "—" placeholder so empty cells are still readable.
$display = function ($v) {
    $v = is_scalar($v) ? trim((string)$v) : '';
    return $v === '' ? '—' : $v;
};

$displayBool = function ($v) {
    if ($v === '' || $v === null) return '—';
    return ((string)$v === '1') ? 'نعم' : 'لا';
};

// Customers.sex enum aligned with the rest of the app (legacy _form.php,
// _smart_form.php, view.php and the existing DB rows): 0=ذكر, 1=أنثى.
$sexLabels = ['0' => 'ذكر', '1' => 'أنثى'];
$sexRaw    = (string)($cust['sex'] ?? '');
$sexLabel  = ($sexRaw !== '' && isset($sexLabels[$sexRaw])) ? $sexLabels[$sexRaw] : '—';

// ── Resolve scan media (front/back/SS income statement). ──
$scanFront  = null;
$scanBack   = null;
$scanIncome = null;
$scanImageIds = $scanInfo['images'] ?? [];
if (is_array($scanImageIds)) {
    if (!empty($scanImageIds['front'])) {
        try { $scanFront = Media::findOne((int)$scanImageIds['front']); } catch (\Throwable $e) {}
    }
    if (!empty($scanImageIds['back'])) {
        try { $scanBack = Media::findOne((int)$scanImageIds['back']); } catch (\Throwable $e) {}
    }
    if (!empty($scanImageIds['income'])) {
        try { $scanIncome = Media::findOne((int)$scanImageIds['income']); } catch (\Throwable $e) {}
    }
}

// ── Risk assessment (lightweight client-visible signal). ──
// Score components — each "miss" adds risk weight. Capped at 100.
$riskScore = 0;
$riskReasons = [];
if (empty($cust['id_number']))            { $riskScore += 25; $riskReasons[] = 'رقم الهوية ناقص'; }
if (empty($cust['primary_phone_number'])) { $riskScore += 15; $riskReasons[] = 'رقم الهاتف ناقص'; }
if (empty($cust['total_salary']))         { $riskScore += 20; $riskReasons[] = 'الراتب غير محدد'; }
if (empty($cust['job_title']))            { $riskScore += 15; $riskReasons[] = 'جهة العمل غير محددة'; }
if (count(array_filter($guarantors, function ($g) {
        return is_array($g) && trim((string)($g['owner_name'] ?? '')) !== '';
    })) < 2) {
    $riskScore += 15; $riskReasons[] = 'يُفضّل وجود معرّفَين على الأقل';
}
if (!$scanFront && !$scanBack) {
    $riskScore += 10; $riskReasons[] = 'لم تُرفع صورة الهوية';
}
$riskScore = min(100, $riskScore);

if ($riskScore <= 20)      { $riskBand = ['label' => 'منخفض', 'class' => 'cw-risk--low',    'icon' => 'fa-shield', 'tone' => 'success']; }
elseif ($riskScore <= 50)  { $riskBand = ['label' => 'متوسط', 'class' => 'cw-risk--medium', 'icon' => 'fa-exclamation-circle', 'tone' => 'warning']; }
else                       { $riskBand = ['label' => 'مرتفع', 'class' => 'cw-risk--high',   'icon' => 'fa-exclamation-triangle', 'tone' => 'error']; }

// ── Counts for the hero pills. ──
$guarantorCount = count(array_filter($guarantors, function ($g) {
    return is_array($g) && trim((string)($g['owner_name'] ?? '')) !== '';
}));
$docCount = ($scanFront ? 1 : 0) + ($scanBack ? 1 : 0) + ($scanIncome ? 1 : 0);

$ownsProp = (string)($cust['do_have_any_property'] ?? '') === '1';
?>

<div class="cw-card cw-review">

    <!-- ═══════════════ HERO ═══════════════ -->
    <div class="cw-review__hero">
        <div class="cw-review__hero-main">
            <?php if ($personalPic): ?>
                <div class="cw-review__avatar cw-review__avatar--photo">
                    <img src="<?= Html::encode((string)$personalPic['url']) ?>"
                         alt="الصورة الشخصية للعميل"
                         loading="lazy">
                </div>
            <?php else: ?>
                <div class="cw-review__avatar" aria-hidden="true">
                    <i class="fa fa-user-circle-o"></i>
                </div>
            <?php endif ?>
            <div class="cw-review__hero-text">
                <div class="cw-review__hero-name"><?= Html::encode($display($cust['name'] ?? '')) ?></div>
                <div class="cw-review__hero-meta">
                    <?php if (!empty($cust['id_number'])): ?>
                        <span class="cw-review__chip">
                            <i class="fa fa-id-card-o" aria-hidden="true"></i>
                            <span>هوية: <?= Html::encode($cust['id_number']) ?></span>
                        </span>
                    <?php endif ?>
                    <?php if (!empty($cust['primary_phone_number'])): ?>
                        <span class="cw-review__chip">
                            <i class="fa fa-phone" aria-hidden="true"></i>
                            <span dir="ltr"><?= Html::encode($cust['primary_phone_number']) ?></span>
                        </span>
                    <?php endif ?>
                    <?php if (!empty($cust['birth_date'])): ?>
                        <span class="cw-review__chip">
                            <i class="fa fa-birthday-cake" aria-hidden="true"></i>
                            <span><?= Html::encode($cust['birth_date']) ?></span>
                        </span>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <div class="cw-review__hero-stats">
            <div class="cw-review__stat">
                <div class="cw-review__stat-num"><?= $guarantorCount ?></div>
                <div class="cw-review__stat-lbl">معرّفون</div>
            </div>
            <div class="cw-review__stat">
                <div class="cw-review__stat-num"><?= $docCount ?></div>
                <div class="cw-review__stat-lbl">مستند</div>
            </div>
            <div class="cw-review__stat cw-review__stat--risk <?= $riskBand['class'] ?>">
                <div class="cw-review__stat-num"><?= $riskScore ?>%</div>
                <div class="cw-review__stat-lbl"><i class="fa <?= $riskBand['icon'] ?>" aria-hidden="true"></i> <?= $riskBand['label'] ?></div>
            </div>
        </div>
    </div>

    <p class="cw-card__hint cw-card__hint--success">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        راجع البيانات أدناه. يمكنك العودة إلى أي خطوة بالنقر على عنوانها أو زر "تعديل" بجانبها، ثم العودة هنا والضغط على "اعتماد العميل" للحفظ النهائي.
    </p>

    <div class="cw-card__body">

        <!-- ═══════════════ Identity recap ═══════════════ -->
        <div class="cw-review-section">
            <div class="cw-review-section__head">
                <h4 class="cw-review-section__title">
                    <i class="fa fa-user" aria-hidden="true"></i>
                    التعريف بالعميل
                </h4>
                <button type="button" class="cw-btn cw-btn--ghost cw-btn--sm" data-cw-step="1">
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    <span>تعديل</span>
                </button>
            </div>
            <dl class="cw-review-list">
                <div class="cw-review-list__row"><dt>الاسم الكامل</dt><dd><?= Html::encode($display($cust['name'] ?? '')) ?></dd></div>
                <div class="cw-review-list__row"><dt>الرقم الوطني</dt><dd dir="ltr"><?= Html::encode($display($cust['id_number'] ?? '')) ?></dd></div>
                <div class="cw-review-list__row"><dt>الجنسية</dt><dd><?= Html::encode($display($lookupName($cust['citizen'] ?? null, $citizenMap))) ?></dd></div>
                <div class="cw-review-list__row"><dt>الجنس</dt><dd><?= Html::encode($sexLabel) ?></dd></div>
                <div class="cw-review-list__row"><dt>تاريخ الميلاد</dt><dd dir="ltr"><?= Html::encode($display($cust['birth_date'] ?? '')) ?></dd></div>
                <div class="cw-review-list__row"><dt>مكان الولادة</dt><dd><?= Html::encode($display($lookupName($cust['city'] ?? null, $cityMap))) ?></dd></div>
                <div class="cw-review-list__row"><dt>الهاتف الرئيسي</dt><dd dir="ltr"><?= Html::encode($display($cust['primary_phone_number'] ?? '')) ?></dd></div>
                <div class="cw-review-list__row"><dt>كيف تعرّفت علينا</dt><dd><?= Html::encode($display($lookupName($cust['hear_about_us'] ?? null, $hearMap))) ?></dd></div>
                <?php if (!empty($cust['email'])): ?>
                    <div class="cw-review-list__row"><dt>البريد الإلكتروني</dt><dd dir="ltr"><?= Html::encode($cust['email']) ?></dd></div>
                <?php endif ?>
                <?php if (!empty($cust['facebook_account'])): ?>
                    <div class="cw-review-list__row"><dt>حساب فيسبوك</dt><dd dir="ltr"><?= Html::encode($cust['facebook_account']) ?></dd></div>
                <?php endif ?>
                <?php if (!empty($cust['notes'])): ?>
                    <div class="cw-review-list__row cw-review-list__row--wide"><dt>ملاحظات</dt><dd><?= Html::encode($cust['notes']) ?></dd></div>
                <?php endif ?>
            </dl>
        </div>

        <!-- ═══════════════ Financial position recap ═══════════════ -->
        <div class="cw-review-section">
            <div class="cw-review-section__head">
                <h4 class="cw-review-section__title">
                    <i class="fa fa-briefcase" aria-hidden="true"></i>
                    الوضع المالي
                </h4>
                <button type="button" class="cw-btn cw-btn--ghost cw-btn--sm" data-cw-step="2">
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    <span>تعديل</span>
                </button>
            </div>
            <dl class="cw-review-list">
                <div class="cw-review-list__row"><dt>جهة العمل</dt><dd><?= Html::encode($display($lookupName($cust['job_title'] ?? null, $jobMap))) ?></dd></div>
                <div class="cw-review-list__row"><dt>الرقم الوظيفي</dt><dd dir="ltr"><?= Html::encode($display($cust['job_number'] ?? '')) ?></dd></div>
                <div class="cw-review-list__row"><dt>إجمالي الراتب</dt><dd><?= Html::encode($display($cust['total_salary'] ?? '')) ?> <small class="cw-review-list__unit">د.أ</small></dd></div>
                <?php if (!empty($cust['last_income_query_date'])): ?>
                    <div class="cw-review-list__row"><dt>تاريخ آخر فحص دخل</dt><dd dir="ltr"><?= Html::encode($cust['last_income_query_date']) ?></dd></div>
                <?php endif ?>
                <?php if (!empty($cust['last_job_query_date'])): ?>
                    <div class="cw-review-list__row"><dt>تاريخ آخر فحص عمل</dt><dd dir="ltr"><?= Html::encode($cust['last_job_query_date']) ?></dd></div>
                <?php endif ?>
                <div class="cw-review-list__row"><dt>مشترك بالضمان</dt><dd><?= Html::encode($displayBool($cust['is_social_security'] ?? '')) ?></dd></div>
                <?php if ((string)($cust['is_social_security'] ?? '') === '1'): ?>
                    <div class="cw-review-list__row"><dt>رقم اشتراك الضمان</dt><dd dir="ltr"><?= Html::encode($display($cust['social_security_number'] ?? '')) ?></dd></div>
                <?php endif ?>
                <?php
                // has_social_security_salary stored as 'yes'/'no' (radio values),
                // not 1/0 — see _step_2_employment.php line ~347. The earlier
                // displayBool() helper expects 1/0, so we map manually here.
                $hasPension = (string)($cust['has_social_security_salary'] ?? '');
                $hasPensionLabel = $hasPension === 'yes' ? 'نعم'
                                  : ($hasPension === 'no' ? 'لا' : '—');
                ?>
                <div class="cw-review-list__row"><dt>يستلم راتب تقاعد/ضمان</dt><dd><?= Html::encode($hasPensionLabel) ?></dd></div>
                <?php if ($hasPension === 'yes'): ?>
                    <?php if (!empty($cust['social_security_salary_source'])): ?>
                        <div class="cw-review-list__row"><dt>مصدر الراتب التقاعدي</dt><dd><?= Html::encode($cust['social_security_salary_source']) ?></dd></div>
                    <?php endif ?>
                    <?php if (!empty($cust['retirement_status'])): ?>
                        <div class="cw-review-list__row"><dt>حالة التقاعد</dt><dd><?= Html::encode($cust['retirement_status']) ?></dd></div>
                    <?php endif ?>
                    <div class="cw-review-list__row"><dt>إجمالي دخل التقاعد</dt><dd><?= Html::encode($display($cust['total_retirement_income'] ?? '')) ?> <small class="cw-review-list__unit">د.أ</small></dd></div>
                <?php endif ?>
                <div class="cw-review-list__row"><dt>البنك</dt><dd><?= Html::encode($display($lookupName($cust['bank_name'] ?? null, $bankMap))) ?></dd></div>
                <?php if (!empty($cust['bank_branch'])): ?>
                    <div class="cw-review-list__row"><dt>الفرع</dt><dd><?= Html::encode($cust['bank_branch']) ?></dd></div>
                <?php endif ?>
                <div class="cw-review-list__row"><dt>رقم الحساب البنكي</dt><dd dir="ltr"><?= Html::encode($display($cust['account_number'] ?? '')) ?></dd></div>
            </dl>
        </div>

        <!-- ═══════════════ Guarantors recap ═══════════════ -->
        <div class="cw-review-section">
            <div class="cw-review-section__head">
                <h4 class="cw-review-section__title">
                    <i class="fa fa-address-book" aria-hidden="true"></i>
                    المعرّفون <span class="cw-review-section__count">(<?= $guarantorCount ?>)</span>
                </h4>
                <button type="button" class="cw-btn cw-btn--ghost cw-btn--sm" data-cw-step="3">
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    <span>تعديل</span>
                </button>
            </div>

            <?php if ($guarantorCount === 0): ?>
                <div class="cw-empty">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    لم تتم إضافة أي معرّف. عُد إلى الخطوة 3 لإضافة معرّف واحد على الأقل.
                </div>
            <?php else: ?>
                <div class="cw-review-cards">
                    <?php foreach ($guarantors as $g): ?>
                        <?php if (!is_array($g) || trim((string)($g['owner_name'] ?? '')) === '') continue; ?>
                        <div class="cw-review-card">
                            <div class="cw-review-card__head">
                                <div class="cw-review-card__title"><?= Html::encode($g['owner_name'] ?? '') ?></div>
                                <?php if (!empty($g['phone_number_owner'])): ?>
                                    <span class="cw-review-card__chip"><?= Html::encode($g['phone_number_owner']) ?></span>
                                <?php endif ?>
                            </div>
                            <?php if (!empty($g['phone_number'])): ?>
                                <div class="cw-review-card__row" dir="ltr">
                                    <i class="fa fa-phone" aria-hidden="true"></i>
                                    <span><?= Html::encode($g['phone_number']) ?></span>
                                </div>
                            <?php endif ?>
                            <?php if (!empty($g['fb_account'])): ?>
                                <div class="cw-review-card__row" dir="ltr">
                                    <i class="fa fa-facebook-square" aria-hidden="true"></i>
                                    <span><?= Html::encode($g['fb_account']) ?></span>
                                </div>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- ═══════════════ Addresses recap (home + work) ═══════════════
             Each block is rendered only when it carries content, so an
             unfilled work address doesn't pollute the recap with an empty
             card. Home is essentially always present (validation requires
             city) — but we still gate it for safety. -->
        <?php
        $addressBlocks = [
            ['key' => 'home', 'title' => 'عنوان السكن', 'icon' => 'fa-home',       'data' => $homeAddress],
            ['key' => 'work', 'title' => 'عنوان العمل', 'icon' => 'fa-briefcase',  'data' => $workAddress],
        ];
        ?>
        <?php foreach ($addressBlocks as $block):
            $a = $block['data'];
            if (!$addrHasContent($a)) continue;
        ?>
            <div class="cw-review-section">
                <div class="cw-review-section__head">
                    <h4 class="cw-review-section__title">
                        <i class="fa <?= Html::encode($block['icon']) ?>" aria-hidden="true"></i>
                        <?= Html::encode($block['title']) ?>
                    </h4>
                    <button type="button" class="cw-btn cw-btn--ghost cw-btn--sm" data-cw-step="3">
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                        <span>تعديل</span>
                    </button>
                </div>
                <dl class="cw-review-list">
                    <div class="cw-review-list__row"><dt>المدينة</dt><dd><?= Html::encode($display($a['address_city'] ?? '')) ?></dd></div>
                    <div class="cw-review-list__row"><dt>المنطقة</dt><dd><?= Html::encode($display($a['address_area'] ?? '')) ?></dd></div>
                    <div class="cw-review-list__row"><dt>الشارع</dt><dd><?= Html::encode($display($a['address_street'] ?? '')) ?></dd></div>
                    <div class="cw-review-list__row"><dt>المبنى / الطابق</dt><dd><?= Html::encode($display($a['address_building'] ?? '')) ?></dd></div>
                    <?php if (!empty($a['postal_code'])): ?>
                        <div class="cw-review-list__row"><dt>الرمز البريدي</dt><dd dir="ltr"><?= Html::encode($a['postal_code']) ?></dd></div>
                    <?php endif ?>
                    <?php if (!empty($a['plus_code'])): ?>
                        <div class="cw-review-list__row"><dt>Plus Code</dt><dd dir="ltr"><?= Html::encode($a['plus_code']) ?></dd></div>
                    <?php endif ?>
                    <?php if (!empty($a['address'])): ?>
                        <div class="cw-review-list__row"><dt>ملاحظات</dt><dd><?= Html::encode($a['address']) ?></dd></div>
                    <?php endif ?>
                </dl>
            </div>
        <?php endforeach ?>

        <!-- ═══════════════ Real-estate recap (only if owns) ═══════════════
             Edit button points to step 2 because real-estate now lives in
             the financial-position card (see _step_2_employment.php § D). -->
        <?php if ($ownsProp): ?>
            <div class="cw-review-section">
                <div class="cw-review-section__head">
                    <h4 class="cw-review-section__title">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        الأصول العقارية
                    </h4>
                    <button type="button" class="cw-btn cw-btn--ghost cw-btn--sm" data-cw-step="2">
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                        <span>تعديل</span>
                    </button>
                </div>
                <dl class="cw-review-list">
                    <div class="cw-review-list__row"><dt>اسم/نوع العقار</dt><dd><?= Html::encode($display($cust['property_name'] ?? '')) ?></dd></div>
                    <div class="cw-review-list__row"><dt>رقم العقار</dt><dd dir="ltr"><?= Html::encode($display($cust['property_number'] ?? '')) ?></dd></div>
                </dl>
            </div>
        <?php endif ?>

        <!-- ═══════════════ Documents hub ═══════════════ -->
        <div class="cw-review-section">
            <div class="cw-review-section__head">
                <h4 class="cw-review-section__title">
                    <i class="fa fa-folder-open" aria-hidden="true"></i>
                    مستندات العميل
                </h4>
                <button type="button" class="cw-btn cw-btn--ghost cw-btn--sm" data-cw-step="1">
                    <i class="fa fa-upload" aria-hidden="true"></i>
                    <span>إضافة/تعديل</span>
                </button>
            </div>

            <div class="cw-review-docs">
                <?php
                // Required slots — every customer file should have these three.
                // Renders missing-state tiles when a slot is empty so the
                // reviewer immediately sees what still needs uploading.
                $docTiles = [
                    ['key' => 'front',  'media' => $scanFront,  'label' => 'وجه الهوية'],
                    ['key' => 'back',   'media' => $scanBack,   'label' => 'ظهر الهوية'],
                    ['key' => 'income', 'media' => $scanIncome, 'label' => 'كشف الضمان / إثبات الدخل', 'editStep' => 2],
                ];
                foreach ($docTiles as $tile):
                    $media = $tile['media'];
                    if ($media):
                        $imgUrl = method_exists($media, 'getUrl') ? $media->getUrl() : '';
                        $ext    = strtolower(pathinfo((string)$media->fileName, PATHINFO_EXTENSION));
                        $isPdf  = $ext === 'pdf';
                ?>
                    <a href="<?= Html::encode($imgUrl) ?>" target="_blank" rel="noopener"
                       class="cw-review-doc cw-review-doc--filled <?= $isPdf ? 'cw-review-doc--pdf' : '' ?>">
                        <div class="cw-review-doc__thumb"
                             <?= $isPdf ? 'data-cw-pdf-thumb data-pdf-url="' . Html::encode($imgUrl) . '"' : '' ?>>
                            <?php if ($isPdf): ?>
                                <i class="fa fa-file-pdf-o cw-review-doc__pdf-fallback" aria-hidden="true"></i>
                                <span class="cw-review-doc__pdf-loading">
                                    <i class="fa fa-spinner fa-pulse" aria-hidden="true"></i>
                                </span>
                            <?php elseif ($imgUrl): ?>
                                <img src="<?= Html::encode($imgUrl) ?>" alt="<?= Html::encode($tile['label']) ?>" loading="lazy">
                            <?php else: ?>
                                <i class="fa fa-file-image-o" aria-hidden="true"></i>
                            <?php endif ?>
                        </div>
                        <div class="cw-review-doc__label">
                            <i class="fa fa-check-circle" aria-hidden="true"></i>
                            <?= Html::encode($tile['label']) ?>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="cw-review-doc cw-review-doc--missing">
                        <div class="cw-review-doc__thumb">
                            <i class="fa fa-camera" aria-hidden="true"></i>
                        </div>
                        <div class="cw-review-doc__label">
                            <i class="fa fa-info-circle" aria-hidden="true"></i>
                            <?= Html::encode($tile['label']) ?> — لم تُرفع
                        </div>
                    </div>
                <?php endif; endforeach ?>

                <!-- Personal photo (if uploaded) — also visible in the hero
                     avatar but rendered here too so reviewers can open it
                     full-size and so the docs hub gives a complete picture
                     of what's attached to the customer file. -->
                <?php if ($personalPic): ?>
                    <a href="<?= Html::encode((string)$personalPic['url']) ?>" target="_blank" rel="noopener"
                       class="cw-review-doc cw-review-doc--filled cw-review-doc--photo">
                        <div class="cw-review-doc__thumb">
                            <img src="<?= Html::encode((string)$personalPic['url']) ?>"
                                 alt="الصورة الشخصية للعميل" loading="lazy">
                        </div>
                        <div class="cw-review-doc__label">
                            <i class="fa fa-check-circle" aria-hidden="true"></i>
                            الصورة الشخصية
                        </div>
                    </a>
                <?php endif ?>

                <!-- Ad-hoc supporting documents the user added in step 1.
                     PDFs get a first-page render via PDF.js (see review.js). -->
                <?php foreach ($extraDocs as $idx => $doc):
                    if (!is_array($doc) || empty($doc['url'])) continue;
                    $docUrl   = (string)$doc['url'];
                    $docName  = (string)($doc['file_name'] ?? ('مستند ' . ($idx + 1)));
                    $mime     = (string)($doc['mime'] ?? '');
                    $isPdfDoc = $mime === 'application/pdf'
                              || strtolower(pathinfo($docName, PATHINFO_EXTENSION)) === 'pdf';
                ?>
                    <a href="<?= Html::encode($docUrl) ?>" target="_blank" rel="noopener"
                       class="cw-review-doc cw-review-doc--filled cw-review-doc--extra <?= $isPdfDoc ? 'cw-review-doc--pdf' : '' ?>">
                        <div class="cw-review-doc__thumb"
                             <?= $isPdfDoc ? 'data-cw-pdf-thumb data-pdf-url="' . Html::encode($docUrl) . '"' : '' ?>>
                            <?php if ($isPdfDoc): ?>
                                <i class="fa fa-file-pdf-o cw-review-doc__pdf-fallback" aria-hidden="true"></i>
                                <span class="cw-review-doc__pdf-loading">
                                    <i class="fa fa-spinner fa-pulse" aria-hidden="true"></i>
                                </span>
                            <?php else: ?>
                                <img src="<?= Html::encode($docUrl) ?>" alt="<?= Html::encode($docName) ?>" loading="lazy">
                            <?php endif ?>
                        </div>
                        <div class="cw-review-doc__label">
                            <i class="fa fa-paperclip" aria-hidden="true"></i>
                            <?= Html::encode(mb_strimwidth($docName, 0, 26, '…', 'UTF-8')) ?>
                        </div>
                    </a>
                <?php endforeach ?>
            </div>

            <p class="cw-review-section__hint">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                يمكن إضافة المزيد من المستندات لاحقاً من صفحة العميل بعد الاعتماد.
            </p>
        </div>

        <!-- ═══════════════ Risk assessment widget ═══════════════ -->
        <div class="cw-review-section">
            <div class="cw-review-section__head">
                <h4 class="cw-review-section__title">
                    <i class="fa fa-shield" aria-hidden="true"></i>
                    تقييم المخاطر
                </h4>
            </div>

            <div class="cw-risk <?= $riskBand['class'] ?>">
                <div class="cw-risk__gauge" role="img" aria-label="درجة المخاطر <?= $riskScore ?> من 100">
                    <div class="cw-risk__gauge-bar">
                        <div class="cw-risk__gauge-fill" style="width: <?= (int)$riskScore ?>%"></div>
                    </div>
                    <div class="cw-risk__gauge-num"><?= $riskScore ?>%</div>
                </div>
                <div class="cw-risk__body">
                    <div class="cw-risk__band">
                        <i class="fa <?= $riskBand['icon'] ?>" aria-hidden="true"></i>
                        <span>درجة المخاطر: <?= $riskBand['label'] ?></span>
                    </div>
                    <?php if (!empty($riskReasons)): ?>
                        <ul class="cw-risk__reasons">
                            <?php foreach ($riskReasons as $r): ?>
                                <li><i class="fa fa-circle-o" aria-hidden="true"></i> <?= Html::encode($r) ?></li>
                            <?php endforeach ?>
                        </ul>
                    <?php else: ?>
                        <div class="cw-risk__ok">
                            <i class="fa fa-check-circle" aria-hidden="true"></i>
                            البيانات مكتملة — لا توجد ملاحظات.
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- ═══════════════ Final CTA ═══════════════ -->
        <?php
            // Mode-aware copy. The wizard payload carries _mode so the
            // partial works correctly even when rendered standalone (e.g.
            // an AJAX step refresh) without re-reading $mode from the
            // controller.
            $isEditMode = (string)($payload['_mode'] ?? 'create') === 'edit';
        ?>
        <div class="cw-review-final">
            <div class="cw-review-final__text">
                <h4 class="cw-review-final__title">
                    <i class="fa fa-flag-checkered" aria-hidden="true"></i>
                    <?= $isEditMode ? 'جاهز لحفظ التعديلات؟' : 'جاهز لاعتماد العميل؟' ?>
                </h4>
                <p><?= $isEditMode
                    ? 'سيتم تحديث ملف العميل وربط أي مستندات جديدة تم مسحها ضوئياً تلقائياً.'
                    : 'سيتم حفظ السجل بشكل دائم في قاعدة بيانات العملاء، وربط أي مستندات تم مسحها ضوئياً تلقائياً.' ?></p>
            </div>
            <div class="cw-review-final__actions">
                <button type="button" class="cw-btn cw-btn--lg cw-btn--success" data-cw-action="finish">
                    <i class="fa <?= $isEditMode ? 'fa-floppy-o' : 'fa-check-circle' ?>" aria-hidden="true"></i>
                    <span><?= $isEditMode ? 'حفظ التعديلات' : 'اعتماد وإضافة العميل' ?></span>
                </button>
            </div>
        </div>

    </div>
</div>
