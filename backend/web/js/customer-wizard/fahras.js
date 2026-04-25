/**
 * Customer Wizard V2 — Fahras gate-rail.
 *
 * Lives next to (but is independent from) core.js. Behavioural goals:
 *   • Auto-check the customer in the central Fahras index whenever the
 *     national-ID input stops changing for 700 ms (with throttling).
 *   • Render a self-contained verdict card with five visual states:
 *       idle | loading | can | warn | block | error | override.
 *   • Hard-block the wizard's "Next" button (server already blocks at
 *     /validate, but the UX cue must precede the round-trip).
 *   • Honour an existing manager override that is persisted in the
 *     wizard draft (no re-fetch surprise).
 *   • Provide a Modal flow for managers with the
 *     `customer.fahras.override` permission to bypass `cannot_sell`
 *     verdicts with a logged justification.
 *   • Provide a "Search Fahras by name" modal for ad-hoc lookups.
 *
 * Public surface (window.CWFahras):
 *   CWFahras.init({ urls: {check, search, override} })
 *   CWFahras.recheck()                  → forces a fresh check
 *   CWFahras.getVerdict()               → last verdict object (or null)
 *   CWFahras.isBlocking()               → boolean
 *
 * Defensive design: the entire module is a no-op when the verdict card
 * is not present in the DOM (e.g. params.fahras.enabled === false, or
 * the user is not on Step 1).
 */
