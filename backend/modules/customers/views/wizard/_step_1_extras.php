<?php

use yii\helpers\Html;

/**
 * Customer Wizard V2 — «الصورة الشخصية والمستندات الإضافية» partial.
 *
 * Two independent buckets, intentionally rendered with very different
 * visual weight so the user understands one is mandatory and the other
 * is not:
 *
 *   1. Personal photo  — REQUIRED, never collapsed, visually prominent
 *      with a red "مطلوب" chip and an attention-grabbing accent border.
 *      Becomes the customer's headshot on the contract print preview.
 *
 *   2. Additional documents — OPTIONAL, kept inside <details> so it
 *      doesn't crowd the form for users who don't need it (utility
 *      bills, salary letters, vehicle registration, …).
 *
 * Why this split (was a single collapsed <details> before): users were
 * routinely missing the photo because the whole block was tucked behind
 * a tiny "اختياري" chevron. Promoting the photo to a first-class block
 * with its own heading + required chip eliminates that miss rate while
 * keeping the docs bucket out of the way for happy-path data entry.
 *
 * Markup contract consumed by extras.js:
 *   • The whole partial wrapper:    [data-cw-extras]
 *   • Each uploader:                [data-cw-extras-uploader=<purpose>]
 *   • Required uploader is also:    [data-cw-extras-required="1"]
 *   • Hidden file input:            input[type=file][data-cw-extras-input]
 *   • Hidden form input mirroring photo state (so the wizard's standard
 *     server-error renderer can attach the "missing photo" error to the
 *     correct on-screen card):     input[type=hidden][name="Customers[_extras_photo_id]"]
 *   • Trigger button:               [data-cw-extras-trigger]
 *   • Render target for thumbs:     [data-cw-extras-list]
 *
 * @var array $payload  Decoded wizard draft (so we can rehydrate previous uploads)
 */

$extras = $payload['_extras'] ?? [];
$photo  = (is_array($extras) && !empty($extras['photo']) && is_array($extras['photo']))
        ? $extras['photo'] : null;
$docs   = (is_array($extras) && !empty($extras['docs'])  && is_array($extras['docs']))
        ? array_values($extras['docs']) : [];

