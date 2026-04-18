<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * Step 3 — Guarantors, primary address & real-estate.
 *
 * Mental model:
 *   "Who vouches for this customer, where do they live, and what assets
 *    can serve as additional risk mitigation?"
 *
 * Section A — المعرّفون (dynamic rows)
 *   • Min 1, max 10. Recommended 2 (per business policy).
 *   • Each row: name + phone + relationship + facebook (optional).
 *   • Implemented via lightweight CWDynamic widget (see fields.js).
 *
 * Section B — العنوان الأساسي
 *   • Single primary address (city + area + street + building + notes).
 *   • Map / geolocation polish deferred to a future iteration so we can
 *     ship a working wizard first; the underlying Address row already
 *     supports lat/lng for later enhancement without a migration.
 *
 * Section C — العقارات (progressive disclosure)
 *   • Toggle "هل يملك عقاراً؟" → reveals property_name + property_number.
 *   • Stored in the Customers row itself (do_have_any_property + property_*).
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

$address = $d['address'] ?? [];
$addrG = function (string $k, $default = '') use ($address) {
    return isset($address[$k]) && $address[$k] !== null ? $address[$k] : $default;
};

$cust    = $d['Customers'] ?? [];
$ownsRaw = $cust['do_have_any_property'] ?? '';
$ownsProp = (string)$ownsRaw === '1';

