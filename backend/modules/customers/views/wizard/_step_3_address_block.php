<?php

use yii\helpers\Html;

/**
 * Step 3 — Single address block (one of: home / work).
 *
 * Mental model:
 *   "One address card with its own grid of fields + its own map widget."
 *   The wizard renders this partial twice (residential + work) so the
 *   loan officer can capture both locations a customer typically has.
 *
 * Field naming:
 *   addresses[<key>][address_city] etc., where <key> ∈ {home, work}.
 *   The map widget targets fields via a unique attribute selector
 *   (`data-cw-addr-fields-root="<key>"`) so multiple instances on the
 *   same .cw-card never collide.
 *
 * Collapsing rules (always-collapsible variant):
 *   • Every block renders as a native <details> closed by default — even
 *     when the draft already has data. The user is the only one who
 *     decides when to expand a block; the chip in the summary makes
 *     saved data scannable at a glance ("غير مُعبَّأ" or
 *     "city · area · lat,lng") so nothing is hidden by surprise.
 *   • The Leaflet map is initialized lazily on the first expansion to
 *     avoid measuring tile sizes inside a display:none container.
 *
 * Address type:
 *   The block exposes a <select name="addresses[<key>][address_type]">
 *   so the user can re-classify a block at will (mirrors the legacy
 *   wizard behaviour). The summary title + icon update live to reflect
 *   the chosen label ("عنوان السكن" + fa-home, "عنوان العمل" + fa-briefcase, …).
 *
 * @var string $key       'home' | 'work' — used for prefix + scoping
 * @var int    $typeCode  Default address_type value (1 = work, 2 = home)
 * @var bool   $required  Whether City is required for this block
 * @var array  $values    Saved values for this block (may be empty)
 * @var array  $cities    id→name list for the city <datalist>
 * @var string $note      Optional Arabic note shown under the title
 */

$key      = $key      ?? 'home';
$typeCode = (int)($typeCode ?? 2);
$required = (bool)($required ?? false);
$values   = is_array($values ?? null) ? $values : [];
$cities   = is_array($cities ?? null) ? $cities : [];
$note     = $note     ?? '';

$prefix = 'addresses[' . $key . ']';
$idSfx  = '-' . $key;

$g = function (string $k, $default = '') use ($values) {
    return isset($values[$k]) && $values[$k] !== null ? $values[$k] : $default;
};

// ── Address-type vocabulary (kept in lock-step with the JS in
//    address-map.js so the live-toggle stays consistent). ──
$typeOptions = [
    2 => 'عنوان السكن',
    1 => 'عنوان العمل',
];
$typeIcons = [
    2 => 'fa-home',
    1 => 'fa-briefcase',
];

// Resolve the *current* type from saved data, falling back to the
// caller's default. This drives the initial summary title/icon so the
// first paint matches whatever the dropdown will read.
$currentType = (int)($g('address_type', $typeCode) ?: $typeCode);
if (!isset($typeOptions[$currentType])) $currentType = $typeCode;

$titleText = $typeOptions[$currentType] ?? 'عنوان';
$titleIcon = $typeIcons[$currentType]   ?? 'fa-map-marker';

