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
            // Forward: validate first; only advance on success.
            CW.savePartial({ validate: true })
                .then(function (res) {
                    if (res && res.ok) {
                        state.completed[state.current] = true;
                        switchTo(n);
                    } else if (res && res.errors) {
                        renderServerErrors(res.errors);
                        CW.toast('يرجى تصحيح الحقول قبل المتابعة.', 'error');
                    }
                }, function () {
                    CW.toast('تعذّر الحفظ — تحقق من الاتصال وأعد المحاولة.', 'error');
                });
        } else {
            // Backward: free movement, but still save current state silently.
            CW.savePartial();
            switchTo(n);
        }
    };

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
        // Native <button> handles Enter/Space; no extra bindings needed.
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
    }

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

            if (type === 'checkbox') {
                if (/\[\]$/.test(name)) {
                    if (this.checked) (data[name] = data[name] || []).push($el.val());
                } else {
                    data[name] = this.checked ? ($el.val() || '1') : '';
                }
            } else if (type === 'radio') {
                if (this.checked) data[name] = $el.val();
                else if (!(name in data)) data[name] = '';
            } else if (type === 'file') {
                /* file inputs uploaded out-of-band */
            } else if (this.tagName === 'SELECT' && this.multiple) {
                data[name] = $el.val() || [];
            } else if (/\[\]$/.test(name)) {
                (data[name] = data[name] || []).push($el.val());
            } else {
                data[name] = $el.val();
            }
        });
        return data;
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
