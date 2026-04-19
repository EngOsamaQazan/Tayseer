/* ============================================================
   Customer Wizard V2 — International phone-input enhancement
   -----------------------------------------------------------
   Wraps every `[data-cw-phone]` input with an intl-tel-input
   instance (https://github.com/jackocnr/intl-tel-input). The
   library renders a country-flag combobox to the left of the
   input and validates / normalizes numbers via libphonenumber.

   Why we use it:
     • Validates ANY international format (not just Jordanian)
       so we don't lose Syrian / Iraqi / Saudi phone numbers.
     • Stores numbers in canonical E.164 (e.g. "+962791234567")
       for reliable WhatsApp / SMS lookups downstream.
     • Provides locale-aware placeholders that change with the
       selected country (no more wrong "07XXXXXXXX" hint when
       the user picked Saudi Arabia).
     • Has a built-in country search input → faster than a long
       <select>, accessible with keyboard, and pre-screen-reader
       friendly out of the box.

   Integration contract:
     • Markup: <input type="tel" data-cw-phone … />
     • Default country = "jo"; preferredCountries = the Levant +
       Gulf set most relevant for our customer base.
     • On input blur, value is rewritten to E.164 if valid.
     • On form submission OR draft serialization the field already
       holds E.164, so no special hooks are required in core.js.
     • Re-runs on `cw:rows:added` (for guarantor template clones).

   Asset loading: layout.php registers the intl-tel-input CSS+JS
   from the unpkg CDN; this file is loaded after them and waits
   for `window.intlTelInput` before binding. If the CDN ever fails
   to load we degrade silently — the underlying <input> still
   accepts a phone number and the server validators still run.
   ============================================================ */
