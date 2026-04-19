/**
 * Customer Wizard V2 — Core (navigation, AJAX save, toasts, a11y).
 *
 * Design principles:
 *   • Server is the single source of truth — every step transition saves.
 *   • Client never holds canonical state; it just collects input and POSTs.
 *   • All UI updates respect prefers-reduced-motion and keyboard navigation.
 *   • Idempotent: safe to re-init on the same DOM (handlers are namespaced).
 *   • Save requests are serialized via a small promise queue (no thrash).
 *
 * Public surface (window.CW):
 *   CW.init(opts)      → bind events, mount stepper, set initial step.
 *   CW.goTo(n)         → navigate to step n (saves current first).
 *   CW.next() / .prev()
 *   CW.toast(msg, type, ttl?)
 *   CW.savePartial({validate?})
 *   CW.destroy()       → unbind everything (for SPA-style hot swaps).
 */
(function (window, $) {
    'use strict';

    if (!$) {
        if (window.console) console.error('[CW] jQuery is required.');
        return;
    }

    var CW = window.CW = window.CW || {};
    var EVT_NS = '.cw';

    // ─── State ──────────────────────────────────────────────────────────────
    var state = {
        urls:        {},
        totalSteps:  4,
        current:     1,
        completed:   {},
        dirty:       false,
        autosaveTm:  null,
        saveQueue:   $.Deferred().resolve().promise(),
        $shell:      null,
        $stepper:    null,
        $sections:   null,
        $statusPill: null,
        $announcer:  null,
        initialized: false,
    };

    // ─── Bootstrapping ──────────────────────────────────────────────────────
    CW.init = function (opts) {
        opts = opts || {};

        // Idempotent re-init: tear down previous bindings first.
        if (state.initialized) CW.destroy();

        state.urls       = $.extend({}, opts.urls || {});
        // Expose URLs to sibling modules (scan.js, fields.js, ...) so they
        // can resolve endpoints without re-reading the markup.
        CW._urls = state.urls;
        state.totalSteps = opts.totalSteps || 4;
        state.current    = clampStep(opts.currentStep || 1);
        state.completed  = {};
        state.dirty      = false;

        // Mode (create | edit) + the customer being edited. Sibling modules
        // (fahras.js, review.js, …) read these to short-circuit create-only
        // behaviour when the user is updating an existing record.
        CW.mode       = (opts.mode === 'edit') ? 'edit' : 'create';
        CW.customerId = parseInt(opts.customerId, 10) || 0;

        state.$shell      = $(opts.shellSelector || '#cw-shell');
        if (!state.$shell.length) return;
        state.$stepper    = state.$shell.find('[data-cw-stepper]');
        state.$sections   = state.$shell.find('[data-cw-section]');
        state.$statusPill = state.$shell.find('[data-cw-status]');
        state.$announcer  = state.$shell.find('[data-cw-announcer]');

        bindStepperClicks();
        bindNavButtons();
        bindAutoSave();
        bindKeyboard();
        bindBeforeUnload();

        renderStepper();
        renderNavMode(state.current);
        showSection(state.current, false);

        state.initialized = true;
    };

    CW.destroy = function () {
        if (!state.initialized) return;
        $(document).off(EVT_NS);
        $(window).off(EVT_NS);
        if (state.$shell) state.$shell.off(EVT_NS);
        clearTimeout(state.autosaveTm);
        state.initialized = false;
    };

    // ─── Navigation ─────────────────────────────────────────────────────────
    CW.goTo = function (n) {
        n = clampStep(n);
        if (n === state.current) return;

        if (n > state.current) {
            // Forward: validate first; only advance on success. The validate
            // endpoint does NOT persist, so chain a real save() afterwards
            // before advancing — otherwise the destination step (especially
            // the read-only review at step 4) would render against a stale
            // server-side draft.
            CW.savePartial({ validate: true })
                .then(function (res) {
                    if (res && res.ok) {
                        state.completed[state.current] = true;
                        CW.savePartial().always(function () { advanceTo(n); });
                    } else if (res && res.errors) {
                        renderServerErrors(res.errors);
                        CW.toast('يرجى تصحيح الحقول قبل المتابعة.', 'error');
                    }
                }, function () {
                    CW.toast('تعذّر الحفظ — تحقق من الاتصال وأعد المحاولة.', 'error');
                });
        } else {
            // Backward: free movement, but still save current state silently.
            CW.savePartial().always(function () { advanceTo(n); });
        }
    };

    /**
     * Switch to step `n`, re-fetching the partial from the server first for
     * read-only review steps so it reflects the latest persisted draft.
     * Input-bearing steps already have their state in the live DOM, so we
     * skip the round-trip there to avoid wiping unsaved local edits.
     */
    function advanceTo(n) {
        if (isReviewStep(n)) {
            refetchStep(n).always(function () { switchTo(n); });
        } else {
            switchTo(n);
        }
    }

    function isReviewStep(n) {
        // Step 4 today is the only review/recap surface — it has no editable
        // inputs, just a server-rendered snapshot of the draft.
        return n === state.totalSteps;
    }

    /**
     * Re-render a step's section by fetching its partial HTML from the
     * server. The outer <section data-cw-section> wrapper stays in place so
     * focus, hidden/inert toggling and aria-label keep working.
     */
    function refetchStep(n) {
        var $section = state.$sections.filter('[data-cw-section="' + n + '"]');
        if (!$section.length || !state.urls.step) {
            return $.Deferred().resolve().promise();
        }
        return $.ajax({
            url: state.urls.step,
            method: 'GET',
            data: { n: n },
            dataType: 'html',
            timeout: 15000,
        }).done(function (html) {
            $section.html(html);
            $(document).trigger('cw:step:rendered', [{ n: n, $section: $section }]);
        }).fail(function () {
            CW.toast('تعذّر تحديث شاشة المراجعة — جرّب إعادة التحميل.', 'warning');
        });
    }

    CW.next = function () { CW.goTo(state.current + 1); };
    CW.prev = function () { CW.goTo(state.current - 1); };

    // ─── Persistence (queued — never two saves in flight) ────────────────────
    CW.savePartial = function (opts) {
        opts = opts || {};
        var snapshotStep = state.current;
        var payload = {
            step: snapshotStep,
            data: collectStepData(snapshotStep),
        };
        var csrfParam = $('meta[name="csrf-param"]').attr('content') || '_csrf-backend';
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        if (csrfToken) payload[csrfParam] = csrfToken;

        var dfd = $.Deferred();
        // Chain onto previous save so we never overlap.
        state.saveQueue = state.saveQueue.then(function () {
            setStatus('saving');
            return $.ajax({
                url: opts.validate ? state.urls.validate : state.urls.save,
                method: 'POST',
                data: payload,
                dataType: 'json',
                timeout: 15000,
            }).then(function (res) {
                if (res && res.ok) {
                    state.dirty = false;
                    setStatus('saved');
                } else {
                    setStatus('error');
                }
                dfd.resolve(res);
                return res;
            }, function (xhr, textStatus) {
                setStatus('error');
                dfd.reject(xhr, textStatus);
                // Don't break the chain — return a resolved promise.
                return $.Deferred().resolve().promise();
            });
        });
        return dfd.promise();
    };

    // ─── UX helpers ─────────────────────────────────────────────────────────
    CW.toast = function (message, type, ttl) {
        type = type || 'info';
        ttl  = (typeof ttl === 'number') ? ttl : 4500;

        var $host = $('.cw-toast-host');
        if (!$host.length) {
            // Fallback if layout didn't pre-mount the host.
            $host = $('<div class="cw-toast-host" role="region" aria-live="polite" aria-label="إشعارات النظام"></div>')
                .appendTo(document.body);
        }

        var iconMap = {
            info:    'fa-info-circle',
            success: 'fa-check-circle',
            warning: 'fa-exclamation-triangle',
            error:   'fa-times-circle'
        };

        var $toast = $('<div class="cw-toast" role="alert"></div>')
            .addClass('cw-toast--' + type)
            .append($('<i class="fa" aria-hidden="true"></i>').addClass(iconMap[type] || iconMap.info))
            .append($('<span></span>').text(String(message)));
        $host.append($toast);

        setTimeout(function () {
            $toast.css({ transition: 'opacity 200ms ease', opacity: 0 });
            setTimeout(function () { $toast.remove(); }, 220);
        }, ttl);
    };

    // ─── Internals ──────────────────────────────────────────────────────────

    function clampStep(n) {
        n = parseInt(n, 10) || 1;
        if (n < 1) n = 1;
        if (n > state.totalSteps) n = state.totalSteps;
        return n;
    }

    function bindStepperClicks() {
        // Use event delegation namespaced for clean teardown.
        state.$stepper.on('click' + EVT_NS, '[data-cw-step]', function (e) {
            e.preventDefault();
            var n = parseInt($(this).attr('data-cw-step'), 10);
            CW.goTo(n);
        });
        // Also honor [data-cw-step] anywhere inside the shell — Step 4's
        // "Edit" buttons use this to jump back to a specific step.
        state.$shell.on('click' + EVT_NS, 'button[data-cw-step], a[data-cw-step]', function (e) {
            // Skip if it lives inside the stepper (already handled above).
            if ($(this).closest('[data-cw-stepper]').length) return;
            e.preventDefault();
            var n = parseInt($(this).attr('data-cw-step'), 10);
            CW.goTo(n);
        });
    }

    function bindNavButtons() {
        state.$shell.on('click' + EVT_NS, '[data-cw-action="next"]',  function (e) { e.preventDefault(); CW.next(); });
        state.$shell.on('click' + EVT_NS, '[data-cw-action="prev"]',  function (e) { e.preventDefault(); CW.prev(); });
        state.$shell.on('click' + EVT_NS, '[data-cw-action="save-draft"]', function (e) {
            e.preventDefault();
            CW.savePartial().then(function (res) {
                if (res && res.ok) CW.toast('تم حفظ المسودة بنجاح.', 'success');
            });
        });
        state.$shell.on('click' + EVT_NS, '[data-cw-action="discard"]', function (e) {
            e.preventDefault();
            if (!window.confirm('هل تريد إلغاء المسودة وبدء عميل جديد من الصفر؟ سيتم فقدان البيانات الحالية.')) return;
            $.ajax({
                url: state.urls.discard,
                method: 'POST',
                data: csrfPayload(),
            }).always(function () {
                window.location.href = state.urls.start;
            });
        });
        state.$shell.on('click' + EVT_NS, '[data-cw-action="finish"]', function (e) {
            e.preventDefault();
            var $btn = $(this);
            CW.finalize($btn);
        });
    }

    /**
     * Final commit — POST the draft to the server, which validates ALL
     * steps once more, creates the Customer + sub-rows in a transaction,
     * adopts orphan scan-Media rows, and clears the draft.
     *
     * On success: navigate to the redirect URL the server returned (the
     * legacy "create-summary" page so the user can immediately start a
     * contract for the brand-new customer).
     *
     * On validation failure: jump to the first step that has errors so the
     * user can fix them in context — never bury an error on a step that's
     * not currently visible.
     */
    CW.finalize = function ($btn) {
        $btn = $btn || state.$shell.find('[data-cw-action="finish"]').first();

        // First, save current step data (Step 4 has no inputs but we still
        // honor the contract — and doing so flushes any in-flight edit).
        CW.savePartial().always(function () {
            // Disable button + spinner while we wait for the server.
            var origHtml = $btn.html();
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> <span>جاري الاعتماد…</span>');

            $.ajax({
                url: state.urls.finish,
                method: 'POST',
                data: csrfPayload(),
                dataType: 'json',
                timeout: 30000,
            }).done(function (res) {
                if (res && res.ok) {
                    state.dirty = false;
                    // Disarm the beforeunload guard — we're navigating intentionally.
                    $(window).off('beforeunload' + EVT_NS);
                    CW.toast(res.message || 'تم اعتماد العميل بنجاح.', 'success', 2500);
                    setTimeout(function () {
                        window.location.href = res.redirect || state.urls.start;
                    }, 600);
                    return;
                }

                // Validation failures — jump to the first step that complains.
                if (res && res.errors) {
                    var firstStep = null;
                    Object.keys(res.errors).some(function (k) {
                        var m = /^step(\d+)$/.exec(k);
                        if (m) { firstStep = parseInt(m[1], 10); return true; }
                        return false;
                    });
                    if (firstStep && firstStep !== state.current) {
                        CW.toast(
                            (res.error || 'يرجى تصحيح بعض الحقول') +
                            ' — انتقلنا إلى الخطوة ' + firstStep + '.',
                            'error', 6000
                        );
                        switchTo(firstStep);
                        // Render the per-step errors after the section is visible.
                        setTimeout(function () {
                            renderServerErrors(res.errors['step' + firstStep] || {});
                        }, 80);
                    } else {
                        renderServerErrors(res.errors['step' + state.current] || {});
                        CW.toast(res.error || 'تعذّر اعتماد العميل.', 'error');
                    }
                } else {
                    CW.toast((res && res.error) || 'تعذّر اعتماد العميل — حاول مجدداً.', 'error');
                }
                $btn.prop('disabled', false).html(origHtml);
            }).fail(function () {
                CW.toast('تعذّر الاتصال بالخادم — تحقّق من الإنترنت وأعد المحاولة.', 'error');
                $btn.prop('disabled', false).html(origHtml);
            });
        });
    };

    function bindAutoSave() {
        state.$shell.on('input' + EVT_NS + ' change' + EVT_NS, '[data-cw-section] :input', function () {
            state.dirty = true;
            setStatus('dirty');
            scheduleAutosave();
        });
    }

    function scheduleAutosave() {
        clearTimeout(state.autosaveTm);
        state.autosaveTm = setTimeout(function () {
            if (state.dirty) CW.savePartial();
        }, 1500);
    }

    function bindKeyboard() {
        // SCOPED keyboard shortcuts — only when focus is inside the wizard
        // shell. We DON'T grab Alt+Arrow globally (would hijack browser
        // back/forward, violating WCAG 2.1.4 Character Key Shortcuts).
        // Ctrl+Alt+N / Ctrl+Alt+P are safe combos that no browser uses.
        state.$shell.on('keydown' + EVT_NS, function (e) {
            if (!(e.ctrlKey && e.altKey)) return;
            var key = (e.key || '').toLowerCase();
            if (key === 'n' || key === 'arrowleft') { e.preventDefault(); CW.next(); }
            if (key === 'p' || key === 'arrowright') { e.preventDefault(); CW.prev(); }
            if (key === 's') { e.preventDefault(); state.$shell.find('[data-cw-action="save-draft"]').first().click(); }
        });
    }

    function bindBeforeUnload() {
        $(window).on('beforeunload' + EVT_NS, function (e) {
            if (state.dirty) {
                // Modern browsers ignore custom strings; just non-empty triggers prompt.
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });
    }

    function switchTo(n) {
        state.current = n;
        showSection(n, true);
        renderStepper();
        renderNavMode(n);
        announce('انتقلت إلى الخطوة ' + n + ' من ' + state.totalSteps + '.');

        var $target = state.$sections.filter('[data-cw-section="' + n + '"]');
        // Move focus to the section itself (it has tabindex=-1 + aria-label
        // describing the step) so screen readers announce the step on entry.
        if ($target.length) {
            $target.focus();
        }

        // Notify other modules (CWFields, future widgets) that a new partial
        // is now visible so they can re-bind their per-field enhancements.
        $(document).trigger('cw:step:changed', [{ n: n, $section: $target }]);
        // Smooth scroll to top of shell.
        var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var top = state.$shell.offset().top - 80;
        if (prefersReduced) {
            window.scrollTo(0, Math.max(0, top));
        } else {
            $('html, body').stop().animate({ scrollTop: top }, 250);
        }
    }

    function showSection(n, animate) {
        state.$sections.each(function () {
            var $s = $(this);
            var sn = parseInt($s.attr('data-cw-section'), 10);
            if (sn === n) {
                $s.removeAttr('hidden').removeAttr('inert').addClass('cw-section--active');
            } else {
                $s.attr('hidden', '').attr('inert', '').removeClass('cw-section--active');
            }
        });
        if (!animate) {
            var $target = state.$sections.filter('[data-cw-section="' + n + '"]');
            if ($target.length) {
                $target.css('animation', 'none');
                void $target[0].offsetWidth; // force reflow
                $target.css('animation', '');
            }
        }
    }

    /**
     * Toggle the toolbar's "Next" button vs the in-page Finish flow.
     * On the last step the Next button is hidden — the user commits via
     * the big "اعتماد العميل" CTA inside the review partial.
     */
    function renderNavMode(n) {
        var isLast = (n >= state.totalSteps);
        var $next  = state.$shell.find('[data-cw-action="next"]');
        if (isLast) {
            $next.attr('hidden', '').prop('disabled', true);
        } else {
            $next.removeAttr('hidden').prop('disabled', false);
        }
    }

    function renderStepper() {
        state.$stepper.find('[data-cw-step]').each(function () {
            var $s   = $(this);
            var n    = parseInt($s.attr('data-cw-step'), 10);
            var isCur  = (n === state.current);
            var isDone = !!state.completed[n] && n !== state.current;
            $s.toggleClass('cw-step--done', isDone);
            if (isCur) {
                $s.attr('aria-current', 'step');
            } else {
                $s.removeAttr('aria-current');
            }
        });
    }

    var STATUS_MAP = {
        saving: { cls: 'cw-pill--info',    icon: 'fa-spinner fa-spin', text: 'جاري الحفظ…' },
        saved:  { cls: 'cw-pill--saved',   icon: 'fa-check',           text: 'محفوظ' },
        dirty:  { cls: 'cw-pill--warning', icon: 'fa-pencil',          text: 'تغييرات غير محفوظة' },
        error:  { cls: 'cw-pill--error',   icon: 'fa-exclamation',     text: 'فشل الحفظ' },
        ready:  { cls: '',                 icon: 'fa-cloud',           text: 'جاهز' },
    };

    function setStatus(name) {
        if (!state.$statusPill || !state.$statusPill.length) return;
        var s = STATUS_MAP[name] || STATUS_MAP.ready;
        // Build via DOM (no innerHTML) to keep XSS-safe even if STATUS_MAP grows.
        var $icon = $('<i class="fa" aria-hidden="true"></i>').addClass(s.icon);
        var $txt  = $('<span></span>').text(s.text);
        state.$statusPill
            .removeClass('cw-pill--info cw-pill--saved cw-pill--warning cw-pill--error')
            .addClass(s.cls)
            .empty()
            .append($icon)
            .append(' ')
            .append($txt);
    }

    function announce(msg) {
        if (!state.$announcer || !state.$announcer.length) return;
        // Clear first so identical messages re-announce.
        state.$announcer.text('');
        setTimeout(function () { state.$announcer.text(msg); }, 80);
    }

    /** Collect every input inside the active section. Handles arrays
     *  (`name="foo[]"`), <select multiple>, checkboxes, radios. */
    function collectStepData(n) {
        var $section = state.$sections.filter('[data-cw-section="' + n + '"]');
        if (!$section.length) return {};
        var data = {};
        $section.find(':input[name]').each(function () {
            var $el  = $(this);
            var name = $el.attr('name');
            if (!name) return;
            var type = ((this.type || '') + '').toLowerCase();
            var value;

            if (type === 'checkbox') {
                if (/\[\]$/.test(name)) {
                    if (!this.checked) return;
                    value = $el.val();
                } else {
                    value = this.checked ? ($el.val() || '1') : '';
                }
            } else if (type === 'radio') {
                if (this.checked) {
                    value = $el.val();
                } else {
                    // Initialize the slot to '' so an empty group still
                    // shows up in validation, but only if no sibling has
                    // already filled it.
                    if (!hasNestedKey(data, name)) {
                        setNestedValue(data, name, '');
                    }
                    return;
                }
            } else if (type === 'file') {
                /* file inputs uploaded out-of-band */
                return;
            } else if (this.tagName === 'SELECT' && this.multiple) {
                value = $el.val() || [];
            } else {
                value = $el.val();
            }

            setNestedValue(data, name, value);
        });
        return data;
    }

    /**
     * Convert a Yii-style field name like "Customers[name]" or
     * "Customers[addresses][0][street]" into a nested write on the
     * destination object — so jQuery's $.param produces the bracket
     * notation that PHP's $_POST parser reconstructs to the same
     * nested arrays the controller expects.
     *
     * Names ending with "[]" become array pushes.
     */
    function setNestedValue(target, fieldName, value) {
        var parts = parseBrackets(fieldName);
        if (!parts.length) return;

        // Trailing "[]" → push to array at the parent.
        var pushToArray = false;
        if (parts[parts.length - 1] === '') {
            pushToArray = true;
            parts.pop();
        }

        var node = target;
        for (var i = 0; i < parts.length - 1; i++) {
            var key = parts[i];
            if (node[key] === undefined || node[key] === null ||
                typeof node[key] !== 'object') {
                node[key] = {};
            }
            node = node[key];
        }
        var last = parts[parts.length - 1];
        if (pushToArray) {
            if (!Array.isArray(node[last])) node[last] = [];
            node[last].push(value);
        } else {
            node[last] = value;
        }
    }

    /** Same traversal as setNestedValue but read-only; returns true if path set. */
    function hasNestedKey(target, fieldName) {
        var parts = parseBrackets(fieldName);
        if (!parts.length) return false;
        if (parts[parts.length - 1] === '') parts.pop();
        var node = target;
        for (var i = 0; i < parts.length; i++) {
            if (!node || typeof node !== 'object') return false;
            if (!Object.prototype.hasOwnProperty.call(node, parts[i])) return false;
            node = node[parts[i]];
        }
        return true;
    }

    /** "Customers[name]"      → ["Customers", "name"]
     *  "Customers[addr][0][s]" → ["Customers", "addr", "0", "s"]
     *  "tags[]"               → ["tags", ""]                                  */
    function parseBrackets(fieldName) {
        var out = [];
        var firstBracket = fieldName.indexOf('[');
        if (firstBracket === -1) return [fieldName];

        out.push(fieldName.slice(0, firstBracket));
        var rest = fieldName.slice(firstBracket);
        // Match every [...] segment, including [] (empty for array push).
        var re = /\[([^\]]*)\]/g, m;
        while ((m = re.exec(rest)) !== null) {
            out.push(m[1]);
        }
        return out;
    }

    function renderServerErrors(errors) {
        state.$shell.find('.cw-field--error').removeClass('cw-field--error');
        state.$shell.find('.cw-field__error-msg').remove();

        var firstName = null;
        Object.keys(errors).forEach(function (key) {
            if (!firstName) firstName = key;
            var sel = '[name="' + cssEscape(key) + '"]';
            var $input = state.$shell.find(sel).first();
            if (!$input.length) return;
            var $field = $input.closest('.cw-field, .form-group');
            if (!$field.length) $field = $input.parent();
            $field.addClass('cw-field--error');
            $field.append(
                $('<div class="cw-field__error-msg" role="alert"></div>').text(errors[key])
            );
            $input.attr('aria-invalid', 'true');
        });

        if (firstName) {
            var $first = state.$shell.find('[name="' + cssEscape(firstName) + '"]').first();
            if ($first.length) setTimeout(function () { $first.focus(); }, 100);
        }
    }

    function csrfPayload() {
        var p = {};
        var name = $('meta[name="csrf-param"]').attr('content') || '_csrf-backend';
        var tok  = $('meta[name="csrf-token"]').attr('content');
        if (tok) p[name] = tok;
        return p;
    }

    /** Minimal CSS.escape polyfill for safe attribute selectors. */
    function cssEscape(s) {
        if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(s);
        return String(s).replace(/(["'\\\[\]\.\#\(\)\:])/g, '\\$1');
    }

})(window, window.jQuery);
