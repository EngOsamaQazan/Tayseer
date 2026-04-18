/* ============================================================
   Customer Wizard V2 — Field-level UX enhancements
   -----------------------------------------------------------
   Pure-jQuery, no framework dependency. Self-contained module
   that auto-initialises on DOM ready and re-applies whenever
   CW emits a step-changed event.

   Responsibilities:
     1. data-cw-mask="digits"     — strip non-digits as user types
     2. data-cw-mask="phone-jo"   — light Jordanian-mobile formatter
     3. data-cw-counter-for="id"  — live char counter (uses maxlength)
     4. <details>-based fieldsets — keep aria-expanded in sync

   Design rules:
     • Never block keyboard navigation or paste behaviour.
     • Always preserve cursor position after programmatic edits.
     • Run idempotently — re-bind cleanly with namespaced events.
   ============================================================ */
(function ($) {
    'use strict';

    var NS = '.cwfields';

    function init(root) {
        var $root = root ? $(root) : $('#cw-shell');
        if (!$root.length) return;

        bindMasks($root);
        bindCounters($root);
        bindCollapsibles($root);
    }

    function destroy(root) {
        var $root = root ? $(root) : $('#cw-shell');
        $root.off(NS);
        $root.find('input, textarea, summary').off(NS);
    }

    /* ── 1. Input masks ────────────────────────────────────── */

    function bindMasks($root) {
        $root.find('input[data-cw-mask]').each(function () {
            var $el = $(this);
            var kind = ($el.attr('data-cw-mask') || '').toLowerCase();

            $el.off(NS).on('input' + NS, function () {
                if (kind === 'digits') {
                    enforceDigits(this);
                } else if (kind === 'phone-jo') {
                    formatPhoneJordan(this);
                }
            });
        });
    }

    /**
     * Strip every non-digit while preserving the relative cursor position.
     */
    function enforceDigits(el) {
        var raw = el.value;
        var clean = raw.replace(/\D+/g, '');
        if (clean === raw) return;

        var pos = el.selectionStart || clean.length;
        var removed = raw.slice(0, pos).replace(/\D+/g, '').length;
        el.value = clean;
        try { el.setSelectionRange(removed, removed); } catch (e) { /* unsupported */ }
    }

    /**
     * Allow `+` only as the first character; everything else digits-only.
     * Keep the cursor stable.
     */
    function formatPhoneJordan(el) {
        var raw = el.value;
        var clean = raw.charAt(0) === '+'
            ? '+' + raw.slice(1).replace(/\D+/g, '')
            : raw.replace(/\D+/g, '');
        if (clean === raw) return;
        var diff = raw.length - clean.length;
        var pos  = (el.selectionStart || clean.length) - diff;
        el.value = clean;
        try { el.setSelectionRange(pos, pos); } catch (e) { /* unsupported */ }
    }

    /* ── 2. Live character counters ────────────────────────── */

    function bindCounters($root) {
        $root.find('[data-cw-counter-for]').each(function () {
            var $counter = $(this);
            var targetId = $counter.attr('data-cw-counter-for');
            var $target  = $('#' + cssEscape(targetId));
            if (!$target.length) return;

            var max = parseInt($target.attr('maxlength'), 10);
            if (!max || isNaN(max)) return;

            var update = function () {
                var len = ($target.val() || '').length;
                $counter.text(len + '/' + max);
                $counter.toggleClass('cw-field__counter--near-max', len >= max - 20);
                $counter.toggleClass('cw-field__counter--at-max',  len >= max);
            };

            $target.off('input' + NS).on('input' + NS, update);
            update();
        });
    }

    /* ── 3. Collapsible fieldsets — keep aria state truthful ── */

    function bindCollapsibles($root) {
        $root.find('details.cw-fieldset--collapsible').each(function () {
            var el = this;
            $(el).off('toggle' + NS).on('toggle' + NS, function () {
                var $sum = $(el).find('> summary').first();
                $sum.attr('aria-expanded', el.open ? 'true' : 'false');
            }).trigger('toggle' + NS);
        });
    }

    /* ── Tiny CSS.escape polyfill (matches core.js helper) ── */

    function cssEscape(value) {
        if (window.CSS && typeof CSS.escape === 'function') return CSS.escape(value);
        return String(value).replace(/[^a-zA-Z0-9_-]/g, function (ch) {
            return '\\' + ch;
        });
    }

    /* ── Public hook + auto-init + re-init on step change ── */

    window.CWFields = { init: init, destroy: destroy };

    $(function () {
        init();
        $(document).on('cw:step:rendered cw:step:changed', function (_e, info) {
            // Re-init when a new partial is swapped in.
            if (info && info.$section) init(info.$section);
            else init();
        });
    });

})(jQuery);
