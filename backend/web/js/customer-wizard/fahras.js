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
                    // Intentional navigation away from the wizard — silence
                    // the beforeunload "unsaved changes" guard so the rep
                    // doesn't see a confusing browser confirm dialog. The
                    // <a> handles the actual navigation.
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
                renderError(msg, resp && resp.blocks);
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
            if (xhr && xhr.responseJSON) {
                msg = xhr.responseJSON.error || xhr.responseJSON.message || msg;
            }
            // Fail-closed by default — server policy mirrors this.
            renderError(msg, true);
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

    function renderError(message, blocks) {
        state.$card.attr('data-cw-fahras-state', 'error');
        var html =
            '<div class="cw-fahras__icon" aria-hidden="true"><i class="fa fa-exclamation-triangle"></i></div>' +
            '<div class="cw-fahras__message">' +
                '<p class="cw-fahras__text"><strong>تعذّر الاتصال بنظام الفهرس.</strong></p>' +
                '<p class="cw-fahras__sub">' + escapeHtml(message) + '</p>' +
                (blocks
                    ? '<p class="cw-fahras__meta"><i class="fa fa-lock"></i> ' +
                      'لا يمكن إنشاء العميل قبل عودة نظام الفهرس. يرجى المحاولة لاحقاً.</p>'
                    : '<p class="cw-fahras__meta"><i class="fa fa-exclamation"></i> ' +
                      'تحذير فقط — يمكنك المتابعة على مسؤوليتك.</p>') +
            '</div>';
        state.$body.html(html);
        state.$matchesBox.attr('hidden', '').prop('hidden', true);
        state.$card.find('[data-cw-fahras-action="recheck"]').prop('disabled', false);
    }

    function renderVerdict(resp) {
        var verdict = resp.verdict || 'no_record';
        var blocks  = !!resp.blocks;
        var warns   = !!resp.warns;

        // Same-company optimisation: when every match in the verdict comes
        // from THIS Tayseer instance's own company, the customer is already
        // ours and the right action is to add a new contract on their
        // existing record — not to recreate them as a customer. Render a
        // distinct state and a green CTA in place of (or alongside) the
        // standard block message. The wizard's "Next" button stays locked
        // either way: the rep should leave the wizard, not push through it.
        var sameCompany = !!(resp.same_company_only && resp.own_company_name);

        var stateName = 'can';
        var icon      = 'fa-check-circle';
        var title     = 'يمكن إضافة العميل.';
        if (sameCompany) {
            stateName = 'same-company';
            icon      = 'fa-handshake-o';
            title     = 'هذا العميل مسجَّل لدى شركتنا — أنشئ له عقداً جديداً بدلاً من إضافته كعميل.';
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
        if (sameCompany) {
            ctaHtml = renderSameCompanyCta(resp);
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
        // same-company case; otherwise fall back to the raw Fahras reason.
        var subRaw = sameCompany
            ? (resp.same_company_message_ar || resp.reason_ar || '')
            : (resp.reason_ar || '');
        var subText = subRaw
            ? '<p class="cw-fahras__sub">' + escapeHtml(subRaw) + '</p>'
            : '';

        var html =
            '<div class="cw-fahras__icon" aria-hidden="true"><i class="fa ' + icon + '"></i></div>' +
            '<div class="cw-fahras__message">' +
                '<p class="cw-fahras__text"><strong>' + escapeHtml(title) + '</strong></p>' +
                subText +
                (meta.length ? '<p class="cw-fahras__meta">' + meta.join(' · ') + '</p>' : '') +
                ctaHtml +
            '</div>';
        state.$body.html(html);

        renderMatches(resp.matches || []);
    }

    /**
     * Build the green «إضافة عقد جديد للعميل» CTA strip rendered when the
     * Fahras verdict matches are exclusively from this Tayseer instance's
     * own company. Three sub-cases handled:
     *   1. Local customer found + user can add contracts → primary CTA link.
     *   2. Local customer found + user lacks CONT_CREATE → disabled link
     *      with explanatory tooltip (so the rep escalates to a manager).
     *   3. No local customer found (Fahras-cache only)   → no CTA, just an
     *      info note (the controller already produced a friendly message).
     */
    function renderSameCompanyCta(resp) {
        var url           = resp.add_contract_url || '';
        var hasUrl        = !!url;
        var allowed       = !!resp.add_contract_allowed;
        var custName      = resp.existing_customer_name || '';
        var btnLabel      = custName
            ? 'إضافة عقد جديد لـ ' + custName
            : 'إضافة عقد جديد للعميل';

        // Customer is in Fahras under "us" but not yet imported into Tayseer.
        // Without a local id we have nowhere to link to; bail with no CTA.
        if (!hasUrl) {
            return '';
        }

        if (!allowed) {
            return (
                '<div class="cw-fahras__cta">' +
                    '<button type="button" class="cw-btn cw-btn--outline cw-btn--sm" disabled ' +
                            'aria-disabled="true" ' +
                            'title="لا تملك صلاحية «إضافة عقد».">' +
                        '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' +
                        '<span>' + escapeHtml(btnLabel) + '</span>' +
                    '</button>' +
                    '<span class="cw-fahras__cta-hint">' +
                        '<i class="fa fa-info-circle" aria-hidden="true"></i> ' +
                        'تواصل مع المدير لإصدار صلاحية «إضافة عقد».' +
                    '</span>' +
                '</div>'
            );
        }

        return (
            '<div class="cw-fahras__cta">' +
                '<a href="' + escapeHtml(url) + '" ' +
                   'class="cw-btn cw-btn--success cw-btn--sm" ' +
                   'data-cw-fahras-action="add-contract" ' +
                   'data-pjax="0">' +
                    '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' +
                    '<span>' + escapeHtml(btnLabel) + '</span>' +
                '</a>' +
                '<span class="cw-fahras__cta-hint">' +
                    '<i class="fa fa-info-circle" aria-hidden="true"></i> ' +
                    'سيتم نقلك إلى صفحة إنشاء العقد مع تعبئة بيانات العميل.' +
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
    function applyVerdictGate(resp) {
        if (!resp) {
            state.blocking = false;
        } else if (resp.override) {
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
