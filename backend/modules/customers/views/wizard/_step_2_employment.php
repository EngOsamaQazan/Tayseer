<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Step 2 — Employment, income & bank account.
 *
 * Mental model (per Baymard / NN/g grouping research): users think about
 * money in three sub-decks:
 *   1. "Where do I work and how much do I make?" → core fields, always shown.
 *   2. "Am I in the social security system / receiving a pension?" →
 *      progressive-disclosure radios that reveal extra fields only when
 *      relevant. Keeps the form short for the 70% who answer "no".
 *   3. "Banking details" → accordion (collapsed by default) because most
 *      Jordanian customers don't pay via transfer for instalment plans.
 *
 * Layout principles applied:
 *   • CSS grid (cw-grid--3) for desktop; collapses to one column on mobile.
 *   • All fields use `Customers[xxx]` so $model->load($post) works as-is.
 *   • Radio groups (not selects) for binary choices — WCAG 2.4.6 + faster
 *     touch interaction (no extra dropdown step).
 *   • Numeric inputs use inputmode="decimal" + step="0.01" for proper
 *     mobile numeric keypads.
 *   • Combobox widget used for jobs + banks — searchable + inline add.
 *
 * @var array $payload  full draft payload
 * @var int   $step
 * @var array $lookups  ['jobs'=>[{id,name}], 'banks'=>[{id,name}], …]
 */

$d = $payload['step2']['Customers'] ?? [];
$g = function (string $k, $default = '') use ($d) {
    return isset($d[$k]) && $d[$k] !== null ? $d[$k] : $default;
};

$jobs  = ArrayHelper::map($lookups['jobs']  ?? [], 'id', 'name');
$banks = ArrayHelper::map($lookups['banks'] ?? [], 'id', 'name');

$socialSources = Yii::$app->params['socialSecuritySources'] ?? [
    'social_security'        => 'الضمان الاجتماعي',
    'retirement_directorate' => 'مديرية التقاعد المدني والعسكري',
    'both'                   => 'كلاهما',
];

// Helper: are conditional groups initially open?
$isSocSec       = (string)$g('is_social_security') === '1';
$hasSocPension  = (string)$g('has_social_security_salary') === 'yes';
$pensionSource  = (string)$g('social_security_salary_source');
$showRetirement = in_array($pensionSource, ['retirement_directorate', 'both'], true);
$bankOpen       = trim((string)$g('bank_name')) !== ''
              || trim((string)$g('bank_branch')) !== ''
              || trim((string)$g('account_number')) !== '';
