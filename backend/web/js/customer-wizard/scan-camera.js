/**
 * Customer Wizard V2 — Live camera capture with bank-grade auto-detection.
 *
 * UX flow (matches Mashreq Neo / Liv. / NEOLeap patterns):
 *   1. User taps "scan" → full-screen overlay opens, camera permission requested.
 *   2. Live video feed inside an aspect-locked frame; corners glow according to
 *      a real-time quality score (brightness + sharpness + stability).
 *   3. When score >= threshold for 1.2s → flash + capture (front side).
 *   4. Frozen frame shown briefly with "جارٍ التحليل…" → POST to /scan side=front.
 *   5. Server confirms → animated card-flip illustration + "اقلب الهوية الآن".
 *   6. Camera resumes for back side; same auto-capture logic.
 *   7. Server confirms back → close camera, return mapped fields to scan.js.
 *
 * Quality score (0-100, computed every 200ms on a 320×180 downscale):
 *   • brightness   — average luma must land in [55, 200]   weight 30
 *   • sharpness    — Laplacian-style variance > 80         weight 35
 *   • stability    — pixel-diff vs. last frame < 4%         weight 35
 *
 * Why these thresholds: tuned against blurry/dim/well-lit ID samples; chosen
 * to avoid both eager false-captures and never-capturing on dim phones. They
 * are stored as constants so we can adjust per analytics later.
 *
 * Public surface (window.CWCamera):
 *   CWCamera.open({
 *     scanUrl,                // POST endpoint
 *     onFields(fields, side), // called after each successful side capture
 *     onComplete(allFields),  // called after both sides done
 *     onCancel(),             // user closed without finishing
 *     csrfToken,
 *   })
 *   CWCamera.isSupported()    // boolean — getUserMedia + canvas + Promise
 *   CWCamera.close()          // programmatic close
 *
 * Accessibility:
 *   • Full keyboard support: Esc=close, Space=manual capture (override auto).
 *   • Live region announces state changes ("جودة الصورة جيدة", "تم الالتقاط").
 *   • Honors prefers-reduced-motion (no flash, no pulsing).
 *   • Camera stream is stopped on close, beforeunload, AND visibilitychange.
 */
