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
        bindConditionalDisclosure($root);
        bindDynamicRows($root);
    }

    function destroy(root) {
        var $root = root ? $(root) : $('#cw-shell');
        $root.off(NS);
        $root.find('input, textarea, summary, select').off(NS);
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

    /* ── 4. Progressive disclosure (conditional fields) ────── */

    /**
     * Show/hide a "conditional" field based on the value of a controlling
     * radio, checkbox, or select. Two attribute styles:
     *
     *   On radio/checkbox:
     *     data-cw-toggle="#target"        → show #target when this is checked
     *     data-cw-toggle-hide="1"         → hide #target when this is checked
     *                                       (lets the inverse radio collapse it)
     *
     *   On select:
     *     data-cw-toggle-target="#target" → show #target only when this select's
     *     data-cw-toggle-values="a,b,c"     value is one of the listed values.
     *
     * The target gets the `hidden` attribute toggled (semantic + a11y) plus
     * a `cw-conditional--hidden` CSS class for transitions. We also disable
     * descendant inputs while hidden so the browser/jQuery don't submit them.
     */
    function bindConditionalDisclosure($root) {
        // ── Radio / checkbox controllers. ──
        $root.find('input[data-cw-toggle]').each(function () {
            var $ctrl   = $(this);
            var sel     = $ctrl.attr('data-cw-toggle');
            var hideOn  = $ctrl.attr('data-cw-toggle-hide') === '1';
            var $target = $(sel);
            if (!$target.length) return;

            // Same-named radios share the same target — bind on the
            // controller only so each radio in a group fires.
            $ctrl.off('change' + NS).on('change' + NS, function () {
                if (!$ctrl.is(':checked')) return;
                setTargetVisible($target, !hideOn);
            });
        });

        // Initial sync — apply current checked state on render.
        $root.find('input[data-cw-toggle]:checked').each(function () {
            var $ctrl   = $(this);
            var sel     = $ctrl.attr('data-cw-toggle');
            var hideOn  = $ctrl.attr('data-cw-toggle-hide') === '1';
            var $target = $(sel);
            if ($target.length) setTargetVisible($target, !hideOn);
        });

        // ── Select controllers (value-driven). ──
        $root.find('select[data-cw-toggle-target]').each(function () {
            var $sel    = $(this);
            var target  = $sel.attr('data-cw-toggle-target');
            var allowed = ($sel.attr('data-cw-toggle-values') || '')
                            .split(',').map(function (s) { return s.trim(); })
                            .filter(Boolean);
            var $target = $(target);
            if (!$target.length) return;

            var apply = function () {
                var v = String($sel.val() || '');
                setTargetVisible($target, allowed.indexOf(v) !== -1);
            };
            $sel.off('change' + NS).on('change' + NS, apply);
            apply();
        });
    }

    /**
     * Toggle a conditional region's visibility and a11y state, and
     * disable its inner controls when hidden so they don't submit.
     */
    function setTargetVisible($target, visible) {
        if (visible) {
            $target.removeClass('cw-conditional--hidden');
            $target.removeAttr('hidden');
            $target.find(':input').prop('disabled', false);
        } else {
            $target.addClass('cw-conditional--hidden');
            $target.attr('hidden', 'hidden');
            // Keep inputs reachable for serializeForm but blank them so
            // we don't accidentally save stale conditional values.
            $target.find(':input').not('[type="hidden"]').each(function () {
                if (this.tagName === 'SELECT') {
                    this.selectedIndex = 0;
                } else if (this.type === 'radio' || this.type === 'checkbox') {
                    this.checked = false;
                } else {
                    this.value = '';
                }
            });
        }
    }

    /* ── 5. Dynamic row groups (CWDynamic) ─────────────────── */

    /**
     * A tiny accessible alternative to wbraganca/dynamicform — needs no
     * external dependencies beyond jQuery.
     *
     * Markup contract:
     *   <div class="cw-dynamic"
     *        data-cw-dynamic="kind"
     *        data-cw-dynamic-min="1"
     *        data-cw-dynamic-max="10"
     *        data-cw-dynamic-name-prefix="guarantors">
     *       <div data-cw-dynamic-rows>
     *           <div data-cw-dynamic-row data-cw-dynamic-index="0"> … </div>
     *           …
     *       </div>
     *       <template data-cw-dynamic-template> … (uses __INDEX__ and __DISPLAY__) … </template>
     *       <div class="cw-dynamic__actions">
     *           <button data-cw-action="add-row">…</button>
     *           <span data-cw-dynamic-counter></span>
     *       </div>
     *   </div>
     *
     * Each clone gets its `__INDEX__` placeholder replaced with the next
     * available numeric index — both inside attribute values (so input
     * names like `guarantors[3][phone_number]` stay unique) and in text
     * nodes flagged via `data-cw-dynamic-display`.
     */
    function bindDynamicRows($root) {
        $root.find('[data-cw-dynamic]').each(function () {
            var $box = $(this);
            if ($box.data('cwDynamicInited')) return;
            $box.data('cwDynamicInited', true);

            var min = parseInt($box.attr('data-cw-dynamic-min'), 10);
            var max = parseInt($box.attr('data-cw-dynamic-max'), 10);
            if (!isFinite(min) || min < 0) min = 0;
            if (!isFinite(max) || max < 1) max = 99;

            var $rows     = $box.find('[data-cw-dynamic-rows]').first();
            var $tpl      = $box.find('template[data-cw-dynamic-template]').first();
            var $addBtn   = $box.find('[data-cw-action="add-row"]').first();
            var $counter  = $box.find('[data-cw-dynamic-counter]').first();

            function renumberAndSync() {
                var $allRows = $rows.children('[data-cw-dynamic-row]');
                var count = $allRows.length;

                // Disable add when at max; disable remove when at min.
                $addBtn.prop('disabled', count >= max);
                $allRows.find('[data-cw-action="remove-row"]').prop('disabled', count <= min);

                if ($counter.length) {
                    $counter.text(count + ' من ' + max);
                }

                // Renumber the per-row index displays (1-based) for screen
                // readers and visible labels — does NOT renumber the input
                // `name` attributes (PHP doesn't care if indexes are sparse,
                // and renumbering would invalidate any auto-saved draft).
                $allRows.each(function (i) {
                    $(this).find('[data-cw-dynamic-display]').text(i + 1);
                });
            }

            function nextIndex() {
                var max = -1;
                $rows.children('[data-cw-dynamic-row]').each(function () {
                    var v = parseInt($(this).attr('data-cw-dynamic-index'), 10);
                    if (isFinite(v) && v > max) max = v;
                });
                return max + 1;
            }

            $addBtn.off('click' + NS).on('click' + NS, function (e) {
                e.preventDefault();
                if (!$tpl.length) return;
                var count = $rows.children('[data-cw-dynamic-row]').length;
                if (count >= max) return;

                var idx     = nextIndex();
                var display = count + 1;
                var html    = ($tpl[0].innerHTML || '')
                                .replace(/__INDEX__/g,   String(idx))
                                .replace(/__DISPLAY__/g, String(display));

                $rows.append(html);
                renumberAndSync();
                init($rows.children('[data-cw-dynamic-row]').last());

                // Move focus to the first input of the new row for fast entry.
                var $newRow = $rows.children('[data-cw-dynamic-row]').last();
                $newRow.find(':input:visible').first().trigger('focus');
            });

            $box.off('click' + NS, '[data-cw-action="remove-row"]')
                .on('click' + NS, '[data-cw-action="remove-row"]', function (e) {
                    e.preventDefault();
                    var $row = $(e.currentTarget).closest('[data-cw-dynamic-row]');
                    var count = $rows.children('[data-cw-dynamic-row]').length;
                    if (count <= min) return;
                    $row.remove();
                    renumberAndSync();
                });

            renumberAndSync();
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