(function ($) {
    'use strict';

    var NS = '.cwphone';
    var SELECTOR = 'input[data-cw-phone]';
    var INSTANCE_KEY = 'cwIntlInstance';
    /* Buffer between the dial-code button's right edge and where typed
     * digits start. Matches the visual breathing-room intl-tel-input ships
     * by default (~6–8px) and keeps the caret well clear of the button. */
    var BTN_PAD_BUFFER = 6;

    /* ── Defaults reflect Tayseer's primary customer geography ── */
    var DEFAULTS = {
        initialCountry:        'jo',
        // v27+ option name. (The pre-v27 equivalent was `preferredCountries`,
        // which the new library logs a deprecation warning for; we don't pass
        // it so the console stays clean.)
        countryOrder:          ['jo', 'sa', 'ae', 'sy', 'iq', 'ps', 'lb', 'eg', 'kw', 'qa'],
        // ── Why `separateDialCode: false` ──
        // We deliberately do NOT split the dial code into its own button.
        // Doing so creates a visual stutter for pre-filled E.164 numbers
        // (edit mode, smart-scan pre-fill): the button shows "+962" AND
        // the input still carries "+962797707062" → the country code
        // appears twice side-by-side. With separateDialCode disabled, the
        // input always renders the full international number (flag-only
        // dropdown to its left), so there is exactly one "+962" on screen
        // and the form-submit value is always canonical E.164 — no hidden
        // mirror field, no blur-time reconciliation race.
        separateDialCode:      false,
        nationalMode:          false,
        formatOnDisplay:       true,
        autoPlaceholder:       'aggressive',
        placeholderNumberType: 'MOBILE',
        // Utils + libphonenumber are bundled into our vendored
        // intlTelInputWithUtils.min.js — no external loadUtils() needed.
    };

    /**
     * Public API surfaced for other modules (e.g. fields.js after a
     * dynamic row is added, or scan modules that want to reformat a
     * value programmatically).
     */
    window.CWPhone = {
        utilsUrl: null,

        /** Bind every unbound phone input under $root (default = doc). */
        enhance: function ($root) {
            if (typeof window.intlTelInput !== 'function') return;
            $root = $root && $root.length ? $root : $(document);
            $root.find(SELECTOR).each(function () { bindOne(this); });
        },

        /**
         * Programmatically set a value (raw national or E.164) on a
         * phone input and trigger reformat. Used by the SS scan flow
         * if we ever pre-fill phones.
         */
        setNumber: function (input, value) {
            var iti = $(input).data(INSTANCE_KEY);
            if (iti && typeof iti.setNumber === 'function') {
                iti.setNumber(value || '');
            } else {
                $(input).val(value || '');
            }
        },

        /** Returns E.164 if valid, otherwise the raw user input. */
        getE164: function (input) {
            var iti = $(input).data(INSTANCE_KEY);
            if (iti && typeof iti.getNumber === 'function' && typeof iti.isValidNumber === 'function') {
                if (iti.isValidNumber()) return iti.getNumber();
            }
            return $(input).val();
        },
    };

    /**
     * Wrap one input with an intl-tel-input instance. Idempotent —
     * existing instances are left alone so re-enhancing the same root
     * after a step transition is a cheap no-op.
     */
    function bindOne(el) {
        var $el = $(el);
        if ($el.data(INSTANCE_KEY)) return;

        // Honour per-input overrides via data-* (for future expansions
        // like a hard-coded country override on bank phones).
        var initialCountry = $el.attr('data-cw-phone-country') || DEFAULTS.initialCountry;

        var opts = $.extend({}, DEFAULTS, {
            initialCountry: initialCountry,
        });

        try {
            var iti = window.intlTelInput(el, opts);
            $el.data(INSTANCE_KEY, iti);

            // ── Visual integration with our wizard's input style ──
            // intl-tel-input wraps the input in `.iti` — force it to
            // fill the field cell so it lines up with the grid neighbours.
            var $iti = $el.closest('.iti');
            if ($iti.length) {
                $iti.css('width', '100%').addClass('iti--cw');
            }

            // ── Normalise pre-filled values on init ──
            // When the field arrives with an existing E.164 value (edit
            // mode, or after `actionScan` pre-fills from an ID scan), we
            // pipe it through setNumber() so libphonenumber can:
            //   (a) auto-select the matching country flag,
            //   (b) apply locale-aware visual formatting,
            //   (c) write the canonical E.164 back into the input.
            // Because we run with `separateDialCode: false`, the input's
            // .value always retains the full +<dial><national>, so form
            // serialization stays correct without a hidden mirror field.
            var preset = $el.val();
            if (preset && preset.charAt(0) === '+' && typeof iti.setNumber === 'function') {
                try { iti.setNumber(preset); } catch (_) { /* keep raw value */ }
            }

            // ── Keep input's inline padding-left in sync with the button ──
            // intl-tel-input writes `style="padding-left: NNpx"` once at init
            // based on the button width AT THAT INSTANT. Three things break
            // that one-shot measurement:
            //   1. Our `.iti--cw` overrides add `padding-inline: 8px` to the
            //      country button → button ends up ~16px wider than the
            //      library expected, so digits get drawn UNDER the flag.
            //   2. The input is hidden inside an inactive `<section>` at
            //      init time (display:none / inert), so the measured width
            //      is 0, causing padding-left to collapse.
            //   3. Locale / dial-code can change at runtime (user picks SA
            //      `+966` after starting on JO `+962`) and the new dial-code
            //      string is wider/narrower than the old one.
            // A ResizeObserver on the button + a one-shot recompute on every
            // step transition + countrychange covers all three cases.
            syncInputPadding($el[0], $iti[0]);

            // ── E.164 normalization on blur ──
            // We rewrite to E.164 only when the number passes
            // libphonenumber validation; otherwise we leave the user's
            // raw text alone so they can still see what they typed.
            $el.on('blur' + NS, function () {
                if (typeof iti.isValidNumber !== 'function') return;
                if (iti.isValidNumber()) {
                    var e164 = iti.getNumber();
                    if (e164 && e164 !== $el.val()) {
                        $el.val(e164);
                        $el.trigger('change'); // notify draft autosave
                    }
                }
            });

            // ── Soft validation hint ──
            // Toggle a CSS class so the field can render a subtle red
            // outline; the server still runs the authoritative regex.
            $el.on('input' + NS, function () {
                $el.removeClass('cw-input--phone-invalid');
            });
            $el.on('blur' + NS, function () {
                if (!$el.val()) return;
                if (typeof iti.isValidNumber === 'function' && !iti.isValidNumber()) {
                    $el.addClass('cw-input--phone-invalid');
                }
            });
        } catch (e) {
            if (window.console && console.warn) {
                console.warn('[CWPhone] init failed:', e);
            }
        }
    }

    /**
     * Pin the input's inline `padding-left` to the live width of the
     * dial-code button so typed digits never sit underneath it. We also
     * stash the button on the input so a global step-changed handler can
     * recompute everything in one pass.
     */
    function syncInputPadding(input, itiContainer) {
        if (!input || !itiContainer) return;
        var btn = itiContainer.querySelector(
            '.iti__country-container, .iti__selected-country, .iti__flag-container'
        );
        if (!btn) return;

        var apply = function () {
            // Skip when the input is inside a hidden step (width=0) so we
            // don't lock in a 0px padding. The post-step:changed hook will
            // re-run apply() once the section is visible.
            var w = btn.getBoundingClientRect().width;
            if (w <= 0) return;
            var px = Math.ceil(w) + BTN_PAD_BUFFER;
            // Use setProperty(important) so we always beat any stale inline
            // value the library wrote at init.
            input.style.setProperty('padding-left', px + 'px', 'important');
        };

        apply();

        // Track future button-width changes (locale switch, font load,
        // viewport resize via responsive font-size). ResizeObserver fires
        // synchronously after layout, before paint → no visual flicker.
        if (typeof window.ResizeObserver === 'function') {
            try {
                var ro = new window.ResizeObserver(apply);
                ro.observe(btn);
                $(input).data('cwPhonePadObserver', ro);
            } catch (_) { /* SSR / sandboxed env — silently skip. */ }
        }

        $(input).data('cwPhonePadResync', apply);
    }

    /** Re-run padding sync for every bound phone input. Cheap idempotent. */
    function resyncAllPadding($root) {
        $root = $root && $root.length ? $root : $(document);
        $root.find(SELECTOR).each(function () {
            var fn = $(this).data('cwPhonePadResync');
            if (typeof fn === 'function') fn();
        });
    }

    /* ── Auto-init on DOM ready ── */
    $(function () {
        // Library is vendored locally and loaded synchronously before this
        // file by layout.php — so it should already be defined. We still
        // poll briefly in case a slow disk read / cache miss races us.
        var attempts = 0;
        (function tryInit() {
            if (typeof window.intlTelInput === 'function') {
                window.CWPhone.enhance($(document));
                return;
            }
            if (++attempts < 25) setTimeout(tryInit, 200);
        })();
    });

    /* ── Re-enhance after the wizard adds a guarantor row ── */
    $(document).on('cw:rows:added', function (evt, $newRow) {
        if (!$newRow || !$newRow.length) return;
        window.CWPhone.enhance($newRow);
    });

    /* ── Re-enhance after a step transition (defensive) ── */
    $(document).on('cw:step:changed cw:step:rendered', function () {
        window.CWPhone.enhance($(document));
        // The step we're entering may host a phone input that was hidden
        // (width=0) at init time, so its padding-left was skipped. Re-sync
        // now that the section is visible.
        resyncAllPadding($(document));
    });

    /* ── Re-sync padding when a country changes its dial-code width. ── */
    $(document).on('countrychange' + NS, SELECTOR, function () {
        var fn = $(this).data('cwPhonePadResync');
        if (typeof fn === 'function') fn();
    });

    /* ── Re-sync after window resize (font-size media queries kick in
           between 480/768/1366px, which can change button width). ── */
    var resizeT;
    window.addEventListener('resize', function () {
        clearTimeout(resizeT);
        resizeT = setTimeout(function () { resyncAllPadding($(document)); }, 80);
    }, { passive: true });

})(jQuery);
