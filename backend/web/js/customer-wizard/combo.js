/* eslint-disable */
/**
 * CWCombo — accessible searchable combobox + inline "add new" action.
 *
 *   Usage on a vanilla <select>:
 *     <select data-cw-combo="city"
 *             data-cw-combo-add-url="/customers/wizard/add-city"
 *             data-cw-combo-placeholder="ابحث…">
 *       <option value="">— اختر —</option>
 *       <option value="1">عمان</option>
 *       …
 *     </select>
 *
 *   What you get:
 *     • The native <select> is hidden (kept in DOM as the form value carrier
 *       so existing form-collection / validation logic keeps working).
 *     • A search input with a popup listbox you can type into to filter.
 *     • Arrow keys / Enter / Escape work.
 *     • If the user's query doesn't match any option, an "إضافة «X»" row
 *       appears at the top of the list. Activating it POSTs the new value
 *       to data-cw-combo-add-url and selects the returned id.
 *     • Aria roles follow the WAI ARIA 1.2 combobox pattern.
 *     • Re-binding is idempotent: re-running enhance() on already-wired
 *       <select>s is a no-op (we mark them with data-cw-combo-bound=1).
 *
 *   Visible label is read from the original <option>'s text content.
 *   Selected value sync goes both ways: an external script that runs
 *   `$select.val(x).trigger('change')` will update the search input
 *   automatically (we listen on the select's change event).
 */
