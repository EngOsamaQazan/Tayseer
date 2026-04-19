<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * Step 1 — Identity (التعريف بالعميل).
 *
 * Layout principles applied:
 *   • Progressive disclosure — required fields first, optional details below.
 *   • One column on phones, two on tablets, three on wide screens
 *     (CSS-grid with auto-fit + minmax).
 *   • Each field is a self-contained `.cw-field` with label / hint / error slot
 *     so JS error rendering (`renderServerErrors` in core.js) just works.
 *   • Inputs use `name="Customers[xxx]"` to match the model's POST shape that
 *     the eventual finish-action will load via `$model->load($post)`.
 *   • All form-controls have `autocomplete` hints (WCAG 1.3.5).
 *
 * @var array $payload  full draft payload
 * @var int   $step
 * @var array $lookups  ['cities'=>[{id,name}], 'citizens'=>[{id,name}], 'hearAboutUs'=>[{id,name}]]
 */

// Pull the draft slice for this step (or empty if first visit).
$d = $payload['step1']['Customers'] ?? [];
$g = function (string $k, $default = '') use ($d) {
    return isset($d[$k]) && $d[$k] !== null ? $d[$k] : $default;
};

$cities  = ArrayHelper::map($lookups['cities']      ?? [], 'id', 'name');
$citizens = ArrayHelper::map($lookups['citizens']    ?? [], 'id', 'name');
$hearOpts = ArrayHelper::map($lookups['hearAboutUs'] ?? [], 'id', 'name');

// Fahras integration toggles (driven by Yii::$app->params['fahras']).
$fahras = Yii::$app->params['fahras'] ?? [];
$fahrasEnabled = !empty($fahras['enabled']);
$fahrasCanOverride = $fahrasEnabled
    && !empty($fahras['overridePerm'])
    && Yii::$app->user->can($fahras['overridePerm']);

