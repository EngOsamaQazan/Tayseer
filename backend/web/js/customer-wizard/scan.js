/**
 * Customer Wizard V2 — Smart ID Scan handler.
 *
 * Independent module wired purely via DOM contracts (no hard coupling to
 * core.js internals). It uses three public surfaces from core.js:
 *   • CW.toast(message, type, ttl?)     – user feedback
 *   • state.urls.scan via window.CW       – endpoint discovery (set by core.js)
 *
 * Markup contract (rendered by _step_1_identity.php):
 *   <button data-cw-action="scan-identity">
 *   <input  data-cw-role="scan-input" type="file" accept="image/*,.pdf">
 *
 * Flow:
 *   1. Click button → trigger hidden file input.
 *   2. On file change → POST as multipart/form-data to /customers/wizard/scan
 *      (CSRF header, no session lock contention thanks to server-side close).
 *   3. While in-flight: button shows spinner, becomes aria-busy, blocks repeat.
 *   4. On success: snapshot existing values, fill returned fields, highlight
 *      changed inputs for ~3s, dispatch 'change' so Select2/listeners fire,
 *      and surface unmapped lookups + an Undo toast (5s window).
 *   5. On failure: friendly Arabic toast, no field changes.
 *
 * Accessibility:
 *   • The hidden file input is .cw-sr-only + tabindex=-1 so it's not a tab
 *     stop, but the button forwards activation via .click().
 *   • Status announcements ride on CW.toast (polite live region).
 *   • Filled fields get a brief visual highlight class (cw-field--auto-filled)
 *     that respects prefers-reduced-motion via core.css.
 */
