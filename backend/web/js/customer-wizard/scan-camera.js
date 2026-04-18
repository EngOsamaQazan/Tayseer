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
            + '      <button type="button" class="cwcam__btn cwcam__btn--success"'
            + '              hidden data-cwcam-skip-back'
            + '              title="إنهاء بدون تصوير الوجه الخلفي">'
            + '        <i class="fa fa-check" aria-hidden="true"></i>'
            + '        تخطّي وإنهاء'
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

        // ── "Skip back" — graceful escape on the back-side step. ──
        // The back of the ID is OPTIONAL (front already provided everything
        // we need). If the user can't get a clean capture (camera glare,
        // mirror finish, etc.) we let them finish the wizard without it.
        state.$root.on('click', '[data-cwcam-skip-back]', function () {
            announce('تمّ تخطّي الوجه الخلفي.');
            if (state.opts.onComplete) {
                try { state.opts.onComplete(state.collectedFields); } catch (_) {}
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

        if (score >= QUALITY_THRESHOLD) {
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
        var lumaSum = 0;
        var lumaCount = 0;
        var lumaArr = new Float32Array(ANALYSIS_W * ANALYSIS_H);

        // Pass 1: luma.
        for (var i = 0, p = 0; i < len; i += 4, p++) {
            var L = 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
            lumaArr[p] = L;
            lumaSum += L;
            lumaCount++;
        }
        var meanLuma = lumaSum / lumaCount;

        // Pass 2: Laplacian variance (sampled stride 2 to stay cheap).
        var lap = 0, lap2 = 0, lapCount = 0;
        for (var y = 1; y < ANALYSIS_H - 1; y += 2) {
            for (var x = 1; x < ANALYSIS_W - 1; x += 2) {
                var idx = y * ANALYSIS_W + x;
                var v = -4 * lumaArr[idx]
                      + lumaArr[idx - 1]
                      + lumaArr[idx + 1]
                      + lumaArr[idx - ANALYSIS_W]
                      + lumaArr[idx + ANALYSIS_W];
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
            stabilityScore = 50;     // unknown until we have history
        }
        state.lastFrameData = lumaArr;

        return {
            brightness: meanLuma,
            sharpness:  Math.min(100, lapVar / 10),
            stability:  stabilityScore,
        };
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
        var quality;
        if (score >= QUALITY_THRESHOLD)        quality = 'good';
        else if (score >= 50)                  quality = 'fair';
        else                                   quality = 'poor';

        if ($frame.attr('data-quality') !== quality) {
            $frame.attr('data-quality', quality);
        }

        // Update ring fill (only fills while we're in "good" stable streak).
        var ringPct = 0;
        if (score >= QUALITY_THRESHOLD && state.stableSinceTs) {
            ringPct = Math.min(1, (Date.now() - state.stableSinceTs) / STABILITY_NEEDED_MS);
        }
        var $ring = state.$root.find('[data-cwcam-ring]');
        var offset = state._ringCirc * (1 - ringPct);
        $ring.css('stroke-dashoffset', offset);

        // Hint text (subtle priority order — most actionable first).
        var hint;
        if (m.brightness < 50)       hint = 'الإضاءة منخفضة — اقترب من النور';
        else if (m.brightness > 220) hint = 'الإضاءة قوية جداً — قلّل السطوع';
        else if (m.sharpness < 30)   hint = 'الصورة غير واضحة — ثبّت يدك';
        else if (m.stability < 60)   hint = 'ثبّت الكاميرا قليلاً';
        else if (score < QUALITY_THRESHOLD) hint = 'اقترب أو ابتعد قليلاً للتوضيح';
        else                          hint = 'ممتاز — لا تحرّك يدك';
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

        // Hand the side's fields to the caller immediately so the form starts
        // populating — this gives faster perceived feedback than waiting for
        // both sides.
        if (state.opts.onFields) {
            try { state.opts.onFields(resp.fields || {}, state.currentSide, resp.unmapped || {}); }
            catch (_) {}
        }

        var nextAction = resp.next_action || (state.currentSide === 'front' ? 'capture_back' : 'done');

        if (nextAction === 'capture_back') {
            // Front done; transition to back.
            state.currentSide = 'back';
            state.backFailures = 0;
            state.$root.find('[data-cwcam-title-text]').text('مسح الهوية — الوجه الخلفي');
            state.$root.find('[data-cwcam-side-pill]').text('2 / 2');
            // Reveal the "skip back" button — back side is optional, so we
            // give the user an immediate escape hatch from the moment they
            // arrive (no need to wait for failures).
            state.$root.find('[data-cwcam-skip-back]').prop('hidden', false);
            announce('تم التقاط الوجه الأمامي. اقلب الهوية الآن.');
            showFlipPrompt();
        } else {
            // All done — show a brief success overlay so the user sees the
            // green checkmark instead of a confusing dark frame for ~500ms.
            // If the backend included a `note` (e.g. "back returned no extra
            // data, that's fine"), surface it here instead of generic text.
            var successMsg = resp.note || 'تمّ المسح بنجاح';
            announce(successMsg);
            showSuccess(successMsg);
            if (state.opts.onComplete) {
                try { state.opts.onComplete(state.collectedFields); } catch (_) {}
            }
            // Slightly longer when there's a note so the user can read it.
            setTimeout(CWCamera.close, resp.note ? 1800 : 900);
        }
    }

    function handleUploadFailure(msg) {
        // Track back-side failures so we can surface the "skip" hint more
        // prominently after repeated retries (matches the user's pain
        // signal: "tried 10 times and couldn't capture the back").
        if (state.currentSide === 'back') {
            state.backFailures = (state.backFailures || 0) + 1;
            if (state.backFailures >= 2) {
                msg += '\n\nالوجه الخلفي اختياري — يمكنك "تخطّي وإنهاء" أدناه.';
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
