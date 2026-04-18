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

    /* ── Defaults reflect Tayseer's primary customer geography ── */
    var DEFAULTS = {
        initialCountry:        'jo',
        // v27+ option name. (The pre-v27 equivalent was `preferredCountries`,
        // which the new library logs a deprecation warning for; we don't pass
        // it so the console stays clean.)
        countryOrder:          ['jo', 'sa', 'ae', 'sy', 'iq', 'ps', 'lb', 'eg', 'kw', 'qa'],
        separateDialCode:      true,
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
    $(document).on('cw:step:changed', function () {
        window.CWPhone.enhance($(document));
    });

})(jQuery);