// Build the summary "chip" text shown next to the title in the closed
// header. Mirrors the JS `refreshChip()` logic so the first paint
// matches what later edits will produce — no flicker on toggle.
$chipParts = [];
if (trim((string)$g('address_city')) !== '') $chipParts[] = (string)$g('address_city');
if (trim((string)$g('address_area')) !== '') $chipParts[] = (string)$g('address_area');
if ($g('latitude') !== '' && $g('longitude') !== '') {
    $chipParts[] = number_format((float)$g('latitude'), 2) . ', '
                 . number_format((float)$g('longitude'), 2);
}
$chipText  = $chipParts ? implode(' · ', $chipParts) : 'غير مُعبَّأ';
$chipEmpty = empty($chipParts);
?>
<details class="cw-fieldset cw-fieldset--collapsible cw-addr-block"
         data-cw-addr-block="<?= Html::encode($key) ?>"
         data-cw-addr-collapsible>
    <summary class="cw-fieldset__summary">
        <span class="cw-fieldset__title">
            <i class="fa <?= Html::encode($titleIcon) ?>" aria-hidden="true" data-cw-addr-title-icon></i>
            <span data-cw-addr-title-text><?= Html::encode($titleText) ?></span>
            <?php if ($required): ?>
                <span class="cw-field__req" aria-hidden="true">*</span>
            <?php endif ?>
        </span>
        <span class="cw-fieldset__chip cw-addr-block__chip<?= $chipEmpty ? ' cw-fieldset__chip--empty' : '' ?>"
              data-cw-addr-chip
              dir="auto">
            <?= Html::encode($chipText) ?>
        </span>
    </summary>
    <div class="cw-addr-block__body">
        <?php if ($note !== ''): ?>
            <p class="cw-fieldset__hint"><?= Html::encode($note) ?></p>
        <?php endif ?>

        <!-- Address fields root (referenced by the map widget for auto-fill via
             data-addr-fill="…"). Scoped uniquely so multi-instance widgets on the
             same card resolve to the right field set. -->
        <div class="cw-addr-fields-root" data-cw-addr-fields-root="<?= Html::encode($key) ?>">
            <div class="cw-grid cw-grid--3">

                <!-- Address type — dropdown, mirrors the legacy wizard. The
                     name posts as addresses[<key>][address_type] which the
                     controller already honours via setAttributes(). -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[address_type]">
                    <label class="cw-field__label" for="cw-addr-type<?= $idSfx ?>">
                        نوع العنوان
                    </label>
                    <select id="cw-addr-type<?= $idSfx ?>"
                            name="<?= Html::encode($prefix) ?>[address_type]"
                            class="cw-input cw-select"
                            data-cw-addr-type>
                        <?php foreach ($typeOptions as $code => $label): ?>
                            <option value="<?= (int)$code ?>" <?= ($currentType === (int)$code) ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <!-- City (free text, with city lookup as <datalist> hint). -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[address_city]">
                    <label class="cw-field__label" for="cw-addr-city<?= $idSfx ?>">
                        المدينة
                        <?php if ($required): ?>
                            <span class="cw-field__req" aria-hidden="true">*</span>
                        <?php endif ?>
                    </label>
                    <input type="text"
                           id="cw-addr-city<?= $idSfx ?>"
                           name="<?= Html::encode($prefix) ?>[address_city]"
                           value="<?= Html::encode($g('address_city')) ?>"
                           class="cw-input"
                           list="cw-addr-city-options<?= $idSfx ?>"
                           autocomplete="address-level2"
                           maxlength="100"
                           dir="auto"
                           <?= $required ? 'required aria-required="true"' : '' ?>
                           placeholder="مثال: عمّان"
                           data-addr-fill="city">
                    <datalist id="cw-addr-city-options<?= $idSfx ?>">
                        <?php foreach ($cities as $cid => $cname): ?>
                            <option value="<?= Html::encode((string)$cname) ?>"></option>
                        <?php endforeach ?>
                    </datalist>
                </div>

                <!-- Area / neighbourhood. -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[address_area]">
                    <label class="cw-field__label" for="cw-addr-area<?= $idSfx ?>">المنطقة / الحي</label>
                    <input type="text"
                           id="cw-addr-area<?= $idSfx ?>"
                           name="<?= Html::encode($prefix) ?>[address_area]"
                           value="<?= Html::encode($g('address_area')) ?>"
                           class="cw-input"
                           autocomplete="address-level3"
                           maxlength="100"
                           dir="auto"
                           placeholder="مثال: الجبيهة"
                           data-addr-fill="area">
                </div>

                <!-- Street. -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[address_street]">
                    <label class="cw-field__label" for="cw-addr-street<?= $idSfx ?>">الشارع</label>
                    <input type="text"
                           id="cw-addr-street<?= $idSfx ?>"
                           name="<?= Html::encode($prefix) ?>[address_street]"
                           value="<?= Html::encode($g('address_street')) ?>"
                           class="cw-input"
                           autocomplete="address-line1"
                           maxlength="500"
                           dir="auto"
                           placeholder="اسم الشارع والرقم"
                           data-addr-fill="street">
                </div>

                <!-- Building / floor / apt. -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[address_building]">
                    <label class="cw-field__label" for="cw-addr-bldg<?= $idSfx ?>">المبنى / الطابق</label>
                    <input type="text"
                           id="cw-addr-bldg<?= $idSfx ?>"
                           name="<?= Html::encode($prefix) ?>[address_building]"
                           value="<?= Html::encode($g('address_building')) ?>"
                           class="cw-input"
                           autocomplete="address-line2"
                           maxlength="100"
                           dir="auto"
                           placeholder="مثال: عمارة 12 - طابق 3"
                           data-addr-fill="building">
                </div>

                <!-- Postal code. -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[postal_code]">
                    <label class="cw-field__label" for="cw-addr-postal<?= $idSfx ?>">الرمز البريدي</label>
                    <input type="text"
                           id="cw-addr-postal<?= $idSfx ?>"
                           name="<?= Html::encode($prefix) ?>[postal_code]"
                           value="<?= Html::encode($g('postal_code')) ?>"
                           class="cw-input cw-input--mono"
                           inputmode="numeric"
                           autocomplete="postal-code"
                           dir="ltr"
                           maxlength="20"
                           placeholder="مثال: 11953"
                           data-addr-fill="postal">
                </div>

                <!-- Plus Code (Open Location Code). Auto-generated from the
                     marker on the map but also accepts paste/typing — full or
                     short codes (e.g. "8G3QXW26+XX" or "XW26+XX عمّان") will
                     be resolved by smartPaste() and the marker will move. -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[plus_code_display]">
                    <label class="cw-field__label" for="cw-addr-plus-display<?= $idSfx ?>">
                        Plus Code
                    </label>
                    <input type="text"
                           id="cw-addr-plus-display<?= $idSfx ?>"
                           class="cw-input cw-input--mono"
                           value="<?= Html::encode($g('plus_code')) ?>"
                           dir="ltr"
                           maxlength="20"
                           autocomplete="off"
                           spellcheck="false"
                           placeholder="مثال: 8G3QXW26+XX"
                           data-cw-addr-plus-display
                           data-cw-addr-plus-search>
                    <p class="cw-field__hint">
                        يتولّد تلقائياً من الموقع على الخريطة، أو الصق Plus Code واضغط Enter للبحث عنه.
                    </p>
                </div>

                <!-- Notes. -->
                <div class="cw-field" data-cw-field="<?= Html::encode($prefix) ?>[address]">
                    <label class="cw-field__label" for="cw-addr-notes<?= $idSfx ?>">ملاحظات العنوان</label>
                    <input type="text"
                           id="cw-addr-notes<?= $idSfx ?>"
                           name="<?= Html::encode($prefix) ?>[address]"
                           value="<?= Html::encode($g('address')) ?>"
                           class="cw-input"
                           autocomplete="off"
                           maxlength="255"
                           dir="auto"
                           placeholder="نقطة دلالية، علامة مميّزة…">
                </div>
            </div>
        </div>

        <!-- Map widget (Leaflet + Nominatim). The target attribute selects ONLY
             this block's fields-root so a sibling block on the same card never
             gets overwritten by the wrong map. -->
        <div class="cw-addr-map"
             data-cw-addr-map
             data-cw-addr-map-target='[data-cw-addr-fields-root="<?= Html::encode($key) ?>"]'>
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
                        <label class="cw-addr-map__label" for="cw-addr-search<?= $idSfx ?>">بحث عن مكان أو عنوان</label>
                        <input type="search"
                               id="cw-addr-search<?= $idSfx ?>"
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
                        <label class="cw-addr-map__label" for="cw-addr-paste<?= $idSfx ?>">لصق رابط/إحداثيات/Plus Code</label>
                        <input type="text"
                               id="cw-addr-paste<?= $idSfx ?>"
                               class="cw-addr-map__input"
                               placeholder="رابط Google Maps، Plus Code، أو 31.95, 35.91…"
                               data-cw-addr-paste
                               dir="ltr">
                        <p class="cw-addr-map__hint">
                            يمكنك لصق رابط Google Maps، أو Plus Code (مثل
                            <code dir="ltr">8G3QXW26+XX</code> أو
                            <code dir="ltr">XW26+XX عمّان</code>)، أو
                            إحداثيات بأي صيغة (عشرية / DMS) — سنكتشف الموقع تلقائياً.
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
                        <?= ($g('latitude') !== '' && $g('longitude') !== '')
                            ? Html::encode(number_format((float)$g('latitude'), 5) . ', ' . number_format((float)$g('longitude'), 5))
                            : '—' ?>
                    </span>
                </span>
                <span>Plus Code:
                    <span class="cw-addr-map__plus" data-cw-addr-plus-out>
                        <?= Html::encode($g('plus_code') ?: '—') ?>
                    </span>
                </span>
            </div>

            <input type="hidden" name="<?= Html::encode($prefix) ?>[latitude]"  value="<?= Html::encode($g('latitude')) ?>"  data-cw-addr-lat>
            <input type="hidden" name="<?= Html::encode($prefix) ?>[longitude]" value="<?= Html::encode($g('longitude')) ?>" data-cw-addr-lng>
            <input type="hidden" name="<?= Html::encode($prefix) ?>[plus_code]" value="<?= Html::encode($g('plus_code')) ?>" data-cw-addr-plus>
        </div>

    </div><!-- /.cw-addr-block__body -->
</details>
