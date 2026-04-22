/**
 * Phase 6 / M6.1 — Unified MediaUploader (progress UI helpers).
 *
 * core.js renders a minimal "0% / 100% / ✓" status string by default.
 * This optional file replaces that with a proper progress bar inside
 * each row so the UX matches the polish of the existing custom
 * uploaders (smart-media, customer-wizard scan).
 *
 * Loading order: include AFTER core.js. The presence of
 * window.MediaUploaderProgress is enough — core.js looks for it on
 * every progress tick.
 *
 *   <script src="/js/media-uploader/core.js"></script>
 *   <script src="/js/media-uploader/progress.js"></script>
 */
(function (global) {
    'use strict';

    var BAR_HTML =
        '<div class="mu-bar" style="display:flex;align-items:center;gap:6px;min-width:120px;">' +
            '<div class="mu-bar-track" style="position:relative;flex:1;height:6px;background:#e5e7eb;border-radius:999px;overflow:hidden;">' +
                '<div class="mu-bar-fill" style="position:absolute;inset:0 100% 0 0;background:#2563eb;transition:inset .15s ease-out;"></div>' +
            '</div>' +
            '<span class="mu-bar-pct" style="font-variant-numeric:tabular-nums;font-size:12px;color:#374151;min-width:36px;text-align:end;">0%</span>' +
        '</div>';

    function ensureBar(rowEl) {
        var statusEl = rowEl.querySelector('.mu-status');
        if (!statusEl) return null;
        var bar = statusEl.querySelector('.mu-bar');
        if (bar) return bar;
        statusEl.innerHTML = BAR_HTML;
        return statusEl.querySelector('.mu-bar');
    }

    global.MediaUploaderProgress = {
        update: function (rowEl, pct) {
            var bar = ensureBar(rowEl);
            if (!bar) return;
            var fill = bar.querySelector('.mu-bar-fill');
            var label = bar.querySelector('.mu-bar-pct');
            pct = Math.max(0, Math.min(100, Math.round(pct)));
            if (fill) fill.style.inset = '0 ' + (100 - pct) + '% 0 0';
            if (label) label.textContent = pct + '%';
        },
        success: function (rowEl) {
            var statusEl = rowEl.querySelector('.mu-status');
            if (!statusEl) return;
            statusEl.innerHTML = '<span style="color:#16a34a;">✓ تم</span>';
        },
        fail: function (rowEl, msg) {
            var statusEl = rowEl.querySelector('.mu-status');
            if (!statusEl) return;
            statusEl.innerHTML = '<span style="color:#dc2626;">' + (msg || 'فشل الرفع') + '</span>';
        },
    };
})(window);