// Was this draft already overridden by a manager? (Used to render the
// banner pre-filled instead of forcing the user to re-trigger the modal.)
$fahrasOverride = $payload['_fahras_override'] ?? null;
?>
<div class="cw-card">
    <div class="cw-card__header">
        <h3 class="cw-card__title">
            <i class="fa fa-id-card-o" aria-hidden="true"></i>
            التعريف بالعميل
        </h3>
        <div class="cw-scan-actions" role="group" aria-label="خيارات إدخال صور الهوية">
            <button type="button"
                    class="cw-btn cw-btn--primary cw-btn--sm"
                    data-cw-action="scan-identity"
                    aria-describedby="cw-scan-hint">
                <i class="fa fa-camera" aria-hidden="true"></i>
                <span class="cw-scan__label">مسح ذكي للهوية</span>
            </button>
            <button type="button"
                    class="cw-btn cw-btn--outline cw-btn--sm"
                    data-cw-action="scan-identity"
                    data-cw-prefer-upload="1"
                    aria-describedby="cw-scan-hint">
                <i class="fa fa-upload" aria-hidden="true"></i>
                <span class="cw-scan__label">رفع من الجهاز</span>
            </button>
            <button type="button"
                    class="cw-btn cw-btn--outline cw-btn--sm"
                    data-cw-action="scan-paste"
                    aria-describedby="cw-scan-hint"
                    title="لصق صورة من الحافظة (Ctrl+V)">
                <i class="fa fa-clipboard" aria-hidden="true"></i>
                <span class="cw-scan__label">لصق من الحافظة</span>
            </button>
        </div>
        <input type="file"
               class="cw-sr-only"
               data-cw-role="scan-input"
               accept="image/jpeg,image/png,image/webp,application/pdf"
               capture="environment"
               multiple
               aria-hidden="true"
               tabindex="-1">
    </div>

    <p id="cw-scan-hint" class="cw-card__hint">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        افتح <strong>الكاميرا</strong> ليلتقط النظام وجهَي الهوية تلقائياً (مثل تطبيقات البنوك)،
        أو اختر <strong>رفع من الجهاز</strong> لاختيار صور موجودة لديك (JPG/PNG/PDF حتى 10MB لكلٍّ منهما)،
        أو ألصق صورة مباشرة من الحافظة بالضغط على <kbd>Ctrl</kbd>+<kbd>V</kbd>.
        ارفع <strong>صورتين</strong> (وجه الهوية ثم ظهرها) وسنُعبّئ الحقول لك.
    </p>

    <div class="cw-card__body">

        <!-- ── Section A: Quick identity (always visible, top of form). ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">البيانات الأساسية</h4>
                <p class="cw-fieldset__hint">سيتمّ التحقق من البيانات تلقائياً قبل الانتقال للخطوة التالية.</p>
            </div>

            <div class="cw-grid cw-grid--3">

                <!-- Full name (4 words preferred). -->
                <div class="cw-field cw-field--span-2" data-cw-field="Customers[name]">
                    <label class="cw-field__label" for="cw-name">
                        الاسم الرباعي <span class="cw-field__req" aria-hidden="true">*</span>
                        <span class="cw-sr-only">حقل مطلوب</span>
                    </label>
                    <input type="text"
                           id="cw-name"
                           name="Customers[name]"
                           value="<?= Html::encode($g('name')) ?>"
                           class="cw-input"
                           autocomplete="name"
                           autocapitalize="words"
                           spellcheck="false"
                           inputmode="text"
                           maxlength="250"
                           dir="auto"
                           required
                           aria-describedby="cw-name-hint"
                           aria-required="true">
                    <p id="cw-name-hint" class="cw-field__hint">
                        مثال: محمد أحمد سعيد القاضي
                    </p>
                </div>

                <!-- National ID (10 digits in Jordan). -->
                <div class="cw-field" data-cw-field="Customers[id_number]">
                    <label class="cw-field__label" for="cw-id">
                        الرقم الوطني <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <input type="text"
                           id="cw-id"
                           name="Customers[id_number]"
                           value="<?= Html::encode($g('id_number')) ?>"
                           class="cw-input cw-input--mono"
                           inputmode="numeric"
                           pattern="\d{9,12}"
                           maxlength="12"
                           autocomplete="off"
                           dir="ltr"
                           required
                           aria-describedby="cw-id-hint"
                           aria-required="true"
                           data-cw-mask="digits">
                    <p id="cw-id-hint" class="cw-field__hint">10 أرقام عادةً.</p>
                </div>

                <!-- Primary phone (Jordanian mobile). -->
                <div class="cw-field" data-cw-field="Customers[primary_phone_number]">
                    <label class="cw-field__label" for="cw-phone">
                        الهاتف الرئيسي <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <input type="tel"
                           id="cw-phone"
                           name="Customers[primary_phone_number]"
                           value="<?= Html::encode($g('primary_phone_number')) ?>"
                           class="cw-input cw-input--mono"
                           inputmode="tel"
                           autocomplete="tel"
                           dir="ltr"
                           maxlength="22"
                           required
                           aria-describedby="cw-phone-hint"
                           aria-required="true"
                           data-cw-phone>
                    <p id="cw-phone-hint" class="cw-field__hint">
                        اختر بلد الرقم من القائمة (الافتراضي الأردن)؛ سيتم
                        حفظ الرقم بصيغة دولية موحّدة (E.164).
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Section B: Personal details. ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">البيانات الشخصية</h4>
            </div>

            <div class="cw-grid cw-grid--3">

                <!-- Sex (radio group). -->
                <div class="cw-field" data-cw-field="Customers[sex]">
                    <fieldset class="cw-radio-group">
                        <legend class="cw-field__label">
                            الجنس <span class="cw-field__req" aria-hidden="true">*</span>
                        </legend>
                        <div class="cw-radio-row">
                            <label class="cw-radio">
                                <input type="radio" name="Customers[sex]" value="1"
                                       <?= (string)$g('sex') === '1' ? 'checked' : '' ?>
                                       required>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>ذكر</span>
                            </label>
                            <label class="cw-radio">
                                <input type="radio" name="Customers[sex]" value="2"
                                       <?= (string)$g('sex') === '2' ? 'checked' : '' ?>>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>أنثى</span>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <!-- Birth date. -->
                <div class="cw-field" data-cw-field="Customers[birth_date]">
                    <label class="cw-field__label" for="cw-bdate">
                        تاريخ الميلاد <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <input type="date"
                           id="cw-bdate"
                           name="Customers[birth_date]"
                           value="<?= Html::encode($g('birth_date')) ?>"
                           class="cw-input"
                           autocomplete="bday"
                           max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                           min="<?= date('Y-m-d', strtotime('-110 years')) ?>"
                           required
                           aria-describedby="cw-bdate-hint"
                           aria-required="true">
                    <p id="cw-bdate-hint" class="cw-field__hint">
                        لا يقل العمر عن 18 سنة.
                    </p>
                </div>

                <!-- City of birth — searchable combobox with inline "add new". -->
                <div class="cw-field" data-cw-field="Customers[city]">
                    <label class="cw-field__label" for="cw-city">
                        مدينة الولادة <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <select id="cw-city"
                            name="Customers[city]"
                            class="cw-input cw-select"
                            required
                            aria-required="true"
                            data-cw-combo="city"
                            data-cw-combo-placeholder="ابحث عن المدينة أو اكتب اسماً جديداً…"
                            data-cw-combo-add-url="<?= Html::encode(\yii\helpers\Url::to(['/customers/wizard/add-city'])) ?>">
                        <option value="">— اختر المدينة —</option>
                        <?php foreach ($cities as $cid => $cname): ?>
                            <option value="<?= Html::encode((string)$cid) ?>"
                                    <?= (string)$g('city') === (string)$cid ? 'selected' : '' ?>>
                                <?= Html::encode((string)$cname) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <!-- Nationality. -->
                <div class="cw-field" data-cw-field="Customers[citizen]">
                    <label class="cw-field__label" for="cw-citizen">
                        الجنسية <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <select id="cw-citizen"
                            name="Customers[citizen]"
                            class="cw-input cw-select"
                            required
                            aria-required="true"
                            data-cw-combo="citizen"
                            data-cw-combo-placeholder="ابحث عن الجنسية أو اكتب اسماً جديداً…"
                            data-cw-combo-add-as="كجنسية جديدة"
                            data-cw-combo-add-url="<?= Html::encode(\yii\helpers\Url::to(['/customers/wizard/add-citizen'])) ?>">
                        <option value="">— اختر الجنسية —</option>
                        <?php foreach ($citizens as $cid => $cname): ?>
                            <option value="<?= Html::encode((string)$cid) ?>"
                                    <?= (string)$g('citizen') === (string)$cid ? 'selected' : '' ?>>
                                <?= Html::encode((string)$cname) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <p class="cw-field__hint">
                        لا تجد الجنسية؟ اكتب اسمها وستظهر «إضافة» في القائمة فوراً.
                    </p>
                </div>

                <!-- How heard about us. -->
                <div class="cw-field cw-field--span-2" data-cw-field="Customers[hear_about_us]">
                    <label class="cw-field__label" for="cw-hear">
                        كيف سمعت عنا؟ <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <select id="cw-hear"
                            name="Customers[hear_about_us]"
                            class="cw-input cw-select"
                            required
                            aria-required="true">
                        <option value="">— اختر —</option>
                        <?php foreach ($hearOpts as $hid => $hname): ?>
                            <option value="<?= Html::encode((string)$hid) ?>"
                                    <?= (string)$g('hear_about_us') === (string)$hid ? 'selected' : '' ?>>
                                <?= Html::encode((string)$hname) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ── Section C: Optional contact (collapsible). ── -->
        <details class="cw-fieldset cw-fieldset--collapsible" <?= ($g('email') !== '' || $g('facebook_account') !== '' || $g('notes') !== '') ? 'open' : '' ?>>
            <summary class="cw-fieldset__summary">
                <span>
                    <i class="fa fa-envelope-o" aria-hidden="true"></i>
                    وسائل تواصل إضافية وملاحظات
                </span>
                <span class="cw-fieldset__chip">اختياري</span>
            </summary>

            <div class="cw-grid cw-grid--3">

                <!-- Email (optional). -->
                <div class="cw-field" data-cw-field="Customers[email]">
                    <label class="cw-field__label" for="cw-email">البريد الإلكتروني</label>
                    <input type="email"
                           id="cw-email"
                           name="Customers[email]"
                           value="<?= Html::encode($g('email')) ?>"
                           class="cw-input"
                           inputmode="email"
                           autocomplete="email"
                           dir="ltr"
                           maxlength="50"
                           placeholder="name@example.com">
                </div>

                <!-- Facebook (optional). -->
                <div class="cw-field" data-cw-field="Customers[facebook_account]">
                    <label class="cw-field__label" for="cw-fb">حساب فيسبوك</label>
                    <input type="text"
                           id="cw-fb"
                           name="Customers[facebook_account]"
                           value="<?= Html::encode($g('facebook_account')) ?>"
                           class="cw-input"
                           autocomplete="off"
                           dir="ltr"
                           placeholder="facebook.com/username">
                </div>

                <!-- Notes (optional, full width). -->
                <div class="cw-field cw-field--span-3" data-cw-field="Customers[notes]">
                    <label class="cw-field__label" for="cw-notes">
                        ملاحظات
                        <span class="cw-field__counter" data-cw-counter-for="cw-notes" aria-live="polite">
                            <?= mb_strlen((string)$g('notes')) ?>/500
                        </span>
                    </label>
                    <textarea id="cw-notes"
                              name="Customers[notes]"
                              class="cw-input cw-textarea"
                              rows="3"
                              maxlength="500"
                              dir="auto"
                              placeholder="أي معلومة تساعد فريق المتابعة لاحقاً…"><?= Html::encode($g('notes')) ?></textarea>
                </div>
            </div>
        </details>

        <!-- ── Section C′: Fahras verdict card. ──
             Displayed ABOVE the extras / next-button so the rep sees the
             check status before they leave Step 1. The card hydrates
             itself from /customers/wizard/fahras-check whenever the user
             stops typing in the national-ID input.

             States rendered by JS: idle | loading | can | warn | block | error.
        -->
        <?php if ($fahrasEnabled): ?>
        <section class="cw-fahras"
                 data-cw-fahras
                 data-cw-fahras-state="<?= $fahrasOverride ? 'override' : 'idle' ?>"
                 data-cw-fahras-can-override="<?= $fahrasCanOverride ? '1' : '0' ?>"
                 aria-live="polite"
                 aria-atomic="true"
                 role="region"
                 aria-label="فحص الفهرس">

            <header class="cw-fahras__header">
                <div class="cw-fahras__title">
                    <i class="fa fa-shield" aria-hidden="true"></i>
                    <span>فحص نظام الفهرس</span>
                </div>
                <div class="cw-fahras__actions">
                    <button type="button"
                            class="cw-btn cw-btn--ghost cw-btn--sm"
                            data-cw-fahras-action="recheck"
                            disabled>
                        <i class="fa fa-refresh" aria-hidden="true"></i>
                        <span>إعادة الفحص</span>
                    </button>
                    <button type="button"
                            class="cw-btn cw-btn--outline cw-btn--sm"
                            data-cw-fahras-action="search">
                        <i class="fa fa-search" aria-hidden="true"></i>
                        <span>بحث بالاسم في الفهرس</span>
                    </button>
                </div>
            </header>

            <div class="cw-fahras__body" data-cw-fahras-body>
                <div class="cw-fahras__icon" aria-hidden="true">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div class="cw-fahras__message">
                    <p class="cw-fahras__text">
                        أدخل الرقم الوطني والاسم وسيتم التحقق من العميل تلقائياً قبل الانتقال للخطوة التالية.
                    </p>
                </div>
            </div>

            <?php if ($fahrasOverride): ?>
            <div class="cw-fahras__override-banner" data-cw-fahras-override-banner>
                <i class="fa fa-key" aria-hidden="true"></i>
                <strong>تم تجاوز حظر الفهرس بواسطة المدير.</strong>
                <span>السبب: <?= Html::encode((string)($fahrasOverride['reason'] ?? '—')) ?></span>
                <button type="button"
                        class="cw-btn cw-btn--link cw-btn--sm"
                        data-cw-fahras-action="clear-override">إلغاء التجاوز</button>
            </div>
            <?php endif; ?>

            <details class="cw-fahras__matches" data-cw-fahras-matches hidden>
                <summary>عرض المطابقات في الفهرس</summary>
                <div class="cw-fahras__matches-body" data-cw-fahras-matches-body></div>
            </details>
        </section>

        <!-- Override modal — only injected for users with the override permission. -->
        <?php if ($fahrasCanOverride): ?>
        <div class="cw-fahras-modal" data-cw-fahras-override-modal hidden role="dialog" aria-modal="true" aria-labelledby="cwFahrasOverrideTitle">
            <div class="cw-fahras-modal__backdrop" data-cw-fahras-action="close-modal"></div>
            <div class="cw-fahras-modal__dialog" role="document">
                <header class="cw-fahras-modal__header">
                    <h3 id="cwFahrasOverrideTitle"><i class="fa fa-key" aria-hidden="true"></i> تجاوز حظر الفهرس</h3>
                    <button type="button" class="cw-fahras-modal__close" data-cw-fahras-action="close-modal" aria-label="إغلاق">×</button>
                </header>
                <div class="cw-fahras-modal__body">
                    <p class="cw-fahras-modal__warn">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                        أنت على وشك تجاوز قرار الفهرس بمنع إضافة هذا العميل. سيتم إشعار الإدارة وتسجيل سبب التجاوز في سجل الفهرس.
                    </p>
                    <label class="cw-field__label" for="cwFahrasOverrideReason">سبب التجاوز <span class="cw-field__req" aria-hidden="true">*</span></label>
                    <textarea id="cwFahrasOverrideReason"
                              class="cw-input cw-textarea"
                              rows="4"
                              minlength="10"
                              maxlength="1000"
                              placeholder="اشرح السبب بوضوح (10 أحرف على الأقل)…"></textarea>
                    <p class="cw-fahras-modal__hint" data-cw-fahras-modal-error hidden></p>
                </div>
                <footer class="cw-fahras-modal__footer">
                    <button type="button" class="cw-btn cw-btn--outline" data-cw-fahras-action="close-modal">إلغاء</button>
                    <button type="button" class="cw-btn cw-btn--danger" data-cw-fahras-action="confirm-override">
                        <i class="fa fa-key" aria-hidden="true"></i>
                        <span>تأكيد التجاوز والمتابعة</span>
                    </button>
                </footer>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search-by-name modal -->
        <div class="cw-fahras-modal" data-cw-fahras-search-modal hidden role="dialog" aria-modal="true" aria-labelledby="cwFahrasSearchTitle">
            <div class="cw-fahras-modal__backdrop" data-cw-fahras-action="close-search-modal"></div>
            <div class="cw-fahras-modal__dialog cw-fahras-modal__dialog--lg" role="document">
                <header class="cw-fahras-modal__header">
                    <h3 id="cwFahrasSearchTitle"><i class="fa fa-search" aria-hidden="true"></i> بحث في الفهرس</h3>
                    <button type="button" class="cw-fahras-modal__close" data-cw-fahras-action="close-search-modal" aria-label="إغلاق">×</button>
                </header>
                <div class="cw-fahras-modal__body">
                    <div class="cw-fahras-search__bar">
                        <input type="text"
                               class="cw-input"
                               data-cw-fahras-search-input
                               placeholder="ادخل الاسم أو الرقم الوطني (3 أحرف على الأقل)…"
                               autocomplete="off"
                               minlength="3"
                               dir="auto">
                        <button type="button" class="cw-btn cw-btn--primary" data-cw-fahras-action="run-search">
                            <i class="fa fa-search" aria-hidden="true"></i>
                            <span>بحث</span>
                        </button>
                    </div>
                    <div class="cw-fahras-search__results" data-cw-fahras-search-results>
                        <p class="cw-field__hint">سيتم البحث عبر جميع شركات التقسيط المرتبطة بنظام الفهرس.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Section D: Customer photo + ad-hoc supporting documents.
             Self-contained partial — handles its own draft hydration,
             upload flow, and delete buttons. Renders here so it lives
             alongside the rest of "personal info" rather than fighting
             for screen real-estate on the busy review step. ── -->
        <?= $this->render('_step_1_extras', ['payload' => $payload]) ?>

    </div>
</div>