?>
<div class="cw-card">
    <div class="cw-card__header">
        <h3 class="cw-card__title">
            <i class="fa fa-briefcase" aria-hidden="true"></i>
            العمل والدخل المالي
        </h3>
    </div>

    <p class="cw-card__hint">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        املأ بيانات جهة العمل والراتب — هذه أكثر الحقول تأثيراً على
        تقييم المخاطر لاحقاً. الحقول الاختيارية مطوية وتُكشف عند الحاجة.
    </p>

    <div class="cw-card__body">

        <!-- ── Section A: Employment basics. ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">جهة العمل والمنصب</h4>
            </div>

            <div class="cw-grid cw-grid--3">

                <!-- Employer / job (searchable combobox + add new + meta alert). -->
                <div class="cw-field cw-field--span-2" data-cw-field="Customers[job_title]">
                    <label class="cw-field__label" for="cw-job">
                        جهة العمل <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <select id="cw-job"
                            name="Customers[job_title]"
                            class="cw-input cw-select"
                            required
                            aria-required="true"
                            data-cw-combo="job"
                            data-cw-combo-placeholder="ابحث عن جهة العمل أو اكتب اسماً جديداً…"
                            data-cw-combo-add-as="كجهة عمل جديدة"
                            data-cw-combo-add-url="<?= Html::encode(Url::to(['/customers/wizard/add-job'])) ?>"
                            data-cw-combo-meta-url="<?= Html::encode(Url::to(['/customers/wizard/job-meta'])) ?>">
                        <option value="">— اختر جهة العمل —</option>
                        <?php foreach ($jobs as $jid => $jname): ?>
                            <option value="<?= Html::encode((string)$jid) ?>"
                                    <?= (string)$g('job_title') === (string)$jid ? 'selected' : '' ?>>
                                <?= Html::encode((string)$jname) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <!-- combo.js auto-creates [data-cw-combo-meta] right after
                         .cw-combo with the address/phones/hours warning, but we
                         pre-mount it so the layout doesn't shift on first fetch. -->
                    <div data-cw-combo-meta
                         class="cw-combo__meta-host"
                         aria-live="polite"
                         hidden></div>
                    <p class="cw-field__hint">
                        ابدأ الكتابة للبحث، وإن لم تجدها يمكنك إضافتها فوراً من القائمة.
                    </p>
                </div>

                <!-- Job number / employee ID. -->
                <div class="cw-field" data-cw-field="Customers[job_number]">
                    <label class="cw-field__label" for="cw-jobnum">الرقم الوظيفي</label>
                    <input type="text"
                           id="cw-jobnum"
                           name="Customers[job_number]"
                           value="<?= Html::encode($g('job_number')) ?>"
                           class="cw-input"
                           autocomplete="off"
                           dir="ltr"
                           maxlength="20"
                           inputmode="text"
                           placeholder="—">
                    <p class="cw-field__hint">اختياري إن لم يكن متوفراً.</p>
                </div>
            </div>
        </div>

        <!-- ── Section B: Income basics. ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">الراتب والدخل</h4>
            </div>

            <div class="cw-grid cw-grid--3">

                <!-- Basic monthly salary (required for risk scoring). -->
                <div class="cw-field" data-cw-field="Customers[total_salary]">
                    <label class="cw-field__label" for="cw-salary">
                        الراتب الأساسي (شهرياً) <span class="cw-field__req" aria-hidden="true">*</span>
                        <span class="cw-field__suffix">د.أ</span>
                    </label>
                    <input type="number"
                           id="cw-salary"
                           name="Customers[total_salary]"
                           value="<?= Html::encode($g('total_salary')) ?>"
                           class="cw-input cw-input--mono"
                           inputmode="decimal"
                           step="0.01"
                           min="0"
                           max="999999"
                           autocomplete="off"
                           dir="ltr"
                           required
                           aria-required="true"
                           placeholder="0.00">
                    <p class="cw-field__hint">صافي الراتب الشهري بالدينار الأردني.</p>
                </div>

                <!-- Last income query date (if any). -->
                <div class="cw-field" data-cw-field="Customers[last_income_query_date]">
                    <label class="cw-field__label" for="cw-incq">آخر استعلام دخل</label>
                    <input type="date"
                           id="cw-incq"
                           name="Customers[last_income_query_date]"
                           value="<?= Html::encode($g('last_income_query_date')) ?>"
                           class="cw-input"
                           max="<?= date('Y-m-d') ?>"
                           autocomplete="off">
                    <p class="cw-field__hint">إن كان لديك استعلام رسمي حديث.</p>
                </div>

                <!-- Last job query date. -->
                <div class="cw-field" data-cw-field="Customers[last_job_query_date]">
                    <label class="cw-field__label" for="cw-jobq">آخر استعلام وظيفي</label>
                    <input type="date"
                           id="cw-jobq"
                           name="Customers[last_job_query_date]"
                           value="<?= Html::encode($g('last_job_query_date')) ?>"
                           class="cw-input"
                           max="<?= date('Y-m-d') ?>"
                           autocomplete="off">
                </div>
            </div>
        </div>

        <!-- ── Section C: Social security & pension (progressive disclosure). ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">
                    <i class="fa fa-shield" aria-hidden="true"></i>
                    الضمان الاجتماعي والتقاعد
                </h4>
                <p class="cw-fieldset__hint">
                    اختر الإجابة المناسبة وستظهر الحقول الإضافية تلقائياً عند الحاجة.
                </p>
            </div>

            <!-- ── Smart upload: SS detailed statement (PDF / image) ── -->
            <div class="cw-scan-doc"
                 data-cw-scan-income
                 role="region"
                 aria-labelledby="cw-incscan-title">
                <div class="cw-scan-doc__head">
                    <div class="cw-scan-doc__icon" aria-hidden="true">
                        <i class="fa fa-file-pdf-o"></i>
                    </div>
                    <div class="cw-scan-doc__text">
                        <h5 id="cw-incscan-title" class="cw-scan-doc__title">
                            ارفع كشف الضمان الاجتماعي وسنعبّئ البيانات تلقائياً
                        </h5>
                        <p class="cw-scan-doc__hint">
                            "كشف البيانات التفصيلي" الصادر من المؤسسة العامة للضمان —
                            <strong>PDF أو صورة (JPG/PNG)</strong> حتى 10 ميجابايت.
                            سنقرأ منه: رقم التأمين، آخر راتب شهري، جهة العمل الحالية،
                            وفترات الاشتراك تلقائياً.
                        </p>
                    </div>
                    <div class="cw-scan-doc__actions">
                        <button type="button"
                                class="cw-btn cw-btn--primary cw-btn--sm"
                                data-cw-action="pick-income-doc">
                            <i class="fa fa-upload" aria-hidden="true"></i>
                            <span>اختر الكشف</span>
                        </button>
                    </div>
                    <input type="file"
                           class="cw-sr-only"
                           data-cw-role="income-input"
                           accept="application/pdf,image/jpeg,image/png,image/webp"
                           aria-hidden="true"
                           tabindex="-1">
                </div>

                <!-- Status pill (idle | uploading | success | error) -->
                <div class="cw-scan-doc__status"
                     data-cw-role="income-status"
                     role="status"
                     aria-live="polite"
                     hidden></div>

                <!-- Summary block populated by scan-income.js -->
                <div class="cw-scan-doc__summary"
                     data-cw-role="income-summary"
                     hidden>
                    <div class="cw-scan-doc__summary-grid"
                         data-cw-role="income-summary-grid"></div>
                    <details class="cw-scan-doc__details">
                        <summary>عرض جداول الكشف الكاملة (فترات الاشتراك + الرواتب)</summary>
                        <div data-cw-role="income-summary-tables"></div>
                    </details>
                </div>
            </div>

            <!-- Q1: Subscribed to social security? -->
            <div class="cw-grid cw-grid--3">
                <div class="cw-field cw-field--span-1" data-cw-field="Customers[is_social_security]">
                    <fieldset class="cw-radio-group">
                        <legend class="cw-field__label">
                            مشترك بالضمان؟
                            <span class="cw-field__req" aria-hidden="true">*</span>
                        </legend>
                        <div class="cw-radio-row">
                            <label class="cw-radio">
                                <input type="radio"
                                       name="Customers[is_social_security]" value="1"
                                       data-cw-toggle="#cw-soc-num-row"
                                       <?= $isSocSec ? 'checked' : '' ?>
                                       required>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>نعم</span>
                            </label>
                            <label class="cw-radio">
                                <input type="radio"
                                       name="Customers[is_social_security]" value="0"
                                       data-cw-toggle="#cw-soc-num-row"
                                       data-cw-toggle-hide="1"
                                       <?= !$isSocSec && $g('is_social_security') !== '' ? 'checked' : '' ?>>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>لا</span>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <!-- Conditional: subscription number. -->
                <div id="cw-soc-num-row"
                     class="cw-field cw-field--span-2 cw-conditional <?= $isSocSec ? '' : 'cw-conditional--hidden' ?>"
                     data-cw-field="Customers[social_security_number]"
                     <?= $isSocSec ? '' : 'hidden' ?>>
                    <label class="cw-field__label" for="cw-socnum">
                        رقم اشتراك الضمان
                    </label>
                    <input type="text"
                           id="cw-socnum"
                           name="Customers[social_security_number]"
                           value="<?= Html::encode($g('social_security_number')) ?>"
                           class="cw-input cw-input--mono"
                           inputmode="numeric"
                           maxlength="50"
                           dir="ltr"
                           autocomplete="off"
                           placeholder="—">
                </div>
            </div>

            <!-- Q2: Receiving a pension salary? -->
            <div class="cw-grid cw-grid--3" style="margin-top: 16px;">
                <div class="cw-field cw-field--span-1" data-cw-field="Customers[has_social_security_salary]">
                    <fieldset class="cw-radio-group">
                        <legend class="cw-field__label">يتقاضى رواتب تقاعدية؟</legend>
                        <div class="cw-radio-row">
                            <label class="cw-radio">
                                <input type="radio"
                                       name="Customers[has_social_security_salary]" value="yes"
                                       data-cw-toggle="#cw-pension-source-row"
                                       <?= $hasSocPension ? 'checked' : '' ?>>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>نعم</span>
                            </label>
                            <label class="cw-radio">
                                <input type="radio"
                                       name="Customers[has_social_security_salary]" value="no"
                                       data-cw-toggle="#cw-pension-source-row"
                                       data-cw-toggle-hide="1"
                                       <?= !$hasSocPension && $g('has_social_security_salary') !== '' ? 'checked' : '' ?>>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>لا</span>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <!-- Conditional: pension source + (if directorate) retirement details. -->
                <div id="cw-pension-source-row"
                     class="cw-field cw-field--span-2 cw-conditional <?= $hasSocPension ? '' : 'cw-conditional--hidden' ?>"
                     <?= $hasSocPension ? '' : 'hidden' ?>>
                    <label class="cw-field__label" for="cw-psrc">مصدر الراتب التقاعدي</label>
                    <select id="cw-psrc"
                            name="Customers[social_security_salary_source]"
                            class="cw-input cw-select"
                            data-cw-toggle-target="#cw-retirement-details"
                            data-cw-toggle-values="retirement_directorate,both">
                        <option value="">— اختر المصدر —</option>
                        <?php foreach ($socialSources as $key => $label): ?>
                            <option value="<?= Html::encode($key) ?>"
                                    <?= $pensionSource === $key ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach ?>
                    </select>

                    <div id="cw-retirement-details"
                         class="cw-grid cw-grid--2 cw-conditional <?= $showRetirement ? '' : 'cw-conditional--hidden' ?>"
                         style="margin-top: 12px;"
                         <?= $showRetirement ? '' : 'hidden' ?>>
                        <div class="cw-field" data-cw-field="Customers[retirement_status]">
                            <label class="cw-field__label" for="cw-rstatus">حالة التقاعد</label>
                            <select id="cw-rstatus"
                                    name="Customers[retirement_status]"
                                    class="cw-input cw-select">
                                <option value="">—</option>
                                <option value="effective" <?= $g('retirement_status') === 'effective' ? 'selected' : '' ?>>فعّال</option>
                                <option value="stopped"   <?= $g('retirement_status') === 'stopped'   ? 'selected' : '' ?>>متوقف</option>
                            </select>
                        </div>
                        <div class="cw-field" data-cw-field="Customers[total_retirement_income]">
                            <label class="cw-field__label" for="cw-rinc">
                                دخل التقاعد <span class="cw-field__suffix">د.أ</span>
                            </label>
                            <input type="number"
                                   id="cw-rinc"
                                   name="Customers[total_retirement_income]"
                                   value="<?= Html::encode($g('total_retirement_income')) ?>"
                                   class="cw-input cw-input--mono"
                                   inputmode="decimal"
                                   step="0.01"
                                   min="0"
                                   dir="ltr"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Section D: Bank details (collapsed by default). ── -->
        <details class="cw-fieldset cw-fieldset--collapsible" <?= $bankOpen ? 'open' : '' ?>>
            <summary class="cw-fieldset__summary">
                <span>
                    <i class="fa fa-university" aria-hidden="true"></i>
                    الحساب البنكي
                </span>
                <span class="cw-fieldset__chip">اختياري</span>
            </summary>

            <div class="cw-grid cw-grid--3">

                <!-- Bank (combobox + add new). -->
                <div class="cw-field" data-cw-field="Customers[bank_name]">
                    <label class="cw-field__label" for="cw-bank">البنك</label>
                    <select id="cw-bank"
                            name="Customers[bank_name]"
                            class="cw-input cw-select"
                            data-cw-combo="bank"
                            data-cw-combo-placeholder="ابحث عن البنك أو اكتب اسماً جديداً…"
                            data-cw-combo-add-as="كبنك جديد"
                            data-cw-combo-add-url="<?= Html::encode(Url::to(['/customers/wizard/add-bank'])) ?>">
                        <option value="">— اختر البنك —</option>
                        <?php foreach ($banks as $bid => $bname): ?>
                            <option value="<?= Html::encode((string)$bid) ?>"
                                    <?= (string)$g('bank_name') === (string)$bid ? 'selected' : '' ?>>
                                <?= Html::encode((string)$bname) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <!-- Branch. -->
                <div class="cw-field" data-cw-field="Customers[bank_branch]">
                    <label class="cw-field__label" for="cw-bbranch">الفرع</label>
                    <input type="text"
                           id="cw-bbranch"
                           name="Customers[bank_branch]"
                           value="<?= Html::encode($g('bank_branch')) ?>"
                           class="cw-input"
                           autocomplete="off"
                           maxlength="100"
                           placeholder="—">
                </div>

                <!-- Account number. -->
                <div class="cw-field" data-cw-field="Customers[account_number]">
                    <label class="cw-field__label" for="cw-bacc">رقم الحساب</label>
                    <input type="text"
                           id="cw-bacc"
                           name="Customers[account_number]"
                           value="<?= Html::encode($g('account_number')) ?>"
                           class="cw-input cw-input--mono"
                           inputmode="numeric"
                           dir="ltr"
                           maxlength="50"
                           autocomplete="off"
                           placeholder="—">
                </div>
            </div>
        </details>
    </div>
</div>
