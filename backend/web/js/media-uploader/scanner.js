/**
 * Phase 6 / M6.1 — Unified MediaUploader (wizard scanner).
 *
 * The customer wizard captures multi-side documents (front/back of an
 * ID, of a military letter, …) and uses GroupNameRegistry codes like
 * '0_front' / '0_back' to keep front and back addressable separately.
 * This file is a thin wrapper around the core uploader that:
 *
 *   • Enforces one-file-at-a-time per "side" slot
 *   • Replaces the existing slot's media id when the user re-takes a
 *     scan instead of accumulating duplicates
 *   • Emits a `media:scanned` CustomEvent so wizard step JS can advance
 *     the form without coupling to MediaUploader internals
 *
 * Usage:
 *
 *   <div data-media-scanner
 *        data-entity-type="customer"
 *        data-uploaded-via="wizard"
 *        data-target="#cw-scan-ids">
 *
 *     <div class="mu-scan-side" data-group-name="0_front">
 *       <button type="button" data-media-pick>الوجه الأمامي</button>
 *       <input type="file" data-media-input accept="image/*"
 *              capture="environment" style="display:none">
 *       <div class="mu-scan-preview"></div>
 *     </div>
 *
 *     <div class="mu-scan-side" data-group-name="0_back">
 *       <button type="button" data-media-pick>الوجه الخلفي</button>
 *       <input type="file" data-media-input accept="image/*"
 *              capture="environment" style="display:none">
 *       <div class="mu-scan-preview"></div>
 *     </div>
 *
 *   </div>
 *
 * The hidden target receives a JSON map of {groupName: mediaId} so the
 * wizard's PHP step handler can adopt the orphans at customer-create
 * time using MediaService::adopt().
 */
(function (global) {
    'use strict';

    if (!global.MediaUploader) {
        console.error('MediaUploader.scanner: core.js must load first.');
        return;
    }
    var MediaUploader = global.MediaUploader;

    function attachScanner(root, opts) {
        if (root.__mediaScannerAttached) return;
        root.__mediaScannerAttached = true;
        opts = opts || {};

        var rootCfg = MediaUploader.readConfig(root);
        var targetSel = root.dataset.target || '';
        var targetEl = targetSel ? document.querySelector(targetSel) : null;
        var captured = {}; // { groupName: mediaId }

        function syncTarget() {
            if (!targetEl) return;
            targetEl.value = JSON.stringify(captured);
        }

        Array.from(root.querySelectorAll('.mu-scan-side')).forEach(function (side) {
            var group = side.dataset.groupName || '';
            if (!group) return;

            var input   = side.querySelector('[data-media-input]');
            var pickBtn = side.querySelector('[data-media-pick]');
            var preview = side.querySelector('.mu-scan-preview');

            if (pickBtn && input) {
                pickBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    input.click();
                });
            }

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) return;

                if (preview) {
                    preview.innerHTML = '<small style="color:#6b7280;">جاري الرفع…</small>';
                }

                var sideCfg = Object.assign({}, rootCfg, {
                    groupName: group,
                    autoClassify: rootCfg.autoClassify,
                    multiple: false,
                });

                MediaUploader.uploadOne(file, sideCfg, opts, function (pct) {
                    if (preview) {
                        preview.innerHTML = '<small style="color:#6b7280;">رفع ' + pct + '%</small>';
                    }
                }).then(function (resp) {
                    captured[group] = resp.file.id;
                    syncTarget();
                    if (preview) {
                        preview.innerHTML =
                            '<a href="' + resp.file.url + '" target="_blank">' +
                                '<img src="' + (resp.file.thumb_url || resp.file.url) + '"' +
                                     ' style="max-width:160px;max-height:160px;border-radius:6px;' +
                                            'border:1px solid #e5e7eb;">' +
                            '</a>';
                    }
                    root.dispatchEvent(new CustomEvent('media:scanned', {
                        detail: { groupName: group, file: resp.file },
                        bubbles: true
                    }));
                    if (typeof opts.onSuccess === 'function') opts.onSuccess(resp, file, side);
                }).catch(function (err) {
                    if (preview) {
                        preview.innerHTML = '<small style="color:#dc2626;">' + (err.message || 'فشل الرفع') + '</small>';
                    }
                    if (typeof opts.onError === 'function') opts.onError(err, file, side);
                }).then(function () {
                    input.value = '';
                });
            });
        });
    }

    MediaUploader.scanner = function (selector, opts) {
        var nodes = (typeof selector === 'string')
            ? document.querySelectorAll(selector)
            : (selector instanceof Element ? [selector] : selector);
        Array.from(nodes || []).forEach(function (n) { attachScanner(n, opts); });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            MediaUploader.scanner('[data-media-scanner]');
        });
    } else {
        MediaUploader.scanner('[data-media-scanner]');
    }
})(window);