$cousins = ArrayHelper::map($lookups['cousins'] ?? [], 'name', 'name');
$cities  = ArrayHelper::map($lookups['cities']  ?? [], 'id',   'name');
?>
<div class="cw-card">
    <div class="cw-card__header">
        <h3 class="cw-card__title">
            <i class="fa fa-users" aria-hidden="true"></i>
            المعرّفون والعنوان والعقارات
        </h3>
    </div>

    <p class="cw-card__hint">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        نوصي بإضافة معرّفَين على الأقل لتقليل المخاطر المحتسبة، وإدخال
        عنوان أساسي واحد. حقول العقار اختيارية وتظهر فقط عند الحاجة.
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

                            <div class="cw-grid cw-grid--4">
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
                                           maxlength="20"
                                           placeholder="07XXXXXXXX"
                                           data-cw-mask="phone-jo">
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
                        <div class="cw-grid cw-grid--4">
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
                                       maxlength="20"
                                       placeholder="07XXXXXXXX"
                                       data-cw-mask="phone-jo">
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

        <!-- ── Section B: Primary address. ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                    العنوان الأساسي
                </h4>
                <p class="cw-fieldset__hint">
                    معلومات السكن الحالية. سنُضيف اختيار الموقع على الخريطة لاحقاً.
                </p>
            </div>

            <input type="hidden" name="address[address_type]" value="2">

            <div class="cw-grid cw-grid--3">

                <!-- City (free text or pick from cities lookup). -->
                <div class="cw-field" data-cw-field="address[address_city]">
                    <label class="cw-field__label" for="cw-addr-city">
                        المدينة <span class="cw-field__req" aria-hidden="true">*</span>
                    </label>
                    <input type="text"
                           id="cw-addr-city"
                           name="address[address_city]"
                           value="<?= Html::encode($addrG('address_city')) ?>"
                           class="cw-input"
                           list="cw-addr-city-options"
                           autocomplete="address-level2"
                           maxlength="100"
                           dir="auto"
                           required
                           aria-required="true"
                           placeholder="مثال: عمّان">
                    <datalist id="cw-addr-city-options">
                        <?php foreach ($cities as $cid => $cname): ?>
                            <option value="<?= Html::encode((string)$cname) ?>"></option>
                        <?php endforeach ?>
                    </datalist>
                </div>

                <!-- Area / neighbourhood. -->
                <div class="cw-field" data-cw-field="address[address_area]">
                    <label class="cw-field__label" for="cw-addr-area">المنطقة / الحي</label>
                    <input type="text"
                           id="cw-addr-area"
                           name="address[address_area]"
                           value="<?= Html::encode($addrG('address_area')) ?>"
                           class="cw-input"
                           autocomplete="address-level3"
                           maxlength="100"
                           dir="auto"
                           placeholder="مثال: الجبيهة">
                </div>

                <!-- Street. -->
                <div class="cw-field" data-cw-field="address[address_street]">
                    <label class="cw-field__label" for="cw-addr-street">الشارع</label>
                    <input type="text"
                           id="cw-addr-street"
                           name="address[address_street]"
                           value="<?= Html::encode($addrG('address_street')) ?>"
                           class="cw-input"
                           autocomplete="address-line1"
                           maxlength="500"
                           dir="auto"
                           placeholder="اسم الشارع والرقم">
                </div>

                <!-- Building / floor / apt. -->
                <div class="cw-field" data-cw-field="address[address_building]">
                    <label class="cw-field__label" for="cw-addr-bldg">المبنى / الطابق</label>
                    <input type="text"
                           id="cw-addr-bldg"
                           name="address[address_building]"
                           value="<?= Html::encode($addrG('address_building')) ?>"
                           class="cw-input"
                           autocomplete="address-line2"
                           maxlength="100"
                           dir="auto"
                           placeholder="مثال: عمارة 12 - طابق 3">
                </div>

                <!-- Postal code. -->
                <div class="cw-field" data-cw-field="address[postal_code]">
                    <label class="cw-field__label" for="cw-addr-postal">الرمز البريدي</label>
                    <input type="text"
                           id="cw-addr-postal"
                           name="address[postal_code]"
                           value="<?= Html::encode($addrG('postal_code')) ?>"
                           class="cw-input cw-input--mono"
                           inputmode="numeric"
                           autocomplete="postal-code"
                           dir="ltr"
                           maxlength="20"
                           placeholder="مثال: 11953">
                </div>

                <!-- Notes. -->
                <div class="cw-field" data-cw-field="address[address]">
                    <label class="cw-field__label" for="cw-addr-notes">ملاحظات العنوان</label>
                    <input type="text"
                           id="cw-addr-notes"
                           name="address[address]"
                           value="<?= Html::encode($addrG('address')) ?>"
                           class="cw-input"
                           autocomplete="off"
                           maxlength="255"
                           dir="auto"
                           placeholder="نقطة دلالية، علامة مميّزة…">
                </div>
            </div>
        </div>

        <!-- ── Section C: Real-estate (progressive disclosure). ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    العقارات
                </h4>
            </div>

            <div class="cw-grid cw-grid--3">
                <div class="cw-field" data-cw-field="Customers[do_have_any_property]">
                    <fieldset class="cw-radio-group">
                        <legend class="cw-field__label">
                            هل يملك العميل عقاراً؟
                        </legend>
                        <div class="cw-radio-row">
                            <label class="cw-radio">
                                <input type="radio"
                                       name="Customers[do_have_any_property]" value="1"
                                       data-cw-toggle="#cw-property-row"
                                       <?= $ownsProp ? 'checked' : '' ?>>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>نعم</span>
                            </label>
                            <label class="cw-radio">
                                <input type="radio"
                                       name="Customers[do_have_any_property]" value="0"
                                       data-cw-toggle="#cw-property-row"
                                       data-cw-toggle-hide="1"
                                       <?= !$ownsProp && $ownsRaw !== '' ? 'checked' : '' ?>>
                                <span class="cw-radio__mark" aria-hidden="true"></span>
                                <span>لا</span>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <div id="cw-property-row"
                     class="cw-field cw-field--span-2 cw-conditional <?= $ownsProp ? '' : 'cw-conditional--hidden' ?>"
                     <?= $ownsProp ? '' : 'hidden' ?>>
                    <div class="cw-grid cw-grid--2">
                        <div class="cw-field" data-cw-field="Customers[property_name]">
                            <label class="cw-field__label" for="cw-prop-name">اسم/نوع العقار</label>
                            <input type="text"
                                   id="cw-prop-name"
                                   name="Customers[property_name]"
                                   value="<?= Html::encode($cust['property_name'] ?? '') ?>"
                                   class="cw-input"
                                   maxlength="50"
                                   dir="auto"
                                   placeholder="مثال: شقة سكنية">
                        </div>
                        <div class="cw-field" data-cw-field="Customers[property_number]">
                            <label class="cw-field__label" for="cw-prop-num">رقم العقار / الطابو</label>
                            <input type="text"
                                   id="cw-prop-num"
                                   name="Customers[property_number]"
                                   value="<?= Html::encode($cust['property_number'] ?? '') ?>"
                                   class="cw-input cw-input--mono"
                                   maxlength="100"
                                   dir="ltr"
                                   placeholder="—">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
