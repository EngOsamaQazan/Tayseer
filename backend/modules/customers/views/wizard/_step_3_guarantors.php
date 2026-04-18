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
 * Section B — العنوان الأساسي
 *   • Single primary address (city + area + street + building + notes).
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

$address = $d['address'] ?? [];
$addrG = function (string $k, $default = '') use ($address) {
    return isset($address[$k]) && $address[$k] !== null ? $address[$k] : $default;
};

$cousins = ArrayHelper::map($lookups['cousins'] ?? [], 'name', 'name');
$cities  = ArrayHelper::map($lookups['cities']  ?? [], 'id',   'name');
?>
<div class="cw-card">
    <div class="cw-card__header">
        <h3 class="cw-card__title">
            <i class="fa fa-users" aria-hidden="true"></i>
            الكفلاء والعنوان
        </h3>
    </div>

    <p class="cw-card__hint">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        نوصي بإضافة معرّفَين على الأقل لتقليل المخاطر المحتسبة، وإدخال
        عنوان أساسي واحد للعميل.
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

        <!-- ── Section B: Primary address. ── -->
        <div class="cw-fieldset">
            <div class="cw-fieldset__head">
                <h4 class="cw-fieldset__title">
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                    العنوان الأساسي
                </h4>
                <p class="cw-fieldset__hint">
                    معلومات السكن الحالية للعميل. ابحث على الخريطة أو الصق
                    رابط/إحداثيات لتعبئة الحقول تلقائياً.
                </p>
            </div>

            <input type="hidden" name="address[address_type]" value="2">

            <!-- ── Address fields root (referenced by the map widget for
                 auto-fill via data-addr-fill="…"). ── -->
            <div class="cw-addr-fields-root">
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
                               placeholder="مثال: عمّان"
                               data-addr-fill="city">
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
                               placeholder="مثال: الجبيهة"
                               data-addr-fill="area">
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
                               placeholder="اسم الشارع والرقم"
                               data-addr-fill="street">
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
                               placeholder="مثال: عمارة 12 - طابق 3"
                               data-addr-fill="building">
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
                               placeholder="مثال: 11953"
                               data-addr-fill="postal">
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

            <!-- ── Map widget (Leaflet + Nominatim).
                 Reverse-geocoding writes only to EMPTY [data-addr-fill]
                 inputs above so the user's manual edits are never lost. -->
            <div class="cw-addr-map"
                 data-cw-addr-map
                 data-cw-addr-map-target=".cw-addr-fields-root">
                <div class="cw-addr-map__head">
                    <h5 class="cw-addr-map__title">
                        <i class="fa fa-map" aria-hidden="true"></i>
                        تحديد الموقع على الخريطة
                    </h5>
                    <div class="cw-addr-map__actions">
                        <button type="button"
                                class="cw-addr-map__btn"
                                data-cw-addr-geolocate
                                title="استخدام موقعي الحالي">
                            <i class="fa fa-crosshairs" aria-hidden="true"></i>
                            <span>موقعي</span>
                        </button>
                        <button type="button"
                                class="cw-addr-map__btn"
                                data-cw-addr-clear
                                title="مسح الموقع المختار">
                            <i class="fa fa-times" aria-hidden="true"></i>
                            <span>مسح</span>
                        </button>
                    </div>
                </div>

                <div class="cw-addr-map__body">
                    <aside class="cw-addr-map__sidebar">
                        <div class="cw-addr-map__field">
                            <label class="cw-addr-map__label" for="cw-addr-search">بحث عن مكان أو عنوان</label>
                            <input type="search"
                                   id="cw-addr-search"
                                   class="cw-addr-map__input"
                                   placeholder="مثال: مستشفى الإسلامي، شارع المدينة المنورة…"
                                   data-cw-addr-search
                                   autocomplete="off"
                                   dir="auto">
                            <ul class="cw-addr-map__results"
                                role="listbox"
                                aria-label="نتائج البحث"
                                data-cw-addr-results></ul>
                        </div>

                        <div class="cw-addr-map__field">
                            <label class="cw-addr-map__label" for="cw-addr-paste">لصق رابط/إحداثيات</label>
                            <input type="text"
                                   id="cw-addr-paste"
                                   class="cw-addr-map__input"
                                   placeholder="رابط Google Maps أو 31.95, 35.91 أو DMS…"
                                   data-cw-addr-paste
                                   dir="ltr">
                            <p class="cw-addr-map__hint">
                                يمكنك لصق رابط Google Maps أو إحداثيات بأي
                                صيغة (عشرية / DMS) — سنكتشف الموقع تلقائياً.
                            </p>
                        </div>

                        <p class="cw-addr-map__hint">
                            انقر على الخريطة أو اسحب الدبّوس لتعديل الموقع.
                            ستُملأ حقول العنوان أعلاه تلقائياً (لن نستبدل أي
                            قيمة كتبتها يدوياً).
                        </p>
                    </aside>

                    <div class="cw-addr-map__container"
                         data-cw-addr-map-canvas
                         aria-label="الخريطة التفاعلية لاختيار العنوان"></div>
                </div>

                <div class="cw-addr-map__footer">
                    <span>الإحداثيات:
                        <span class="cw-addr-map__coord" data-cw-addr-coord>
                            <?= ($addrG('latitude') !== '' && $addrG('longitude') !== '')
                                ? Html::encode(number_format((float)$addrG('latitude'), 5) . ', ' . number_format((float)$addrG('longitude'), 5))
                                : '—' ?>
                        </span>
                    </span>
                    <span>Plus Code:
                        <span class="cw-addr-map__plus" data-cw-addr-plus-out>
                            <?= Html::encode($addrG('plus_code') ?: '—') ?>
                        </span>
                    </span>
                </div>

                <input type="hidden" name="address[latitude]"  value="<?= Html::encode($addrG('latitude')) ?>"  data-cw-addr-lat>
                <input type="hidden" name="address[longitude]" value="<?= Html::encode($addrG('longitude')) ?>" data-cw-addr-lng>
                <input type="hidden" name="address[plus_code]" value="<?= Html::encode($addrG('plus_code')) ?>" data-cw-addr-plus>
            </div>
        </div>

    </div>
</div>
