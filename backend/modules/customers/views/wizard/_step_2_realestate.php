<?php

use yii\helpers\Html;

/**
 * Step 2 — Section D — Real-estate assets (multi-row repeater).
 *
 * Why a repeater (not a single row)?
 *   The legacy customer schema kept a single `property_name` / `property_number`
 *   pair on `customers`, but the production data has many customers with two
 *   or more properties stored in the dedicated `realEstate` table. Capping
 *   the wizard at one row would silently drop assets on first edit-and-save —
 *   a data-loss bug. The repeater lets the user add/remove as many properties
 *   as needed, while we still mirror the FIRST row back into the Customers
 *   row's legacy columns for downstream reports that haven't migrated yet.
 *
 * Data shape:
 *   $payload['step2']['realestates'] = [
 *       ['id' => 12, 'property_type' => 'شقة سكنية', 'property_number' => 'حوض 5/12'],
 *       ['id' => 13, 'property_type' => 'أرض',        'property_number' => '...'],
 *   ]
 *   - `id` is `0` (or absent) for new rows, `>0` for existing realEstate rows.
 *
 * Wired to: backend/web/js/customer-wizard/realestate.js (add/remove/renumber).
 *
 * @var array $payload  full draft payload
 */

$d = $payload['step2']['Customers'] ?? [];
$g = function (string $k, $default = '') use ($d) {
    return isset($d[$k]) && $d[$k] !== null ? $d[$k] : $default;
};

$ownsRaw  = $g('do_have_any_property', '');
$rows     = $payload['step2']['realestates'] ?? [];
if (!is_array($rows)) $rows = [];

// Derive "owns property?" intent: explicit flag wins, otherwise infer from
// the presence of any non-empty row.
$hasAnyRow = false;
foreach ($rows as $r) {
    if (!is_array($r)) continue;
    if (trim((string)($r['property_type']   ?? '')) !== ''
     || trim((string)($r['property_number'] ?? '')) !== '') {
        $hasAnyRow = true;
        break;
    }
}
$ownsProp = (string)$ownsRaw === '1' || $hasAnyRow;

// Always render at least one (empty) row when the section opens, so the user
// has something to type into without an extra click.
if (empty($rows)) {
    $rows = [['id' => 0, 'property_type' => '', 'property_number' => '']];
}
?>
<div class="cw-fieldset">
    <div class="cw-fieldset__head">
        <h4 class="cw-fieldset__title">
            <i class="fa fa-home" aria-hidden="true"></i>
            الأصول العقارية
        </h4>
        <p class="cw-fieldset__hint">
            العقارات تُعدّ ضمن أصول العميل لأغراض تقييم الملاءة المالية
            وقد تُستخدم كضمان إضافي للعقد عند الحاجة. يمكنك إضافة أكثر من
            عقار للعميل الواحد.
        </p>
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

            <div class="cw-realestate"
                 data-cw-realestate
                 aria-label="قائمة عقارات العميل">

                <div class="cw-realestate__list" data-cw-realestate-list>
                    <?php foreach ($rows as $i => $r): ?>
                        <?php
                            $rid   = (int)($r['id']              ?? 0);
                            $rtype = (string)($r['property_type']   ?? '');
                            $rnum  = (string)($r['property_number'] ?? '');
                        ?>
                        <div class="cw-realestate__row"
                             data-cw-realestate-row
                             data-cw-realestate-index="<?= (int)$i ?>">
                            <div class="cw-realestate__row-head">
                                <span class="cw-realestate__row-title">
                                    عقار <span data-cw-realestate-num><?= (int)$i + 1 ?></span>
                                </span>
                                <button type="button"
                                        class="cw-btn cw-btn--ghost cw-btn--sm cw-realestate__remove"
                                        data-cw-realestate-remove
                                        title="حذف هذا العقار"
                                        aria-label="حذف هذا العقار">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="cw-grid cw-grid--2">
                                <input type="hidden"
                                       name="realestates[<?= (int)$i ?>][id]"
                                       value="<?= (int)$rid ?>"
                                       data-cw-realestate-id>
                                <div class="cw-field">
                                    <label class="cw-field__label">اسم/نوع العقار</label>
                                    <input type="text"
                                           name="realestates[<?= (int)$i ?>][property_type]"
                                           value="<?= Html::encode($rtype) ?>"
                                           class="cw-input"
                                           maxlength="100"
                                           dir="auto"
                                           placeholder="مثال: شقة سكنية"
                                           data-cw-realestate-field="property_type">
                                </div>
                                <div class="cw-field">
                                    <label class="cw-field__label">رقم العقار / الطابو</label>
                                    <input type="text"
                                           name="realestates[<?= (int)$i ?>][property_number]"
                                           value="<?= Html::encode($rnum) ?>"
                                           class="cw-input cw-input--mono"
                                           maxlength="100"
                                           dir="ltr"
                                           placeholder="—"
                                           data-cw-realestate-field="property_number">
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>

                <div class="cw-realestate__actions">
                    <button type="button"
                            class="cw-btn cw-btn--ghost cw-btn--sm"
                            data-cw-realestate-add>
                        <i class="fa fa-plus" aria-hidden="true"></i>
                        <span>إضافة عقار آخر</span>
                    </button>
                </div>

                <!-- Hidden template — cloned by realestate.js. We keep the
                     placeholder __INDEX__ so the JS can renumber after every
                     add/remove without re-parsing the DOM. -->
                <template data-cw-realestate-template>
                    <div class="cw-realestate__row"
                         data-cw-realestate-row
                         data-cw-realestate-index="__INDEX__">
                        <div class="cw-realestate__row-head">
                            <span class="cw-realestate__row-title">
                                عقار <span data-cw-realestate-num>__NUM__</span>
                            </span>
                            <button type="button"
                                    class="cw-btn cw-btn--ghost cw-btn--sm cw-realestate__remove"
                                    data-cw-realestate-remove
                                    title="حذف هذا العقار"
                                    aria-label="حذف هذا العقار">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="cw-grid cw-grid--2">
                            <input type="hidden"
                                   name="realestates[__INDEX__][id]"
                                   value="0"
                                   data-cw-realestate-id>
                            <div class="cw-field">
                                <label class="cw-field__label">اسم/نوع العقار</label>
                                <input type="text"
                                       name="realestates[__INDEX__][property_type]"
                                       value=""
                                       class="cw-input"
                                       maxlength="100"
                                       dir="auto"
                                       placeholder="مثال: شقة سكنية"
                                       data-cw-realestate-field="property_type">
                            </div>
                            <div class="cw-field">
                                <label class="cw-field__label">رقم العقار / الطابو</label>
                                <input type="text"
                                       name="realestates[__INDEX__][property_number]"
                                       value=""
                                       class="cw-input cw-input--mono"
                                       maxlength="100"
                                       dir="ltr"
                                       placeholder="—"
                                       data-cw-realestate-field="property_number">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
