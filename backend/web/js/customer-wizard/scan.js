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
            var $el = $('[name="' + cssEscape(key) + '"]').first();
            if ($el.length) snap[key] = $el.val();
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

            var $el = $('[name="' + cssEscape(name) + '"]').first();
            if (!$el.length) continue;

            var current = String($el.val() || '').trim();
            var next = String(value).trim();
            if (current === next) continue;

            $el.val(next).trigger('change').trigger('input');

            // Visual cue: brief highlight on the field's wrapper.
            var $wrap = $el.closest('[data-cw-field]');
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
            var $el = $('[name="' + cssEscape(name) + '"]').first();
            if ($el.length) {
                $el.val(snap[name] || '').trigger('change').trigger('input');
                restored++;
            }
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
    function uploadScan(file) {
        var fd = new FormData();
        fd.append('file', file, file.name);
        fd.append('_csrf-backend', csrfToken());

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
     * Main click handler — bound once at module load (idempotent via NS).
     */
    function onScanClick(e) {
        e.preventDefault();
        var $btn   = $(this);
        var $input = $btn.closest('.cw-card__header').siblings('input[data-cw-role="scan-input"]')
                       .add($btn.siblings('input[data-cw-role="scan-input"]'))
                       .first();

        // Fallback: search the whole step section if siblings traversal misses.
        if (!$input.length) {
            $input = $btn.closest('[data-cw-section]')
                         .find('input[data-cw-role="scan-input"]')
                         .first();
        }
        if (!$input.length) {
            toast('لم يتم العثور على حقل رفع الملف.', 'error');
            return;
        }
        $input.trigger('click');
    }

    /**
     * File-input change handler — fires once a file is picked.
     */
    function onFileChange(e) {
        var input = e.target;
        if (!input.files || !input.files.length) return;

        var file = input.files[0];
        var $btn = $(this).closest('.cw-card__header').find('[data-cw-action="scan-identity"]')
                     .add($(this).siblings('[data-cw-action="scan-identity"]'))
                     .first();

        if (!$btn.length) {
            $btn = $(this).closest('[data-cw-section]').find('[data-cw-action="scan-identity"]').first();
        }

        // Light client-side guard rails (server is authoritative).
        var maxBytes = 10 * 1024 * 1024;
        var allowed  = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (file.size > maxBytes) {
            toast('حجم الملف أكبر من 10 ميجابايت.', 'error');
            input.value = '';
            return;
        }
        if (file.type && allowed.indexOf(file.type) === -1) {
            toast('نوع الملف غير مدعوم — استخدم JPG / PNG / WEBP / PDF.', 'error');
            input.value = '';
            return;
        }

        setBusy($btn, true);
        toast('جارٍ تحليل الوثيقة بالذكاء الاصطناعي…', 'info', 2000);

        uploadScan(file)
            .done(function (resp) {
                if (!resp || !resp.ok) {
                    var msg = (resp && resp.error) ? resp.error
                            : 'تعذّر تحليل الوثيقة — جرّب صورة أوضح.';
                    toast(msg, 'error', 6000);
                    return;
                }

                var fields = resp.fields || {};
                if (!Object.keys(fields).length) {
                    toast('لم يتمكّن النظام من استخراج بيانات قابلة للاستخدام.', 'warning', 5000);
                    return;
                }

                lastSnapshot = snapshot(fields);
                var changedCount = applyFields(fields);

                if (!changedCount) {
                    toast('البيانات المُستخرَجة مطابقة للحقول الحالية.', 'info', 4000);
                    return;
                }

                // Build success message + surface any unmapped lookups.
                var msg = 'تمّ ملء ' + changedCount + ' حقلاً تلقائياً.';
                var unmapped = resp.unmapped || {};
                var hints = [];
                if (unmapped.city)    hints.push('مدينة الولادة: «' + unmapped.city + '»');
                if (unmapped.citizen) hints.push('الجنسية: «' + unmapped.citizen + '»');
                if (hints.length) {
                    msg += ' — يرجى اختيار يدوياً: ' + hints.join('، ') + '.';
                }
                toast(msg, 'success', 7000);
            })
            .fail(function (xhr) {
                var serverMsg = '';
                try {
                    var j = xhr && xhr.responseJSON;
                    if (j && j.error) serverMsg = j.error;
                } catch (_) { /* noop */ }
                toast(serverMsg || 'فشل الاتصال بخدمة المسح — تحقّق من الإنترنت.', 'error', 6000);
            })
            .always(function () {
                setBusy($btn, false);
                input.value = '';     // allow re-selecting the same file
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