;(function ($, window) {
    'use strict';
    if (!$) return;

    var NS = '.cwcombo';

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    /** Loose-match Arabic-aware normalization for filtering & dup checks. */
    function normalize(s) {
        s = String(s || '').toLowerCase();
        s = s.replace(/[\u064B-\u0652\u0670\u0640]/g, '');     // diacritics + tatweel
        s = s.replace(/[إأآا]/g, 'ا');
        s = s.replace(/ى/g, 'ي');
        s = s.replace(/ة/g, 'ه');
        s = s.replace(/\s+/g, ' ').trim();
        return s;
    }

    function uid(prefix) {
        return prefix + '-' + Math.random().toString(36).slice(2, 9);
    }

    function buildOption(value, label, selected) {
        return $('<option/>').attr('value', value).text(label).prop('selected', !!selected);
    }

    /**
     * Wrap a single <select> with the combobox UI.
     */
    function enhance($select) {
        if ($select.attr('data-cw-combo-bound') === '1') return;
        $select.attr('data-cw-combo-bound', '1');

        var addUrl       = $select.attr('data-cw-combo-add-url') || '';
        var placeholder  = $select.attr('data-cw-combo-placeholder') || 'ابحث…';
        // Kind ('city' | 'citizen' | …) only drives microcopy for the
        // "add new" affordance; the network call itself is fully generic.
        var kind         = ($select.attr('data-cw-combo') || '').toLowerCase();
        var addAsLabel   = $select.attr('data-cw-combo-add-as')
                         || (kind === 'citizen' ? 'كجنسية جديدة'
                                                : (kind === 'city' ? 'كمدينة جديدة'
                                                                   : 'كقيمة جديدة'));
        // Optional "selection-meta" endpoint. When set, every selection
        // change will hit this URL with ?id=<value> and render the JSON
        // response into a sibling [data-cw-combo-meta] container (created
        // on demand) — used by the employer combobox to warn when the
        // chosen job has no stored address / phones / working hours.
        var metaUrl      = $select.attr('data-cw-combo-meta-url') || '';
        var listboxId    = uid('cwcombo-listbox');
        var inputId      = uid('cwcombo-input');
        var origLabelId  = $select.closest('.cw-field').find('.cw-field__label').attr('id');
        if (!origLabelId) {
            origLabelId = uid('cwcombo-lbl');
            $select.closest('.cw-field').find('.cw-field__label').attr('id', origLabelId);
        }

        // ── Build wrapper / input / listbox markup ───────────────────────
        var $wrap = $('<div/>', {
            'class': 'cw-combo',
            'role':  'combobox',
            'aria-haspopup': 'listbox',
            'aria-owns':     listboxId,
            'aria-expanded': 'false',
        });

        var $inputBox = $('<div/>', { 'class': 'cw-combo__inputbox' });

        var $input = $('<input/>', {
            type:  'text',
            id:    inputId,
            'class': 'cw-input cw-combo__input',
            placeholder: placeholder,
            autocomplete: 'off',
            'aria-autocomplete': 'list',
            'aria-controls':     listboxId,
            'aria-labelledby':   origLabelId,
            'aria-activedescendant': '',
        });

        var $clear = $('<button/>', {
            type: 'button',
            'class': 'cw-combo__clear',
            'aria-label': 'مسح الاختيار',
            html: '<i class="fa fa-times" aria-hidden="true"></i>',
        });

        var $caret = $('<span/>', {
            'class': 'cw-combo__caret',
            'aria-hidden': 'true',
            html: '<i class="fa fa-chevron-down"></i>',
        });

        $inputBox.append($input).append($clear).append($caret);

        var $listbox = $('<ul/>', {
            id:       listboxId,
            'class':  'cw-combo__listbox',
            'role':   'listbox',
            'aria-labelledby': origLabelId,
            tabindex: -1,
            hidden:   true,
        });

        $wrap.append($inputBox).append($listbox);
        // Visually hide the select but keep it in the form. We use .cw-combo__hidden-select
        // (display:none) instead of `hidden` because some browsers stop submitting
        // values for [hidden] form controls in odd ways.
        $select.addClass('cw-combo__hidden-select').after($wrap);

        // ── State ────────────────────────────────────────────────────────
        var activeIdx = -1;
        var visibleOpts = [];     // [{id, label, $li}, …]
        var addEntry = null;      // {label, $li} or null
        var open = false;

        // ── Selection sync ──────────────────────────────────────────────
        function syncFromSelect() {
            var val = $select.val();
            var $opt = $select.find('option[value="' + cssEscape(String(val || '')) + '"]');
            if (val && $opt.length) {
                $input.val($opt.text().trim());
                $clear.show();
            } else {
                $input.val('');
                $clear.hide();
            }
        }
        syncFromSelect();
        $select.on('change' + NS, syncFromSelect);

        // Meta-fetch (e.g. employer enrichment alert).
        var metaXhr = null;
        function refreshMeta() {
            if (!metaUrl) return;
            var $host = ensureMetaHost();
            var val   = $select.val();
            if (!val) { $host.empty().attr('hidden', ''); return; }

            if (metaXhr && metaXhr.readyState !== 4) { try { metaXhr.abort(); } catch (e) {} }

            $host.removeAttr('hidden').html(
                '<div class="cw-combo__meta cw-combo__meta--loading">' +
                  '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> جارٍ التحقق من بيانات الجهة…' +
                '</div>'
            );

            metaXhr = $.ajax({
                url:      metaUrl,
                type:     'GET',
                dataType: 'json',
                data:     { id: val },
                cache:    false,
            }).done(function (resp) {
                renderMeta($host, resp);
            }).fail(function (xhr) {
                if (xhr && xhr.statusText === 'abort') return;
                $host.empty().attr('hidden', '');
            });
        }
        function ensureMetaHost() {
            var $host = $wrap.next('[data-cw-combo-meta]');
            if (!$host.length) {
                $host = $('<div/>', {
                    'data-cw-combo-meta': '',
                    'class': 'cw-combo__meta-host',
                    'aria-live': 'polite',
                    hidden: true,
                });
                $wrap.after($host);
            }
            return $host;
        }
        function renderMeta($host, resp) {
            if (!resp || resp.ok !== true) { $host.empty().attr('hidden', ''); return; }
            var missing = resp.missing || [];
            if (!missing.length) {
                $host.html(
                    '<div class="cw-combo__meta cw-combo__meta--ok">' +
                      '<i class="fa fa-check-circle" aria-hidden="true"></i> ' +
                      '<span>بيانات الجهة مكتملة (عنوان، هواتف، ودوام).</span>' +
                    '</div>'
                ).removeAttr('hidden');
                return;
            }
            var labels = {
                address: 'العنوان',
                phones:  'أرقام الهواتف',
                hours:   'أوقات الدوام',
            };
            var pretty = missing.map(function (m) { return labels[m] || m; });
            var msg = pretty.length === 1
                ? 'لا يوجد ' + pretty[0] + ' مخزن لهذه الجهة.'
                : 'لم يتم تخزين ' + pretty.slice(0, -1).join('، ') + ' و' + pretty[pretty.length - 1] + ' لهذه الجهة.';

            // The edit button only renders when the server provides a URL —
            // keeps the UI sensible if the route is ever disabled by RBAC.
            var btnHtml = '';
            if (resp.edit_url) {
                btnHtml =
                    '<a class="cw-combo__meta-cta" data-cw-meta-edit ' +
                       'href="' + resp.edit_url + '" target="_blank" rel="noopener">' +
                      '<i class="fa fa-pencil-square-o" aria-hidden="true"></i> ' +
                      '<span>تحديث جهة العمل</span>' +
                      '<i class="fa fa-external-link cw-combo__meta-cta-ext" aria-hidden="true"></i>' +
                    '</a>';
            }

            $host.html(
                '<div class="cw-combo__meta cw-combo__meta--warn" role="status">' +
                  '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>' +
                  '<div class="cw-combo__meta-body">' +
                    '<strong>' + msg + '</strong>' +
                    '<span class="cw-combo__meta-hint">' +
                      'يمكن استكمال هذه البيانات لاحقاً من شاشة «جهات العمل» — لن يمنعك ذلك من المتابعة الآن.' +
                    '</span>' +
                  '</div>' +
                  btnHtml +
                '</div>'
            ).removeAttr('hidden');
        }
        if (metaUrl) {
            $select.on('change' + NS, refreshMeta);
            // Run once on bind in case the field is pre-filled from a draft.
            refreshMeta();

            // When the user clicks "update" we open the jobs editor in a new
            // tab; on return to the wizard tab we re-poll the endpoint so the
            // alert vanishes the moment the missing fields land in the DB —
            // no manual refresh needed. We register the focus listener once
            // per combobox instance and mark a "needs refresh" intent only
            // after the user actually clicked the CTA, so we don't spam
            // the endpoint on every tab switch.
            var pendingRefresh = false;
            $wrap.on('click' + NS, '[data-cw-meta-edit]', function () {
                pendingRefresh = true;
            });
            window.addEventListener('focus', function () {
                if (!pendingRefresh) return;
                pendingRefresh = false;
                refreshMeta();
            });
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState !== 'visible') return;
                if (!pendingRefresh) return;
                pendingRefresh = false;
                refreshMeta();
            });
        }

        function cssEscape(s) { return s.replace(/(["\\])/g, '\\$1'); }

        // ── Listbox portal + position ───────────────────────────────────
        //
        // The listbox is moved out of the .cw-card subtree at open time and
        // appended to <body> so that any ancestor with `overflow: hidden`
        // (every .cw-card has it) can no longer clip the popup. We then
        // anchor it to the input via getBoundingClientRect + position:fixed.
        //
        // Direction (down vs up) flips automatically when there isn't enough
        // room below the input — matching native <select> behaviour.
        function positionListbox() {
            if (!open) return;
            var input = $input[0];
            if (!input) return;

            var rect       = input.getBoundingClientRect();
            var vh         = window.innerHeight || document.documentElement.clientHeight;
            var listH      = $listbox.outerHeight();
            var spaceBelow = vh - rect.bottom;
            var spaceAbove = rect.top;

            // Match the input's width so options align cleanly.
            $listbox.css('width', rect.width + 'px');

            // Prefer below; flip up only when necessary AND there's room.
            var openUp = (spaceBelow < Math.min(listH, 200))
                      && (spaceAbove > spaceBelow);

            $listbox.css({
                left: rect.left + 'px',
                top:  openUp
                    ? Math.max(8, rect.top - listH - 4) + 'px'
                    : (rect.bottom + 4) + 'px',
            });
        }

        // Recompute on viewport / ancestor scroll while open. We use capture
        // so we hear scroll on inner containers too (the wizard sits inside
        // a scrollable AdminLTE wrapper).
        var positionTickQueued = false;
        function schedulePosition() {
            if (!open || positionTickQueued) return;
            positionTickQueued = true;
            (window.requestAnimationFrame || function (cb) { return setTimeout(cb, 16); })(function () {
                positionTickQueued = false;
                positionListbox();
            });
        }

        // ── Open / close ────────────────────────────────────────────────
        function openList() {
            if (open) return;
            open = true;
            $wrap.attr('aria-expanded', 'true');

            // Portal to <body> so .cw-card overflow:hidden can't clip us.
            if ($listbox[0].parentNode !== document.body) {
                document.body.appendChild($listbox[0]);
            }

            $listbox.removeAttr('hidden');
            renderList($input.val());
            positionListbox();

            // Track viewport changes while open.
            window.addEventListener('scroll',  schedulePosition, true);
            window.addEventListener('resize',  schedulePosition);
        }
        function closeList() {
            if (!open) return;
            open = false;
            $wrap.attr('aria-expanded', 'false');
            $listbox.attr('hidden', '');
            activeIdx = -1;
            $input.attr('aria-activedescendant', '');
            window.removeEventListener('scroll', schedulePosition, true);
            window.removeEventListener('resize', schedulePosition);
        }

        // ── Render ──────────────────────────────────────────────────────
        function renderList(query) {
            $listbox.empty();
            visibleOpts = [];
            addEntry = null;
            activeIdx = -1;
            $input.attr('aria-activedescendant', '');

            var q = normalize(query);
            var seenExact = false;

            $select.find('option').each(function () {
                var val   = String(this.value || '');
                var label = String(this.text || '').trim();
                if (val === '' || !label) return;     // skip the empty placeholder

                var n = normalize(label);
                if (q !== '' && n.indexOf(q) === -1) return;
                if (q !== '' && n === q) seenExact = true;

                var liId = uid('cwcombo-opt');
                var $li = $('<li/>', {
                    id:    liId,
                    'class': 'cw-combo__option',
                    'role':  'option',
                    'aria-selected': (val === String($select.val() || '')) ? 'true' : 'false',
                    'data-value': val,
                    text: label,
                });
                if (q) {
                    var idx = n.indexOf(q);
                    if (idx >= 0) {
                        $li.html(
                            $('<span/>').text(label.substring(0, idx)).prop('outerHTML') +
                            '<mark>' + $('<span/>').text(label.substring(idx, idx + query.length)).html() + '</mark>' +
                            $('<span/>').text(label.substring(idx + query.length)).prop('outerHTML')
                        );
                    }
                }
                $listbox.append($li);
                visibleOpts.push({ id: liId, value: val, label: label, $li: $li });
            });

            // "Add new" entry — only when there's a query, no exact match,
            // and the form has an add-url.
            if (q && !seenExact && addUrl) {
                var addLiId = uid('cwcombo-add');
                var $add = $('<li/>', {
                    id:     addLiId,
                    'class': 'cw-combo__option cw-combo__option--add',
                    'role':  'option',
                    'aria-selected': 'false',
                    html: '<i class="fa fa-plus" aria-hidden="true"></i> ' +
                          '<span>إضافة «<strong></strong>» <em></em></span>',
                });
                $add.find('strong').text(query.trim());
                $add.find('em').text(addAsLabel);
                $listbox.prepend($add);
                addEntry = { id: addLiId, label: query.trim(), $li: $add };
                // Move add entry to position 0 in visibleOpts for nav.
                visibleOpts.unshift({ id: addLiId, value: '__ADD__', label: addEntry.label, $li: $add, isAdd: true });
            }

            if (!visibleOpts.length) {
                $listbox.append($('<li/>', {
                    'class': 'cw-combo__empty',
                    text: 'لا توجد نتائج. اكتب اسماً جديداً لإضافته.',
                }));
            }

            // Pre-highlight the first item so Enter is meaningful.
            if (visibleOpts.length) {
                setActive(0);
            }

            // List height likely changed → re-anchor (esp. for "open up").
            positionListbox();
        }

        function setActive(idx) {
            if (idx < 0 || idx >= visibleOpts.length) return;
            visibleOpts.forEach(function (o, i) {
                o.$li.toggleClass('cw-combo__option--active', i === idx);
            });
            activeIdx = idx;
            $input.attr('aria-activedescendant', visibleOpts[idx].id);

            // Scroll into view.
            var li = visibleOpts[idx].$li[0];
            if (li && li.scrollIntoView) {
                li.scrollIntoView({ block: 'nearest' });
            }
        }

        function commit(idx) {
            if (idx < 0 || idx >= visibleOpts.length) return;
            var sel = visibleOpts[idx];
            if (sel.isAdd) {
                doAdd(sel.label);
                return;
            }
            $select.val(sel.value).trigger('change');
            closeList();
            $input.blur();
        }

        // ── "Add new" round trip ────────────────────────────────────────
        function doAdd(rawName) {
            if (!addUrl || !rawName) return;

            // Replace the add row with a spinner state.
            if (addEntry && addEntry.$li.length) {
                addEntry.$li.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> جارٍ الحفظ…');
            }

            $.ajax({
                url: addUrl,
                type: 'POST',
                dataType: 'json',
                data: { name: rawName, _csrf: csrfToken() },
            }).done(function (resp) {
                if (!resp || !resp.ok || !resp.id) {
                    var err = (resp && resp.error) ? resp.error : 'تعذّر إضافة العنصر.';
                    if (addEntry) addEntry.$li.html('<i class="fa fa-exclamation-triangle"></i> ' + err);
                    return;
                }
                var idStr = String(resp.id);
                var name  = resp.name || rawName;

                // Inject a new <option> if it doesn't already exist.
                var $opt = $select.find('option[value="' + cssEscape(idStr) + '"]');
                if (!$opt.length) {
                    $select.append(buildOption(idStr, name, false));
                }
                $select.val(idStr).trigger('change');
                closeList();

                // Visual ack on the wrapping field.
                var $field = $select.closest('[data-cw-field]');
                $field.addClass('cw-field--auto-filled');
                window.setTimeout(function () {
                    $field.removeClass('cw-field--auto-filled');
                }, 2500);

                // Toast reuses the wizard's toast helper if present.
                var msg;
                if (resp.restored)      msg = 'تمّت استعادة «' + name + '» (كانت محذوفة سابقاً) واختيارها.';
                else if (resp.existed)  msg = 'تم اختيار «' + name + '».';
                else                    msg = 'تمّت إضافة «' + name + '» إلى القائمة.';
                if (window.CW && typeof window.CW.toast === 'function') {
                    window.CW.toast(msg, 'success', 5000);
                }
            }).fail(function () {
                if (addEntry) addEntry.$li.html('<i class="fa fa-exclamation-triangle"></i> فشل الاتصال بالخادم.');
            });
        }

        // ── Events ──────────────────────────────────────────────────────
        $input.on('focus' + NS, openList);
        $input.on('input' + NS, function () {
            openList();
            renderList($input.val());
            $clear.toggle(!!$input.val());
        });
        $input.on('keydown' + NS, function (e) {
            var key = e.key;
            if (!open && (key === 'ArrowDown' || key === 'Enter' || key === ' ')) {
                openList();
                e.preventDefault();
                return;
            }
            if (key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(activeIdx + 1, visibleOpts.length - 1)); }
            else if (key === 'ArrowUp')   { e.preventDefault(); setActive(Math.max(activeIdx - 1, 0)); }
            else if (key === 'Home')      { e.preventDefault(); setActive(0); }
            else if (key === 'End')       { e.preventDefault(); setActive(visibleOpts.length - 1); }
            else if (key === 'Enter')     {
                e.preventDefault();
                if (activeIdx >= 0) {
                    commit(activeIdx);
                } else if ($input.val().trim() && addUrl) {
                    // No suggestion is highlighted but user typed something
                    // unique → treat Enter as an "add" shortcut.
                    doAdd($input.val().trim());
                }
            }
            else if (key === 'Escape')    {
                if (open) { e.preventDefault(); closeList(); $input.val(''); syncFromSelect(); }
            }
        });

        $listbox.on('mousedown' + NS, '.cw-combo__option', function (e) {
            e.preventDefault();     // keep focus on input for keyboard chains
            var idx = visibleOpts.findIndex(function (o) { return o.$li[0] === e.currentTarget; });
            if (idx >= 0) commit(idx);
        });
        $listbox.on('mouseover' + NS, '.cw-combo__option', function (e) {
            var idx = visibleOpts.findIndex(function (o) { return o.$li[0] === e.currentTarget; });
            if (idx >= 0) setActive(idx);
        });

        $clear.on('click' + NS, function (e) {
            e.preventDefault();
            $select.val('').trigger('change');
            $input.val('').focus();
            renderList('');
        });

        $caret.on('mousedown' + NS, function (e) {
            e.preventDefault();
            if (open) closeList(); else { $input.focus(); openList(); }
        });

        // Close when focus leaves the wrapper. We use a tiny delay so
        // mousedown on a list option fires first and the click commit can
        // run before the listbox vanishes.
        $wrap.on('focusout' + NS, function (e) {
            window.setTimeout(function () {
                if (!$wrap.is(':focus-within')) closeList();
            }, 120);
        });

        // Outside-click guard. Because the listbox is portaled to <body>,
        // it lives outside $wrap — clicks inside it must NOT close, but
        // clicks anywhere else should. Using a document-level mousedown
        // (capture phase) makes this watertight on touch + desktop.
        document.addEventListener('mousedown', function (e) {
            if (!open) return;
            if ($wrap[0].contains(e.target))    return;
            if ($listbox[0].contains(e.target)) return;
            closeList();
        }, true);

        // Close any open listbox when the user navigates to another step
        // or the wizard tears down — prevents a stale popup hanging in
        // body after the field's section is hidden.
        $(document).on('cw:step:changed' + NS, function () {
            if (open) closeList();
        });

        // ── Public, per-instance API attached to the wrapper element ────
        // Used by scan-income.js to "type" the extracted employer name
        // into the search box and surface the inline "إضافة «X»" CTA so
        // the user can confirm before any DB write happens.
        $wrap.data('cwComboApi', {
            prefillSearch: function (text) {
                var s = String(text || '').trim();
                if (!s) return;
                $select.val('').trigger('change');
                $input.val(s);
                $clear.show();
                openList();
                renderList(s);
                // Keep focus on the input so the user can hit Enter to commit.
                try { $input[0].focus({ preventScroll: false }); } catch (e) { $input.focus(); }
            },
            refreshMeta: refreshMeta,
        });
    }

    function enhanceAll($root) {
        ($root || $(document)).find('select[data-cw-combo]').each(function () {
            enhance($(this));
        });
    }

    /**
     * Static helper used by external scripts (e.g. scan-income.js): given
     * a target <select>, find its enhanced wrapper and call prefillSearch.
     * Idempotent — if the select isn't enhanced yet, enhance it first.
     */
    function prefillSearch($select, text) {
        if (!$select || !$select.length) return false;
        if ($select.attr('data-cw-combo-bound') !== '1') enhance($select);
        var $wrap = $select.next('.cw-combo');
        var api = $wrap.data('cwComboApi');
        if (api && typeof api.prefillSearch === 'function') {
            api.prefillSearch(text);
            return true;
        }
        return false;
    }

    // Public API for re-binding after partial DOM updates.
    window.CWCombo = {
        enhance:       enhance,
        enhanceAll:    enhanceAll,
        prefillSearch: prefillSearch,
    };

    $(function () {
        enhanceAll();
        $(document).on('cw:step:rendered cw:step:changed', function (e, payload) {
            if (payload && payload.$section) enhanceAll(payload.$section);
            else enhanceAll();
        });
    });

})(window.jQuery, window);