$photoId = $photo ? (int)($photo['image_id'] ?? 0) : 0;
?>
<div class="cw-extras" data-cw-extras>

    <!-- ── Block 1 (REQUIRED): personal photo. ──────────────────────────── -->
    <fieldset class="cw-fieldset cw-extras__photo-block<?= $photo ? ' cw-extras__photo-block--filled' : '' ?>"
              data-cw-extras-required-block>
        <legend class="cw-fieldset__title cw-fieldset__title--required">
            <i class="fa fa-id-card-o" aria-hidden="true"></i>
            <span>الصورة الشخصية للعميل</span>
            <span class="cw-fieldset__chip cw-fieldset__chip--required" aria-label="حقل مطلوب">
                <i class="fa fa-asterisk" aria-hidden="true"></i>
                مطلوب
            </span>
        </legend>

        <p class="cw-fieldset__hint">
            ارفع <strong>صورة العميل الشخصية</strong> — ستظهر على بطاقة طباعة العقد.
            يمكنك اختيار صورة من جهازك أو
            <strong>لصقها مباشرة من الحافظة</strong> بضغط
            <kbd>Ctrl</kbd>+<kbd>V</kbd> داخل المربع.
        </p>

        <div class="cw-field cw-extras__uploader cw-extras__uploader--photo cw-extras__uploader--required"
             data-cw-extras-uploader="photo"
             data-cw-extras-required="1"
             data-cw-extras-multi="0"
             data-cw-extras-accept="image/jpeg,image/png,image/webp"
             tabindex="0"
             role="region"
             aria-label="رفع الصورة الشخصية — حقل مطلوب — اضغط Ctrl+V للصق صورة من الحافظة">

            <div class="cw-extras__dropzone cw-extras__dropzone--photo"
                 data-cw-extras-list role="list"
                 aria-label="الصورة الشخصية الحالية">
                <?php if ($photo): ?>
                    <div class="cw-extras__item cw-extras__item--photo"
                         data-cw-extras-item
                         data-image-id="<?= (int)$photo['image_id'] ?>"
                         role="listitem">
                        <div class="cw-extras__thumb cw-extras__thumb--portrait">
                            <img src="<?= Html::encode((string)($photo['url'] ?? '')) ?>"
                                 alt="<?= Html::encode((string)($photo['file_name'] ?? 'صورة شخصية')) ?>"
                                 loading="lazy">
                        </div>
                        <div class="cw-extras__meta">
                            <strong class="cw-extras__name"><?= Html::encode((string)($photo['file_name'] ?? '')) ?></strong>
                            <span class="cw-extras__sub">سيتم استخدامها على بطاقة العقد</span>
                        </div>
                        <button type="button"
                                class="cw-extras__del"
                                data-cw-extras-del
                                aria-label="حذف الصورة الشخصية">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endif ?>
            </div>

            <div class="cw-extras__actions">
                <button type="button" class="cw-btn cw-btn--primary cw-btn--sm"
                        data-cw-extras-trigger>
                    <i class="fa fa-camera" aria-hidden="true"></i>
                    <span><?= $photo ? 'تغيير الصورة' : 'اختيار صورة' ?></span>
                </button>
                <button type="button" class="cw-btn cw-btn--outline cw-btn--sm"
                        data-cw-extras-paste
                        title="لصق صورة من الحافظة (Ctrl+V)">
                    <i class="fa fa-clipboard" aria-hidden="true"></i>
                    <span>لصق من الحافظة</span>
                </button>
                <span class="cw-extras__hint-inline">JPG / PNG / WEBP — حتى 10MB</span>
            </div>

            <input type="file"
                   class="cw-sr-only"
                   data-cw-extras-input
                   accept="image/jpeg,image/png,image/webp"
                   capture="user"
                   tabindex="-1"
                   aria-hidden="true">

            <!-- Mirrors the uploaded photo's id into form data so the wizard's
                 standard server-error renderer can attach a "missing photo"
                 error directly to this card. Kept in sync by extras.js. -->
            <input type="hidden"
                   name="Customers[_extras_photo_id]"
                   data-cw-extras-photo-id
                   value="<?= $photoId > 0 ? $photoId : '' ?>">
        </div>
    </fieldset>

    <!-- ── Block 2 (OPTIONAL): additional supporting documents. ────────── -->
    <details class="cw-fieldset cw-fieldset--collapsible cw-extras__docs-block"
             <?= $docs ? 'open' : '' ?>>
        <summary class="cw-fieldset__summary">
            <span>
                <i class="fa fa-paperclip" aria-hidden="true"></i>
                مستندات داعمة إضافية
            </span>
            <span class="cw-fieldset__chip">اختياري</span>
        </summary>

        <p class="cw-fieldset__hint">
            ارفع أي <strong>مستندات داعمة</strong> (فواتير، شهادات راتب،
            رخص…) تودّ إرفاقها بملف العميل. كرر <kbd>Ctrl</kbd>+<kbd>V</kbd>
            أو <kbd>Win</kbd>+<kbd>V</kbd> لإضافة عدة صور دفعة واحدة.
        </p>

        <div class="cw-field cw-extras__uploader cw-extras__uploader--docs"
             data-cw-extras-uploader="doc"
             data-cw-extras-multi="1"
             data-cw-extras-accept="image/jpeg,image/png,image/webp,application/pdf"
             tabindex="0"
             role="region"
             aria-label="رفع مستندات إضافية — اضغط Ctrl+V أو Win+V للصق صور متعددة">

            <div class="cw-extras__dropzone" data-cw-extras-list role="list"
                 aria-label="المستندات المرفقة">
                <?php foreach ($docs as $doc): ?>
                    <?php $isPdf = isset($doc['mime']) && $doc['mime'] === 'application/pdf'; ?>
                    <div class="cw-extras__item"
                         data-cw-extras-item
                         data-image-id="<?= (int)$doc['image_id'] ?>"
                         role="listitem">
                        <div class="cw-extras__thumb cw-extras__thumb--<?= $isPdf ? 'pdf' : 'img' ?>">
                            <?php if ($isPdf): ?>
                                <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                            <?php else: ?>
                                <img src="<?= Html::encode((string)($doc['url'] ?? '')) ?>"
                                     alt="<?= Html::encode((string)($doc['file_name'] ?? 'مستند')) ?>"
                                     loading="lazy">
                            <?php endif ?>
                        </div>
                        <div class="cw-extras__meta">
                            <strong class="cw-extras__name"><?= Html::encode((string)($doc['file_name'] ?? '')) ?></strong>
                            <?php if (!empty($doc['size'])): ?>
                                <span class="cw-extras__sub">
                                    <?= number_format(((int)$doc['size']) / 1024, 0) ?> KB
                                </span>
                            <?php endif ?>
                        </div>
                        <button type="button"
                                class="cw-extras__del"
                                data-cw-extras-del
                                aria-label="حذف المستند">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="cw-extras__actions">
                <button type="button" class="cw-btn cw-btn--outline cw-btn--sm"
                        data-cw-extras-trigger>
                    <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                    <span>إضافة مستندات</span>
                </button>
                <button type="button" class="cw-btn cw-btn--outline cw-btn--sm"
                        data-cw-extras-paste
                        title="لصق من الحافظة (Ctrl+V أو Win+V لعدّة صور)">
                    <i class="fa fa-clipboard" aria-hidden="true"></i>
                    <span>لصق من الحافظة</span>
                </button>
                <span class="cw-extras__hint-inline">JPG / PNG / WEBP / PDF — حتى 10MB لكل ملف</span>
            </div>

            <input type="file"
                   class="cw-sr-only"
                   data-cw-extras-input
                   accept="image/jpeg,image/png,image/webp,application/pdf"
                   multiple
                   tabindex="-1"
                   aria-hidden="true">
        </div>
    </details>

</div>
