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
 *
 * @deprecated Phase 6 / M6.2 — superseded by MediaUploader.scanner
 *             (backend/web/js/media-uploader/scanner.js). The wizard-
 *             specific OCR + auto-fill behaviour stays here for now,
 *             but the file-upload primitives can be lifted to the
 *             unified bundle once we are ready to retire the bespoke
 *             scan endpoint. Removal target: M8 (≈ 2026-07-19).
 */
(function ($, window) {

    if (typeof console !== 'undefined' && console.warn) {
        console.warn(
            'DEPRECATED uploader: customer-wizard/scan.js — file upload '
            + 'primitives are now in MediaUploader.scanner. The OCR auto-'
            + 'fill flow remains here pending Phase 8 cleanup.'
        );
    }
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

    /** Cheap HTML-escape — never trust server strings inside .html(). */
    function esc(s) {
        return $('<div/>').text(s == null ? '' : String(s)).html();
    }

    /**
     * Render document-integrity warnings as a BLOCKING modal popup that
     * the rep MUST explicitly acknowledge by clicking «اعتماد» before
     * any further interaction with the wizard. This is intentionally
     * disruptive — a discrepancy in the physical document is a fraud /
     * data-integrity signal and a passive banner is too easy to dismiss
     * inattentively.
     *
     * Currently emitted: ID_FRONT_BACK_MISMATCH (printed national ID on
     * the front of a Jordanian civil ID does not match the MRZ-encoded
     * one on the back). The modal:
     *   • Locks focus inside itself (keyboard-trap) until acknowledged.
     *   • Cannot be dismissed by clicking the backdrop or pressing Esc —
     *     the rep MUST click the primary button. This is the only place
     *     in the wizard where we make this trade-off; standard alerts
     *     remain Esc-dismissable everywhere else.
     *   • Visually loud (amber palette, large icon, comparison strip).
     *   • Plays no parallel toast — the modal IS the notification.
     *
     * Multiple warnings in the same response are queued and shown one
     * after another (the next opens when the previous is acknowledged).
     *
     * @param {Array<{level,code,title,message,data}>} warnings
     */
    function renderScanWarnings(warnings) {
        if (!warnings || !warnings.length) return;
        warnings.forEach(function (w) { showWarningModal(w); });
    }

    // Queue lets us show modals one-at-a-time even if a multi-side scan
    // returns several warnings in a tight burst.
    var __warningQueue = [];
    var __warningOpen  = false;

    function showWarningModal(w) {
        if (!w || !w.code) return;
        __warningQueue.push(w);
        if (!__warningOpen) drainWarningQueue();
    }

    function drainWarningQueue() {
        if (!__warningQueue.length) { __warningOpen = false; return; }
        __warningOpen = true;
        var w = __warningQueue.shift();
        openWarningModal(w, function () {
            // Brief gap so the next modal doesn't feel stacked.
            setTimeout(drainWarningQueue, 120);
        });
    }

    function openWarningModal(w, onAck) {
        var code  = w.code;
        var title = w.title   || 'تنبيه';
        var msg   = w.message || '';
        var data  = w.data    || {};

        // Comparison strip (currently only meaningful for the ID mismatch).
        var dataHtml = '';
        if (code === 'ID_FRONT_BACK_MISMATCH') {
            dataHtml =
                '<dl class="cw-scan-warning__data">' +
                    '<dt>الوجه (المطبوع):</dt>' +
                    '<dd><code>' + esc(data.front_id || '—') + '</code> ' +
                        '<span class="cw-scan-warning__pill cw-scan-warning__pill--accept">' +
                        'المعتمد</span>' +
                    '</dd>' +
                    '<dt>الظهر (MRZ):</dt>' +
                    '<dd><code>' + esc(data.back_mrz_id || '—') + '</code> ' +
                        '<span class="cw-scan-warning__pill cw-scan-warning__pill--reject">' +
                        'مُهمَل</span>' +
                    '</dd>' +
                '</dl>';
        }

        var $modal = $(
            '<div class="cw-scan-modal" role="dialog" aria-modal="true" ' +
                 'aria-labelledby="cw-scan-modal-title" ' +
                 'aria-describedby="cw-scan-modal-body" ' +
                 'data-cw-warning-code="' + esc(code) + '">' +
                '<div class="cw-scan-modal__backdrop" aria-hidden="true"></div>' +
                '<div class="cw-scan-modal__dialog">' +
                    '<div class="cw-scan-modal__icon" aria-hidden="true">' +
                        '<i class="fa fa-exclamation-triangle"></i>' +
                    '</div>' +
                    '<h3 id="cw-scan-modal-title" class="cw-scan-modal__title">' +
                        esc(title) +
                    '</h3>' +
                    '<div id="cw-scan-modal-body" class="cw-scan-modal__body">' +
                        '<p class="cw-scan-modal__msg">' + esc(msg) + '</p>' +
                        dataHtml +
                    '</div>' +
                    '<div class="cw-scan-modal__actions">' +
                        '<button type="button" class="cw-btn cw-btn--warning cw-btn--lg" ' +
                                'data-cw-scan-modal-ack autofocus>' +
                            '<i class="fa fa-check" aria-hidden="true"></i> ' +
                            '<span>اعتماد رقم الوجه والمتابعة</span>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        // Save the previously-focused element so we can restore focus on
        // close — standard a11y modal hygiene (WCAG 2.4.3 Focus Order).
        var prevFocus = document.activeElement;

        // Keyboard trap: keep Tab inside the modal until acknowledged.
        // We only have one focusable element (the ACK button) right now,
        // so the trap is a simple "if Tab pressed → refocus the button".
        function onKeyDown(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                $modal.find('[data-cw-scan-modal-ack]').focus();
                return;
            }
            // Enter triggers the primary action (acceptance) — natural
            // because the button is the focused element. Esc deliberately
            // does NOTHING: this dialog is a hard ack-required gate.
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
            }
        }
        document.addEventListener('keydown', onKeyDown, true);

        // Acknowledgement closes the modal, restores prior focus, and
        // signals the queue to advance.
        $modal.on('click', '[data-cw-scan-modal-ack]', function () {
            document.removeEventListener('keydown', onKeyDown, true);
            $modal.remove();
            try { if (prevFocus && prevFocus.focus) prevFocus.focus(); } catch (_) {}
            if (typeof onAck === 'function') onAck();
        });

        // Backdrop click intentionally does NOTHING — see header comment.
        // We still cancel the event so it doesn't bubble to anything below.
        $modal.find('.cw-scan-modal__backdrop').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });

        // Mount + focus the acknowledgement button on the next tick so the
        // browser's autofocus heuristics don't fight us.
        $('body').append($modal);
        setTimeout(function () {
            $modal.find('[data-cw-scan-modal-ack]').focus();
        }, 30);
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
        var preferUpload = !!($btn.data('cw-prefer-upload') ||
                              $btn.attr('data-cw-prefer-upload'));

        // ── Try camera mode first (unless user explicitly chose upload). ──
        // CWCamera.open() handles ALL failure modes with a proper UX:
        //   • insecure context  → "افتح الموقع عبر https" message + fallback button
        //   • permission denied → browser-specific recovery instructions
        //   • no camera         → "لا توجد كاميرا" + fallback
        // So we route through it when available unless the trigger explicitly
        // opts out via `data-cw-prefer-upload="1"` — the dedicated
        // "رفع من الجهاز" button uses that to jump straight to the file
        // picker (and force gallery/files mode on mobile, see below).
        if (window.CWCamera && typeof window.CWCamera.open === 'function' &&
            !preferUpload) {
            openCamera($btn);
            return;
        }

        if (!preferUpload) {
            // Implicit fallback (no live camera available) — tell the user
            // why we're switching modes. The explicit "upload" button is
            // silent because the user already knows what they asked for.
            toast('متصفحك لا يدعم الكاميرا الحيّة — سنفتح اختيار ملف بدلاً.', 'info', 4000);
        }
        openFilePicker($btn, { preferUpload: preferUpload });
    }

    function openFilePicker($btn, opts) {
        opts = opts || {};
        var $input = findFileInput($btn);
        if (!$input.length) {
            toast('لم يتم العثور على حقل رفع الملف.', 'error');
            return;
        }

        // Mobile browsers honour the `capture` attribute by jumping straight
        // to the rear camera, which defeats the purpose of the "رفع من الجهاز"
        // button. Strip the attribute for that path so the OS picker (gallery
        // + files + cloud sources) opens instead, then restore it after the
        // change event fires so the camera-fallback path keeps working.
        if (opts.preferUpload && $input.is('[capture]')) {
            var prevCapture = $input.attr('capture');
            $input.removeAttr('capture');
            $input.one('change' + NS + ' cancel' + NS, function () {
                $input.attr('capture', prevCapture);
            });
            // Safety net: some browsers don't fire `cancel`; restore on focus.
            setTimeout(function () {
                if ($input.attr('capture') == null) {
                    $input.attr('capture', prevCapture);
                }
            }, 60000);
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
        // Side bucket → hidden-input slot. ID cards keep front/back; passport
        // and license live in their own 'single' slot so a passport upload
        // doesn't clobber a previously captured ID-front image.
        var slot;
        if      (side === 'back')   slot = 'back';
        else if (side === 'single') slot = 'single';
        else                        slot = 'front';

        var name = 'WizardScan[' + slot + '_image_id]';
        var $hidden = $('input[name="' + cssEscape(name) + '"]').first();
        if (!$hidden.length) {
            $hidden = $('<input type="hidden">').attr('name', name).appendTo('body');
        }
        $hidden.val(String(imageId));
    }

    /**
     * File-input change handler — accepts one OR two files (front + back).
     * Delegates the heavy lifting to processFiles() so the same pipeline
     * is shared with the clipboard-paste path (button + Ctrl+V).
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

        // Cap to two files — anything beyond is probably a misclick.
        var files = Array.prototype.slice.call(input.files, 0, 2);
        processFiles($btn, files, function () { input.value = ''; });
    }

    /**
     * Validate + upload + apply for an array of File objects (camera, file
     * picker, or clipboard). Centralised so every entry-point shares the
     * same size/type guards, sequential upload semantics, undo snapshot
     * lifecycle, and final summary toast.
     *
     * @param {jQuery}        $btn      Trigger button (for the busy spinner).
     * @param {File[]}        files     Up to 2 files (front, back).
     * @param {Function?}     onDone    Optional cleanup (e.g. clear input).
     */
    function processFiles($btn, files, onDone) {
        if (!files || !files.length) {
            if (typeof onDone === 'function') onDone();
            return;
        }

        var maxBytes = 10 * 1024 * 1024;
        var allowed  = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            if (f.size > maxBytes) {
                toast('الملف #' + (i + 1) + ': الحجم أكبر من 10 ميجابايت.', 'error', 6000);
                if (typeof onDone === 'function') onDone();
                return;
            }
            if (f.type && allowed.indexOf(f.type) === -1) {
                toast('الملف #' + (i + 1) + ': نوع غير مدعوم — استخدم JPG / PNG / WEBP / PDF.', 'error', 6000);
                if (typeof onDone === 'function') onDone();
                return;
            }
        }

        setBusy($btn, true);
        if (files.length === 2) {
            toast('جارٍ تحليل الوجهين…', 'info', 2500);
        } else {
            toast('جارٍ تحليل الوثيقة بالذكاء الاصطناعي…', 'info', 2000);
        }

        var totalChanged     = 0;
        var totalImagesAdded = 0;
        var lastUnmapped     = {};
        var hadAnyError      = false;
        var detectedSidesSeen = {}; // 'front' | 'back' | 'single' → true
        // Reset snapshot so the first file builds a fresh undo state.
        lastSnapshot = null;

        // Run uploads sequentially so the back-side request gets the
        // already-detected issuing_body for context-aware extraction.
        //
        // ── side='auto' (no hint) on multi-file uploads ───────────────
        // The OS file picker (and clipboard) returns files in *its own*
        // order — usually alphabetical, never necessarily front-then-back
        // as the user might assume. Sending a hardcoded sideHint based on
        // array index forced the server's strict mismatch check to reject
        // the upload whenever the user picked them in the "wrong" order.
        // Letting the server auto-detect from image content (Gemini is
        // very reliable at this) eliminates that whole class of bug.
        // The response carries `side_detected` so we can still slot the
        // image and message the user appropriately.
        var seq = $.Deferred().resolve();
        files.forEach(function (file, idx) {
            seq = seq.then(function () {
                return uploadScan(file, null).done(function (resp) {
                    if (!resp || !resp.ok) {
                        hadAnyError = true;
                        var msg = (resp && resp.error) ? resp.error
                                : 'تعذّر تحليل الملف #' + (idx + 1) + ' — جرّب صورة أوضح.';
                        toast(msg, 'error', 6000);
                        return;
                    }

                    if (resp.image_id) {
                        // Single-face docs (passport=1, license=2) are stored
                        // under their own key so they don't fight the
                        // ID-front/back image slots.
                        var fileSide = (resp.side_detected === 'single')   ? 'single'
                                     : (resp.side_detected === 'back')     ? 'back'
                                     : (resp.side_detected === 'front')    ? 'front'
                                     : 'front'; // unknown → treat as primary slot
                        setScanImageRef(fileSide, resp.image_id);
                        totalImagesAdded++;
                        detectedSidesSeen[fileSide] = true;
                    }

                    if (resp.unmapped) lastUnmapped = $.extend(lastUnmapped, resp.unmapped);

                    var beforeChanged = totalChanged;
                    var thisChanged = applyServerFields(resp.fields || {}, resp.unmapped || {}, {
                        silentEmpty:   true,
                        silentSuccess: true,
                    });
                    totalChanged = beforeChanged + thisChanged;

                    // Surface server-supplied confirmation note (e.g. "تم
                    // التعرف على جواز السفر…") so the rep gets explicit
                    // feedback that the right document type was matched.
                    if (resp.note) {
                        toast(resp.note, 'info', 4000);
                    }

                    // Render any document-integrity warnings (currently:
                    // ID_FRONT_BACK_MISMATCH). These are never transient —
                    // they reflect a real discrepancy in the physical
                    // document the rep is processing and MUST be seen.
                    if (resp.warnings && resp.warnings.length) {
                        renderScanWarnings(resp.warnings);
                    }

                    if (files.length === 2 && idx === 0 && (thisChanged || resp.image_id)
                        && resp.side_detected !== 'single') {
                        var detectedLabel = (resp.side_detected === 'back')
                            ? 'الوجه الخلفي'
                            : (resp.side_detected === 'front')
                                ? 'الوجه الأمامي'
                                : 'الوجه الأول';
                        var nextLabel = (resp.side_detected === 'back')
                            ? 'الوجه الأمامي'
                            : (resp.side_detected === 'front')
                                ? 'الوجه الخلفي'
                                : 'الوجه الثاني';
                        toast('تم تحليل ' + detectedLabel + ' — جارٍ تحليل ' + nextLabel + '…', 'info', 2500);
                    }
                });
            });
        });

        seq.always(function () {
            setBusy($btn, false);
            if (typeof onDone === 'function') onDone();

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

    /* ─────────────────────────────────────────────────────────────────
     * CLIPBOARD PASTE
     * Two entry points share the same processFiles() pipeline:
     *   1. The "لصق من الحافظة" button  → navigator.clipboard.read()
     *   2. Anywhere on Step 1 via Ctrl+V → ClipboardEvent.clipboardData
     * Both paths are scoped so they don't fire while the user is pasting
     * into a normal text input/textarea (we honour native paste there).
     * ─────────────────────────────────────────────────────────────────*/

    /**
     * True when the active wizard section actually contains an identity
     * scan trigger. Keeps the global Ctrl+V listener inert on every other
     * step (employment, addresses, review) so we never hijack the user's
     * normal paste behaviour outside step 1.
     */
    function pasteSinkAvailable() {
        return $('section.cw-section--active [data-cw-action="scan-identity"]').length > 0;
    }

    /**
     * Resolve the trigger button to use for spinner/aria when paste arrives
     * from a global Ctrl+V (no source element to bubble from).
     */
    function activePasteTrigger() {
        return $('section.cw-section--active [data-cw-action="scan-identity"]').first();
    }

    /**
     * "لصق من الحافظة" button — uses the modern Async Clipboard API.
     * Falls back to a friendly hint when the browser blocks programmatic
     * read or the user hasn't granted clipboard permission yet (very
     * common on http://, where the API is gated to user-gesture only).
     */
    function onPasteClick(e) {
        e.preventDefault();
        var $btn = $(this);

        if (!navigator.clipboard || typeof navigator.clipboard.read !== 'function') {
            toast('متصفحك لا يدعم القراءة من الحافظة — اضغط Ctrl+V لإلصاق الصورة مباشرة.', 'info', 6000);
            return;
        }

        navigator.clipboard.read().then(function (clipItems) {
            var pending = [];
            (clipItems || []).forEach(function (item) {
                var imgType = (item.types || []).find(function (t) {
                    return t === 'image/png'
                        || t === 'image/jpeg'
                        || t === 'image/webp';
                });
                if (!imgType) return;
                pending.push(item.getType(imgType).then(function (blob) {
                    var ext = imgType.split('/')[1] || 'png';
                    return new File([blob],
                        'clipboard_' + Date.now() + '_' + pending.length + '.' + ext,
                        { type: imgType });
                }));
            });

            if (!pending.length) {
                toast('لا توجد صورة في الحافظة — انسخ صورة الهوية ثم أعد المحاولة.', 'warning', 5000);
                return;
            }

            Promise.all(pending).then(function (files) {
                processFiles($btn, files.slice(0, 2));
            });
        }).catch(function () {
            toast('تعذّر الوصول إلى الحافظة — اضغط Ctrl+V لإلصاق الصورة، أو امنح الإذن من إعدادات المتصفح.',
                  'warning', 6500);
        });
    }

    /**
     * Global Ctrl+V handler. Only consumes the event when:
     *   • The active wizard step actually has a scan trigger (step 1), AND
     *   • The clipboard payload contains at least one image file, AND
     *   • The paste did NOT originate from an editable text surface
     *     (input/textarea/contenteditable) — otherwise the user is
     *     probably pasting an Arabic name, not a screenshot.
     */
    function onGlobalPaste(e) {
        if (!pasteSinkAvailable()) return;

        var target = e.target || document.activeElement;
        if (target) {
            var tag = (target.tagName || '').toLowerCase();
            var isEditable = tag === 'input' || tag === 'textarea' || target.isContentEditable;
            if (isEditable) {
                // Honour native paste into text fields. The exception is
                // when the clipboard *only* contains files (no text):
                // pasting a screenshot while focus happens to be in a
                // field shouldn't be silently swallowed.
                var cd = e.originalEvent && e.originalEvent.clipboardData;
                var hasText = cd && (cd.getData('text/plain') || cd.getData('text/html'));
                if (hasText) return;
            }
        }

        var clip = e.originalEvent && e.originalEvent.clipboardData;
        if (!clip || !clip.items) return;

        var files = [];
        for (var i = 0; i < clip.items.length; i++) {
            var it = clip.items[i];
            if (it.kind === 'file' && /^image\/(png|jpeg|webp)$/.test(it.type || '')) {
                var f = it.getAsFile();
                if (f) files.push(f);
            }
        }

        if (!files.length) return;

        e.preventDefault();
        toast('تم لصق ' + files.length + ' صورة من الحافظة — جارٍ التحليل…', 'info', 2500);
        processFiles(activePasteTrigger(), files.slice(0, 2));
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

        $doc.off('click' + NS, '[data-cw-action="scan-paste"]')
            .on('click' + NS, '[data-cw-action="scan-paste"]', onPasteClick);

        // Bind the global paste handler exactly once (rebinding on every
        // step re-render would multiply firings).
        if (!bind._pasteBound) {
            $doc.on('paste' + NS, onGlobalPaste);
            bind._pasteBound = true;
        }
    }

    /* Init: bind on DOM ready + on every step render so freshly inserted
     * partials stay wired without needing core.js cooperation. */
    $(function () {
        bind();
        $(document).on('cw:step:rendered cw:step:changed', bind);
    });

})(window.jQuery, window);
