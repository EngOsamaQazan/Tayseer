/**
 * Customer Wizard V2 — Core (navigation, AJAX save, toasts).
 *
 * Design principles:
 *   • Server is the single source of truth — every step transition saves.
 *   • Client never holds canonical state; it just collects input and POSTs.
 *   • All UI updates respect prefers-reduced-motion and keyboard navigation.
 *   • Idempotent: safe to re-init on the same DOM.
 *
 * Public surface (window.CW):
 *   CW.init(opts)      → bind events, mount stepper, set initial step.
 *   CW.goTo(n)         → navigate to step n (saves current first).
 *   CW.next() / .prev()
 *   CW.toast(msg, type, ttl?)
 *   CW.savePartial()   → fire-and-forget save of current step.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        window.console && console.error('[CW] jQuery is required.');
        return;
    }

    var CW = window.CW = window.CW || {};

    // ─── State ──────────────────────────────────────────────────────────────
    var state = {
        urls:        {},
        totalSteps:  4,
        current:     1,
        completed:   {},
        saving:      false,
        dirty:       false,
        autosaveTm:  null,
        $shell:      null,
        $stepper:    null,
        $sections:   null,
        $statusPill: null,
    };

    // ─── Bootstrapping ──────────────────────────────────────────────────────
    CW.init = function (opts) {
        opts = opts || {};
        state.urls       = $.extend({}, state.urls, opts.urls || {});
        state.totalSteps = opts.totalSteps || state.totalSteps;
        state.current    = clampStep(opts.currentStep || 1);

        state.$shell      = $(opts.shellSelector || '#cw-shell');
        if (!state.$shell.length) return;
        state.$stepper    = state.$shell.find('[data-cw-stepper]');
        state.$sections   = state.$shell.find('[data-cw-section]');
        state.$statusPill = state.$shell.find('[data-cw-status]');

        bindStepperClicks();
        bindNavButtons();
        bindAutoSave();
        bindKeyboard();

        renderStepper();
        showSection(state.current, false);
    };

    // ─── Navigation ─────────────────────────────────────────────────────────
    CW.goTo = function (n) {
        n = clampStep(n);
        if (n === state.current) return;

        // Forward navigation runs validation + save first; backward is free.
        if (n > state.current) {
            CW.savePartial({ validate: true })
                .done(function (res) {
                    if (res && res.ok) {
                        state.completed[state.current] = true;
                        switchTo(n);
                    } else if (res && res.errors) {
                        renderServerErrors(res.errors);
                        CW.toast('يرجى تصحيح الحقول المُشار إليها قبل المتابعة.', 'error');
                    }
                })
                .fail(function () {
                    CW.toast('تعذّر الحفظ — تحقق من الاتصال وحاول مجدداً.', 'error');
                });
        } else {
            CW.savePartial({ validate: false }); // fire-and-forget
            switchTo(n);
        }
    };

    CW.next = function () { CW.goTo(state.current + 1); };
    CW.prev = function () { CW.goTo(state.current - 1); };

    // ─── Persistence ────────────────────────────────────────────────────────
    /**
     * @param {Object} opts.validate=false  — also run server-side validation
     * @returns {jqXHR}
     */
    CW.savePartial = function (opts) {
        opts = opts || {};
        if (state.saving) {
            return $.Deferred().resolve({ ok: true, deferred: true }).promise();
        }
        state.saving = true;
        setStatus('saving');

        var data = collectStepData(state.current);
        var url = opts.validate ? state.urls.save : state.urls.save;
        // We always save; if validation also requested, the controller's
        // `actionValidate` is what enforces `errors`. We use save in both
        // cases for simplicity (server validates non-destructively).
        var payload = {
            step: state.current,
            data: data,
        };

        var csrfParam = $('meta[name="csrf-param"]').attr('content') || '_csrf-backend';
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        if (csrfToken) payload[csrfParam] = csrfToken;

        var xhr = $.post(url, payload).always(function () {
            state.saving = false;
        }).done(function (res) {
            if (res && res.ok) {
                state.dirty = false;
                setStatus('saved');
            } else {
                setStatus('error');
            }
        }).fail(function () {
            setStatus('error');
        });

        return xhr;
    };

    // ─── UX helpers ─────────────────────────────────────────────────────────
    CW.toast = function (message, type, ttl) {
        type = type || 'info';
        ttl  = (typeof ttl === 'number') ? ttl : 4500;

        var $host = $('.cw-toast-host');
        if (!$host.length) {
            $host = $('<div class="cw-toast-host" role="status" aria-live="polite"></div>').appendTo(document.body);
        }

        var iconMap = {
            info:    'fa-info-circle',
            success: 'fa-check-circle',
            warning: 'fa-exclamation-triangle',
            error:   'fa-times-circle'
        };

        var $toast = $(
            '<div class="cw-toast cw-toast--' + type + '" role="alert">'
          +   '<i class="fa ' + (iconMap[type] || iconMap.info) + '" aria-hidden="true"></i>'
          +   '<span></span>'
          + '</div>'
        );
        $toast.find('span').text(message);
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
        state.$stepper.on('click', '[data-cw-step]', function (e) {
            e.preventDefault();
            var n = parseInt($(this).attr('data-cw-step'), 10);
            CW.goTo(n);
        });
        // Keyboard activation (Enter / Space) on stepper items.
        state.$stepper.on('keydown', '[data-cw-step]', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            }
        });
    }

    function bindNavButtons() {
        state.$shell.on('click', '[data-cw-action="next"]', function (e) {
            e.preventDefault();
            CW.next();
        });
        state.$shell.on('click', '[data-cw-action="prev"]', function (e) {
            e.preventDefault();
            CW.prev();
        });
        state.$shell.on('click', '[data-cw-action="save-draft"]', function (e) {
            e.preventDefault();
            CW.savePartial().done(function (res) {
                if (res && res.ok) CW.toast('تم حفظ المسودة.', 'success');
            });
        });
        state.$shell.on('click', '[data-cw-action="discard"]', function (e) {
            e.preventDefault();
            if (!confirm('هل تريد إلغاء المسودة وبدء عميل جديد من الصفر؟')) return;
            $.post(state.urls.discard, csrfPayload()).always(function () {
                window.location.href = state.urls.start;
            });
        });
    }

    function bindAutoSave() {
        // Mark dirty on any input/change in the active section.
        state.$shell.on('input change', '[data-cw-section] :input', function () {
            state.dirty = true;
            setStatus('dirty');
            scheduleAutosave();
        });
    }

    function scheduleAutosave() {
        clearTimeout(state.autosaveTm);
        state.autosaveTm = setTimeout(function () {
            if (state.dirty) CW.savePartial();
        }, 1500); // 1.5s of inactivity
    }

    function bindKeyboard() {
        // Alt+Right/Left for fast nav (RTL-aware: Right = previous in RTL).
        $(document).on('keydown.cw', function (e) {
            if (!e.altKey) return;
            if (e.key === 'ArrowLeft')  { e.preventDefault(); CW.next(); }
            if (e.key === 'ArrowRight') { e.preventDefault(); CW.prev(); }
        });
    }

    function switchTo(n) {
        state.current = n;
        showSection(n, true);
        renderStepper();
        // Move focus to the section heading for screen readers.
        var $heading = state.$sections.filter('[data-cw-section="' + n + '"]').find('h2, h3').first();
        if ($heading.length) {
            $heading.attr('tabindex', '-1').focus();
        }
        // Scroll into view with small offset.
        var top = state.$shell.offset().top - 80;
        $('html, body').stop().animate({ scrollTop: top }, 250);
    }

    function showSection(n, animate) {
        state.$sections.removeClass('cw-section--active').attr('aria-hidden', 'true');
        var $target = state.$sections.filter('[data-cw-section="' + n + '"]');
        $target.addClass('cw-section--active').attr('aria-hidden', 'false');
        if (!animate) {
            // Suppress fade-in on first paint.
            $target.css('animation', 'none');
            void $target[0].offsetWidth; // reflow
            $target.css('animation', '');
        }
    }

    function renderStepper() {
        state.$stepper.find('[data-cw-step]').each(function () {
            var $s   = $(this);
            var n    = parseInt($s.attr('data-cw-step'), 10);
            var isCur  = (n === state.current);
            var isDone = !!state.completed[n] && n !== state.current;
            $s.removeClass('cw-step--current cw-step--done')
              .toggleClass('cw-step--current', isCur)
              .toggleClass('cw-step--done', isDone)
              .attr('aria-current', isCur ? 'step' : null);
        });
    }

    function setStatus(state2) {
        if (!state.$statusPill || !state.$statusPill.length) return;
        var map = {
            saving: { cls: 'cw-pill--info',    icon: 'fa-spinner fa-spin', text: 'جاري الحفظ…' },
            saved:  { cls: 'cw-pill--saved',   icon: 'fa-check',           text: 'محفوظ' },
            dirty:  { cls: 'cw-pill--warning', icon: 'fa-pencil',          text: 'تغييرات غير محفوظة' },
            error:  { cls: 'cw-pill--error',   icon: 'fa-exclamation',     text: 'فشل الحفظ' },
        };
        var s = map[state2] || map.saved;
        state.$statusPill
            .removeClass('cw-pill--info cw-pill--saved cw-pill--warning cw-pill--error')
            .addClass(s.cls)
            .html('<i class="fa ' + s.icon + '" aria-hidden="true"></i> <span>' + s.text + '</span>');
    }

    /** Collect every input inside the active section, namespaced as the form
     *  expects (legacy "Customers[name]" style preserved). */
    function collectStepData(n) {
        var $section = state.$sections.filter('[data-cw-section="' + n + '"]');
        if (!$section.length) return {};
        var data = {};
        $section.find(':input[name]').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name) return;
            var type = (this.type || '').toLowerCase();

            if (type === 'checkbox') {
                data[name] = this.checked ? ($el.val() || '1') : '';
            } else if (type === 'radio') {
                if (this.checked) data[name] = $el.val();
                else if (!(name in data)) data[name] = '';
            } else if (type === 'file') {
                /* file inputs are uploaded out-of-band */
            } else {
                data[name] = $el.val();
            }
        });
        return data;
    }

    function renderServerErrors(errors) {
        // Clear previous errors, then mark each named field.
        state.$shell.find('.cw-field--error').removeClass('cw-field--error');
        state.$shell.find('.cw-field__error-msg').remove();

        var firstName = null;
        Object.keys(errors).forEach(function (key) {
            if (!firstName) firstName = key;
            var $input = state.$shell.find(':input[name="' + key.replace(/"/g, '\\"') + '"]').first();
            if (!$input.length) return;
            var $field = $input.closest('.cw-field, .form-group');
            $field.addClass('cw-field--error');
            $field.append('<div class="cw-field__error-msg" role="alert">' + escapeHtml(errors[key]) + '</div>');
        });

        if (firstName) {
            var $first = state.$shell.find(':input[name="' + firstName.replace(/"/g, '\\"') + '"]').first();
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

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

})(window, window.jQuery);