(function ($, window) {
    'use strict';

    if (!$) return;

    var NS = '.cwScan';
    var lastSnapshot = null;   // { selector: previousValue, ... } — for Undo

    /**
     * Resolve the scan endpoint. Prefers the URL injected by core.js into
     * window.CW (so we never hard-code routes here), with a sane fallback.
     */
    function scanUrl() {
        try {
            if (window.CW && window.CW._urls && window.CW._urls.scan) {
                return window.CW._urls.scan;
            }
        } catch (e) { /* noop */ }
        return '/customers/wizard/scan';
    }

    /**
     * Resolve the "add lookup row" endpoint URL for a given kind ('city' or
     * 'citizen'). Falls back to the conventional path if CW._urls is missing.
     */
    function lookupAddUrl(kind) {
        var key = kind === 'citizen' ? 'addCitizen' : 'addCity';
        var fallback = kind === 'citizen'
            ? '/customers/wizard/add-citizen'
            : '/customers/wizard/add-city';
        try {
            if (window.CW && window.CW._urls && window.CW._urls[key]) {
                return window.CW._urls[key];
            }
        } catch (e) { /* noop */ }
        return fallback;
    }

    /** Fire CW.toast safely if core.js loaded; otherwise silent fallback. */
    function toast(msg, type, ttl) {
        if (window.CW && typeof window.CW.toast === 'function') {
            window.CW.toast(msg, type || 'info', ttl);
        }
    }

    /** Read the CSRF token Yii embeds in <meta name="csrf-token">. */
    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    /** Snapshot fields we're about to overwrite — needed for Undo. */
    function snapshot(fieldMap) {
        var snap = {};
        for (var key in fieldMap) {
            if (!Object.prototype.hasOwnProperty.call(fieldMap, key)) continue;
            var $all = $('[name="' + cssEscape(key) + '"]');
            if (!$all.length) continue;
            var first = $all.first();
            var type  = (first.attr('type') || '').toLowerCase();
            // Radio group: capture which value was checked (or empty).
            if (type === 'radio' || type === 'checkbox') {
                var $checked = $all.filter(':checked').first();
                snap[key] = $checked.length ? String($checked.val()) : '';
            } else {
                snap[key] = first.val();
            }
        }
        return snap;
    }

    /**
     * CSS.escape polyfill for older browsers — only the chars we care about
     * inside attribute selectors (we won't see exotic input here).
     */
    function cssEscape(s) {
        return String(s).replace(/(["\\\[\]])/g, '\\$1');
    }

    /**
     * Apply the field map to the form. Returns the count of fields actually
     * changed so we can build a meaningful toast message.
     */
    function applyFields(fieldMap) {
        var changed = 0;
        for (var name in fieldMap) {
            if (!Object.prototype.hasOwnProperty.call(fieldMap, name)) continue;
            var value = fieldMap[name];
            if (value === null || value === undefined) continue;

            // Match ALL elements with this name — radio groups have many.
            var $all = $('[name="' + cssEscape(name) + '"]');
            if (!$all.length) continue;

            var next  = String(value).trim();
            var first = $all.first();
            var type  = (first.attr('type') || '').toLowerCase();
            var didChange = false;

            // ── Radio / checkbox group: find the input whose value matches
            //    and check it. .val() doesn't do this; jQuery's setter only
            //    sets .value on the element, it doesn't tick the radio.
            if (type === 'radio' || type === 'checkbox') {
                var $target = $all.filter('[value="' + cssEscape(next) + '"]').first();
                if (!$target.length) continue;     // unknown value
                if ($target.is(':checked')) continue;     // already set
                // Clear siblings (radios), then check the matched input.
                if (type === 'radio') $all.prop('checked', false);
                $target.prop('checked', true).trigger('change');
                didChange = true;
                first = $target;     // for the highlight wrap below
            } else {
                var current = String(first.val() || '').trim();
                if (current === next) continue;
                first.val(next).trigger('change').trigger('input');
                didChange = true;
            }

            if (!didChange) continue;

            // Visual cue: brief highlight on the field's wrapper.
            var $wrap = first.closest('[data-cw-field]');
            if ($wrap.length) {
                $wrap.addClass('cw-field--auto-filled');
                window.setTimeout(function ($w) {
                    return function () { $w.removeClass('cw-field--auto-filled'); };
                }($wrap), 3000);
            }
            changed++;
        }
        return changed;
    }

    /** Restore the snapshot taken before applyFields (Undo). */
    function restoreSnapshot(snap) {
        if (!snap) return 0;
        var restored = 0;
        for (var name in snap) {
            if (!Object.prototype.hasOwnProperty.call(snap, name)) continue;
            var $all = $('[name="' + cssEscape(name) + '"]');
            if (!$all.length) continue;
            var first = $all.first();
            var type  = (first.attr('type') || '').toLowerCase();
            var v = snap[name] || '';
            if (type === 'radio' || type === 'checkbox') {
                if (type === 'radio') $all.prop('checked', false);
                if (v !== '') {
                    $all.filter('[value="' + cssEscape(String(v)) + '"]')
                        .prop('checked', true).trigger('change');
                }
            } else {
                first.val(v).trigger('change').trigger('input');
            }
            restored++;
        }
        return restored;
    }

    /**
     * Lock/unlock the scan button while the network request is pending.
     */
    function setBusy($btn, busy) {
        if (busy) {
            $btn.data('cw-original-html', $btn.html())
                .prop('disabled', true)
                .attr('aria-busy', 'true')
                .html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> <span>جارٍ المسح…</span>');
        } else {
            var html = $btn.data('cw-original-html');
            if (html) $btn.html(html);
            $btn.prop('disabled', false).removeAttr('aria-busy');
        }
    }

    /**
     * Send the file to the server and resolve with the parsed JSON response.
     * Rejects on transport errors only — application-level {ok:false} is
     * passed through and handled by the caller.
     */
    function uploadScan(file, sideHint) {
        var fd = new FormData();
        fd.append('file', file, file.name);
        fd.append('_csrf-backend', csrfToken());
        if (sideHint === 'front' || sideHint === 'back') {
            fd.append('side', sideHint);
        }

        return $.ajax({
            url: scanUrl(),
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000,
            headers: { 'X-CSRF-Token': csrfToken() }
        });
    }

    /**
     * Resolve the file-input that pairs with the given scan trigger.
     */
    function findFileInput($trigger) {
        var $input = $trigger.closest('.cw-card__header')
                        .siblings('input[data-cw-role="scan-input"]')
                        .add($trigger.siblings('input[data-cw-role="scan-input"]'))
                        .first();
        if (!$input.length) {
            $input = $trigger.closest('[data-cw-section]')
                         .find('input[data-cw-role="scan-input"]')
                         .first();
        }
        return $input;
    }

    /**
     * Apply a server-mapped fields object to the form. Wraps the lower-level
     * snapshot+applyFields helpers and surfaces toasts/unmapped hints.
     *
     * @returns {number} count of fields that were actually changed
     */
    function applyServerFields(fields, unmapped, opts) {
        opts = opts || {};
        var hasFields   = !!(fields && Object.keys(fields).length);
        var hasUnmapped = !!(unmapped && (unmapped.city || unmapped.citizen));

        // ── Always render unmapped-lookup banners FIRST. They must fire
        //    even when no field was changed (e.g. OCR returned only a
        //    city name that didn't match any DB row), otherwise the
        //    inline "add to database" CTA never reaches the user.
        if (hasUnmapped) {
            if (unmapped.city) {
                renderUnmappedBanner('Customers[city]', unmapped.city, {
                    kind: 'city',
                    label: 'مدينة الولادة',
                    addLabel: 'إضافة «' + unmapped.city + '» إلى قائمة المدن',
                });
            }
            if (unmapped.citizen) {
                renderUnmappedBanner('Customers[citizen]', unmapped.citizen, {
                    kind: 'citizen',
                    label: 'الجنسية',
                    addLabel: 'إضافة «' + unmapped.citizen + '» إلى قائمة الجنسيات',
                });
            }
        }
        // Don't auto-clear banners on an empty-fields call: the camera
        // pipeline calls applyServerFields once per side, and the second
        // call (back side) might legitimately have no city while the
        // first call's city banner should remain visible. Banners are
        // cleared explicitly when the user resolves them.

        if (!hasFields) {
            if (opts.silentEmpty !== true && !hasUnmapped) {
                toast('لم يتمكّن النظام من استخراج بيانات قابلة للاستخدام.', 'warning', 5000);
            }
            return 0;
        }

        if (!lastSnapshot) lastSnapshot = snapshot(fields);
        var changed = applyFields(fields);

        if (!changed) {
            if (opts.silentEmpty !== true && !hasUnmapped) {
                toast('البيانات المُستخرَجة مطابقة للحقول الحالية.', 'info', 4000);
            }
            return 0;
        }

        if (opts.silentSuccess === true) return changed;

        toast('تمّ ملء ' + changed + ' حقلاً تلقائياً.', 'success', 5000);
        return changed;
    }

    /**
     * Render (or replace) an inline banner inside a field wrapper that tells
     * the user a value was extracted but didn't match any option in the
     * dropdown — and offers a one-tap action to fix it.
     *
     * For cities we offer "إضافة إلى قائمة المدن" which POSTs to the new
     * add-city endpoint and refreshes the <select> in place. For other
     * lookups we just show the extracted text as a hint.
     */
    function renderUnmappedBanner(fieldName, rawValue, cfg) {
        var $wrap = $('[data-cw-field="' + fieldName + '"]').first();
        if (!$wrap.length) return;

        $wrap.find('.cw-unmapped').remove();

        var $banner = $('<div/>', {
            'class': 'cw-unmapped',
            role:    'note',
            'aria-live': 'polite',
        });

        $('<span/>', { 'class': 'cw-unmapped__text' })
            .html(
                '<i class="fa fa-info-circle" aria-hidden="true"></i> ' +
                cfg.label + ' المُستخرَجة: ' +
                '<strong></strong>'
            )
            .find('strong').text(rawValue).end()
            .appendTo($banner);

        if (cfg.kind === 'city' || cfg.kind === 'citizen') {
            var $btn = $('<button/>', {
                type: 'button',
                'class': 'cw-unmapped__btn',
                text: cfg.addLabel || ('إضافة «' + rawValue + '»'),
            });
            $btn.on('click', function (e) {
                e.preventDefault();
                handleAddLookup(cfg.kind, fieldName, rawValue, $banner, $btn);
            });
            $banner.append($btn);
        }

        $wrap.append($banner);
    }

    function removeUnmappedBanner(fieldName) {
        $('[data-cw-field="' + fieldName + '"] .cw-unmapped').remove();
    }

    /**
     * POST an unresolved lookup value (city/citizen name extracted by OCR)
     * to the matching wizard endpoint, then inject the resulting option into
     * the <select> and select it. Idempotent: handles existed/restored/new
     * cases identically from the user's perspective.
     *
     * @param {'city'|'citizen'} kind  which lookup we're updating
     * @param {string}  fieldName     bracketed name, e.g. 'Customers[city]'
     * @param {string}  rawValue      the OCR-extracted text
     * @param {jQuery}  $banner       the banner element to remove on success
     * @param {jQuery}  $btn          the action button (for disable/restore)
     */
    function handleAddLookup(kind, fieldName, rawValue, $banner, $btn) {
        var labels = kind === 'citizen'
            ? { missing: 'لم يتم العثور على حقل الجنسية في النموذج.',
                fail:    'تعذّر إضافة الجنسية.',
                added:   'تمّت إضافة «{n}» إلى قائمة الجنسيات.' }
            : { missing: 'لم يتم العثور على حقل المدينة في النموذج.',
                fail:    'تعذّر إضافة المدينة.',
                added:   'تمّت إضافة «{n}» إلى قائمة المدن.' };

        var $select = $('select[name="' + cssEscape(fieldName) + '"]').first();
        if (!$select.length) {
            toast(labels.missing, 'error');
            return;
        }
        var origText = $btn.text();
        $btn.prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> جارٍ الحفظ…');

        $.ajax({
            url: lookupAddUrl(kind),
            type: 'POST',
            dataType: 'json',
            data: {
                name: rawValue,
                _csrf: csrfToken(),
            },
        }).done(function (resp) {
            if (!resp || !resp.ok || !resp.id) {
                toast((resp && resp.error) || labels.fail, 'error', 6000);
                $btn.prop('disabled', false).text(origText);
                return;
            }

            var idStr = String(resp.id);
            var name  = resp.name || rawValue;

            var $existing = $select.find('option[value="' + cssEscape(idStr) + '"]');
            if (!$existing.length) {
                $('<option/>', { value: idStr, text: name })
                    .insertAfter($select.find('option').first());
            }

            $select.val(idStr).trigger('change');

            var $wrap = $select.closest('[data-cw-field]');
            $wrap.addClass('cw-field--auto-filled');
            window.setTimeout(function () {
                $wrap.removeClass('cw-field--auto-filled');
            }, 3000);

            $banner.remove();
            var msg;
            if (resp.restored) {
                msg = 'تمّت استعادة «' + name + '» (كانت محذوفة سابقاً) واختيارها.';
            } else if (resp.existed) {
                msg = 'تم اختيار «' + name + '» (موجودة مسبقاً).';
            } else {
                msg = labels.added.replace('{n}', name);
            }
            toast(msg, 'success', 5500);
        }).fail(function () {
            toast('فشل الاتصال بالخادم. حاول مرة أخرى.', 'error', 6000);
            $btn.prop('disabled', false).text(origText);
        });
    }

    /**
     * Main click handler — bound once at module load (idempotent via NS).
     *
     * Strategy:
     *   1. If the live-camera module (CWCamera) loaded AND the device supports
     *      getUserMedia, open the bank-style auto-capture overlay.
     *   2. Otherwise (desktop without camera, iframe sandbox, etc.) fall back
     *      transparently to the file picker.
     *
     * The user can also explicitly choose "ارفع ملفاً" inside the camera UI
     * to switch modes mid-flow.
     */
    function onScanClick(e) {
        e.preventDefault();
        var $btn = $(this);

        // ── Try camera mode first. ──
        // CWCamera.open() handles ALL failure modes with a proper UX:
        //   • insecure context  → "افتح الموقع عبر https" message + fallback button
        //   • permission denied → browser-specific recovery instructions
        //   • no camera         → "لا توجد كاميرا" + fallback
        // So we always route through it when available — the file picker
        // is only the absolute last-resort path.
        if (window.CWCamera && typeof window.CWCamera.open === 'function' &&
            !$btn.data('cw-prefer-upload')) {
            openCamera($btn);
            return;
        }

        toast('متصفحك لا يدعم الكاميرا الحيّة — سنفتح اختيار ملف بدلاً.', 'info', 4000);
        openFilePicker($btn);
    }

    function openFilePicker($btn) {
        var $input = findFileInput($btn);
        if (!$input.length) {
            toast('لم يتم العثور على حقل رفع الملف.', 'error');
            return;
        }
        $input.trigger('click');
    }

    function openCamera($btn) {
        lastSnapshot = null;     // fresh snapshot will be built per-side
        window.CWCamera.open({
            scanUrl:   scanUrl(),
            csrfToken: csrfToken(),
            onFields:  function (fields, side, unmapped, meta) {
                // Apply incrementally so the user sees the form fill in real-time
                // — but stay quiet about success toasts; the camera UI itself
                // already shows "تم التقاط الوجه...".
                applyServerFields(fields, unmapped, {
                    silentEmpty:   true,
                    silentSuccess: true,
                });
                // Stash the per-side Media id in a hidden input so the
                // wizard's draft autosave includes it. Server-side
                // rememberScanInDraft() already persists this to _scan.images,
                // but having it in the form too means a manual "Save draft"
                // button click also carries the link.
                if (meta && meta.image_id) {
                    setScanImageRef(side, meta.image_id);
                }
            },
            onComplete: function (allFields, allImages) {
                // Final toast once both sides processed.
                var changedTotal = Object.keys(allFields || {}).length;
                var imagesSaved  = allImages ? Object.keys(allImages).length : 0;
                var bits = [];
                if (changedTotal) bits.push('تم ملء ' + changedTotal + ' حقلاً تلقائياً');
                if (imagesSaved)  bits.push('تم حفظ ' + imagesSaved + ' صورة في ملف العميل');
                if (bits.length)  toast('✓ ' + bits.join(' — ') + '.', 'success', 6500);
                else              toast('انتهى المسح ولم يتم استخراج بيانات.', 'warning', 5000);
            },
            onCancel:   function () { /* no toast — user closed deliberately */ },
            onFallback: function () { openFilePicker($btn); },
            onError:    function (code) {
                if (code === 'camera_unsupported') {
                    openFilePicker($btn);
                }
                // Other errors are shown inside the camera overlay itself.
            },
        });
    }

    /**
     * Stash a Media row id in a hidden input so it survives the wizard's
     * autosave cycle (the controller's rememberScanInDraft() also persists
     * it server-side; the hidden field is a belt-and-suspenders backup).
     */
    function setScanImageRef(side, imageId) {
        var name = 'WizardScan[' + (side === 'back' ? 'back' : 'front') + '_image_id]';
        var $hidden = $('input[name="' + cssEscape(name) + '"]').first();
        if (!$hidden.length) {
            $hidden = $('<input type="hidden">').attr('name', name).appendTo('body');
        }
        $hidden.val(String(imageId));
    }

    /**
     * File-input change handler — accepts one OR two files (front + back).
     * Each file is uploaded in sequence so the server can persist a Media
     * row per side and so that side-aware extraction (front vs back)
     * works correctly. The user can pick a single file (treated as front)
     * or two files (treated as front, then back).
     */
    function onFileChange(e) {
        var input = e.target;
        if (!input.files || !input.files.length) return;

        var $btn = $(this).closest('.cw-card__header').find('[data-cw-action="scan-identity"]')
                     .add($(this).siblings('[data-cw-action="scan-identity"]'))
                     .first();
        if (!$btn.length) {
            $btn = $(this).closest('[data-cw-section]').find('[data-cw-action="scan-identity"]').first();
        }

        var maxBytes = 10 * 1024 * 1024;
        var allowed  = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        // Cap to two files — anything beyond is probably a misclick.
        var files = Array.prototype.slice.call(input.files, 0, 2);
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            if (f.size > maxBytes) {
                toast('الملف #' + (i + 1) + ': الحجم أكبر من 10 ميجابايت.', 'error', 6000);
                input.value = '';
                return;
            }
            if (f.type && allowed.indexOf(f.type) === -1) {
                toast('الملف #' + (i + 1) + ': نوع غير مدعوم — استخدم JPG / PNG / WEBP / PDF.', 'error', 6000);
                input.value = '';
                return;
            }
        }

        setBusy($btn, true);
        if (files.length === 2) {
            toast('جارٍ تحليل الوجه الأمامي ثم الخلفي…', 'info', 2500);
        } else {
            toast('جارٍ تحليل الوثيقة بالذكاء الاصطناعي…', 'info', 2000);
        }

        var totalChanged   = 0;
        var totalImagesAdded = 0;
        var lastUnmapped   = {};
        var hadAnyError    = false;
        // Reset snapshot so the first file builds a fresh undo state.
        lastSnapshot = null;

        // Run uploads sequentially so the back-side request gets the
        // already-detected issuing_body for context-aware extraction.
        // Each response is rendered IMMEDIATELY (fields + unmapped banner)
        // so the user never wonders whether extraction worked while the
        // second upload is still in flight.
        var seq = $.Deferred().resolve();
        files.forEach(function (file, idx) {
            // First file → front, second → back. The server will re-detect
            // and override if its OCR disagrees, but this hint helps.
            var sideHint = (idx === 0) ? 'front' : 'back';
            seq = seq.then(function () {
                return uploadScan(file, sideHint).done(function (resp) {
                    if (!resp || !resp.ok) {
                        hadAnyError = true;
                        var msg = (resp && resp.error) ? resp.error
                                : 'تعذّر تحليل الملف #' + (idx + 1) + ' — جرّب صورة أوضح.';
                        toast(msg, 'error', 6000);
                        return;
                    }

                    if (resp.image_id) {
                        var fileSide = (resp.side_detected === 'back') ? 'back'
                                     : (resp.side_detected === 'front') ? 'front'
                                     : sideHint;
                        setScanImageRef(fileSide, resp.image_id);
                        totalImagesAdded++;
                    }

                    if (resp.unmapped) lastUnmapped = $.extend(lastUnmapped, resp.unmapped);

                    // ── Apply this response's payload IMMEDIATELY: fields
                    //    are filled and any unmapped lookup banner (e.g.
                    //    "إضافة كفرنجه إلى المدن") shows up right away,
                    //    not after the 2nd upload finishes 10-15s later.
                    var beforeChanged = totalChanged;
                    var thisChanged = applyServerFields(resp.fields || {}, resp.unmapped || {}, {
                        silentEmpty:   true,
                        silentSuccess: true,
                    });
                    totalChanged = beforeChanged + thisChanged;

                    // Quick per-file ack so the user sees movement between
                    // the front and back analyses.
                    if (files.length === 2 && idx === 0 && (thisChanged || resp.image_id)) {
                        toast('تم تحليل الوجه الأمامي — جارٍ تحليل الخلفي…', 'info', 2500);
                    }
                });
            });
        });

        seq.always(function () {
            setBusy($btn, false);
            input.value = '';

            // Final summary toast (banners + fields are already on screen).
            if (hadAnyError && totalChanged === 0 && totalImagesAdded === 0) return;

            var bits = [];
            if (totalChanged)      bits.push('تم ملء ' + totalChanged + ' حقلاً تلقائياً');
            if (totalImagesAdded)  bits.push('تم حفظ ' + totalImagesAdded + ' صورة في ملف العميل');
            if (bits.length) {
                toast('✓ ' + bits.join(' — ') + '.', 'success', 6500);
            } else if (!hadAnyError) {
                toast('انتهى التحليل ولم تُستخرج بيانات قابلة للاستخدام.', 'warning', 5000);
            }
        }).fail(function (xhr) {
            var serverMsg = '';
            try {
                var j = xhr && xhr.responseJSON;
                if (j && j.error) serverMsg = j.error;
            } catch (_) { /* noop */ }
            toast(serverMsg || 'فشل الاتصال بخدمة المسح — تحقّق من الإنترنت.', 'error', 6000);
        });
    }

    /**
     * Idempotent binder — safe to call after each step re-render.
     */
    function bind() {
        var $doc = $(document);
        $doc.off('click' + NS, '[data-cw-action="scan-identity"]')
            .on('click' + NS, '[data-cw-action="scan-identity"]', onScanClick);

        $doc.off('change' + NS, 'input[data-cw-role="scan-input"]')
            .on('change' + NS, 'input[data-cw-role="scan-input"]', onFileChange);
    }

    /* Init: bind on DOM ready + on every step render so freshly inserted
     * partials stay wired without needing core.js cooperation. */
    $(function () {
        bind();
        $(document).on('cw:step:rendered cw:step:changed', bind);
    });

})(window.jQuery, window);
