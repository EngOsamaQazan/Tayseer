/* ============================================================
   Customer Wizard V2 — RealEstate repeater
   -----------------------------------------------------------
   Pure-jQuery; auto-initialises on DOM ready and re-binds on
   every step-changed event from core.js.

   Responsibilities:
     1. Add a new (empty) realestates[] row from a hidden <template>.
     2. Remove a row (refuse to remove the last visible row — clear it
        instead, mirroring the "always keep one row" UX in Step 3
        guarantors).
     3. Renumber every row's [name="realestates[N][...]"] indices and
        the visible "عقار N" header after every add/remove so the
        server-side parser receives a contiguous, zero-based array
        regardless of how many times the user edited the list.

   Design rules:
     • Never wipe data when toggling the "owns property?" radio: we
       only show/hide the wrapper. Toggling "no" zeroes the rows on
       the server side via finishEdit's "skip empty rows" filter.
     • Idempotent: every binding lives in the .cwre namespace and
       gets re-attached cleanly on init().
   ============================================================ */
(function ($) {
    'use strict';

    var NS  = '.cwre';
    var SEL = {
        root:      '[data-cw-realestate]',
        list:      '[data-cw-realestate-list]',
        row:       '[data-cw-realestate-row]',
        addBtn:    '[data-cw-realestate-add]',
        removeBtn: '[data-cw-realestate-remove]',
        template:  '[data-cw-realestate-template]',
        num:       '[data-cw-realestate-num]'
    };

    function init(root) {
        var $root = root ? $(root) : $('#cw-shell');
        if (!$root.length) return;

        $root.find(SEL.root).each(function () {
            bindRepeater(this);
        });
    }

    function destroy(root) {
        var $root = root ? $(root) : $('#cw-shell');
        $root.find(SEL.root).off(NS);
    }

    function bindRepeater(rootEl) {
        var $root = $(rootEl);
        $root.off(NS);

        $root.on('click' + NS, SEL.addBtn, function (e) {
            e.preventDefault();
            addRow($root);
        });

        $root.on('click' + NS, SEL.removeBtn, function (e) {
            e.preventDefault();
            removeRow($root, $(this).closest(SEL.row));
        });
    }

    /* ── Add ────────────────────────────────────────────────── */

    function addRow($root) {
        var $tpl = $root.find(SEL.template);
        if (!$tpl.length) return;

        var nextIdx = $root.find(SEL.row).length;
        var html    = tplHtml($tpl)
            .replace(/__INDEX__/g, String(nextIdx))
            .replace(/__NUM__/g,   String(nextIdx + 1));

        var $row = $(html);
        $root.find(SEL.list).append($row);

        var $firstInput = $row.find('input[type="text"]').first();
        if ($firstInput.length) $firstInput.trigger('focus');

        renumber($root);
        notifyChange($root);
    }

    /* Pull the template HTML in a way that works for both real
     * <template> elements (DocumentFragment) and the fallback where
     * the markup is rendered as a plain <template> tag whose innerHTML
     * is the cloneable string. */
    function tplHtml($tpl) {
        var el = $tpl[0];
        if (el && el.content && el.content.firstElementChild) {
            return el.content.firstElementChild.outerHTML;
        }
        return $tpl.html();
    }

    /* ── Remove ─────────────────────────────────────────────── */

    function removeRow($root, $row) {
        if (!$row.length) return;

        var $rows = $root.find(SEL.row);
        if ($rows.length <= 1) {
            // Don't leave the user staring at an empty list — clear in
            // place instead. The "owns property?" radio handles the
            // hide/show of the whole block.
            $row.find('input[type="text"]').val('');
            $row.find('input[type="hidden"][data-cw-realestate-id]').val('0');
            notifyChange($root);
            return;
        }

        $row.remove();
        renumber($root);
        notifyChange($root);
    }

    /* ── Renumber every row's [name="realestates[N][...]"] +
         the visible header so the array is contiguous. ─── */

    function renumber($root) {
        $root.find(SEL.row).each(function (i) {
            var $row = $(this);
            $row.attr('data-cw-realestate-index', i);
            $row.find(SEL.num).text(i + 1);

            $row.find('input[name^="realestates["]').each(function () {
                var $i   = $(this);
                var name = $i.attr('name') || '';
                var next = name.replace(/^realestates\[\d+\]/, 'realestates[' + i + ']');
                if (next !== name) $i.attr('name', next);
            });
        });
    }

    /* ── Tell core.js that the draft is dirty so the autosave
         picks the new rows up on the next debounce tick. ─── */

    function notifyChange($root) {
        // core.js listens on the cw-shell for any 'input' / 'change'.
        $root.trigger('input' + NS, [{ source: 'realestate' }]);
        $root.find('input').first().trigger('change');
    }

    /* ── Bootstrap ─────────────────────────────────────────── */

    $(function () {
        init(document);
        $(document).on('cw:step-changed', function () { init(document); });
        $(document).on('cw:before-step-change', function () { destroy(document); });
    });
})(jQuery);