(function ($, window, document) {
    'use strict';

    if (!$) return;

    var CWCamera = window.CWCamera = {};

    // ─── Tunables ───────────────────────────────────────────────────────────
    var QUALITY_THRESHOLD   = 75;     // 0-100; auto-capture once score >= this
    var DOC_THRESHOLD       = 70;     // 0-100; minimum document-likelihood to auto-fire
    // ID-1 cards have an aspect ratio of 1.586:1. The detector below finds
    // the actual card rectangle anywhere in the frame (we no longer rely
    // on the user perfectly aligning to a fixed inset).
    var DOC_ASPECT_MIN      = 1.30;   // min width/height (ID-1 = 1.586)
    var DOC_ASPECT_MAX      = 2.10;   // max width/height (passport = ~1.42)
    var DOC_MIN_W_RATIO     = 0.35;   // card width must be >= 35% of frame width
    var DOC_MIN_H_RATIO     = 0.25;   // card height must be >= 25% of frame height
    var DOC_EDGE_MIN_GRAD   = 14;     // min gradient magnitude for an edge peak
    var DOC_MIN_TEXT_LINES  = 4;      // min horizontal text-like stripes inside
    var STABILITY_NEEDED_MS = 1200;   // milliseconds the score must stay above
    var SAMPLE_INTERVAL_MS  = 200;    // analyze 5fps (saves CPU/battery)
    var ANALYSIS_W          = 320;    // downscale width for analysis
    var ANALYSIS_H          = 180;    // 16:9
    var MAX_UPLOAD_W        = 1280;   // cap uploaded image width
    var JPEG_QUALITY        = 0.85;
    var FLIP_PROMPT_MS      = 2200;   // pause after front capture before back

    // ─── State (per session) ────────────────────────────────────────────────
    var state = null;

    // ─── Capability detection ───────────────────────────────────────────────
    /**
     * Returns true when the browser theoretically can open a live camera.
     * NOTE: this is a *capability* check, not a *permission* check. A return
     * value of true does NOT guarantee getUserMedia() will succeed — the
     * actual call can still be rejected with NotAllowedError if the user
     * (or a Permissions-Policy header) blocks access.
     *
     * Crucially this returns false on insecure contexts (http://) because
     * `navigator.mediaDevices` is undefined there on Chrome/Edge.
     */
    CWCamera.isSupported = function () {
        return !!(window.isSecureContext &&
                  navigator.mediaDevices &&
                  navigator.mediaDevices.getUserMedia &&
                  window.HTMLCanvasElement &&
                  window.Promise);
    };

    /**
     * Detailed diagnostics object — surfaced in the error overlay so support
     * can pinpoint the exact reason the camera failed without round-trips.
     */
    CWCamera.diagnose = function () {
        var ua = navigator.userAgent || '';
        return {
            secureContext:  !!window.isSecureContext,
            protocol:       window.location.protocol,
            host:           window.location.host,
            mediaDevices:   !!navigator.mediaDevices,
            getUserMedia:   !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
            permissionsApi: !!(navigator.permissions && navigator.permissions.query),
            isIOS:          /iPad|iPhone|iPod/i.test(ua) && !window.MSStream,
            isAndroid:      /Android/i.test(ua),
            isSafari:       /^((?!chrome|android).)*safari/i.test(ua),
            isChromiumLike: /Chrome|Chromium|CriOS|EdgA?|OPR\//i.test(ua),
            isFirefox:      /Firefox|FxiOS/i.test(ua),
        };
    };

    /**
     * Probe the Permissions API (where available) to detect that the user
     * has previously denied camera access — so we can show a more helpful
     * message instead of triggering another silent rejection.
     *
     * Returns a Promise<'granted'|'denied'|'prompt'|'unknown'>.
     */
    CWCamera.queryPermission = function () {
        if (!navigator.permissions || !navigator.permissions.query) {
            return Promise.resolve('unknown');
        }
        return navigator.permissions.query({ name: 'camera' })
            .then(function (status) { return status.state || 'unknown'; })
            .catch(function () { return 'unknown'; });
    };

    /* ════════════════════════════════════════════════════════════════════════
       PUBLIC API
       ════════════════════════════════════════════════════════════════════════ */

    CWCamera.open = function (opts) {
        if (state && state.open) return;     // already running
        opts = opts || {};

        // ── Pre-flight #1: insecure context (http:// on a real domain). ──
        // getUserMedia rejects silently on Chrome/Edge over HTTP, leaving
        // the user staring at "no permission" with no actionable hint.
        // We check up-front so we can show a *correct* explanation.
        if (!window.isSecureContext) {
            // Bypass the regular UI mount — go straight to a focused error.
            mountErrorOnly({
                title:  'الكاميرا تتطلّب اتصالاً آمناً (HTTPS)',
                detail: 'افتح الموقع باستخدام عنوان https:// (وليس http://) ثم حاول مرة أخرى. ' +
                        'هذا قيد أمني من المتصفح لحماية كاميرتك.',
                code:   'insecure_context',
                opts:   opts,
            });
            return;
        }

        // ── Pre-flight #2: capability check (very old browsers). ──
        if (!CWCamera.isSupported()) {
            opts.onError && opts.onError('camera_unsupported');
            return;
        }

        state = {
            open:           true,
            opts:           opts,
            stream:         null,
            currentSide:    'front',
            collectedFields:{},
            stableSinceTs:  0,
            lastFrameData:  null,
            sampleTimer:    null,
            uploading:      false,
            captureLocked:  false,
            $root:          null,
            $video:         null,
            $analysisCv:    null,
            announce:       function (msg) {},
        };

        mountUI();
        bindGlobalListeners();

        // ── Pre-flight #3: probe Permissions API (non-blocking). ──
        // If the state is already 'denied', skip the doomed getUserMedia call
        // and show targeted instructions for the current browser.
        CWCamera.queryPermission().then(function (perm) {
            if (perm === 'denied') {
                handleStreamError(makeErr('NotAllowedError', 'permissions_api_denied'));
                return;
            }
            startStream().then(function () {
                announce('الكاميرا جاهزة، ضع الوجه الأمامي للهوية داخل الإطار.');
                startQualityLoop();
            }).catch(handleStreamError);
        });
    };

    function makeErr(name, message) {
        var e = new Error(message || name);
        e.name = name;
        return e;
    }

    CWCamera.close = function () {
        if (!state) return;
        unbindGlobalListeners();
        stopStream();
        if (state.sampleTimer) {
            clearInterval(state.sampleTimer);
            state.sampleTimer = null;
        }
        if (state.$root && state.$root.length) {
            state.$root.remove();
        }
        state.open = false;
        state = null;
    };

    /* ════════════════════════════════════════════════════════════════════════
       STREAM / PERMISSIONS
       ════════════════════════════════════════════════════════════════════════ */

    function startStream() {
        var constraints = {
            audio: false,
            video: {
                facingMode: { ideal: 'environment' },
                width:      { ideal: 1920 },
                height:     { ideal: 1080 },
            },
        };
        return navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
            state.stream = stream;
            state.$video[0].srcObject = stream;
            return state.$video[0].play().catch(function () { /* autoplay quirks */ });
        });
    }

    function stopStream() {
        if (!state || !state.stream) return;
        try {
            state.stream.getTracks().forEach(function (t) { t.stop(); });
        } catch (_) { /* noop */ }
        state.stream = null;
    }

    /**
     * Translate a getUserMedia error into a precise, actionable Arabic message
     * tailored to the user's browser. We explicitly enumerate the common
     * cases instead of a single "denied" message, because mobile users have
     * very different recovery paths on iOS Safari vs. Android Chrome.
     */
    function handleStreamError(err) {
        var name = (err && err.name) || 'unknown';
        var diag = CWCamera.diagnose();

        var title;
        var detail;
        var code = name;

        switch (name) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                title = 'تم رفض إذن الكاميرا';
                detail = buildPermissionInstructions(diag);
                break;

            case 'NotFoundError':
            case 'DevicesNotFoundError':
            case 'OverconstrainedError':
                title  = 'لا توجد كاميرا متاحة';
                detail = 'لم نعثر على كاميرا خلفية على هذا الجهاز. جرّب جهازاً آخر أو ارفع صورة من المعرض.';
                break;

            case 'NotReadableError':
            case 'TrackStartError':
                title  = 'الكاميرا مستخدَمة الآن';
                detail = 'تطبيق آخر يستخدم الكاميرا (مثلاً مكالمة فيديو). أغلقه ثم أعد المحاولة.';
                break;

            case 'AbortError':
                title  = 'تمّ إلغاء فتح الكاميرا';
                detail = 'حدث اضطراب أثناء بدء الكاميرا — جرّب مرّة أخرى.';
                break;

            case 'SecurityError':
                title  = 'إعدادات الأمان تمنع الكاميرا';
                detail = 'يبدو أنّ سياسة المتصفح أو الموقع تمنع تشغيل الكاميرا (Permissions-Policy). ' +
                         'افتح الموقع مباشرةً (وليس داخل إطار iframe) ثم أعد المحاولة.';
                break;

            default:
                title  = 'تعذّر تشغيل الكاميرا';
                detail = 'حدث خطأ غير متوقّع: ' + name + '. ' +
                         'يمكنك المتابعة برفع صورة بدلاً من التصوير المباشر.';
        }

        showError(title, detail, diag, code);

        if (state && state.opts && state.opts.onError) {
            try { state.opts.onError(name); } catch (_) {}
        }
    }

    /**
     * Build browser/OS-specific recovery instructions for a denied camera
     * permission. This is the #1 support pain-point on mobile — most users
     * don't know that "denied" can be reset only via the address-bar lock
     * icon or the OS settings.
     */
    function buildPermissionInstructions(diag) {
        if (diag.isIOS) {
            return 'على آيفون: افتح "الإعدادات" ← Safari ← الكاميرا ← اختر "اسمح" أو "اسأل". ' +
                   'ثم أعد تحميل الصفحة. يمكنك أيضاً استخدام رفع الصورة كبديل سريع.';
        }
        if (diag.isAndroid && diag.isChromiumLike) {
            return 'على أندرويد: اضغط على أيقونة القفل 🔒 بجانب العنوان ← الأذونات ← الكاميرا ← اسمح. ' +
                   'ثم أعد تحميل الصفحة. أو استخدم زر "ارفع ملفاً" أدناه.';
        }
        if (diag.isFirefox) {
            return 'في فايرفوكس: اضغط على الأيقونة في شريط العنوان وغيّر إذن الكاميرا إلى "اسمح". ' +
                   'ثم أعد المحاولة، أو ارفع صورة من جهازك.';
        }
        return 'في إعدادات المتصفح: اسمح بالوصول إلى الكاميرا لهذا الموقع، ثم أعد تحميل الصفحة. ' +
               'بديلاً، استخدم زر "ارفع ملفاً" أدناه لرفع صورة الهوية.';
    }

    /**
     * Mount only the error-state of the overlay (used when we know up-front
     * — e.g. insecure context — that the camera will never start).
     */
    function mountErrorOnly(args) {
        // Build a stripped-down state so showError + close work normally.
        state = {
            open:           true,
            opts:           args.opts || {},
            stream:         null,
            currentSide:    'front',
            collectedFields:{},
            stableSinceTs:  0,
            lastFrameData:  null,
            sampleTimer:    null,
            uploading:      false,
            captureLocked:  true,
            $root:          null,
            $video:         null,
            $analysisCv:    null,
            announce:       function (msg) {},
        };
        mountUI();
        bindGlobalListeners();
        showError(args.title, args.detail, CWCamera.diagnose(), args.code || 'precondition_failed');
        if (args.opts.onError) try { args.opts.onError(args.code); } catch (_) {}
    }

    /* ════════════════════════════════════════════════════════════════════════
       UI MOUNT
       ════════════════════════════════════════════════════════════════════════ */

    function mountUI() {
        var html = ''
            + '<div class="cwcam" data-cwcam-root role="dialog" aria-modal="true"'
            + '     aria-labelledby="cwcam-title" aria-describedby="cwcam-status">'
            + '  <div class="cwcam__topbar">'
            + '    <button type="button" class="cwcam__close" data-cwcam-close'
            + '            aria-label="إغلاق الكاميرا">'
            + '      <i class="fa fa-times" aria-hidden="true"></i>'
            + '    </button>'
            + '    <h2 class="cwcam__title" id="cwcam-title">'
            + '      <span data-cwcam-title-text>مسح الهوية — الوجه الأمامي</span>'
            + '    </h2>'
            + '    <div class="cwcam__side-pill" data-cwcam-side-pill>1 / 2</div>'
            + '  </div>'

            + '  <div class="cwcam__stage">'
            + '    <video class="cwcam__video" data-cwcam-video'
            + '           autoplay playsinline muted'
            + '           aria-label="معاينة الكاميرا"></video>'

            + '    <div class="cwcam__frame" data-cwcam-frame aria-hidden="true">'
            + '      <span class="cwcam__corner cwcam__corner--tl"></span>'
            + '      <span class="cwcam__corner cwcam__corner--tr"></span>'
            + '      <span class="cwcam__corner cwcam__corner--bl"></span>'
            + '      <span class="cwcam__corner cwcam__corner--br"></span>'
            + '      <div class="cwcam__hint" data-cwcam-hint>'
            + '        ضع الهوية داخل الإطار'
            + '      </div>'
            + '    </div>'

            + '    <div class="cwcam__progress" data-cwcam-progress aria-hidden="true">'
            + '      <svg viewBox="0 0 64 64" width="64" height="64">'
            + '        <circle class="cwcam__progress-track" cx="32" cy="32" r="28"></circle>'
            + '        <circle class="cwcam__progress-fill"  cx="32" cy="32" r="28"'
            + '                data-cwcam-ring></circle>'
            + '      </svg>'
            + '      <span class="cwcam__progress-icon" data-cwcam-ring-icon>'
            + '        <i class="fa fa-camera" aria-hidden="true"></i>'
            + '      </span>'
            + '    </div>'

            + '    <div class="cwcam__flash" data-cwcam-flash aria-hidden="true"></div>'

            + '    <div class="cwcam__overlay cwcam__overlay--flip" hidden'
            + '         data-cwcam-flip>'
            + '      <div class="cwcam__flip-card" aria-hidden="true">'
            + '        <i class="fa fa-id-card-o cwcam__flip-icon"></i>'
            + '      </div>'
            + '      <p class="cwcam__overlay-title">اقلب الهوية الآن</p>'
            + '      <p class="cwcam__overlay-sub">سنلتقط الوجه الخلفي تلقائياً</p>'
            + '    </div>'

            + '    <div class="cwcam__overlay cwcam__overlay--processing" hidden'
            + '         data-cwcam-processing>'
            + '      <div class="cwcam__spinner" aria-hidden="true"></div>'
            + '      <p class="cwcam__overlay-title">جارٍ تحليل الهوية…</p>'
            + '    </div>'

            + '    <div class="cwcam__overlay cwcam__overlay--success" hidden'
            + '         data-cwcam-success aria-live="polite">'
            + '      <i class="fa fa-check-circle cwcam__success-icon" aria-hidden="true"></i>'
            + '      <p class="cwcam__overlay-title" data-cwcam-success-msg>تمّ بنجاح</p>'
            + '    </div>'

            + '    <div class="cwcam__overlay cwcam__overlay--error" hidden'
            + '         data-cwcam-error role="alert">'
            + '      <i class="fa fa-exclamation-triangle cwcam__error-icon" aria-hidden="true"></i>'
            + '      <p class="cwcam__overlay-title" data-cwcam-error-title></p>'
            + '      <p class="cwcam__overlay-sub"   data-cwcam-error-detail></p>'
            + '      <div class="cwcam__overlay-actions">'
            + '        <button type="button" class="cwcam__btn cwcam__btn--primary"'
            + '                data-cwcam-fallback>'
            + '          <i class="fa fa-upload" aria-hidden="true"></i>'
            + '          ارفع صورة بدلاً'
            + '        </button>'
            + '        <button type="button" class="cwcam__btn cwcam__btn--ghost"'
            + '                data-cwcam-retry>أعد المحاولة</button>'
            + '      </div>'
            + '      <details class="cwcam__diag" data-cwcam-diag-wrap>'
            + '        <summary class="cwcam__diag-summary">تفاصيل تقنية للدعم</summary>'
            + '        <pre class="cwcam__diag-body" data-cwcam-diag></pre>'
            + '      </details>'
            + '    </div>'
            + '  </div>'

            + '  <div class="cwcam__bottombar">'
            + '    <p class="cwcam__status" id="cwcam-status" data-cwcam-status'
            + '       role="status" aria-live="polite" aria-atomic="true">'
            + '      جارٍ تشغيل الكاميرا…'
            + '    </p>'
            + '    <div class="cwcam__actions">'
            + '      <button type="button" class="cwcam__btn cwcam__btn--ghost"'
            + '              data-cwcam-manual>'
            + '        <i class="fa fa-hand-pointer-o" aria-hidden="true"></i>'
            + '        التقاط يدوي'
            + '      </button>'
            + '      <button type="button" class="cwcam__btn cwcam__btn--ghost"'
            + '              data-cwcam-fallback>'
            + '        <i class="fa fa-upload" aria-hidden="true"></i>'
            + '        ارفع ملفاً بدلاً'
            + '      </button>'
            + '      <button type="button" class="cwcam__btn cwcam__btn--ghost"'
            + '              hidden data-cwcam-skip-back'
            + '              title="استخدمه فقط إذا تعذّر تصوير الظهر تماماً (سيُحفظ السجل بدون رقم البطاقة)">'
            + '        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>'
            + '        تخطّي رغم عدم اكتمال البيانات'
            + '      </button>'
            + '    </div>'
            + '  </div>'

            + '  <canvas data-cwcam-analysis class="cwcam__hidden-canvas"'
            + '          width="' + ANALYSIS_W + '" height="' + ANALYSIS_H + '"></canvas>'
            + '</div>';

        state.$root        = $(html).appendTo(document.body);
        state.$video       = state.$root.find('[data-cwcam-video]');
        state.$analysisCv  = state.$root.find('[data-cwcam-analysis]');

        var $statusEl = state.$root.find('[data-cwcam-status]');
        state.announce = function (msg) {
            $statusEl.text(msg);
        };

        // The ring's circumference (2πr where r=28) — pre-computed for offset math.
        var CIRC = 2 * Math.PI * 28;
        state.$root.find('[data-cwcam-ring]').css({
            'stroke-dasharray':  CIRC,
            'stroke-dashoffset': CIRC,
        });
        state._ringCirc = CIRC;

        bindUIEvents();
        // Trap focus inside the dialog.
        state.$root.find('[data-cwcam-close]').focus();
        $('html').addClass('cwcam-locked');
    }

    function bindUIEvents() {
        state.$root.on('click', '[data-cwcam-close]', function () {
            if (state.opts.onCancel) try { state.opts.onCancel(); } catch (_) {}
            CWCamera.close();
        });
        state.$root.on('click', '[data-cwcam-manual]', function () {
            triggerCapture(/*manual=*/true);
        });
        state.$root.on('click', '[data-cwcam-fallback]', function () {
            if (state.opts.onFallback) try { state.opts.onFallback(); } catch (_) {}
            CWCamera.close();
        });
        state.$root.on('click', '[data-cwcam-retry]', function () {
            hideOverlay('error');
            startQualityLoop();
        });

        // ── "Skip back" — last-resort escape on the back-side step. ──
        // The back of the ID is REQUIRED (it carries document_number which
        // doesn't appear on the front). We hide this button by default and
        // only reveal it after several failed attempts so the user has an
        // out when their card is genuinely unreadable (e.g. damaged surface,
        // mirror finish on top of glare). Skipping here means the customer
        // record will be created WITHOUT a document_number — flagged for
        // manual entry later.
        state.$root.on('click', '[data-cwcam-skip-back]', function () {
            if (!confirm('سيُكمل النظام بدون رقم البطاقة (Document No). يجب إدخاله يدوياً لاحقاً. هل تريد المتابعة؟')) {
                return;
            }
            announce('تمّ تخطّي الوجه الخلفي.');
            if (state.opts.onComplete) {
                try { state.opts.onComplete(state.collectedFields, state.collectedImages); } catch (_) {}
            }
            CWCamera.close();
        });
    }

    function bindGlobalListeners() {
        $(document).on('keydown.cwcam', function (e) {
            if (!state) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                state.$root.find('[data-cwcam-close]').click();
            } else if (e.key === ' ' || e.code === 'Space') {
                if (!state.uploading && !state.captureLocked) {
                    e.preventDefault();
                    triggerCapture(true);
                }
            }
        });
        $(window).on('beforeunload.cwcam', stopStream);
        $(document).on('visibilitychange.cwcam', function () {
            if (document.hidden && state && state.stream) {
                // Pause analysis to save battery; resume when visible.
                if (state.sampleTimer) clearInterval(state.sampleTimer);
                state.sampleTimer = null;
            } else if (!document.hidden && state && state.stream && !state.sampleTimer) {
                startQualityLoop();
            }
        });
    }

    function unbindGlobalListeners() {
        $(document).off('keydown.cwcam visibilitychange.cwcam');
        $(window).off('beforeunload.cwcam');
        $('html').removeClass('cwcam-locked');
    }

    /* ════════════════════════════════════════════════════════════════════════
       QUALITY ANALYSIS LOOP
       ════════════════════════════════════════════════════════════════════════ */

    function startQualityLoop() {
        if (state.sampleTimer) clearInterval(state.sampleTimer);
        state.stableSinceTs = 0;
        state.lastFrameData = null;
        state.sampleTimer = setInterval(sampleFrame, SAMPLE_INTERVAL_MS);
    }

    function sampleFrame() {
        if (!state || !state.stream || state.uploading || state.captureLocked) return;

        var v = state.$video[0];
        if (!v || v.readyState < 2 || v.videoWidth === 0) return;

        var cv  = state.$analysisCv[0];
        var ctx = cv.getContext('2d', { willReadFrequently: true });

        try {
            ctx.drawImage(v, 0, 0, ANALYSIS_W, ANALYSIS_H);
        } catch (e) { return; }

        var img;
        try { img = ctx.getImageData(0, 0, ANALYSIS_W, ANALYSIS_H); }
        catch (e) { return; }

        var metrics = analyzeFrame(img.data);
        var score = computeScore(metrics);

        renderQuality(score, metrics);

        // Auto-capture demands BOTH: image is technically usable (score ≥
        // QUALITY_THRESHOLD) AND we're confident a document is in frame
        // (docScore ≥ DOC_THRESHOLD). The doc gate prevents firing on a
        // washing machine, hand, ceiling, etc. that happens to be sharp
        // and well-lit. Manual capture still works regardless.
        var qualityOk = score >= QUALITY_THRESHOLD;
        var docOk     = (metrics.docScore || 0) >= DOC_THRESHOLD;

        if (qualityOk && docOk) {
            if (!state.stableSinceTs) state.stableSinceTs = Date.now();
            var elapsed = Date.now() - state.stableSinceTs;
            if (elapsed >= STABILITY_NEEDED_MS) {
                triggerCapture(false);
            }
        } else {
            state.stableSinceTs = 0;
        }
    }

    /**
     * Analyze a single frame's pixel buffer.
     * Returns { brightness, sharpness, stability } each 0-100ish.
     *
     * Implementation notes:
     *   • brightness — mean luma using Rec.709 weights.
     *   • sharpness  — variance of a 4-neighbour Laplacian on luma; we sample
     *                  every 4th pixel for speed (still ~14k samples → robust).
     *   • stability  — sum of per-pixel luma diffs vs. previous frame (sampled),
     *                  divided by sample count, then mapped 1 - diff/255.
     */
    function analyzeFrame(data) {
        var len = data.length;
        var W = ANALYSIS_W, H = ANALYSIS_H;
        var lumaArr = new Float32Array(W * H);

        // Pass 1: per-pixel luma.
        var lumaSum = 0;
        for (var i = 0, p = 0; i < len; i += 4, p++) {
            var L = 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
            lumaArr[p] = L;
            lumaSum += L;
        }
        var meanLuma = lumaSum / (W * H);

        // Pass 2: global Laplacian variance (sharpness).
        var lap = 0, lap2 = 0, lapCount = 0;
        for (var y = 1; y < H - 1; y += 2) {
            for (var x = 1; x < W - 1; x += 2) {
                var idx = y * W + x;
                var v = -4 * lumaArr[idx]
                      + lumaArr[idx - 1]
                      + lumaArr[idx + 1]
                      + lumaArr[idx - W]
                      + lumaArr[idx + W];
                lap  += v;
                lap2 += v * v;
                lapCount++;
            }
        }
        var lapMean = lap / lapCount;
        var lapVar  = (lap2 / lapCount) - (lapMean * lapMean);

        // Pass 3: stability (vs last frame, sampled stride 4).
        var stabilityScore = 0;
        if (state.lastFrameData) {
            var prev = state.lastFrameData;
            var diff = 0, diffCount = 0;
            for (var k = 0; k < lumaArr.length; k += 4) {
                diff += Math.abs(lumaArr[k] - prev[k]);
                diffCount++;
            }
            var meanDiff = diff / diffCount;          // 0-255
            stabilityScore = Math.max(0, 100 - meanDiff * 4);
        } else {
            stabilityScore = 50;
        }
        state.lastFrameData = lumaArr;

        // ── Document detection: find the actual card rectangle. ──
        var docInfo = detectCardRectangle(lumaArr, W, H);
        var docScore = scoreDocument(docInfo, lumaArr, W, H);

        return {
            brightness: meanLuma,
            sharpness:  Math.min(100, lapVar / 10),
            stability:  stabilityScore,
            docScore:   docScore,
            _doc:       docInfo,    // {found, top, bot, left, right, aspect, textLines, contrast}
        };
    }

    /**
     * Detect a card-shaped rectangle anywhere in the frame.
     *
     * Algorithm:
     *   1. Project luma gradients onto rows (vertical-gradient sum per row)
     *      and columns (horizontal-gradient sum per col). These projections
     *      have sharp peaks at the actual edges of any rectangular object.
     *   2. Scan inward from each side looking for the first row/column
     *      whose projection exceeds a noise-tolerant threshold.
     *   3. The four peak positions form a candidate rectangle. We validate
     *      its aspect ratio (ID-1 ≈ 1.586) and minimum size.
     *
     * Returns:
     *   { found: false }                       on no detection
     *   { found: true, top, bot, left, right, width, height, aspect,
     *     edgeStrength }                       on success
     */
    function detectCardRectangle(luma, W, H) {
        // Build row & column gradient projections.
        var rowG = new Float32Array(H);
        var colG = new Float32Array(W);

        for (var y = 1; y < H - 1; y++) {
            var rs = 0;
            for (var x = 0; x < W; x++) {
                rs += Math.abs(luma[(y + 1) * W + x] - luma[(y - 1) * W + x]);
            }
            rowG[y] = rs / W;
        }
        for (var x2 = 1; x2 < W - 1; x2++) {
            var cs = 0;
            for (var y2 = 0; y2 < H; y2++) {
                cs += Math.abs(luma[y2 * W + (x2 + 1)] - luma[y2 * W + (x2 - 1)]);
            }
            colG[x2] = cs / H;
        }

        // Find the strongest peak in a range, scanning inward from `start`
        // toward `end`. Returns the index, or -1 if no peak passes threshold.
        function findEdge(arr, start, end, thresh) {
            var step = (end > start) ? 1 : -1;
            var bestI = -1, bestV = thresh;
            for (var i = start; i !== end; i += step) {
                if (arr[i] > bestV) {
                    bestV = arr[i];
                    bestI = i;
                    // Early exit: once we found a peak, stop after the next
                    // local maximum window passes (to avoid jumping past the
                    // real edge to inner text).
                    if (bestV > thresh * 2) break;
                }
            }
            return { idx: bestI, val: bestV };
        }

        var thresh = DOC_EDGE_MIN_GRAD;
        // Top edge: scan from y=2 → H/2.
        var top   = findEdge(rowG, 2,           Math.floor(H / 2),     thresh);
        // Bottom edge: scan from y=H-3 → H/2.
        var bot   = findEdge(rowG, H - 3,       Math.floor(H / 2),     thresh);
        // Left edge: scan from x=2 → W/2.
        var left  = findEdge(colG, 2,           Math.floor(W / 2),     thresh);
        // Right edge: scan from x=W-3 → W/2.
        var right = findEdge(colG, W - 3,       Math.floor(W / 2),     thresh);

        if (top.idx === -1 || bot.idx === -1 || left.idx === -1 || right.idx === -1) {
            return { found: false, reason: 'no_edges' };
        }

        var rectW = right.idx - left.idx;
        var rectH = bot.idx - top.idx;

        if (rectW < W * DOC_MIN_W_RATIO) return { found: false, reason: 'too_narrow' };
        if (rectH < H * DOC_MIN_H_RATIO) return { found: false, reason: 'too_short' };

        var aspect = rectW / rectH;
        if (aspect < DOC_ASPECT_MIN || aspect > DOC_ASPECT_MAX) {
            return { found: false, reason: 'wrong_aspect', aspect: aspect };
        }

        // Edge strength = average of the 4 detected peak values.
        var edgeStrength = (top.val + bot.val + left.val + right.val) / 4;

        return {
            found: true,
            top: top.idx, bot: bot.idx, left: left.idx, right: right.idx,
            width: rectW, height: rectH,
            aspect: aspect,
            edgeStrength: edgeStrength,
        };
    }

    /**
     * Score the detected rectangle 0-100 based on:
     *   1. Aspect ratio fit — closer to 1.586 is better
     *   2. Edge strength — stronger peaks = real cardstock
     *   3. Text-line count inside — real IDs have ≥ 4 horizontal text stripes
     *   4. Brightness contrast inside vs outside — card stands out
     */
    function scoreDocument(doc, luma, W, H) {
        if (!doc || !doc.found) return 0;

        // 1. Aspect ratio score (Gaussian around 1.586).
        var aspectErr = Math.abs(doc.aspect - 1.586);
        var aspectScore = Math.max(0, 100 - aspectErr * 250);  // 1.586±0.4 → 0

        // 2. Edge strength score.
        var edgeScore;
        var es = doc.edgeStrength;
        if (es >= 35)      edgeScore = 100;
        else if (es >= 18) edgeScore = 50 + (es - 18) / 17 * 50;
        else if (es >= 10) edgeScore = (es - 10) / 8 * 50;
        else               edgeScore = 0;

        // 3. Text-line count inside the detected rectangle.
        var textLines = countTextLines(luma, W, doc.left, doc.top, doc.right, doc.bot);
        doc.textLines = textLines;

        var textScore;
        if (textLines >= 8)      textScore = 100;
        else if (textLines >= 5) textScore = 60 + (textLines - 5) / 3 * 40;
        else if (textLines >= 2) textScore = (textLines - 2) / 3 * 60;
        else                     textScore = 0;

        // 4. Inside vs outside contrast.
        var contrast = computeInsideOutsideContrast(luma, W, H, doc);
        doc.contrast = contrast;
        var contrastScore;
        var ac = Math.abs(contrast);
        if (ac >= 25)      contrastScore = 100;
        else if (ac >= 10) contrastScore = 50 + (ac - 10) / 15 * 50;
        else               contrastScore = ac / 10 * 50;

        // ── HARD GATES — any single failure vetoes the doc detection. ──
        // These prevent a washing-machine + "kind of rectangular" false
        // positive from passing on accumulated weak scores alone.
        if (textLines < DOC_MIN_TEXT_LINES) {
            doc._gate = 'no_text';
            return Math.min(40, Math.round(
                aspectScore * 0.3 + edgeScore * 0.4 + contrastScore * 0.3
            ));
        }
        if (es < DOC_EDGE_MIN_GRAD) {
            doc._gate = 'weak_edges';
            return Math.min(40, Math.round(aspectScore * 0.5 + textScore * 0.5));
        }

        // Final score — weighted geometric mean so a single weak signal
        // pulls the overall score down hard.
        var s = Math.pow(Math.max(5, aspectScore)   * 0.01, 0.20)
              * Math.pow(Math.max(5, edgeScore)     * 0.01, 0.30)
              * Math.pow(Math.max(5, textScore)     * 0.01, 0.35)
              * Math.pow(Math.max(5, contrastScore) * 0.01, 0.15);
        return Math.round(s * 100);
    }

    /**
     * Count horizontal text-like stripes inside a rectangle. A "text stripe"
     * is a row whose horizontal-gradient activity exceeds a threshold AND
     * sits within a band where activity drops below threshold above and
     * below it (i.e. it's a discrete line, not part of a uniform texture).
     */
    function countTextLines(luma, W, x0, y0, x1, y1) {
        var rectW = x1 - x0;
        var rectH = y1 - y0;
        if (rectW <= 0 || rectH <= 0) return 0;

        // Horizontal-gradient activity per row, restricted to the rectangle.
        var rowAct = new Float32Array(rectH);
        for (var ry = 0; ry < rectH; ry++) {
            var s = 0;
            var y = y0 + ry;
            for (var x = x0 + 1; x < x1 - 1; x++) {
                s += Math.abs(luma[y * W + (x + 1)] - luma[y * W + (x - 1)]);
            }
            rowAct[ry] = s / Math.max(1, rectW);
        }

        // Smooth (3-tap moving average) to suppress single-pixel noise.
        var smoothed = new Float32Array(rectH);
        for (var i = 1; i < rectH - 1; i++) {
            smoothed[i] = (rowAct[i - 1] + rowAct[i] + rowAct[i + 1]) / 3;
        }
        smoothed[0] = rowAct[0];
        smoothed[rectH - 1] = rowAct[rectH - 1];

        // Compute mean + dynamic threshold = mean * 1.4.
        var mean = 0;
        for (var j = 0; j < rectH; j++) mean += smoothed[j];
        mean /= rectH;
        if (mean < 4) return 0;     // empty / uniform interior
        var thr = Math.max(8, mean * 1.4);

        // Walk and count "high → low" transitions = end of a text stripe.
        var lines = 0;
        var inStripe = false;
        var stripeStart = 0;
        for (var k = 0; k < rectH; k++) {
            if (smoothed[k] >= thr) {
                if (!inStripe) { inStripe = true; stripeStart = k; }
            } else if (inStripe) {
                inStripe = false;
                var thickness = k - stripeStart;
                // A real text stripe is 1-6 rows thick at this resolution.
                // A washing-machine logo "stripe" is usually 0-1 (thin
                // accent line) or 10+ (a big colored band). Both rejected.
                if (thickness >= 1 && thickness <= 8) lines++;
            }
        }
        return lines;
    }

    /**
     * Brightness contrast: mean luma inside the rectangle vs outside.
     * Positive = inside brighter (typical white card on darker surface).
     */
    function computeInsideOutsideContrast(luma, W, H, doc) {
        var inSum = 0, inN = 0, outSum = 0, outN = 0;
        // Sample stride 2 for speed.
        for (var y = 0; y < H; y += 2) {
            var inside = (y >= doc.top && y < doc.bot);
            for (var x = 0; x < W; x += 2) {
                var L = luma[y * W + x];
                if (inside && x >= doc.left && x < doc.right) {
                    inSum += L; inN++;
                } else {
                    outSum += L; outN++;
                }
            }
        }
        var meanIn  = inN  ? inSum  / inN  : 0;
        var meanOut = outN ? outSum / outN : 0;
        return meanIn - meanOut;
    }

    /**
     * Combine the three sub-scores into one 0-100 overall.
     * Brightness must be in a usable band (penalize too-dim AND too-bright).
     */
    function computeScore(m) {
        var bScore;
        if (m.brightness < 40)       bScore = m.brightness / 40 * 60;
        else if (m.brightness < 55)  bScore = 60 + (m.brightness - 40) / 15 * 40;
        else if (m.brightness < 200) bScore = 100;
        else if (m.brightness < 230) bScore = 100 - (m.brightness - 200) / 30 * 40;
        else                          bScore = 60 - Math.min(60, (m.brightness - 230) / 25 * 60);

        var sScore = Math.min(100, Math.max(0, m.sharpness));
        var tScore = Math.min(100, Math.max(0, m.stability));

        return Math.round(bScore * 0.30 + sScore * 0.35 + tScore * 0.35);
    }

    /* ════════════════════════════════════════════════════════════════════════
       UI FEEDBACK
       ════════════════════════════════════════════════════════════════════════ */

    function renderQuality(score, m) {
        var $frame = state.$root.find('[data-cwcam-frame]');
        var docOk      = (m.docScore || 0) >= DOC_THRESHOLD;
        var qualityOk  = score >= QUALITY_THRESHOLD;

        // Frame border colors:
        //   good  → green ring + auto-fire countdown active (only when both
        //           technical quality AND document presence pass)
        //   fair  → yellow (acceptable lighting/sharpness but no doc detected,
        //           OR doc detected but a bit blurry)
        //   poor  → red (nothing usable in frame)
        var quality;
        if (qualityOk && docOk)                quality = 'good';
        else if (score >= 50 || (m.docScore || 0) >= 30) quality = 'fair';
        else                                   quality = 'poor';

        if ($frame.attr('data-quality') !== quality) {
            $frame.attr('data-quality', quality);
        }

        // Update ring fill (only fills while we're in "good" stable streak).
        var ringPct = 0;
        if (quality === 'good' && state.stableSinceTs) {
            ringPct = Math.min(1, (Date.now() - state.stableSinceTs) / STABILITY_NEEDED_MS);
        }
        var $ring = state.$root.find('[data-cwcam-ring]');
        var offset = state._ringCirc * (1 - ringPct);
        $ring.css('stroke-dashoffset', offset);

        // ── Hint priority (most actionable first). ──
        // Doc-presence hints come BEFORE technical hints so the user is
        // told "place a card in the frame" before being asked to fix
        // brightness on an empty surface.
        var doc = m._doc || {};
        var hint;
        if (!doc.found) {
            // No card-shaped rectangle detected at all.
            switch (doc.reason) {
                case 'no_edges':
                    hint = 'وجّه الكاميرا نحو الوثيقة — لم يُكشَف إطار بطاقة';
                    break;
                case 'too_narrow':
                case 'too_short':
                    hint = 'اقترب أكثر حتى تملأ الوثيقة الإطار';
                    break;
                case 'wrong_aspect':
                    hint = 'هذه ليست بطاقة هوية — وجّه الكاميرا نحو الوثيقة الصحيحة';
                    break;
                default:
                    hint = 'ضع وثيقة هوية أو شهادة تعيين أمام الكاميرا';
            }
        } else if ((doc.textLines || 0) < DOC_MIN_TEXT_LINES) {
            // We see a rectangle, but no text inside — likely not a document.
            hint = 'لم يُكشَف نص داخل الإطار — هل الوثيقة بالاتجاه الصحيح؟';
        } else if (m.brightness < 50) {
            hint = 'الإضاءة منخفضة — اقترب من النور';
        } else if (m.brightness > 220) {
            hint = 'الإضاءة قوية جداً — قلّل السطوع';
        } else if (m.sharpness < 30) {
            hint = 'الصورة غير واضحة — ثبّت يدك';
        } else if (m.stability < 60) {
            hint = 'ثبّت الكاميرا قليلاً';
        } else if (!docOk) {
            hint = 'اقترب قليلاً وثبّت الوثيقة في وسط الإطار';
        } else if (!qualityOk) {
            hint = 'اقترب أو ابتعد قليلاً للتوضيح';
        } else {
            hint = 'ممتاز — لا تحرّك يدك';
        }
        state.$root.find('[data-cwcam-hint]').text(hint);
    }

    /* ════════════════════════════════════════════════════════════════════════
       CAPTURE + UPLOAD
       ════════════════════════════════════════════════════════════════════════ */

    function triggerCapture(manual) {
        if (state.uploading || state.captureLocked) return;
        state.captureLocked = true;
        if (state.sampleTimer) {
            clearInterval(state.sampleTimer);
            state.sampleTimer = null;
        }

        flashEffect();

        captureBlob().then(function (blob) {
            uploadCapture(blob);
        }).catch(function (err) {
            showError('تعذّر التقاط الصورة — أعد المحاولة.');
            state.captureLocked = false;
            startQualityLoop();
        });
    }

    function flashEffect() {
        var prefersReducedMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) return;

        var $f = state.$root.find('[data-cwcam-flash]');
        $f.removeClass('cwcam__flash--active');
        // force reflow so re-adding the class restarts the animation
        void $f[0].offsetWidth;
        $f.addClass('cwcam__flash--active');
        if (navigator.vibrate) try { navigator.vibrate(40); } catch (_) {}
    }

    /**
     * Capture current video frame to a JPEG blob, downscaled to MAX_UPLOAD_W.
     */
    function captureBlob() {
        return new Promise(function (resolve, reject) {
            var v = state.$video[0];
            if (!v || !v.videoWidth) return reject(new Error('no_video'));

            var ratio = v.videoHeight / v.videoWidth;
            var w = Math.min(v.videoWidth, MAX_UPLOAD_W);
            var h = Math.round(w * ratio);

            var canvas = document.createElement('canvas');
            canvas.width  = w;
            canvas.height = h;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(v, 0, 0, w, h);

            if (canvas.toBlob) {
                canvas.toBlob(function (blob) {
                    if (blob) resolve(blob);
                    else      reject(new Error('toBlob_failed'));
                }, 'image/jpeg', JPEG_QUALITY);
            } else {
                // Legacy fallback (very old browsers).
                var dataUrl = canvas.toDataURL('image/jpeg', JPEG_QUALITY);
                var bin = atob(dataUrl.split(',')[1]);
                var arr = new Uint8Array(bin.length);
                for (var i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
                resolve(new Blob([arr], { type: 'image/jpeg' }));
            }
        });
    }

    function uploadCapture(blob) {
        state.uploading = true;
        showOverlay('processing');

        var fd = new FormData();
        fd.append('file', blob, 'scan_' + state.currentSide + '.jpg');
        fd.append('side', state.currentSide);
        fd.append('_csrf-backend', state.opts.csrfToken || '');

        $.ajax({
            url: state.opts.scanUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000,
            headers: {
                'X-CSRF-Token':     state.opts.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .done(function (resp) { handleUploadSuccess(resp); })
        .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.error) ||
                      'فشل الاتصال بخدمة المسح.';
            handleUploadFailure(msg);
        })
        .always(function () {
            state.uploading = false;
        });
    }

    function handleUploadSuccess(resp) {
        hideOverlay('processing');
        if (!resp || !resp.ok) {
            handleUploadFailure((resp && resp.error) ||
                'تعذّر تحليل الهوية — جرّب مرة أخرى بإضاءة أفضل.');
            return;
        }

        // Merge fields from this side into the running collection.
        if (resp.fields) {
            $.extend(state.collectedFields, resp.fields);
        }

        // Track the persisted Media row id per side, so the caller (scan.js)
        // can stash it in a hidden form field — actionFinish() then adopts
        // these orphan rows for the new customer.
        if (!state.collectedImages) state.collectedImages = {};
        if (resp.image_id) {
            state.collectedImages[state.currentSide] = resp.image_id;
        }

        // Hand the side's fields to the caller immediately so the form starts
        // populating — this gives faster perceived feedback than waiting for
        // both sides.
        if (state.opts.onFields) {
            try {
                state.opts.onFields(
                    resp.fields || {},
                    state.currentSide,
                    resp.unmapped || {},
                    { image_id: resp.image_id || null, issuing_body: resp.issuing_body || null }
                );
            } catch (_) {}
        }

        var nextAction = resp.next_action || (state.currentSide === 'front' ? 'capture_back' : 'done');

        if (nextAction === 'capture_back') {
            // Front done; transition to back.
            state.currentSide = 'back';
            state.backFailures = 0;
            state.$root.find('[data-cwcam-title-text]').text('مسح الهوية — الوجه الخلفي');
            state.$root.find('[data-cwcam-side-pill]').text('2 / 2');
            // The back is REQUIRED (we need document_number from MRZ/serial).
            // Hide the skip button initially and only show it as a last-resort
            // after multiple failures (handleUploadFailure does that).
            state.$root.find('[data-cwcam-skip-back]')
                .prop('hidden', true)
                .removeClass('cwcam__btn--pulse');
            announce('تم التقاط الوجه الأمامي. اقلب الهوية الآن.');
            showFlipPrompt();
        } else {
            // All done — show a brief success overlay so the user sees the
            // green checkmark instead of a confusing dark frame for ~500ms.
            // If the backend included a `note` (e.g. "intelligence card back
            // is blank — that's expected"), surface it here.
            var successMsg = resp.note || 'تمّ المسح بنجاح';
            announce(successMsg);
            showSuccess(successMsg);
            if (state.opts.onComplete) {
                try { state.opts.onComplete(state.collectedFields, state.collectedImages); } catch (_) {}
            }
            // Slightly longer when there's a note so the user can read it.
            setTimeout(CWCamera.close, resp.note ? 1800 : 900);
        }
    }

    function handleUploadFailure(msg) {
        // Track back-side failures so we can surface the last-resort "skip"
        // option after repeated retries. The back is REQUIRED for a complete
        // record (it carries document_number) so we make the user try at
        // least three times before exposing the bypass — and even then it's
        // marked as "incomplete" not "done".
        if (state.currentSide === 'back') {
            state.backFailures = (state.backFailures || 0) + 1;

            if (state.backFailures === 1) {
                msg += '\n\nنصيحة: قرّب الكاميرا حتى تملأ البطاقة الإطار، وثبّتها على سطح مظلم لقراءة سطور MRZ.';
            } else if (state.backFailures === 2) {
                msg += '\n\nجرّب إضاءة جانبية (لا تواجه الكاميرا مباشرة) لتقليل الانعكاس.';
            } else if (state.backFailures >= 3) {
                msg += '\n\nإذا تعذّر الالتقاط: يمكنك "تخطّي رغم عدم اكتمال البيانات" أدناه — لكن سيلزمك إدخال رقم البطاقة يدوياً لاحقاً.';
                state.$root.find('[data-cwcam-skip-back]')
                    .prop('hidden', false)
                    .addClass('cwcam__btn--pulse');
            }
        }
        showError(msg);
        state.captureLocked = false;
        // Don't auto-resume; require user confirmation via Retry button.
    }

    function showSuccess(msg) {
        state.$root.find('[data-cwcam-success-msg]').text(msg || 'تمّ بنجاح');
        showOverlay('success');
    }

    function showFlipPrompt() {
        showOverlay('flip');
        setTimeout(function () {
            if (!state) return;
            hideOverlay('flip');
            state.captureLocked = false;
            startQualityLoop();
        }, FLIP_PROMPT_MS);
    }

    /* ════════════════════════════════════════════════════════════════════════
       OVERLAY HELPERS
       ════════════════════════════════════════════════════════════════════════ */

    function showOverlay(name) {
        state.$root.find('[data-cwcam-' + name + ']').prop('hidden', false);
    }
    function hideOverlay(name) {
        state.$root.find('[data-cwcam-' + name + ']').prop('hidden', true);
    }
    /**
     * Show the error overlay with a title, an actionable detail line, and a
     * collapsible diagnostics dump.
     *
     * @param {string} title         short headline (used by SR announcer too)
     * @param {string} detail        actionable explanation / next-step
     * @param {object} [diag]        diagnostics object from CWCamera.diagnose()
     * @param {string} [code]        error code (logged + included in diag)
     */
    function showError(title, detail, diag, code) {
        state.$root.find('[data-cwcam-error-title]').text(title || 'تعذّر الإكمال');
        state.$root.find('[data-cwcam-error-detail]').text(detail || '');

        // Build the diagnostics block — kept as plain JSON so the user can
        // copy/paste it into a support ticket without losing structure.
        if (diag) {
            var payload = $.extend({}, diag, {
                error_code: code || 'unknown',
                timestamp:  new Date().toISOString(),
            });
            try {
                state.$root.find('[data-cwcam-diag]')
                    .text(JSON.stringify(payload, null, 2));
                state.$root.find('[data-cwcam-diag-wrap]').show();
            } catch (_) {
                state.$root.find('[data-cwcam-diag-wrap]').hide();
            }
        } else {
            state.$root.find('[data-cwcam-diag-wrap]').hide();
        }

        showOverlay('error');
        announce(title);
    }
    function announce(msg) {
        if (state && state.announce) state.announce(msg);
    }

})(window.jQuery, window, document);
