/**
 * PIN SYSTEM — Reusable pinned items manager
 *
 * Usage:
 *   PinSystem.init({
 *       type: 'judiciary_case',
 *       barSelector: '#pin-bar',
 *       buildUrl: function(itemId) { return '/judiciary/judiciary/view?id=' + itemId; }
 *   });
 *
 *   // Pin button in table row:
 *   PinSystem.togglePin(itemId, 'Label text', 'extra info');
 */
var PinSystem = (function () {
    var cfg = {};
    var pins = [];

    var _delegated = false;

    function init(options) {
        cfg = $.extend({
            type: '',
            barSelector: '#pin-bar',
            buildUrl: function (id) { return '#'; },
            toggleUrl: '/pin/toggle',
            listUrl: '/pin/list',
        }, options);
        loadPins();

        if (!_delegated) {
            _delegated = true;
            $(document).on('click', '.pin-chip-unpin', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var id = $(this).data('unpin');
                var label = $(this).data('label');
                var extra = $(this).data('extra');
                togglePin(id, label, extra);
            });
        }
    }

    function loadPins() {
        $.getJSON(cfg.listUrl, { type: cfg.type }, function (res) {
            if (res.success) {
                pins = res.pins || [];
                renderBar();
                updateButtons();
            }
        });
    }

    function togglePin(itemId, label, extra) {
        $.post(cfg.toggleUrl, {
            type: cfg.type,
            item_id: itemId,
            label: label || '',
            extra: extra || '',
            _csrf: yii.getCsrfToken(),
        }, function (res) {
            if (res.success) {
                pins = res.pins || [];
                renderBar();
                updateButtons();
            }
        }, 'json');
    }

    function renderBar() {
        var $bar = $(cfg.barSelector);
        if (!$bar.length) return;

        if (!pins.length) {
            $bar.removeClass('has-pins');
            return;
        }

        var html = '<div class="pin-bar-hdr">'
            + '<i class="fa fa-thumb-tack"></i>'
            + '<span>العناصر المثبتة</span>'
            + '<span class="pin-bar-count">' + pins.length + '</span>'
            + '</div>'
            + '<div class="pin-bar-body">';

        for (var i = 0; i < pins.length; i++) {
            var p = pins[i];
            var url = cfg.buildUrl(p.item_id);
            html += '<span class="pin-chip" data-pin-id="' + p.item_id + '">'
                + '<a href="' + url + '" class="pin-chip-link">'
                + '<i class="fa fa-thumb-tack pin-chip-icon"></i>'
                + '<span class="pin-chip-id">' + escHtml(p.label || '#' + p.item_id) + '</span>';
            if (p.extra_info) {
                html += '<span class="pin-chip-label">' + escHtml(p.extra_info) + '</span>';
            }
            html += '</a>'
                + '<button type="button" class="pin-chip-unpin" data-unpin="' + p.item_id + '" '
                + 'data-label="' + escAttr(p.label || '') + '" '
                + 'data-extra="' + escAttr(p.extra_info || '') + '" '
                + 'title="إلغاء التثبيت">'
                + '<i class="fa fa-times"></i>'
                + '</button>'
                + '</span>';
        }
        html += '</div>';
        $bar.html(html).addClass('has-pins');
    }

    function updateButtons() {
        var pinnedIds = {};
        for (var i = 0; i < pins.length; i++) {
            pinnedIds[pins[i].item_id] = true;
        }
        $('.pin-btn').each(function () {
            var id = $(this).data('item-id');
            if (pinnedIds[id]) {
                $(this).addClass('pinned').attr('title', 'إلغاء التثبيت');
            } else {
                $(this).removeClass('pinned').attr('title', 'تثبيت');
            }
        });
    }

    function isPinned(itemId) {
        for (var i = 0; i < pins.length; i++) {
            if (pins[i].item_id == itemId) return true;
        }
        return false;
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }
    function escAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;');
    }

    return {
        init: init,
        togglePin: togglePin,
        isPinned: isPinned,
        reload: loadPins,
    };
})();