(function ($, window) {
    'use strict';
    if (!$) return;

    var NS = '.cwFahras';
    // ── Why 200ms (down from 700ms) ──
    // The product spec is "fire as soon as the rep finishes typing the id +
    // name". 700ms felt laggy and let the rep tab away to the next field
    // before the verdict surfaced. 200ms is small enough to feel immediate
    // (well under the 100-300ms perceptual-instant threshold once you add
    // network latency to Fahras) yet large enough to coalesce a burst of
    // keystrokes into a single request — typing a 10-digit id at ~6 chars/sec
    // still produces ONE call, not ten. Verdict caching is fully disabled
    // server-side, so every fired request hits Fahras live.
    var DEBOUNCE_MS = 200;

    // ─── State ──────────────────────────────────────────────────────────
    var state = {
        urls:        {},
        $card:       null,
        $body:       null,
        $matchesBox: null,
        $matchesBody:null,
        $idInput:    null,
        $nameInput:  null,
        $phoneInput: null,
        $next:       null,
        canOverride: false,
        debounceTm:  null,
        inflight:    null,            // jqXHR
        lastQueryKey:null,
        lastVerdict: null,
        blocking:    false,           // current "Next button must be locked"
        override:    null,            // {reason, at, idNumber} once approved
    };

    // ─── Helpers ────────────────────────────────────────────────────────
    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function toast(msg, type, ttl) {
        if (window.CW && typeof window.CW.toast === 'function') {
            window.CW.toast(msg, type || 'info', ttl);
        }
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function trim(s) { return $.trim(String(s || '')); }

    function cleanId(s) { return String(s || '').replace(/\D+/g, ''); }

    function cleanName(s) {
        // Collapse runs of whitespace; preserve Arabic letters as-is.
        return trim(String(s || '').replace(/\s+/g, ' '));
    }

    function queryKey(id, name) {
        return cleanId(id) + '|' + cleanName(name).toLowerCase();
    }

    function isLikelyFullName(name) {
        // Encourage at least 2 words before triggering an automatic check
        // (Fahras matches by exact id_number too, so this only matters
        // when the rep types the name first).
        var n = cleanName(name);
        if (!n) return false;
        return n.split(' ').filter(Boolean).length >= 2;
    }

    function isLikelyId(id) {
        var c = cleanId(id);
        return c.length >= 9 && c.length <= 12;
    }

    // ─── Init ───────────────────────────────────────────────────────────
    var CWFahras = window.CWFahras = window.CWFahras || {};

    CWFahras.init = function (opts) {
        opts = opts || {};
        state.urls = $.extend({}, opts.urls || {});

        // ── Edit mode: Fahras is a CREATE-time gate (it screens new
        // customers against the central blacklist). When editing an
        // existing customer we deliberately bail out so the verdict
        // card never appears and the "Next" button is never locked. ──
        if (window.CW && window.CW.mode === 'edit') {
            return;
        }

        state.$card = $('[data-cw-fahras]');
        if (!state.$card.length) return;          // Fahras disabled / not on step 1

        state.$body        = state.$card.find('[data-cw-fahras-body]');
        state.$matchesBox  = state.$card.find('[data-cw-fahras-matches]');
        state.$matchesBody = state.$card.find('[data-cw-fahras-matches-body]');
        state.canOverride  = state.$card.attr('data-cw-fahras-can-override') === '1';

        state.$idInput     = $('#cw-id');
        state.$nameInput   = $('#cw-name');
        state.$phoneInput  = $('#cw-phone');
        state.$next        = $('#cw-shell [data-cw-action="next"]');

        // If the partial pre-rendered an override banner, capture its meta.
        var $banner = state.$card.find('[data-cw-fahras-override-banner]');
        if ($banner.length) {
            state.override = {
                reason:   trim($banner.find('span').first().text().replace(/^السبب:\s*/, '')),
                idNumber: cleanId(state.$idInput.val()),
                at:       Math.floor(Date.now() / 1000),
            };
            state.blocking = false;
            updateNextButton();
        }

        bindEvents();

        // Auto-trigger if both id + name are already populated (draft hydration).
        if (isLikelyId(state.$idInput.val()) || isLikelyFullName(state.$nameInput.val())) {
            scheduleCheck(0);                       // immediate
        } else {
            renderState('idle');
        }
    };

    function bindEvents() {
        // Debounced auto-check on id / name / phone changes.
        state.$idInput.on('input' + NS + ' change' + NS, function () {
            scheduleCheck(DEBOUNCE_MS);
        });
        state.$nameInput.on('input' + NS + ' change' + NS, function () {
            scheduleCheck(DEBOUNCE_MS);
        });
        state.$phoneInput.on('input' + NS + ' change' + NS, function () {
            // Phone alone doesn't trigger; only refresh if we already have id.
            if (isLikelyId(state.$idInput.val())) scheduleCheck(DEBOUNCE_MS);
        });

        // Card buttons (delegated to allow re-rendering).
        state.$card.on('click' + NS, '[data-cw-fahras-action]', function (e) {
            var action = $(this).attr('data-cw-fahras-action');
            switch (action) {
                case 'recheck':         e.preventDefault(); CWFahras.recheck();         break;
                case 'search':          e.preventDefault(); openSearchModal();          break;
                case 'open-override':   e.preventDefault(); openOverrideModal();        break;
                case 'clear-override':  e.preventDefault(); clearOverride();            break;
                case 'add-contract':
                case 'update-customer':
                    // Intentional navigation away from the wizard — silence
                    // the beforeunload "unsaved changes" guard so the rep
                    // doesn't see a confusing browser confirm dialog. The
                    // <a> handles the actual navigation. Both CTAs leave
                    // the wizard for a different page (contract create /
                    // customer edit), so the same disarm logic applies.
                    if (window.CW && typeof window.CW.disarmUnloadGuard === 'function') {
                        window.CW.disarmUnloadGuard();
                    } else {
                        try { $(window).off('beforeunload.cw'); } catch (_) {}
                    }
                    break;
            }
        });

        // Modals (override + search) are attached to <body> level.
        $(document).on('click' + NS, '[data-cw-fahras-action="close-modal"], [data-cw-fahras-action="close-search-modal"]', function (e) {
            e.preventDefault();
            closeAllModals();
        });
        $(document).on('click' + NS, '[data-cw-fahras-action="confirm-override"]', function (e) {
            e.preventDefault();
            submitOverride();
        });
        $(document).on('click' + NS, '[data-cw-fahras-action="run-search"]', function (e) {
            e.preventDefault();
            runSearch();
        });
        $(document).on('keydown' + NS, function (e) {
            if (e.key === 'Escape') closeAllModals();
        });

        // Quality-of-life: pressing Enter inside the search input runs it.
        $(document).on('keypress' + NS, '[data-cw-fahras-search-input]', function (e) {
            if (e.which === 13) { e.preventDefault(); runSearch(); }
        });

        // Pre-empt CW.next() when blocked. Bound in *capture* phase so it
        // fires before core.js's delegated jQuery handler.
        var $next = state.$next.get(0);
        if ($next) {
            $next.addEventListener('click', function (e) {
                if (!state.blocking) return;
                e.preventDefault();
                e.stopImmediatePropagation();
                announceBlocked();
            }, true);
        }

        // Re-apply the lock when the user navigates back to Step 1.
        $(document).on('cw:step:rendered' + NS, function (_e, info) {
            if (info && info.n === 1) {
                updateNextButton();
            }
        });
    }

    function scheduleCheck(delay) {
        clearTimeout(state.debounceTm);
        state.debounceTm = setTimeout(performCheck, delay);
    }

    function performCheck(force) {
        var id   = cleanId(state.$idInput.val());
        var name = cleanName(state.$nameInput.val());
        var phone = trim(state.$phoneInput.val());

        // Need EITHER a valid id OR a 2+ word name to bother Fahras.
        if (!isLikelyId(id) && !isLikelyFullName(name)) {
            renderState('idle');
            applyVerdictGate(null);
            return;
        }

        var key = queryKey(id, name);
        if (!force && key === state.lastQueryKey && state.lastVerdict) {
            // Already have a fresh verdict for this exact input.
            return;
        }
        state.lastQueryKey = key;

        // If a manager override exists for THIS exact id, honour it.
        if (state.override && state.override.idNumber && state.override.idNumber === id) {
            renderOverrideState();
            applyVerdictGate({ override: true });
            return;
        }

        if (state.inflight) {
            try { state.inflight.abort(); } catch (_) {}
        }

        renderState('loading');

        state.inflight = $.ajax({
            url:      state.urls.check,
            method:   'POST',
            dataType: 'json',
            data: {
                id_number:        id,
                name:             name,
                phone:            phone,
                _csrf:            csrfToken(),
            },
            timeout: 15000,
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                var msg = (resp && (resp.error || resp.message)) || 'تعذّر الاتصال بنظام الفهرس.';
                renderError(msg, resp && resp.blocks, resp || null);
                applyVerdictGate({ verdict: 'error', blocks: resp && resp.blocks });
                return;
            }
            // The integration may be globally disabled — render a neutral state.
            if (resp.enabled === false) {
                renderState('idle');
                applyVerdictGate(null);
                return;
            }
            state.lastVerdict = resp;
            renderVerdict(resp);
            applyVerdictGate(resp);
        }).fail(function (xhr, status) {
            if (status === 'abort') return;
            var msg = 'فشل الاتصال بنظام الفهرس.';
            var errResp = (xhr && xhr.responseJSON) || null;
            if (errResp) {
                msg = errResp.error || errResp.message || msg;
            }
            // Fail-closed by default — server policy mirrors this.
            renderError(msg, true, errResp);
            applyVerdictGate({ verdict: 'error', blocks: true });
        });
    }

    // ─── Rendering ──────────────────────────────────────────────────────
    function renderState(kind) {
        state.$card.attr('data-cw-fahras-state', kind);
        state.$matchesBox.attr('hidden', '').prop('hidden', true);
        state.$matchesBody.empty();

        var html;
        switch (kind) {
            case 'idle':
                html =
                    '<div class="cw-fahras__icon" aria-hidden="true"><i class="fa fa-info-circle"></i></div>' +
                    '<div class="cw-fahras__message">' +
                        '<p class="cw-fahras__text">' +
                            'أدخل الرقم الوطني والاسم وسيتم التحقق من العميل تلقائياً قبل الانتقال للخطوة التالية.' +
                        '</p>' +
                    '</div>';
                break;
            case 'loading':
                html =
                    '<div class="cw-fahras__icon" aria-hidden="true"><i class="fa fa-circle-o-notch fa-spin"></i></div>' +
                    '<div class="cw-fahras__message">' +
                        '<p class="cw-fahras__text">جاري التحقق من العميل في نظام الفهرس…</p>' +
                        '<p class="cw-fahras__sub">قد يستغرق ذلك بضع ثوانٍ بسبب فحص جميع شركات التقسيط المرتبطة.</p>' +
                    '</div>';
                break;
        }
        if (html) state.$body.html(html);

        // Recheck button only enabled once we have data to recheck.
        state.$card.find('[data-cw-fahras-action="recheck"]').prop('disabled', kind === 'loading' || kind === 'idle');
    }

    function renderOverrideState() {
        state.$card.attr('data-cw-fahras-state', 'override');
        var reason = (state.override && state.override.reason) || '—';
        var html =
            '<div class="cw-fahras__icon" aria-hidden="true"><i class="fa fa-key"></i></div>' +
            '<div class="cw-fahras__message">' +
                '<p class="cw-fahras__text"><strong>تم تجاوز قرار الفهرس بواسطة المدير.</strong></p>' +
                '<p class="cw-fahras__sub">سبب التجاوز: ' + escapeHtml(reason) + '</p>' +
                '<p class="cw-fahras__meta"><i class="fa fa-shield"></i> سيتم تسجيل التجاوز في سجل الفهرس وإشعار الإدارة.</p>' +
            '</div>';
        state.$body.html(html);
        state.$matchesBox.attr('hidden', '').prop('hidden', true);
        state.$card.find('[data-cw-fahras-action="recheck"]').prop('disabled', false);
    }

    /**
     * Transport-level failure path: AJAX itself failed (network down,
     * 5xx, JSON parse error, server returned ok:false). The wizard MUST
     * surface this loudly — silently swallowing a Fahras outage and
     * letting the rep proceed would let blocked customers slip through
     * the gate. We render a prominent red banner with the exact error
     * text so support can diagnose, and we keep the recheck button
     * enabled so the rep can retry once Fahras is back.
     */
    function renderError(message, blocks, resp) {
        state.$card.attr('data-cw-fahras-state', 'error');
        // The "blocks" argument is honoured in the messaging only — the
        // Next button gate is decided by applyVerdictGate(), which (post
        // 2026-04) explicitly does NOT block on transport-level errors.
        // The body text mirrors that policy so the rep is never told
        // "you cannot proceed" because of a network blip.
        var html =
            '<div class="cw-fahras__icon" aria-hidden="true"><i class="fa fa-exclamation-triangle"></i></div>' +
            '<div class="cw-fahras__message">' +
                '<p class="cw-fahras__text"><strong>تعذّر الاتصال بنظام الفهرس — لم يصل أي رد.</strong></p>' +
                '<div class="cw-fahras__verdict-panel cw-fahras__verdict-panel--error" role="alert">' +
                    '<p class="cw-fahras__verdict-panel-head">' +
                        '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' +
                        '<strong>تفاصيل الخطأ:</strong> ' +
                        '<span class="cw-fahras__verdict-panel-label">' + escapeHtml(message) + '</span>' +
                    '</p>' +
                '</div>' +
                '<p class="cw-fahras__meta"><i class="fa fa-exclamation"></i> ' +
                'يمكنك المتابعة وسنحاول الفحص لاحقاً تلقائياً. الحظر يسري فقط على القرارات الصريحة من الفهرس (محظور البيع).</p>' +
                renderDiagPanel(resp || {}) +
            '</div>';
        state.$body.html(html);
        state.$matchesBox.attr('hidden', '').prop('hidden', true);
        state.$card.find('[data-cw-fahras-action="recheck"]').prop('disabled', false);
    }

    /**
     * Map a Fahras verdict code to a short Arabic label that we surface
     * on-screen so the rep can always see — at a glance — exactly what
     * Fahras said, even when our productive existing-customer CTA takes
     * the visual centre. "Real and live" Fahras response remains visible
     * regardless of the path the card otherwise renders.
     */
    function fahrasVerdictLabelAr(v) {
        switch (v) {
            case 'no_record':    return 'لا يوجد سجل لهذا العميل في الفهرس';
            case 'can_sell':     return 'مسجَّل في الفهرس بدون قيود — البيع مسموح';
            case 'contact_first':return 'التواصل مع شركة سابقة مطلوب قبل البيع';
            case 'cannot_sell':  return 'البيع ممنوع وفقاً للفهرس';
            case 'error':        return 'تعذّر الحصول على رد من الفهرس (خطأ في الاستجابة)';
            default:             return v ? ('قرار غير معروف: ' + v) : '—';
        }
    }

    /**
     * Translate a Fahras remote-source key (used both in the per-source
     * diagnostic panel and the id-mismatch alert) to its Arabic display
     * label. Unknown keys fall through to the raw value so anything new
     * Fahras adds upstream still appears — just untranslated.
     */
    function remoteSourceLabelAr(key) {
        switch (key) {
            case 'zajal': return 'زجل';
            case 'jadal': return 'جدل';
            case 'namaa': return 'نماء';
            case 'bseel': return 'بسيل';
            case 'watar': return 'وتر';
            case 'majd':  return 'عالم المجد';
            case 'local': return 'القاعدة المحلّية';
            default:      return key || '—';
        }
    }

    /**
     * Render a collapsed «تفاصيل تشخيصية» disclosure carrying the per-source
     * data that produced the verdict — local row counts, per-remote-API row
     * counts, HTTP codes, retry flags, engine input/group counts, and the
     * `promoted` bit. Two consecutive calls returning different verdicts
     * for the same input become trivially diff-able: open this disclosure
     * on each call and the differing field jumps out.
     *
     * Degrades gracefully:
     *   • If the Fahras backend hasn't been redeployed with the _diag block
     *     yet, `resp.diag` is null/missing → returns '' (no panel rendered).
     *   • If diag is present but partial, only known fields are shown.
     *
     * Source map keys are translated to Arabic company labels for legibility.
     */
    function renderDiagPanel(resp) {
        var d = resp && resp.diag;
        if (!d || typeof d !== 'object') return '';

        var localBits = [];
        if (d.local) {
            if (typeof d.local.clients_count === 'number') {
                localBits.push('عملاء محلّيون: ' + d.local.clients_count);
            }
            if (typeof d.local.remote_clients_count === 'number') {
                localBits.push('عملاء مخزّنون من شركات: ' + d.local.remote_clients_count);
            }
        }

        var perSource = [];
        if (d.remote && typeof d.remote === 'object') {
            Object.keys(d.remote).forEach(function (k) {
                var s = d.remote[k] || {};
                var label = remoteSourceLabelAr(k);
                var bits = [];
                if (typeof s.count === 'number') bits.push(s.count + ' صف');
                if (s.http_code) bits.push('HTTP ' + s.http_code);
                if (s.error) bits.push('خطأ: ' + s.error);
                if (s.retried) bits.push('أُعيدت المحاولة');
                // empty_200_recheck flips on when the source returned 0
                // rows with HTTP 200 BUT we had corroborating evidence
                // (local DB or another source) that the customer should
                // be visible — so we ran an extra verification call.
                if (s.empty_200_recheck) bits.push('⚠ تحقّق إضافي (رد فارغ مع وجود أدلّة)');
                perSource.push(
                    '<li><strong>' + escapeHtml(label) + ':</strong> ' +
                    escapeHtml(bits.join(' · ') || '—') + '</li>'
                );
            });
        }

        var engineBits = [];
        if (d.engine) {
            if (typeof d.engine.rows_in === 'number') engineBits.push('صفوف للمحرّك: ' + d.engine.rows_in);
            if (typeof d.engine.groups === 'number') engineBits.push('مجموعات: ' + d.engine.groups);
        }

        var verdictMeta = [];
        if (d.verdict) verdictMeta.push('الحكم: ' + fahrasVerdictLabelAr(d.verdict));
        if (d.reason_code) verdictMeta.push('الكود: ' + d.reason_code);
        if (typeof d.matches_count === 'number') verdictMeta.push('المطابقات: ' + d.matches_count);
        if (d.promoted) verdictMeta.push('⚠ تمت ترقية الحكم إلى خطأ بسبب نقص البيانات');
        if (typeof d.duration_ms === 'number') verdictMeta.push('المدّة: ' + d.duration_ms + ' م/ث');

        return (
            '<details class="cw-fahras__diag" data-cw-fahras-diag>' +
                '<summary>' +
                    '<i class="fa fa-stethoscope" aria-hidden="true"></i> ' +
                    'تفاصيل تشخيصية (لمشاركتها مع الدعم عند تذبذب الحكم)' +
                '</summary>' +
                '<div class="cw-fahras__diag-body">' +
                    (localBits.length
                        ? '<p><strong>القاعدة المحلّية:</strong> ' + escapeHtml(localBits.join(' · ')) + '</p>'
                        : '') +
                    (perSource.length
                        ? '<p><strong>المصادر الحيّة:</strong></p><ul>' + perSource.join('') + '</ul>'
                        : '') +
                    (engineBits.length
                        ? '<p><strong>محرّك التحليل:</strong> ' + escapeHtml(engineBits.join(' · ')) + '</p>'
                        : '') +
                    (verdictMeta.length
                        ? '<p><strong>القرار:</strong> ' + escapeHtml(verdictMeta.join(' · ')) + '</p>'
                        : '') +
                    (d.request_id
                        ? '<p class="cw-fahras__diag-id"><strong>Request ID:</strong> <code>' +
                          escapeHtml(d.request_id) + '</code></p>'
                        : '') +
                '</div>' +
            '</details>'
        );
    }

    function renderVerdict(resp) {
        var verdict = resp.verdict || 'no_record';
        var blocks  = !!resp.blocks;
        var warns   = !!resp.warns;

        // Existing-customer short-circuit: when the rep's national_id
        // matches a row we already have in our LOCAL Customers table,
        // the controller surfaces `same_company_only=true` (kept as the
        // CSS state hook for backwards compatibility) plus URLs for the
        // two productive next-actions: add a new contract on the existing
        // customer, or update the existing customer's data. This fires
        // REGARDLESS of the Fahras verdict — even on `no_record` — because
        // the local row is the source of truth for "we already have them",
        // and creating a duplicate row would corrupt referential integrity.
        // The wizard's "Next" button stays locked either way (controller
        // forces blocks=true): the rep should pick one of the two CTAs.
        // CRITICAL: we still render the actual Fahras verdict & any error
        // alongside — never hide what Fahras said (see verdictPanel below).
        var existingCustomer = !!(resp.same_company_only && resp.existing_customer_id);
        var fahrasErrored    = (verdict === 'error');

        var stateName = 'can';
        var icon      = 'fa-check-circle';
        var title     = 'يمكن إضافة العميل.';
        if (existingCustomer) {
            stateName = 'same-company';
            icon      = 'fa-handshake-o';
            title     = 'هذا العميل موجود لديك مسبقاً — اختر «إضافة عقد جديد» أو «تحديث بيانات العميل».';
        } else if (verdict === 'no_record') {
            stateName = 'can';
            icon      = 'fa-check-circle';
            title     = 'لا توجد قيود على هذا العميل في نظام الفهرس.';
        } else if (verdict === 'can_sell') {
            stateName = 'can';
            icon      = 'fa-check-circle';
            title     = 'العميل مسجَّل سابقاً، ويمكن إضافته (لا توجد مخالفات).';
        } else if (verdict === 'contact_first') {
            stateName = 'warn';
            icon      = 'fa-question-circle';
            title     = 'يجب التواصل مع شركة سابقة قبل اعتماد العميل.';
        } else if (verdict === 'cannot_sell') {
            stateName = 'block';
            icon      = 'fa-ban';
            title     = 'لا يمكن إضافة هذا العميل وفقاً لقرار نظام الفهرس.';
        } else if (verdict === 'error') {
            stateName = 'error';
            icon      = 'fa-exclamation-triangle';
            title     = resp.reason_ar || 'حدث خطأ في فحص الفهرس.';
        }

        state.$card.attr('data-cw-fahras-state', stateName);
        state.$card.find('[data-cw-fahras-action="recheck"]').prop('disabled', false);

        var meta = [];
        // NOTE: `from_cache` is now structurally always false — the service
        // layer no longer caches verdicts (every check hits Fahras live so
        // the rep sees ground truth). The badge is intentionally omitted.
        if (resp.duration_ms != null) meta.push('<i class="fa fa-clock-o"></i> ' + resp.duration_ms + ' ملي/ث');
        if (resp.request_id) meta.push('<i class="fa fa-hashtag"></i> ' + escapeHtml(resp.request_id));

        var ctaHtml = '';
        if (existingCustomer) {
            ctaHtml = renderExistingCustomerCta(resp);
        } else if (blocks && state.canOverride) {
            ctaHtml =
                '<div class="cw-fahras__cta">' +
                    '<button type="button" class="cw-btn cw-btn--danger cw-btn--sm" data-cw-fahras-action="open-override">' +
                        '<i class="fa fa-key" aria-hidden="true"></i> ' +
                        '<span>تجاوز الحظر (مدير)</span>' +
                    '</button>' +
                '</div>';
        }

        // Prefer the controller-supplied tailored message for the
        // existing-customer case; otherwise fall back to the raw Fahras reason.
        var subRaw = existingCustomer
            ? (resp.same_company_message_ar || resp.reason_ar || '')
            : (resp.reason_ar || '');
        var subText = subRaw
            ? '<p class="cw-fahras__sub">' + escapeHtml(subRaw) + '</p>'
            : '';

        // ── Fahras "real & live" verdict panel ───────────────────────────
        // Always visible when we're showing the existing-customer CTA or
        // when Fahras itself errored. This guarantees the rep can never
        // miss what Fahras actually replied — even when our local-DB CTA
        // takes the visual centre. For error responses we paint a red
        // banner so an outage is impossible to ignore.
        var verdictPanel = '';
        var showPanel    = existingCustomer || fahrasErrored;
        if (showPanel) {
            var panelCls = fahrasErrored
                ? 'cw-fahras__verdict-panel cw-fahras__verdict-panel--error'
                : 'cw-fahras__verdict-panel';
            var panelIcon = fahrasErrored ? 'fa-exclamation-triangle' : 'fa-bullhorn';
            var panelHeading = fahrasErrored
                ? 'خطأ في الاتصال بالفهرس'
                : 'رد الفهرس (لحظي):';
            var verdictLabel  = escapeHtml(fahrasVerdictLabelAr(verdict));
            var reasonLine    = '';
            if (resp.reason_ar && (!existingCustomer || resp.reason_ar !== subRaw)) {
                reasonLine = '<p class="cw-fahras__verdict-panel-reason">'
                           + escapeHtml(resp.reason_ar) + '</p>';
            }
            verdictPanel =
                '<div class="' + panelCls + '" role="' + (fahrasErrored ? 'alert' : 'note') + '">' +
                    '<p class="cw-fahras__verdict-panel-head">' +
                        '<i class="fa ' + panelIcon + '" aria-hidden="true"></i> ' +
                        '<strong>' + escapeHtml(panelHeading) + '</strong> ' +
                        '<span class="cw-fahras__verdict-panel-label">' + verdictLabel + '</span>' +
                    '</p>' +
                    reasonLine +
                '</div>';
        }

        var html =
            '<div class="cw-fahras__icon" aria-hidden="true"><i class="fa ' + icon + '"></i></div>' +
            '<div class="cw-fahras__message">' +
                '<p class="cw-fahras__text"><strong>' + escapeHtml(title) + '</strong></p>' +
                subText +
                renderIdMismatchAlert(resp) +
                verdictPanel +
                (meta.length ? '<p class="cw-fahras__meta">' + meta.join(' · ') + '</p>' : '') +
                ctaHtml +
                renderDiagPanel(resp) +
            '</div>';
        state.$body.html(html);

        renderMatches(resp.matches || []);
    }

    /**
     * Render the unmissable yellow alert that fires when Fahras §3.25
     * detected a name match under a *different* national ID than the one
     * the rep typed. Strongly implies the rep made a typo entering the
     * national_id and is about to "create" a customer that already exists
     * elsewhere in our ecosystem under the correct ID.
     *
     * Source of data: `resp.id_mismatch` populated by
     * WizardController::actionFahrasCheck → FahrasVerdict::idMismatch
     * → admin/api/check.php §3.25 (Fahras side, name fallback search).
     *
     * Render contract:
     *   • Returns '' when no mismatch was detected (typical case) so the
     *     caller can concatenate unconditionally.
     *   • Lists every distinct matched national_id with name, source, and
     *     status so the rep can immediately tell whether the typed ID
     *     should be corrected (typo) or whether this is a genuine
     *     same-name-different-person collision.
     *   • Includes an explicit override button that simply re-arms the
     *     wizard's Next button — used only when the rep has verified
     *     with the customer that the typed ID is correct and the name
     *     collision is coincidental. A typo correction is the
     *     overwhelmingly more common path: just re-type the ID and the
     *     auto-recheck will clear the alert organically.
     */
    function renderIdMismatchAlert(resp) {
        var m = resp && resp.id_mismatch;
        if (!m || !m.matches || !m.matches.length) return '';

        var rows = m.matches.map(function (row) {
            var bits = [];
            if (row.national_id) {
                bits.push('<span class="cw-fahras__mismatch-nid">'
                        + escapeHtml(row.national_id) + '</span>');
            }
            if (row.name)    bits.push('<span>' + escapeHtml(row.name) + '</span>');
            if (row.source)  bits.push('<span class="cw-fahras__mismatch-src">'
                                    + escapeHtml(remoteSourceLabelAr(row.source)) + '</span>');
            if (row.account) bits.push('<span>' + escapeHtml(row.account) + '</span>');
            if (row.status)  bits.push('<span class="cw-fahras__mismatch-status">'
                                    + escapeHtml(row.status) + '</span>');
            return '<li>' + bits.join(' · ') + '</li>';
        }).join('');

        var typedId = escapeHtml(m.typed_id || '');

        return (
            '<div class="cw-fahras__id-mismatch" role="alert">' +
                '<p class="cw-fahras__id-mismatch-head">' +
                    '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' +
                    '<strong>تنبيه: الاسم موجود برقم وطني مختلف!</strong>' +
                '</p>' +
                '<p class="cw-fahras__id-mismatch-body">' +
                    'لا يوجد سجل بالرقم الوطني الذي أدخلته (<code>' + typedId + '</code>)، ' +
                    'لكن وُجد عميل بنفس الاسم تقريباً برقم وطني <strong>مختلف</strong>. ' +
                    'تحقّق من رقم هوية العميل قبل المتابعة — في الغالب يوجد خطأ مطبعي:' +
                '</p>' +
                '<ul class="cw-fahras__id-mismatch-list">' + rows + '</ul>' +
            '</div>'
        );
    }

    /**
     * Build the productive CTA strip rendered when the rep's national_id
     * already matches a customer in our local DB. Two side-by-side actions
     * are offered, each gated independently by RBAC:
     *   1. «إضافة عقد جديد» → /contracts/contracts/create?id=N
     *      (gated by Permissions::CONT_CREATE).
     *   2. «تحديث بيانات العميل» → /customers/wizard/edit?id=N
     *      (gated by Permissions::CUST_UPDATE).
     *
     * Each button has three render states, handled independently so the
     * rep always knows what's available, what's missing, and why:
     *   • URL present + permission granted → live action link (success btn).
     *   • URL present + permission denied   → disabled link with hint to
     *                                         escalate to a manager.
     *   • URL missing (no local row info)  → omitted entirely.
     *
     * If neither action is available we return '' and the card just shows
     * the headline + the always-present "Fahras response" panel — never a
     * silent dead-end.
     */
    function renderExistingCustomerCta(resp) {
        var custName = resp.existing_customer_name || '';
        var nameLbl  = custName ? ' لـ ' + custName : ' للعميل';

        var pieces = [];

        // ── Add Contract action ──
        var addUrl = resp.add_contract_url || '';
        if (addUrl) {
            var addAllowed = !!resp.add_contract_allowed;
            var addLabel   = 'إضافة عقد جديد' + nameLbl;
            if (addAllowed) {
                pieces.push(
                    '<a href="' + escapeHtml(addUrl) + '" ' +
                       'class="cw-btn cw-btn--success cw-btn--sm" ' +
                       'data-cw-fahras-action="add-contract" ' +
                       'data-pjax="0">' +
                        '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' +
                        '<span>' + escapeHtml(addLabel) + '</span>' +
                    '</a>'
                );
            } else {
                pieces.push(
                    '<button type="button" class="cw-btn cw-btn--outline cw-btn--sm" disabled ' +
                            'aria-disabled="true" ' +
                            'title="لا تملك صلاحية «إضافة عقد».">' +
                        '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' +
                        '<span>' + escapeHtml(addLabel) + '</span>' +
                    '</button>'
                );
            }
        }

        // ── Update Customer action ──
        var updUrl = resp.update_customer_url || '';
        if (updUrl) {
            var updAllowed = !!resp.update_customer_allowed;
            var updLabel   = 'تحديث بيانات العميل';
            if (updAllowed) {
                pieces.push(
                    '<a href="' + escapeHtml(updUrl) + '" ' +
                       'class="cw-btn cw-btn--info cw-btn--sm" ' +
                       'data-cw-fahras-action="update-customer" ' +
                       'data-pjax="0">' +
                        '<i class="fa fa-pencil-square-o" aria-hidden="true"></i> ' +
                        '<span>' + escapeHtml(updLabel) + '</span>' +
                    '</a>'
                );
            } else {
                pieces.push(
                    '<button type="button" class="cw-btn cw-btn--outline cw-btn--sm" disabled ' +
                            'aria-disabled="true" ' +
                            'title="لا تملك صلاحية «تعديل العملاء».">' +
                        '<i class="fa fa-pencil-square-o" aria-hidden="true"></i> ' +
                        '<span>' + escapeHtml(updLabel) + '</span>' +
                    '</button>'
                );
            }
        }

        if (!pieces.length) return '';

        // Hint copy adapts to which buttons are actually live.
        var hint;
        if (resp.add_contract_allowed && resp.update_customer_allowed) {
            hint = 'اختر «إضافة عقد جديد» لبيع جديد، أو «تحديث بيانات العميل» لتعديل بياناته.';
        } else if (resp.add_contract_allowed) {
            hint = 'سيتم نقلك إلى صفحة إنشاء العقد مع تعبئة بيانات العميل.';
        } else if (resp.update_customer_allowed) {
            hint = 'سيتم نقلك إلى صفحة تعديل بيانات العميل.';
        } else {
            hint = 'تواصل مع المدير لإصدار صلاحية إضافة عقد أو تعديل العملاء.';
        }

        return (
            '<div class="cw-fahras__cta">' +
                pieces.join('') +
                '<span class="cw-fahras__cta-hint">' +
                    '<i class="fa fa-info-circle" aria-hidden="true"></i> ' +
                    escapeHtml(hint) +
                '</span>' +
            '</div>'
        );
    }

    function renderMatches(matches) {
        if (!matches || !matches.length) {
            state.$matchesBox.attr('hidden', '').prop('hidden', true);
            return;
        }
        state.$matchesBox.removeAttr('hidden').prop('hidden', false);
        state.$matchesBody.empty();
        $.each(matches, function (_i, m) {
            var statusCls =
                m.status === 'block' ? 'cw-fahras-match--block' :
                m.status === 'warn'  ? 'cw-fahras-match--warn'  : '';
            var name      = m.name      || m.full_name || '—';
            var idNum     = m.id_number || m.national_id || '';
            var phone     = m.phone     || m.mobile || '';
            var src       = m.source    || m.company || 'فهرس';
            var meta = [];
            if (idNum) meta.push('الرقم الوطني: ' + idNum);
            if (phone) meta.push('الهاتف: ' + phone);
            if (m.account)    meta.push('رقم الحساب: ' + m.account);
            if (m.created_at) meta.push('بتاريخ: ' + m.created_at);

            var $row = $(
                '<div class="cw-fahras-match ' + statusCls + '">' +
                    '<div class="cw-fahras-match__name">' + escapeHtml(name) + '</div>' +
                    '<div class="cw-fahras-match__meta">' + escapeHtml(meta.join(' · ')) + '</div>' +
                    '<div class="cw-fahras-match__src">' + escapeHtml(src) + '</div>' +
                '</div>'
            );
            state.$matchesBody.append($row);
        });
    }

    // ─── "Next" button gate ─────────────────────────────────────────────
    //
    // Blocking policy (matches backend after the 2026-04 failurePolicy=open
    // rollout): the wizard MUST only block when Fahras returned a real
    // "cannot_sell" verdict OR the controller forced blocks=true for one
    // of its productive-CTA / id-mismatch short-circuits. A transport-level
    // failure (verdict='error') is treated as a loud warning — visible red
    // panel — but the rep can still proceed. Blocking on a connectivity
    // hiccup conflated "we don't know" with "the customer is forbidden",
    // which reps reported as illogical (and which the operations team
    // confirmed is not the policy: only cannot_sell is a hard block).
    function applyVerdictGate(resp) {
        if (!resp) {
            state.blocking = false;
        } else if (resp.override) {
            state.blocking = false;
        } else if (resp.verdict === 'error') {
            // Defense-in-depth: even if a stale server still returns
            // blocks=true on error (failurePolicy='closed'), don't lock
            // the Next button — the user explicitly does not want
            // connectivity errors to stop them from proceeding.
            state.blocking = false;
        } else {
            state.blocking = !!resp.blocks;
        }
        updateNextButton();
    }

    function updateNextButton() {
        if (!state.$next || !state.$next.length) return;
        if (state.blocking) {
            state.$next
                .prop('disabled', true)
                .attr('aria-disabled', 'true')
                .attr('title', 'لا يمكن المتابعة — قرار نظام الفهرس يمنع إضافة هذا العميل.');
        } else {
            state.$next
                .prop('disabled', false)
                .removeAttr('aria-disabled')
                .removeAttr('title');
        }
    }

    function announceBlocked() {
        toast('لا يمكن المتابعة قبل تجاوز قرار نظام الفهرس.', 'error', 5000);
        // Scroll the card into view so the rep sees why.
        var top = state.$card.offset().top - 80;
        $('html, body').animate({ scrollTop: top }, 250);
    }

    // ─── Override modal ─────────────────────────────────────────────────
    function openOverrideModal() {
        if (!state.canOverride) return;
        var $modal = $('[data-cw-fahras-override-modal]');
        if (!$modal.length) return;
        $modal.find('[data-cw-fahras-modal-error]').attr('hidden', '').empty();
        $modal.find('#cwFahrasOverrideReason').val('');
        $modal.removeAttr('hidden').prop('hidden', false);
        setTimeout(function () { $modal.find('#cwFahrasOverrideReason').trigger('focus'); }, 30);
    }

    function closeAllModals() {
        $('[data-cw-fahras-override-modal], [data-cw-fahras-search-modal]')
            .attr('hidden', '').prop('hidden', true);
    }

    function submitOverride() {
        var $modal  = $('[data-cw-fahras-override-modal]');
        var $reason = $modal.find('#cwFahrasOverrideReason');
        var $err    = $modal.find('[data-cw-fahras-modal-error]');
        var reason  = trim($reason.val());

        if (reason.length < 10) {
            $err.text('يرجى كتابة سبب واضح للتجاوز (10 أحرف على الأقل).')
                .removeAttr('hidden').prop('hidden', false);
            $reason.trigger('focus');
            return;
        }

        var id   = cleanId(state.$idInput.val());
        var name = cleanName(state.$nameInput.val());
        var phone = trim(state.$phoneInput.val());
        if (!isLikelyId(id)) {
            $err.text('يجب إدخال رقم وطني صحيح أولاً.')
                .removeAttr('hidden').prop('hidden', false);
            return;
        }

        var $btn = $modal.find('[data-cw-fahras-action="confirm-override"]');
        var orig = $btn.html();
        $btn.prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> جاري التسجيل…');

        $.ajax({
            url:      state.urls.override,
            method:   'POST',
            dataType: 'json',
            data: {
                id_number: id,
                name:      name,
                phone:     phone,
                reason:    reason,
                _csrf:     csrfToken(),
            },
            timeout: 20000,
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                $err.text((resp && (resp.error || resp.message)) || 'تعذّر تسجيل التجاوز.')
                    .removeAttr('hidden').prop('hidden', false);
                return;
            }
            state.override = {
                reason:   reason,
                idNumber: id,
                at:       Math.floor(Date.now() / 1000),
            };
            closeAllModals();
            renderOverrideState();
            applyVerdictGate({ override: true });
            toast('تم تسجيل التجاوز بنجاح. يمكنك المتابعة الآن.', 'success', 4000);
        }).fail(function (xhr) {
            var msg = 'تعذّر تسجيل التجاوز.';
            if (xhr && xhr.responseJSON) {
                msg = xhr.responseJSON.error || xhr.responseJSON.message || msg;
            }
            $err.text(msg).removeAttr('hidden').prop('hidden', false);
        }).always(function () {
            $btn.prop('disabled', false).html(orig);
        });
    }

    function clearOverride() {
        state.override = null;
        state.lastQueryKey = null;
        // The server will still honour the persisted draft override unless
        // we POST a fresh check that returns cannot_sell. We simply force a
        // fresh check so the user sees current reality.
        scheduleCheck(0);
    }

    // ─── Search modal ───────────────────────────────────────────────────
    function openSearchModal() {
        var $modal = $('[data-cw-fahras-search-modal]');
        if (!$modal.length) return;

        // Pre-fill with the current name (if any) for convenience.
        var prefill = cleanName(state.$nameInput.val());
        $modal.find('[data-cw-fahras-search-input]').val(prefill);
        $modal.find('[data-cw-fahras-search-results]').html(
            '<p class="cw-field__hint">سيتم البحث عبر جميع شركات التقسيط المرتبطة بنظام الفهرس.</p>'
        );
        $modal.removeAttr('hidden').prop('hidden', false);
        setTimeout(function () { $modal.find('[data-cw-fahras-search-input]').trigger('focus'); }, 30);

        if (prefill && prefill.length >= 3) runSearch();
    }

    function runSearch() {
        var $modal   = $('[data-cw-fahras-search-modal]');
        var $input   = $modal.find('[data-cw-fahras-search-input]');
        var $results = $modal.find('[data-cw-fahras-search-results]');
        var q = trim($input.val());
        if (q.length < 3) {
            $results.html('<p class="cw-fahras-search__error">يرجى إدخال 3 أحرف على الأقل.</p>');
            return;
        }
        $results.html('<p class="cw-fahras-search__loading"><i class="fa fa-spinner fa-spin"></i> جاري البحث…</p>');

        $.ajax({
            url:      state.urls.search,
            method:   'POST',
            dataType: 'json',
            data:     { q: q, limit: 25, _csrf: csrfToken() },
            timeout: 20000,
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                var msg = (resp && (resp.error || resp.message)) || 'تعذّر إتمام البحث.';
                $results.html('<p class="cw-fahras-search__error">' + escapeHtml(msg) + '</p>');
                return;
            }
            renderSearchResults($results, resp.results || []);
        }).fail(function (xhr) {
            var msg = 'تعذّر الاتصال بنظام الفهرس.';
            if (xhr && xhr.responseJSON) {
                msg = xhr.responseJSON.error || xhr.responseJSON.message || msg;
            }
            $results.html('<p class="cw-fahras-search__error">' + escapeHtml(msg) + '</p>');
        });
    }

    function renderSearchResults($results, items) {
        if (!items.length) {
            $results.html('<p class="cw-fahras-search__empty">لا توجد نتائج مطابقة.</p>');
            return;
        }
        $results.empty();
        $.each(items, function (_i, m) {
            var name  = m.name      || m.full_name   || '—';
            var idNum = m.id_number || m.national_id || '';
            var phone = m.phone     || m.mobile      || '';
            var src   = m.source    || m.company     || 'فهرس';
            var meta  = [];
            if (idNum) meta.push('الرقم الوطني: ' + idNum);
            if (phone) meta.push('الهاتف: ' + phone);
            if (m.account)    meta.push('الحساب: ' + m.account);
            if (m.created_at) meta.push('بتاريخ: ' + m.created_at);

            var $row = $(
                '<div class="cw-fahras-match">' +
                    '<div class="cw-fahras-match__name">' + escapeHtml(name) + '</div>' +
                    '<div class="cw-fahras-match__meta">' + escapeHtml(meta.join(' · ')) + '</div>' +
                    '<div class="cw-fahras-match__src">' + escapeHtml(src) + '</div>' +
                '</div>'
            );
            // Click → use this candidate (fill name + id, close modal).
            if (idNum || name) {
                $row.css('cursor', 'pointer').attr('title', 'استخدام هذه البيانات لتعبئة النموذج');
                $row.on('click', function () {
                    if (idNum)        state.$idInput.val(idNum).trigger('input');
                    if (name && name !== '—') state.$nameInput.val(name).trigger('input');
                    closeAllModals();
                });
            }
            $results.append($row);
        });
    }

    // ─── Public API ─────────────────────────────────────────────────────
    CWFahras.recheck    = function () { performCheck(true); };
    CWFahras.getVerdict = function () { return state.lastVerdict; };
    CWFahras.isBlocking = function () { return state.blocking; };
    CWFahras.destroy    = function () {
        $(document).off(NS);
        if (state.$card)      state.$card.off(NS);
        if (state.$idInput)   state.$idInput.off(NS);
        if (state.$nameInput) state.$nameInput.off(NS);
        if (state.$phoneInput) state.$phoneInput.off(NS);
        clearTimeout(state.debounceTm);
        if (state.inflight) try { state.inflight.abort(); } catch (_) {}
    };

}(window.jQuery, window));
