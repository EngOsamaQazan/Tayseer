<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * Step 3 — Guarantors & primary residential address.
 *
 * Mental model:
 *   "Who vouches for this customer, and where do they live?"
 *
 * NOTE: Real-estate ownership USED to live here as Section C, but it's
 * fundamentally a *financial asset* (used in creditworthiness scoring),
 * not addressing data. It was relocated to Step 2 (الوضع المالي) so the
 * mental groupings stay clean: this card is purely about social trust
 * (guarantors) + physical location (address).
 *
 * Section A — المعرّفون (dynamic rows)
 *   • Min 1, max 10. Recommended 2 (per business policy).
 *   • Each row: name + phone + relationship + facebook (optional).
 *   • Implemented via lightweight CWDynamic widget (see fields.js).
 *
 * Section B — العناوين
 *   • Two address blocks: residential (السكن) + work (العمل).
 *   • Residential is required; work is optional. Both share the same
 *     address-map widget but are scoped by data-cw-addr-fields-root="home"
 *     vs ="work" so the two map instances never overwrite each other.
 *   • Map / geolocation polish handled by the address-map widget
 *     (Leaflet + Google Places) introduced in the Q4 follow-up.
 *
 * @var array $payload  full draft payload
 * @var int   $step
 * @var array $lookups
 */

$d = $payload['step3'] ?? [];

$guarantors = $d['guarantors'] ?? [];
if (!is_array($guarantors)) { $guarantors = []; }
// Always render at least one row so the user has somewhere to type.
if (count($guarantors) === 0) {
    $guarantors[] = ['owner_name' => '', 'phone_number' => '', 'phone_number_owner' => '', 'fb_account' => ''];
}

// Address shape — supports both the new `addresses[home|work]` layout and
// the legacy single `address` payload (auto-mapped to "home" so older
// drafts still load cleanly without data loss).
$addresses = $d['addresses'] ?? null;
if (!is_array($addresses) || empty($addresses)) {
    $legacy = is_array($d['address'] ?? null) ? $d['address'] : [];
    $addresses = [
        'home' => $legacy,
        'work' => [],
    ];
}
$homeValues = is_array($addresses['home'] ?? null) ? $addresses['home'] : [];
$workValues = is_array($addresses['work'] ?? null) ? $addresses['work'] : [];

