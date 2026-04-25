/**
 * ═══════════════════════════════════════════════════════════════
 *  Inventory Items — Pro Interactions
 *  Tayseer ERP — نظام تيسير
 *  ─────────────────────────────────────────────────────────────
 *  Features:
 *   1. Status pill + category chip filtering (Pjax)
 *   2. Search debounce
 *   3. View toggle (cards/table)
 *   4. Bulk selection + floating action bar
 *   5. Saved Views (localStorage)
 *   6. Real-time polling (30s) with diff highlight + KPI flash
 *   7. Toast notifications
 *   8. Keyboard shortcuts
 * ═══════════════════════════════════════════════════════════════
 */
(function () {
    'use strict';

    if (window.InvItemsPro && window.InvItemsPro._initialized) return;

    const CFG = window.InvItemsProCfg || {};
    const PJAX_ID = '#crud-datatable-pjax';
    const SV_KEY = 'tayseer.inv.savedViews.v1';
    const VIEW_PREF_KEY = 'tayseer.inv.viewMode';

    /* ═══ Helpers ═══ */
    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
    const $  = (sel, root) => (root || document).querySelector(sel);

    function debounce(fn, wait) {
        let t;
        return function () {
            clearTimeout(t);
            const args = arguments, ctx = this;
            t = setTimeout(() => fn.apply(ctx, args), wait);
        };
    }

    function toast(msg, kind, ms) {
        let el = $('#inv-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'inv-toast';
            el.className = 'inv-toast';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        el.className = 'inv-toast' + (kind ? ' inv-toast--' + kind : '');
        el.innerHTML = '<i class="fa ' + (kind === 'danger' ? 'fa-exclamation-circle' : kind === 'warning' ? 'fa-exclamation-triangle' : kind === 'info' ? 'fa-info-circle' : 'fa-check-circle') + '"></i> <span></span>';
        el.querySelector('span').textContent = msg;
        el.setAttribute('data-show', '1');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.removeAttribute('data-show'), ms || 2800);
    }

    function setQS(updates) {
        const url = new URL(window.location.href);
        Object.keys(updates).forEach(k => {
            const v = updates[k];
            if (v === null || v === '' || v === undefined) url.searchParams.delete(k);
            else url.searchParams.set(k, v);
        });
        return url.toString();
    }

    function pjaxReload(url) {
        if (window.jQuery && window.jQuery.pjax) {
            window.jQuery.pjax.reload({ container: PJAX_ID, url: url, replace: false, push: true, timeout: 8000 });
        } else {
            window.location.href = url;
        }
    }

    /* ═══════════════════════════════════════════════════════
     *  Pills & Chips & Sort
     * ═══════════════════════════════════════════════════════ */
    function bindFilters() {
        document.addEventListener('click', function (e) {
            const pill = e.target.closest('[data-inv-pill]');
            if (pill) {
                e.preventDefault();
                const url = pill.getAttribute('href') || pill.getAttribute('data-url');
                if (url) pjaxReload(url);
                return;
            }
            const chip = e.target.closest('[data-inv-chip]');
            if (chip) {
                e.preventDefault();
                const url = chip.getAttribute('href') || chip.getAttribute('data-url');
                if (url) pjaxReload(url);
                return;
            }
        });
    }

    function bindSort() {
        const sel = $('[data-inv-sort]');
        if (!sel) return;
        sel.addEventListener('change', function () {
            const url = setQS({ sort: this.value, page: null });
            pjaxReload(url);
        });
    }

    function bindSearch() {
        const wrap = $('[data-inv-search]');
        if (!wrap) return;
        const input = wrap.querySelector('input');
        const clear = wrap.querySelector('.inv-search-clear');

        const updateUI = () => {
            wrap.classList.toggle('has-value', !!input.value.trim());
        };
        updateUI();

        const apply = debounce(() => {
            const url = setQS({
                'InventoryItemsSearch[item_name]': input.value.trim() || null,
                page: null
            });
            pjaxReload(url);
        }, 380);

        input.addEventListener('input', () => { updateUI(); apply(); });
        if (clear) {
            clear.addEventListener('click', () => {
                input.value = '';
                updateUI();
                apply();
                input.focus();
            });
        }

        // Keyboard shortcut: '/' focuses search
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                input.focus();
            }
        });
    }

    function bindViewToggle() {
        $$('[data-inv-view]').forEach(a => {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const mode = this.getAttribute('data-inv-view');
                try { localStorage.setItem(VIEW_PREF_KEY, mode); } catch (_) {}
                window.location.href = setQS({ view: mode });
            });
        });
    }

    /* ═══════════════════════════════════════════════════════
     *  Bulk Selection
     * ═══════════════════════════════════════════════════════ */
    const Bulk = {
        selected: new Set(),
        bar: null,
        countEl: null,

        init() {
            this.bar = $('#inv-bulk-bar');
            if (this.bar) this.countEl = this.bar.querySelector('[data-bulk-count]');

            document.addEventListener('change', (e) => {
                const cb = e.target.closest('[data-inv-pick]');
                if (!cb) return;
                const id = cb.getAttribute('data-inv-pick');
                if (cb.checked) this.selected.add(id);
                else this.selected.delete(id);
                this.refresh();
            });

            const selAll = $('[data-inv-select-all]');
            if (selAll) {
                selAll.addEventListener('click', (e) => {
                    e.preventDefault();
                    const all = $$('[data-inv-pick]');
                    const allChecked = all.every(cb => cb.checked);
                    all.forEach(cb => {
                        cb.checked = !allChecked;
                        const id = cb.getAttribute('data-inv-pick');
                        if (cb.checked) this.selected.add(id);
                        else this.selected.delete(id);
                    });
                    this.refresh();
                });
            }

            const cancelBtn = $('[data-bulk-cancel]');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.clear();
                });
            }

            const approveBtn = $('[data-bulk-approve]');
            if (approveBtn) approveBtn.addEventListener('click', (e) => { e.preventDefault(); this.action('approve'); });
            const rejectBtn  = $('[data-bulk-reject]');
            if (rejectBtn)  rejectBtn.addEventListener('click',  (e) => { e.preventDefault(); this.action('reject'); });
            const deleteBtn  = $('[data-bulk-delete]');
            if (deleteBtn)  deleteBtn.addEventListener('click',  (e) => { e.preventDefault(); this.action('delete'); });
        },

        clear() {
            this.selected.clear();
            $$('[data-inv-pick]').forEach(cb => cb.checked = false);
            this.refresh();
        },

        refresh() {
            if (!this.bar) return;
            const n = this.selected.size;
            this.bar.setAttribute('data-show', n > 0 ? '1' : '0');
            if (this.countEl) this.countEl.textContent = n;
        },

        action(kind) {
            if (this.selected.size === 0) return;
            const ids = Array.from(this.selected);
            let confirmMsg = '', url = '', extra = {};
            if (kind === 'approve') {
                confirmMsg = `هل تريد اعتماد ${ids.length} صنف؟`;
                url = CFG.bulkApproveUrl;
            } else if (kind === 'reject') {
                const reason = prompt(`سبب الرفض لـ ${ids.length} صنف (اختياري):`);
                if (reason === null) return;
                confirmMsg = '';
                url = CFG.bulkRejectUrl;
                extra.reason = reason || '';
            } else if (kind === 'delete') {
                confirmMsg = `هل أنت متأكد من حذف ${ids.length} صنف؟ لا يمكن التراجع.`;
                url = CFG.bulkDeleteUrl;
            }
            if (confirmMsg && !confirm(confirmMsg)) return;

            const fd = new FormData();
            fd.append('_csrf', CFG.csrf);
            ids.forEach(id => fd.append('pks[]', id));
            Object.keys(extra).forEach(k => fd.append(k, extra[k]));

            fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json().catch(() => ({})))
                .then(resp => {
                    if (resp && (resp.success === true || resp.forceClose)) {
                        toast(resp.message || 'تمت العملية بنجاح', 'success');
                        this.clear();
                        pjaxReload(window.location.href);
                    } else {
                        toast((resp && resp.message) || 'فشلت العملية', 'danger');
                    }
                })
                .catch(() => toast('خطأ في الاتصال', 'danger'));
        }
    };

    /* ═══════════════════════════════════════════════════════
     *  Saved Views
     * ═══════════════════════════════════════════════════════ */
    const SavedViews = {
        load() {
            try { return JSON.parse(localStorage.getItem(SV_KEY) || '[]'); }
            catch (_) { return []; }
        },
        save(list) {
            try { localStorage.setItem(SV_KEY, JSON.stringify(list)); }
            catch (_) {}
        },
        init() {
            this.render();

            const saveBtn = $('[data-sv-save]');
            if (saveBtn) {
                saveBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const name = prompt('اسم العرض المخصص:', 'عرض جديد');
                    if (!name) return;
                    const list = this.load();
                    list.push({ id: 'u' + Date.now(), name: name, query: window.location.search, custom: true });
                    this.save(list);
                    this.render();
                    toast('تم حفظ العرض', 'success');
                });
            }

            document.addEventListener('click', (e) => {
                const rm = e.target.closest('[data-sv-remove]');
                if (rm) {
                    e.preventDefault(); e.stopPropagation();
                    if (!confirm('حذف هذا العرض المحفوظ؟')) return;
                    const id = rm.getAttribute('data-sv-remove');
                    const list = this.load().filter(v => v.id !== id);
                    this.save(list);
                    this.render();
                }
            });
        },
        render() {
            const wrap = $('[data-sv-list]');
            if (!wrap) return;
            wrap.innerHTML = '';
            const list = this.load();
            const currentQuery = window.location.search;

            list.forEach(v => {
                const a = document.createElement('a');
                a.className = 'inv-sv';
                a.setAttribute('href', window.location.pathname + v.query);
                a.setAttribute('data-sv', v.id);
                if (v.query === currentQuery) a.classList.add('inv-sv--active');
                a.innerHTML = '<i class="fa fa-bookmark-o"></i> <span></span>';
                a.querySelector('span').textContent = v.name;
                if (v.custom) {
                    const x = document.createElement('button');
                    x.type = 'button';
                    x.className = 'inv-sv-remove';
                    x.setAttribute('data-sv-remove', v.id);
                    x.setAttribute('aria-label', 'حذف العرض');
                    x.innerHTML = '<i class="fa fa-times"></i>';
                    a.appendChild(x);
                }
                wrap.appendChild(a);
            });
        }
    };

    /* ═══════════════════════════════════════════════════════
     *  Real-time Polling
     * ═══════════════════════════════════════════════════════ */
    const Live = {
        timer: null,
        enabled: true,
        lastUpdate: null,

        init() {
            const toggle = $('[data-live-toggle]');
            if (!toggle) return;

            try {
                const stored = localStorage.getItem('tayseer.inv.live');
                if (stored === '0') this.enabled = false;
            } catch (_) {}

            this.updateUI();
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.enabled = !this.enabled;
                try { localStorage.setItem('tayseer.inv.live', this.enabled ? '1' : '0'); } catch (_) {}
                this.updateUI();
                if (this.enabled) this.start(); else this.stop();
            });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.pause();
                else if (this.enabled) this.start();
            });

            if (this.enabled) this.start();
        },

        updateUI() {
            const toggle = $('[data-live-toggle]');
            if (!toggle) return;
            toggle.setAttribute('data-live', this.enabled ? 'on' : 'off');
            const lbl = toggle.querySelector('[data-live-label]');
            if (lbl) lbl.textContent = this.enabled ? 'مباشر' : 'متوقّف';
            toggle.setAttribute('title', this.enabled ? 'إيقاف التحديث المباشر' : 'تفعيل التحديث المباشر');
        },

        start() {
            this.stop();
            if (!CFG.streamUrl) return;
            this.timer = setInterval(() => this.poll(), 30000);
            // First poll after 12s
            setTimeout(() => { if (this.enabled && !document.hidden) this.poll(); }, 12000);
        },

        stop() {
            if (this.timer) { clearInterval(this.timer); this.timer = null; }
        },

        pause() { this.stop(); },

        poll() {
            if (!CFG.streamUrl) return;
            const since = this.lastUpdate || 0;
            fetch(CFG.streamUrl + (CFG.streamUrl.indexOf('?') > -1 ? '&' : '?') + 'since=' + since, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.ok) return;
                this.lastUpdate = data.ts || Date.now();
                this.applyKpi(data.kpi);
                if (data.changed && data.changed.length) this.flagChanged(data.changed);
                if (data.totals_changed) {
                    toast('تم تحديث ' + (data.changed_count || data.changed.length) + ' صنف', 'info', 2200);
                }
            })
            .catch(() => { /* silent */ });
        },

        applyKpi(kpi) {
            if (!kpi) return;
            Object.keys(kpi).forEach(key => {
                const el = $('[data-kpi="' + key + '"]');
                if (!el) return;
                const numEl = el.querySelector('.inv-kpi-num');
                const newVal = kpi[key];
                if (numEl) {
                    const oldText = (numEl.textContent || '').replace(/[^\d.]/g, '');
                    const display = (typeof newVal === 'number')
                        ? (key === 'value' ? newVal.toLocaleString('en', { maximumFractionDigits: 0 }) : newVal.toLocaleString('en'))
                        : newVal;
                    if (oldText !== String(newVal)) {
                        numEl.firstChild ? (numEl.firstChild.nodeValue = display) : (numEl.textContent = display);
                        el.setAttribute('data-flash', '1');
                        setTimeout(() => el.removeAttribute('data-flash'), 1300);
                    }
                }
            });
        },

        flagChanged(ids) {
            ids.forEach(id => {
                const card = $('[data-inv-card-id="' + id + '"]');
                if (!card) return;
                if (card.querySelector('.inv-flag-new')) return;
                const meta = card.querySelector('.inv-card-head-meta');
                if (!meta) return;
                const flag = document.createElement('span');
                flag.className = 'inv-flag-new';
                flag.innerHTML = '<i class="fa fa-bolt"></i> جديد';
                meta.insertBefore(flag, meta.firstChild);
                setTimeout(() => flag.remove(), 6000);
            });
        }
    };

    /* ═══════════════════════════════════════════════════════
     *  Init
     * ═══════════════════════════════════════════════════════ */
    function init() {
        bindFilters();
        bindSort();
        bindSearch();
        bindViewToggle();
        Bulk.init();
        SavedViews.init();
        Live.init();

        // Re-init after Pjax reload
        if (window.jQuery) {
            window.jQuery(document).on('pjax:end', PJAX_ID, function () {
                Bulk.refresh();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.InvItemsPro = {
        _initialized: true,
        toast: toast,
        Live: Live,
        Bulk: Bulk,
        SavedViews: SavedViews
    };
})();
