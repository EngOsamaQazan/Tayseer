/* global window, document, jQuery */
/**
 * Customer Wizard V2 — review-step enhancements.
 *
 *   • Renders the FIRST PAGE of every PDF tile (uploaded SS kashf, ad-hoc
 *     PDF documents) into the document tile thumbnail using PDF.js. The
 *     server-rendered tile starts as a red PDF icon + a spinner overlay;
 *     once PDF.js paints the canvas the spinner/icon fade out. If PDF.js
 *     fails to load (offline / blocked CDN) we fall back to the icon —
 *     the link itself still opens the file in a new tab.
 *
 * Why client-side and not server-side?
 *   We don't want a Ghostscript / Imagick dependency on every install,
 *   and the kashf PDFs are often <1MB → tiny client-side render cost.
 *
 * Markup contract:
 *   <div class="cw-review-doc__thumb"
 *        data-cw-pdf-thumb
 *        data-pdf-url="/path/to/file.pdf">
 *       <i class="fa fa-file-pdf-o cw-review-doc__pdf-fallback"></i>
 *       <span class="cw-review-doc__pdf-loading">
 *           <i class="fa fa-spinner fa-pulse"></i>
 *       </span>
 *   </div>
 *
 * Wiring:
 *   The wizard's core re-renders the review step's HTML on every visit
 *   (advanceTo → refetchStep → switchTo), so we listen for the global
 *   `cw:step:rendered` event and re-scan that <section> for new tiles.
 */
(function ($) {
    'use strict';

    // ── PDF.js loader (single-shot, cached) ─────────────────────────────
    //
    // We pin a known-good build (3.x, last release before the v4 worker
    // module split) so the worker URL stays predictable and we don't get
    // CORS surprises on a future jsdelivr cache flip.
    var PDFJS_BASE   = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/';
    var PDFJS_LIB    = PDFJS_BASE + 'pdf.min.js';
    var PDFJS_WORKER = PDFJS_BASE + 'pdf.worker.min.js';

    var pdfJsPromise = null;

    function loadPdfJs() {
        if (window.pdfjsLib) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
            return $.Deferred().resolve(window.pdfjsLib).promise();
        }
        if (pdfJsPromise) return pdfJsPromise;

        pdfJsPromise = $.Deferred();
        var script = document.createElement('script');
        script.src   = PDFJS_LIB;
        script.async = true;
        script.onload = function () {
            if (window.pdfjsLib) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
                pdfJsPromise.resolve(window.pdfjsLib);
            } else {
                pdfJsPromise.reject(new Error('pdfjsLib unavailable after load'));
            }
        };
        script.onerror = function () {
            pdfJsPromise.reject(new Error('Failed to load PDF.js'));
            // Allow a future retry on next render pass.
            pdfJsPromise = null;
        };
        document.head.appendChild(script);
        return pdfJsPromise.promise();
    }

    // ── Render one tile ─────────────────────────────────────────────────
    function renderThumb(thumbEl) {
        if (thumbEl.dataset.cwPdfThumbDone === '1') return;
        thumbEl.dataset.cwPdfThumbDone = '1';

        var url = thumbEl.getAttribute('data-pdf-url');
        if (!url) return;

        loadPdfJs().then(function (pdfjsLib) {
            // getDocument() handles both URL strings and CORS-friendly
            // same-origin paths — the kashf is served from /images/...
            // which is same-origin so no CORS dance needed.
            var loadingTask = pdfjsLib.getDocument(url);
            return loadingTask.promise.then(function (pdf) {
                return pdf.getPage(1);
            });
        }).then(function (page) {
            // Choose a render scale that produces a crisp thumb on retina
            // without wasting bandwidth on huge canvases. The thumb box is
            // ~280px wide max; render at ~2× for sharpness on hi-DPI.
            var rect    = thumbEl.getBoundingClientRect();
            var targetW = Math.max(280, rect.width || 0) * (window.devicePixelRatio || 1);
            var baseVp  = page.getViewport({ scale: 1 });
            var scale   = Math.min(2.5, Math.max(0.6, targetW / baseVp.width));
            var vp      = page.getViewport({ scale: scale });

            var canvas = document.createElement('canvas');
            canvas.width  = Math.floor(vp.width);
            canvas.height = Math.floor(vp.height);
            canvas.setAttribute('aria-hidden', 'true');

            var ctx = canvas.getContext('2d');
            return page.render({ canvasContext: ctx, viewport: vp }).promise.then(function () {
                thumbEl.appendChild(canvas);
                thumbEl.classList.add('cw-review-doc__thumb--rendered');
            });
        }).catch(function (err) {
            // PDF.js failed (network blocked, malformed PDF, password) —
            // leave the fallback icon visible. The tile is still a usable
            // anchor (opens the file in a new tab) so we degrade gracefully.
            thumbEl.classList.add('cw-review-doc__thumb--failed');
            if (window.console && window.console.warn) {
                window.console.warn('[cw:review] PDF thumb failed:', url, err && err.message);
            }
        });
    }

    function renderAllIn(root) {
        var scope = root && root.length ? root[0] : (root || document);
        var tiles = scope.querySelectorAll
            ? scope.querySelectorAll('[data-cw-pdf-thumb]')
            : [];
        for (var i = 0; i < tiles.length; i++) {
            renderThumb(tiles[i]);
        }
    }

    // ── Wiring ──────────────────────────────────────────────────────────
    $(function () { renderAllIn(document); });

    // Re-render whenever the wizard core re-fetches a step partial. Only
    // bother for the review step — every other step has no PDF tiles.
    $(document).on('cw:step:rendered', function (_evt, info) {
        if (info && info.$section) {
            renderAllIn(info.$section);
        } else {
            renderAllIn(document);
        }
    });

})(jQuery);