$cousins = ArrayHelper::map($lookups['cousins'] ?? [], 'name', 'name');
$cities  = ArrayHelper::map($lookups['cities']  ?? [], 'id',   'name');
?>
<div class="cw-card">
    <div class="cw-card__header">
        <h3 class="cw-card__title">
            <i class="fa fa-users" aria-hidden="true"></i>
            المعرّفون والعناوين
        </h3>
    </div>

    <p class="cw-card__hint">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        نوصي بإضافة معرّفَين على الأقل لتقليل المخاطر المحتسبة، وإدخال
        عنوان السكن (إلزامي) وعنوان العمل (اختياري) لمساعدة الفريق على
        الوصول إلى العميل.
    </p>

    <div class="cw-card__body">

        <!-- ── Section A: Guarantors (dynamic rows). ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">
                    <i class="fa fa-address-book" aria-hidden="true"></i>
                    المعرّفون
                </h4>
                <p class="cw-fieldset__hint">
                    أضف الأشخاص الذين يمكن للنظام التواصل معهم في حال تعذّر الوصول إلى العميل.
                </p>
            </div>

            <div class="cw-dynamic"
                 data-cw-dynamic="guarantor"
                 data-cw-dynamic-min="1"
                 data-cw-dynamic-max="10"
                 data-cw-dynamic-name-prefix="guarantors">
                <div class="cw-dynamic__rows" data-cw-dynamic-rows>
                    <?php foreach ($guarantors as $i => $g): ?>
                        <div class="cw-dynamic__row" data-cw-dynamic-row data-cw-dynamic-index="<?= (int)$i ?>">
                            <div class="cw-dynamic__row-head">
                                <span class="cw-dynamic__row-num">#<span data-cw-dynamic-display><?= (int)$i + 1 ?></span></span>
                                <button type="button"
                                        class="cw-btn cw-btn--ghost cw-btn--sm cw-btn--danger"
                                        data-cw-action="remove-row"
                                        aria-label="حذف المعرّف">
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="cw-grid cw-grid--4 cw-grid--phone-wide">
                                <div class="cw-field" data-cw-field="guarantors[<?= (int)$i ?>][owner_name]">
                                    <label class="cw-field__label">
                                        الاسم <span class="cw-field__req" aria-hidden="true">*</span>
                                    </label>
                                    <input type="text"
                                           name="guarantors[<?= (int)$i ?>][owner_name]"
                                           value="<?= Html::encode($g['owner_name'] ?? '') ?>"
                                           class="cw-input"
                                           autocomplete="name"
                                           maxlength="100"
                                           dir="auto"
                                           placeholder="اسم المعرّف">
                                </div>

                                <div class="cw-field" data-cw-field="guarantors[<?= (int)$i ?>][phone_number]">
                                    <label class="cw-field__label">
                                        الهاتف <span class="cw-field__req" aria-hidden="true">*</span>
                                    </label>
                                    <input type="tel"
                                           name="guarantors[<?= (int)$i ?>][phone_number]"
                                           value="<?= Html::encode($g['phone_number'] ?? '') ?>"
                                           class="cw-input cw-input--mono"
                                           inputmode="tel"
                                           autocomplete="tel"
                                           dir="ltr"
                                           maxlength="22"
                                           data-cw-phone>
                                </div>

                                <div class="cw-field" data-cw-field="guarantors[<?= (int)$i ?>][phone_number_owner]">
                                    <label class="cw-field__label">
                                        صلة القرابة <span class="cw-field__req" aria-hidden="true">*</span>
                                    </label>
                                    <select name="guarantors[<?= (int)$i ?>][phone_number_owner]"
                                            class="cw-input cw-select">
                                        <option value="">— اختر —</option>
                                        <?php foreach ($cousins as $cVal => $cName): ?>
                                            <option value="<?= Html::encode((string)$cVal) ?>"
                                                    <?= (string)($g['phone_number_owner'] ?? '') === (string)$cVal ? 'selected' : '' ?>>
                                                <?= Html::encode((string)$cName) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="cw-field" data-cw-field="guarantors[<?= (int)$i ?>][fb_account]">
                                    <label class="cw-field__label">فيسبوك</label>
                                    <input type="text"
                                           name="guarantors[<?= (int)$i ?>][fb_account]"
                                           value="<?= Html::encode($g['fb_account'] ?? '') ?>"
                                           class="cw-input"
                                           autocomplete="off"
                                           dir="ltr"
                                           maxlength="255"
                                           placeholder="—">
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>

                <!-- Hidden template — JS clones this and replaces __INDEX__. -->
                <template data-cw-dynamic-template>
                    <div class="cw-dynamic__row" data-cw-dynamic-row data-cw-dynamic-index="__INDEX__">
                        <div class="cw-dynamic__row-head">
                            <span class="cw-dynamic__row-num">#<span data-cw-dynamic-display>__DISPLAY__</span></span>
                            <button type="button"
                                    class="cw-btn cw-btn--ghost cw-btn--sm cw-btn--danger"
                                    data-cw-action="remove-row"
                                    aria-label="حذف المعرّف">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="cw-grid cw-grid--4 cw-grid--phone-wide">
                            <div class="cw-field" data-cw-field="guarantors[__INDEX__][owner_name]">
                                <label class="cw-field__label">
                                    الاسم <span class="cw-field__req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       name="guarantors[__INDEX__][owner_name]"
                                       class="cw-input"
                                       autocomplete="name"
                                       maxlength="100"
                                       dir="auto"
                                       placeholder="اسم المعرّف">
                            </div>
                            <div class="cw-field" data-cw-field="guarantors[__INDEX__][phone_number]">
                                <label class="cw-field__label">
                                    الهاتف <span class="cw-field__req" aria-hidden="true">*</span>
                                </label>
                                <input type="tel"
                                       name="guarantors[__INDEX__][phone_number]"
                                       class="cw-input cw-input--mono"
                                       inputmode="tel"
                                       autocomplete="tel"
                                       dir="ltr"
                                       maxlength="22"
                                       data-cw-phone>
                            </div>
                            <div class="cw-field" data-cw-field="guarantors[__INDEX__][phone_number_owner]">
                                <label class="cw-field__label">
                                    صلة القرابة <span class="cw-field__req" aria-hidden="true">*</span>
                                </label>
                                <select name="guarantors[__INDEX__][phone_number_owner]" class="cw-input cw-select">
                                    <option value="">— اختر —</option>
                                    <?php foreach ($cousins as $cVal => $cName): ?>
                                        <option value="<?= Html::encode((string)$cVal) ?>"><?= Html::encode((string)$cName) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="cw-field" data-cw-field="guarantors[__INDEX__][fb_account]">
                                <label class="cw-field__label">فيسبوك</label>
                                <input type="text"
                                       name="guarantors[__INDEX__][fb_account]"
                                       class="cw-input"
                                       autocomplete="off"
                                       dir="ltr"
                                       maxlength="255"
                                       placeholder="—">
                            </div>
                        </div>
                    </div>
                </template>

                <div class="cw-dynamic__actions">
                    <button type="button"
                            class="cw-btn cw-btn--outline cw-btn--sm"
                            data-cw-action="add-row">
                        <i class="fa fa-plus" aria-hidden="true"></i>
                        <span>إضافة معرّف آخر</span>
                    </button>
                    <span class="cw-dynamic__counter" data-cw-dynamic-counter aria-live="polite"></span>
                </div>
            </div>
        </div>

        <!-- ── Section B: Addresses (home + work). ── -->
        <!-- Both address blocks are always-collapsible and start CLOSED.
             The user picks which one to expand. The summary chip keeps
             saved data scannable at a glance ("غير مُعبَّأ" or
             "city · area · lat,lng") so nothing is hidden by surprise.
             The address-type dropdown inside each block lets the user
             re-classify on the fly — the title/icon update live. -->
        <?= $this->render('_step_3_address_block', [
            'key'      => 'home',
            'typeCode' => 2,           // 2 = residential, per Address.address_type
            'required' => true,
            'values'   => $homeValues,
            'cities'   => $cities,
            'note'     => 'مكان إقامة العميل الحالي. ابحث على الخريطة أو الصق رابط/إحداثيات لتعبئة الحقول تلقائياً.',
        ]) ?>

        <?= $this->render('_step_3_address_block', [
            'key'      => 'work',
            'typeCode' => 1,           // 1 = work
            'required' => false,
            'values'   => $workValues,
            'cities'   => $cities,
            'note'     => 'مقر عمل العميل (شركة، فرع، مكتب…). يساعد على تحديد ساعات الوصول الأنسب.',
        ]) ?>

    </div>
</div>
