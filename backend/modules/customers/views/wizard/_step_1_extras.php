<?php

use yii\helpers\Html;

/**
 * Customer Wizard V2 — «الصور والمستندات الإضافية» fieldset.
 *
 * Drop-in partial that ships TWO independent uploaders:
 *   1. Personal photo (single file, image only) — becomes the customer's
 *      headshot rendered on the contract print preview.
 *   2. Additional documents (multi-file, images + PDF) — anything the user
 *      wants attached to the customer record that doesn't fit the existing
 *      ID-scan / SSC-kashf slots (utility bills, salary letters, vehicle
 *      registration, …).
 *
 * Both inputs talk to the SAME backend endpoint (POST upload-extra) with
 * a `purpose` discriminator, and both are pre-populated from the wizard's
 * draft payload (`_extras.photo`, `_extras.docs[]`) so a resumed draft
 * shows previously-uploaded files without a re-upload.
 *
 * Markup contract consumed by extras.js:
 *   • The whole fieldset:        [data-cw-extras]
 *   • Each uploader:             [data-cw-extras-uploader=<purpose>]
 *   • Hidden file input:         input[type=file][data-cw-extras-input]
 *   • Trigger button:            [data-cw-extras-trigger]
 *   • Render target for thumbs:  [data-cw-extras-list]
 *
 * @var array $payload  Decoded wizard draft (so we can rehydrate previous uploads)
 */

$extras = $payload['_extras'] ?? [];
$photo  = (is_array($extras) && !empty($extras['photo']) && is_array($extras['photo']))
        ? $extras['photo'] : null;
$docs   = (is_array($extras) && !empty($extras['docs'])  && is_array($extras['docs']))
        ? array_values($extras['docs']) : [];
?>
<details class="cw-fieldset cw-fieldset--collapsible cw-extras"
         data-cw-extras
         <?= ($photo || $docs) ? 'open' : '' ?>>
    <summary class="cw-fieldset__summary">
        <span>
            <i class="fa fa-camera-retro" aria-hidden="true"></i>
            الصورة الشخصية والمستندات الإضافية
        </span>
        <span class="cw-fieldset__chip">اختياري</span>
    </summary>

    <p class="cw-fieldset__hint">
        ارفع <strong>الصورة الشخصية للعميل</strong> لتظهر على شاشة طباعة العقد،
        بالإضافة إلى أي <strong>مستندات داعمة</strong> (فواتير، شهادات راتب، رخص…)
        تودّ إرفاقها بالملف.
    </p>

    <div class="cw-grid cw-grid--2 cw-extras__grid">

        <!-- ── Uploader 1: personal photo (single, replaceable). ── -->
        <div class="cw-field cw-extras__uploader cw-extras__uploader--photo"
             data-cw-extras-uploader="photo"
             data-cw-extras-multi="0"
             data-cw-extras-accept="image/jpeg,image/png,image/webp">
            <label class="cw-field__label">
                <i class="fa fa-user-circle-o" aria-hidden="true"></i>
                الصورة الشخصية للعميل
                <span class="cw-extras__hint-inline">JPG / PNG / WEBP — حتى 10MB</span>
            </label>

            <div class="cw-extras__dropzone" data-cw-extras-list role="list"
                 aria-label="الصورة الشخصية الحالية">
                <?php if ($photo): ?>
                    <div class="cw-extras__item cw-extras__item--photo"
                         data-cw-extras-item
                         data-image-id="<?= (int)$photo['image_id'] ?>"
                         role="listitem">
                        <div class="cw-extras__thumb">
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
                <button type="button" class="cw-btn cw-btn--outline cw-btn--sm"
                        data-cw-extras-trigger>
                    <i class="fa fa-camera" aria-hidden="true"></i>
                    <span><?= $photo ? 'تغيير الصورة' : 'اختيار صورة' ?></span>
                </button>
            </div>

            <input type="file"
                   class="cw-sr-only"
                   data-cw-extras-input
                   accept="image/jpeg,image/png,image/webp"
                   capture="user"
                   tabindex="-1"
                   aria-hidden="true">
        </div>

        <!-- ── Uploader 2: additional documents (multi). ── -->
        <div class="cw-field cw-extras__uploader cw-extras__uploader--docs"
             data-cw-extras-uploader="doc"
             data-cw-extras-multi="1"
             data-cw-extras-accept="image/jpeg,image/png,image/webp,application/pdf">
            <label class="cw-field__label">
                <i class="fa fa-file-text-o" aria-hidden="true"></i>
                مستندات وصور إضافية
                <span class="cw-extras__hint-inline">JPG / PNG / WEBP / PDF — حتى 10MB لكل ملف</span>
            </label>

            <div class="cw-extras__dropzone" data-cw-extras-list role="list"
                 aria-label="المستندات المرفقة">
                <?php foreach ($docs as $doc): ?>
                    <?php
                    $isPdf = isset($doc['mime']) && $doc['mime'] === 'application/pdf';
                    ?>
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
            </div>

            <input type="file"
                   class="cw-sr-only"
                   data-cw-extras-input
                   accept="image/jpeg,image/png,image/webp,application/pdf"
                   multiple
                   tabindex="-1"
                   aria-hidden="true">
        </div>
    </div>
</details>
