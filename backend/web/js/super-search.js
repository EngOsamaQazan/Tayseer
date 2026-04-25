/**
 * Tayseer Super Search (Ctrl+K)
 * ─────────────────────────────────────────────────
 * Command-palette style global search across the system.
 *
 * Required global config (set inline before this script loads):
 *   window.TaySuperSearchConfig = { url: '/search/global' };
 *
 * Public API:
 *   TaySuperSearch.open()    — open the overlay
 *   TaySuperSearch.close()   — close it
 *   TaySuperSearch.toggle()  — flip
 */
(function () {
    'use strict';

    var cfg = window.TaySuperSearchConfig || {};
    var URL = cfg.url || '/search/global';
    var DEBOUNCE_MS = 220;
    var MIN_CHARS = 2;

    var overlay, input, body, footer, spinner;
    var flat = [];           // flattened items array for keyboard nav
    var activeIdx = -1;
    var timer = null;
    var xhr = null;
    var isOpen = false;
    var lastQ = '';

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    /* ── Init ───────────────────────────────────────────────── */
    function init() {
        overlay = $('#tssOverlay');
        if (!overlay) return;

        input   = $('.tss-input',   overlay);
        body    = $('.tss-body',    overlay);
        footer  = $('.tss-footer',  overlay);
        spinner = $('.tss-spinner', overlay);

        // Trigger button click
        var trigger = $('#tssTrigger');
        if (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                open();
            });
        }

        // Close on backdrop click
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) close();
        });

        // Close button (.tss-close-btn)
        var btn = $('.tss-close-btn', overlay);
        if (btn) btn.addEventListener('click', close);

        // Input handling
        input.addEventListener('input', onInput);
        input.addEventListener('keydown', onKeyDown);

        // Global keyboard shortcuts
        document.addEventListener('keydown', onGlobalKey, true);

        // Render hint state
        renderHint();
    }

    /* ── Open / Close ───────────────────────────────────────── */
    function open() {
        if (isOpen) { input.focus(); return; }
        isOpen = true;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(function () {
            input.focus();
            input.select();
        }, 30);
        if (input.value.trim().length >= MIN_CHARS) {
            search(input.value.trim());
        } else {
            renderHint();
        }
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (xhr) { xhr.abort(); xhr = null; }
        if (timer) { clearTimeout(timer); timer = null; }
    }

    function toggle() { isOpen ? close() : open(); }

    /* ── Global Ctrl+K / Cmd+K ──────────────────────────────── */
    function onGlobalKey(e) {
        var k = (e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && k === 'k') {
            e.preventDefault();
            e.stopPropagation();
            toggle();
            return;
        }
        // Forward slash "/" opens search when not typing in an input
        if (k === '/' && !isOpen && !isTypingInField(e.target)) {
            e.preventDefault();
            open();
        }
    }

    function isTypingInField(el) {
        if (!el) return false;
        var t = el.tagName;
        if (t === 'INPUT' || t === 'TEXTAREA' || t === 'SELECT') return true;
        if (el.isContentEditable) return true;
        return false;
    }

    /* ── Input ──────────────────────────────────────────────── */
    function onInput() {
        var q = input.value.trim();
        if (timer) clearTimeout(timer);

        if (q.length < MIN_CHARS) {
            if (xhr) { xhr.abort(); xhr = null; }
            overlay.classList.remove('tss-loading');
            renderHint();
            return;
        }

        overlay.classList.add('tss-loading');
        timer = setTimeout(function () { search(q); }, DEBOUNCE_MS);
    }

    function onKeyDown(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            close();
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            move(1);
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            move(-1);
            return;
        }
        if (e.key === 'Enter') {
            if (activeIdx >= 0 && flat[activeIdx]) {
                e.preventDefault();
                openItem(flat[activeIdx], e.ctrlKey || e.metaKey);
            }
            return;
        }
    }

    /* ── Search ─────────────────────────────────────────────── */
    function search(q) {
        lastQ = q;
        if (xhr) xhr.abort();
        xhr = new XMLHttpRequest();
        xhr.open('GET', URL + (URL.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onload = function () {
            overlay.classList.remove('tss-loading');
            if (xhr.status !== 200) {
                renderError();
                return;
            }
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.q !== lastQ && data.q !== '') return; // race protection
                renderResults(data, q);
            } catch (err) {
                renderError();
            }
        };
        xhr.onerror = function () {
            overlay.classList.remove('tss-loading');
            renderError();
        };
        xhr.send();
    }

    /* ── Render ─────────────────────────────────────────────── */
    function renderHint() {
        flat = [];
        activeIdx = -1;
        body.innerHTML =
            '<div class="tss-hint">' +
                '<div class="tss-empty-title">ابحث عن أي شيء في النظام</div>' +
                '<div class="tss-empty-sub">العملاء، العقود، الموظفون، المخزون، القضايا، الفواتير، الصفحات…</div>' +
                '<div class="tss-hint-tags">' +
                    '<span>اسم العميل</span>' +
                    '<span>رقم الهاتف</span>' +
                    '<span>رقم الهوية</span>' +
                    '<span>رقم العقد</span>' +
                    '<span>رقم القضية</span>' +
                    '<span>اسم الصنف</span>' +
                '</div>' +
            '</div>';
    }

    function renderError() {
        body.innerHTML =
            '<div class="tss-empty">' +
                '<i class="fa-solid fa-circle-exclamation"></i>' +
                '<div class="tss-empty-title">تعذّر إجراء البحث</div>' +
                '<div class="tss-empty-sub">حدث خطأ أثناء الاتصال بالخادم. حاول مرة أخرى.</div>' +
            '</div>';
    }

    function renderResults(data, q) {
        flat = [];
        activeIdx = -1;

        var groups = (data && data.groups) || [];
        if (!groups.length) {
            body.innerHTML =
                '<div class="tss-empty">' +
                    '<i class="fa-solid fa-magnifying-glass"></i>' +
                    '<div class="tss-empty-title">لا توجد نتائج</div>' +
                    '<div class="tss-empty-sub">لم يُعثر على شيء يطابق "' + esc(q) + '"</div>' +
                '</div>';
            return;
        }

        var html = '';
        for (var gi = 0; gi < groups.length; gi++) {
            var g = groups[gi];
            if (!g.items || !g.items.length) continue;
            html += '<div class="tss-group">';
            html +=   '<div class="tss-group-title"><i class="fa-solid ' + esc(g.icon || 'fa-folder') + '"></i> ' + esc(g.title) + '</div>';
            for (var ii = 0; ii < g.items.length; ii++) {
                var item = g.items[ii];
                var idx = flat.length;
                flat.push(item);
                html += renderItem(item, idx, q);
            }
            html += '</div>';
        }
        body.innerHTML = html;

        // Bind item handlers (parent row navigation)
        $$('.tss-item', body).forEach(function (el) {
            el.addEventListener('click', function (e) {
                /* تجاهل النقر إذا حدث على شريحة عقد أو رابط داخلي */
                if (e.target.closest('.tss-chip')) return;
                e.preventDefault();
                var i = parseInt(el.getAttribute('data-idx'), 10);
                if (!isNaN(i) && flat[i]) openItem(flat[i], e.ctrlKey || e.metaKey);
            });
            el.addEventListener('mouseenter', function () {
                var i = parseInt(el.getAttribute('data-idx'), 10);
                if (!isNaN(i)) setActive(i);
            });
        });

        // Contract chip clicks — منع انتشار الحدث للأب لكي يفتح صفحة العقد فقط
        $$('.tss-chip', body).forEach(function (chip) {
            chip.addEventListener('click', function (e) {
                e.stopPropagation();
                /* السلوك الافتراضي للـ <a> يتولى التنقل (يدعم Ctrl/middle-click) */
                if (!(e.ctrlKey || e.metaKey || e.button === 1)) {
                    /* في النقر العادي نُغلق اللوحة قبل أن يحدث التنقل */
                    setTimeout(close, 0);
                }
            });
            chip.addEventListener('auxclick', function (e) { e.stopPropagation(); });
        });

        if (flat.length) setActive(0);
    }

    function renderItem(item, idx, q) {
        var icon = item.icon || 'fa-circle';
        var html = '';
        html += '<div class="tss-item" data-idx="' + idx + '" role="link" tabindex="-1" data-href="' + esc(item.url || '#') + '">';
        html +=   '<div class="tss-item-icon"><i class="fa-solid ' + esc(icon) + '"></i></div>';
        html +=   '<div class="tss-item-body">';
        html +=     '<div class="tss-item-title">' + highlight(esc(item.title || ''), q) + '</div>';
        if (item.sub) {
            html +=   '<div class="tss-item-sub">' + highlight(esc(item.sub), q) + '</div>';
        }
        if (item.contracts && item.contracts.length) {
            html += '<div class="tss-item-chips">';
            html +=   '<span class="tss-chips-label"><i class="fa-solid fa-file-contract"></i> العقود:</span>';
            for (var c = 0; c < item.contracts.length; c++) {
                var ct = item.contracts[c];
                var cls = 'tss-chip tss-chip-' + esc(ct.status || 'secondary');
                html += '<a href="' + esc(ct.url) + '" class="' + cls + '" title="فتح صفحة العقد #' + esc(String(ct.id)) + '">#' + esc(String(ct.id)) + '</a>';
            }
            html += '</div>';
        }
        html +=   '</div>';
        if (item.id) {
            html += '<span class="tss-item-id">#' + esc(String(item.id)) + '</span>';
        }
        html +=   '<span class="tss-item-enter"><i class="fa-solid fa-arrow-turn-down"></i> فتح</span>';
        html += '</div>';
        return html;
    }

    /* ── Navigation ─────────────────────────────────────────── */
    function move(dir) {
        if (!flat.length) return;
        var n = activeIdx + dir;
        if (n < 0) n = flat.length - 1;
        if (n >= flat.length) n = 0;
        setActive(n);
    }

    function setActive(idx) {
        activeIdx = idx;
        var els = $$('.tss-item', body);
        for (var i = 0; i < els.length; i++) {
            els[i].classList.toggle('is-active', i === idx);
        }
        if (els[idx]) els[idx].scrollIntoView({ block: 'nearest' });
    }

    function openItem(item, newTab) {
        if (!item.url || item.url === '#') return;
        if (newTab) {
            window.open(item.url, '_blank');
            return;
        }
        close();
        window.location.href = item.url;
    }

    /* ── Helpers ────────────────────────────────────────────── */
    function esc(s) {
        if (s === null || s === undefined) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML;
    }

    function highlight(text, q) {
        if (!q || !text) return text;
        var words = q.split(/\s+/).filter(Boolean);
        for (var i = 0; i < words.length; i++) {
            var w = words[i].replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            if (!w) continue;
            try {
                var re = new RegExp('(' + w + ')', 'gi');
                text = text.replace(re, '<span class="tss-highlight">$1</span>');
            } catch (_) {}
        }
        return text;
    }

    /* ── Public API ─────────────────────────────────────────── */
    window.TaySuperSearch = { open: open, close: close, toggle: toggle, init: init };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
