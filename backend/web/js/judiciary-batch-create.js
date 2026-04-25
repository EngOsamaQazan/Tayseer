/**
 * Judiciary Batch Create Wizard — client controller.
 *
 * Talks to JudiciaryController via fetch():
 *   - Step 1 → POST batch-parse-input  (returns valid/invalid/has_existing_case + preview rows)
 *   - Step 3 → POST batch-start        (creates batch + items, returns chunks plan)
 *           → POST batch-execute-chunk × N
 *           → POST batch-finalize      (refreshes counters, returns auto_print + judiciary ids)
 *
 * Boot data is rendered server-side into window.BW_BOOT.
 */
(function () {
    'use strict';

    var BOOT = window.BW_BOOT || {};
    var EP = BOOT.endpoints || {};
    var CSRF = BOOT.csrf || {};
    var ADDRESSES = BOOT.addresses || [];
    var LAWYERS = BOOT.lawyers || [];
    var TYPES = BOOT.types || [];
    var COMPANIES = BOOT.companies || [];

    var state = {
        step: 1,
        validIds: [],
        previewByIdx: {},
        rowsOrder: [],
        overrides: {},
        sharedSnapshot: null,
        entryMethod: 'paste',
        batchId: null,
        autoPrint: true,
        chunksDone: 0,
        chunksTotal: 0,
        loadedTemplateId: 0,
    };

    /* ───────── helpers ───────── */

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }
    function fmtMoney(v) {
        if (v === null || v === undefined) return '0';
        return Number(v).toLocaleString('en-US', { maximumFractionDigits: 2 });
    }
    function postForm(url, formData) {
        formData.append(CSRF.param, CSRF.token);
        return fetch(url, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }
    function postJson(url, payload) {
        var fd = new FormData();
        Object.keys(payload).forEach(function (k) {
            var v = payload[k];
            if (v === null || v === undefined) return;
            if (typeof v === 'object') {
                if (Array.isArray(v)) {
                    v.forEach(function (item) { fd.append(k + '[]', item); });
                } else {
                    Object.keys(v).forEach(function (k2) {
                        if (typeof v[k2] === 'object' && v[k2] !== null) {
                            Object.keys(v[k2]).forEach(function (k3) {
                                fd.append(k + '[' + k2 + '][' + k3 + ']', v[k2][k3]);
                            });
                        } else {
                            fd.append(k + '[' + k2 + ']', v[k2]);
                        }
                    });
                }
            } else {
                fd.append(k, v);
            }
        });
        return postForm(url, fd);
    }
    function getJson(url) {
        return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); });
    }

    function setStep(n) {
        state.step = n;
        $$('.bw-step').forEach(function (el) {
            var s = parseInt(el.dataset.step, 10);
            el.classList.toggle('active', s === n);
            el.classList.toggle('done', s < n);
        });
        $('#bw-step-1').style.display = (n === 1) ? '' : 'none';
        $('#bw-step-2').style.display = (n === 2) ? '' : 'none';
        $('#bw-step-3').style.display = (n === 3) ? '' : 'none';
    }

    function showError(msg) {
        alert(msg || 'حدث خطأ');
    }

    /* ───────── Step 1 — input tabs ───────── */

    function activateTab(name) {
        $$('.bw-tab').forEach(function (t) {
            t.classList.toggle('active', t.dataset.tab === name);
        });
        $$('.bw-tab-pane').forEach(function (p) {
            p.classList.toggle('active', p.dataset.pane === name);
        });
        state.entryMethod = name;
    }

    function bindTabs() {
        $$('.bw-tab').forEach(function (t) {
            t.addEventListener('click', function () { activateTab(t.dataset.tab); });
        });
        var startTab = BOOT.defaultTab && BOOT.defaultTab !== 'preview' ? BOOT.defaultTab : 'paste';
        activateTab(startTab);
    }

    function bindPaste() {
        $('#bw-paste-go').addEventListener('click', function () {
            var raw = $('#bw-paste').value.trim();
            if (!raw) { showError('الصق أرقام العقود أولاً'); return; }
            var fd = new FormData();
            fd.append('method', 'paste');
            fd.append('raw', raw);
            state.entryMethod = 'paste';
            postForm(EP.parse, fd).then(handleParseResponse).catch(function (e) { showError(e.message); });
        });
    }

    function bindExcel() {
        var drop = $('#bw-drop'), input = $('#bw-file');
        drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('dragover'); });
        drop.addEventListener('dragleave', function () { drop.classList.remove('dragover'); });
        drop.addEventListener('drop', function (e) {
            e.preventDefault(); drop.classList.remove('dragover');
            if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; submitExcel(); }
        });
        input.addEventListener('change', submitExcel);

        function submitExcel() {
            if (!input.files.length) return;
            var fd = new FormData();
            fd.append('method', 'excel');
            fd.append('file', input.files[0]);
            state.entryMethod = 'excel';
            postForm(EP.parse, fd).then(handleParseResponse).catch(function (e) { showError(e.message); });
        }
    }

    function bindSelection() {
        initFilterSelects();

        $('#bw-f-go').addEventListener('click', loadSearch);
        var clearBtn = $('#bw-f-clear');
        if (clearBtn) clearBtn.addEventListener('click', clearFilters);

        var qInput = document.getElementById('bw-f-q');
        if (qInput) qInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); loadSearch(); }
        });

        $('#bw-sel-all').addEventListener('change', function () {
            $$('#bw-search-table tbody input[type=checkbox]:not(:disabled)').forEach(function (cb) {
                cb.checked = this.checked;
            }, this);
            updateSelCount();
        });
        $('#bw-sel-go').addEventListener('click', function () {
            var ids = [];
            $$('#bw-search-table tbody input[type=checkbox]:checked').forEach(function (cb) {
                ids.push(parseInt(cb.value, 10));
            });
            if (!ids.length) { showError('اختر عقداً واحداً على الأقل'); return; }
            var fd = new FormData();
            fd.append('method', 'selection');
            ids.forEach(function (id) { fd.append('contract_ids[]', id); });
            state.entryMethod = 'selection';
            postForm(EP.parse, fd).then(handleParseResponse).catch(function (e) { showError(e.message); });
        });
    }

    function initFilterSelects() {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') return;
        window.jQuery('#bw-step-1 select[data-bw-multi="1"]').each(function () {
            var $sel = window.jQuery(this);
            if ($sel.data('select2')) return;
            $sel.select2({
                theme: 'bootstrap4',
                placeholder: $sel.data('placeholder') || '— الكل —',
                allowClear: true,
                width: '100%',
                dropdownParent: $sel.closest('.bw-filters'),
            });
        });
    }

    function multiVals(id) {
        var el = document.getElementById(id);
        if (!el) return [];
        return Array.from(el.selectedOptions).map(function (o) { return o.value; }).filter(Boolean);
    }
    function inputVal(id) {
        var el = document.getElementById(id);
        return el ? (el.value || '').trim() : '';
    }

    function loadSearch() {
        var params = new URLSearchParams();

        multiVals('bw-f-status').forEach(function (v) { params.append('statuses[]', v); });
        multiVals('bw-f-type').forEach(function (v) { params.append('contract_types[]', v); });
        multiVals('bw-f-company').forEach(function (v) { params.append('company_ids[]', v); });
        multiVals('bw-f-jobtype').forEach(function (v) { params.append('job_type_ids[]', v); });
        multiVals('bw-f-job').forEach(function (v) { params.append('job_ids[]', v); });

        var df = inputVal('bw-f-from'); if (df) params.append('date_from', df);
        var dt = inputVal('bw-f-to');   if (dt) params.append('date_to', dt);
        var q  = inputVal('bw-f-q');    if (q)  params.append('q', q);

        var tbody = $('#bw-search-table tbody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px;">جارٍ البحث...</td></tr>';

        getJson(EP.search + '?' + params.toString()).then(function (resp) {
            if (!resp.ok) { showError(resp.message || 'فشل البحث'); return; }
            renderSearchResults(resp.items, resp.total);
        }).catch(function (e) { showError(e.message); });
    }

    function clearFilters() {
        ['bw-f-from', 'bw-f-to', 'bw-f-q'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery('#bw-step-1 select[data-bw-multi="1"]').val(null).trigger('change');
        }
    }

    var STATUS_LABELS = {
        active: 'نشط', judiciary: 'قضاء', judiciary_active: 'قضاء فعّال',
        judiciary_paid: 'قضاء مسدد', legal_department: 'قانوني',
        settlement: 'تسوية', finished: 'منتهي', canceled: 'ملغي',
    };

    function renderSearchResults(items, total) {
        var tbody = $('#bw-search-table tbody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px;">لا توجد نتائج</td></tr>';
            updateSelCount(0); return;
        }
        tbody.innerHTML = items.map(function (r) {
            var caseLabel = r.judiciary_id
                ? '<span class="bw-pill bw-pill-warn">قضية #' + r.judiciary_id + '</span>'
                : '<span class="bw-pill bw-pill-ok">جاهز</span>';
            var disabled = r.judiciary_id ? 'disabled title="للعقد قضية فعّالة"' : '';
            var statusLabel = STATUS_LABELS[r.status] || (r.status || '—');
            return '<tr>' +
                '<td><input type="checkbox" value="' + r.id + '" ' + disabled + '></td>' +
                '<td>' + r.id + '</td>' +
                '<td>' + (r.client_names || '—') + '</td>' +
                '<td>' + (r.date_of_sale || '—') + '</td>' +
                '<td>' + fmtMoney(r.remaining) + '</td>' +
                '<td>' + statusLabel + '</td>' +
                '<td>' + caseLabel + '</td>' +
                '</tr>';
        }).join('');
        $$('#bw-search-table tbody input[type=checkbox]').forEach(function (cb) {
            cb.addEventListener('change', updateSelCount);
        });
        updateSelCount(total);
    }

    function updateSelCount(total) {
        var n = $$('#bw-search-table tbody input[type=checkbox]:checked').length;
        var totalSuffix = (typeof total === 'number' && total > 0)
            ? ' من أصل ' + total + ' نتيجة'
            : '';
        $('#bw-sel-count').textContent = n
            ? ('تم اختيار ' + n + ' عقد' + totalSuffix)
            : ('لم يتم اختيار أي عقد' + totalSuffix);
    }

    /* ───────── Step 2 — preview + shared form ───────── */

    function handleParseResponse(resp) {
        if (!resp.ok) { showError(resp.message || 'فشل التحليل'); return; }
        if (resp.preview.length > BOOT.maxContracts) {
            if (!confirm('تم اختيار ' + resp.preview.length + ' عقداً، الحد الأعلى ' + BOOT.maxContracts + '. سيتم اقتطاع الباقي. متابعة؟')) return;
            resp.preview = resp.preview.slice(0, BOOT.maxContracts);
            resp.valid = resp.valid.slice(0, BOOT.maxContracts);
        }

        if (resp.invalid && resp.invalid.length) {
            var msg = resp.invalid.length + ' أرقام عقود غير موجودة وسيتم تجاهلها: ' + resp.invalid.slice(0, 10).join(', ');
            console.warn(msg);
        }
        if (resp.has_existing_case && resp.has_existing_case.length) {
            console.warn(resp.has_existing_case.length + ' عقد له قضية مسبقة وسيتم تجاهله');
        }

        state.validIds = resp.valid;
        state.previewByIdx = {};
        state.rowsOrder = [];
        state.overrides = {};
        resp.preview.forEach(function (row) {
            state.previewByIdx[row.contract_id] = row;
            state.rowsOrder.push(row.contract_id);
        });

        renderPreview();
        setStep(2);
        loadTemplates();
    }

    function renderPreview() {
        var tbody = $('#bw-preview-table tbody');
        if (!state.rowsOrder.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:20px;">لا توجد عقود</td></tr>';
            updateSummary(); return;
        }
        tbody.innerHTML = state.rowsOrder.map(function (cid) {
            var r = state.previewByIdx[cid];
            var ov = state.overrides[cid] || {};
            return '<tr data-cid="' + cid + '">' +
                '<td>' + cid + '</td>' +
                '<td>' + (r.client_names || '—') + '</td>' +
                '<td>' + fmtMoney(r.remaining) + '</td>' +
                '<td>' + lawyerSelect(ov.lawyer_id || '') + '</td>' +
                '<td>' + typeSelect(ov.type_id || '') + '</td>' +
                '<td>' + companySelect(ov.company_id || r.company_id || '') + '</td>' +
                '<td>' + addressSelect(ov.address_id || '') + '</td>' +
                '<td><button type="button" class="bw-rm">إزالة</button></td>' +
                '</tr>';
        }).join('');
        bindPreviewEditors();
        updateSummary();
    }

    function bindPreviewEditors() {
        $$('#bw-preview-table tbody tr').forEach(function (tr) {
            var cid = parseInt(tr.dataset.cid, 10);
            tr.querySelectorAll('select').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    state.overrides[cid] = state.overrides[cid] || {};
                    state.overrides[cid][sel.dataset.field] = sel.value;
                    updateSummary();
                });
            });
            tr.querySelector('.bw-rm').addEventListener('click', function () {
                state.rowsOrder = state.rowsOrder.filter(function (x) { return x !== cid; });
                delete state.overrides[cid];
                delete state.previewByIdx[cid];
                renderPreview();
            });
        });
    }

    function lawyerSelect(val) {
        var opts = '<option value="">— مشترك —</option>' + LAWYERS.map(function (l) {
            return '<option value="' + l.id + '"' + (String(l.id) === String(val) ? ' selected' : '') + '>' + l.name + '</option>';
        }).join('');
        return '<select data-field="lawyer_id">' + opts + '</select>';
    }
    function typeSelect(val) {
        var opts = '<option value="">— مشترك —</option>' + TYPES.map(function (t) {
            return '<option value="' + t.id + '"' + (String(t.id) === String(val) ? ' selected' : '') + '>' + t.name + '</option>';
        }).join('');
        return '<select data-field="type_id">' + opts + '</select>';
    }
    function companySelect(val) {
        var opts = '<option value="">— مشترك —</option>' + COMPANIES.map(function (c) {
            return '<option value="' + c.id + '"' + (String(c.id) === String(val) ? ' selected' : '') + '>' + c.name + '</option>';
        }).join('');
        return '<select data-field="company_id">' + opts + '</select>';
    }
    function addressSelect(val) {
        var opts = '<option value="">— مشترك —</option>' + ADDRESSES.map(function (a) {
            var label = (a.address || '').substring(0, 60);
            return '<option value="' + a.id + '"' + (String(a.id) === String(val) ? ' selected' : '') + '>' + label + '</option>';
        }).join('');
        return '<select data-field="address_id">' + opts + '</select>';
    }

    function updateSummary() {
        var pct = parseFloat($('#bw-percentage').value || '0') || 0;
        var totalRem = 0;
        state.rowsOrder.forEach(function (cid) {
            var r = state.previewByIdx[cid];
            if (r) totalRem += Number(r.remaining || 0);
        });
        var totalFee = pct > 0 ? (totalRem * pct / 100) : 0;

        $('#bw-sum-count').textContent = state.rowsOrder.length;
        $('#bw-sum-rem').textContent = fmtMoney(totalRem);
        $('#bw-sum-fee').textContent = fmtMoney(totalFee);
        $('#bw-preview-count').textContent = state.rowsOrder.length ? '(' + state.rowsOrder.length + ')' : '';
    }

    /* ───────── Templates ───────── */

    var templatesCache = [];

    function loadTemplates() {
        getJson(EP.tplList + '?include_data=1').then(function (resp) {
            if (!resp.ok) return;
            templatesCache = resp.items || [];
            var sel = $('#bw-tpl-load');
            sel.innerHTML = '<option value="">-- تحميل قالب --</option>' + templatesCache.map(function (t) {
                return '<option value="' + t.id + '">' + t.name + ' (' + t.usage_count + ')</option>';
            }).join('');
        });
    }

    function applyTemplate(t) {
        var d = t.data || {};
        if (d.court_id) $('#bw-court').value = d.court_id;
        if (d.lawyer_id) $('#bw-lawyer').value = d.lawyer_id;
        if (d.type_id) $('#bw-type').value = d.type_id;
        if (d.company_id !== undefined) $('#bw-company').value = d.company_id || '';
        if (d.percentage !== undefined) $('#bw-percentage').value = d.percentage;
        if (d.year) $('#bw-year').value = d.year;
        if (d.address_mode === 'random') {
            $('#bw-address').value = 'random';
        } else if (d.address_id) {
            $('#bw-address').value = d.address_id;
        }
        if (d.auto_print !== undefined) $('#bw-auto-print').checked = !!d.auto_print;
        updateSummary();
    }

    function bindTemplateActions() {
        $('#bw-tpl-load').addEventListener('change', function () {
            var id = parseInt(this.value, 10);
            if (!id) return;
            state.loadedTemplateId = id;
            var t = templatesCache.find(function (x) { return x.id === id; });
            if (t) applyTemplate(t);
        });

        $('#bw-tpl-save').addEventListener('click', function () {
            var name = prompt('اسم القالب:');
            if (!name) return;
            var data = collectShared();
            postJson(EP.tplSave, { name: name, data: data }).then(function (resp) {
                if (!resp.ok) { showError(resp.message || 'فشل حفظ القالب'); return; }
                alert('تم حفظ القالب');
                loadTemplates();
            });
        });
    }

    function collectShared() {
        return {
            court_id: $('#bw-court').value,
            lawyer_id: $('#bw-lawyer').value,
            type_id: $('#bw-type').value,
            company_id: $('#bw-company').value,
            percentage: $('#bw-percentage').value,
            year: $('#bw-year').value,
            address_mode: $('#bw-address').value === 'random' ? 'random' : 'fixed',
            address_id: $('#bw-address').value === 'random' ? null : $('#bw-address').value,
            auto_print: $('#bw-auto-print').checked ? 1 : 0,
        };
    }

    /* ───────── Step 3 — execute ───────── */

    function bindStepNav() {
        $('#bw-back-1').addEventListener('click', function () { setStep(1); });
        $('#bw-go-3').addEventListener('click', function () {
            if (!state.rowsOrder.length) { showError('لا توجد عقود في الدفعة'); return; }
            var shared = collectShared();
            if (!shared.court_id || !shared.lawyer_id) { showError('المحكمة والمحامي حقول مطلوبة'); return; }

            state.sharedSnapshot = shared;
            state.autoPrint = !!$('#bw-auto-print').checked;
            startExecution();
        });
        $('#bw-percentage').addEventListener('input', updateSummary);
    }

    function startExecution() {
        setStep(3);
        $('#bw-progress').style.width = '0%';
        $('#bw-progress').textContent = '0%';
        $('#bw-log').innerHTML = '';
        $('#bw-final-actions').style.display = 'none';

        var payload = {
            contract_ids: state.rowsOrder,
            shared: state.sharedSnapshot,
            overrides: state.overrides,
            entry_method: state.entryMethod,
            template_id: state.loadedTemplateId,
        };
        postJson(EP.start, payload).then(function (resp) {
            if (!resp.ok) { showError(resp.message); return; }
            state.batchId = resp.batch_id;
            state.chunksTotal = resp.chunks;
            state.chunksDone = 0;
            log('بدأت الدفعة #' + resp.batch_id + ' — ' + resp.total + ' عقد، ' + resp.chunks + ' دفعات.', 'ok');
            runNextChunk();
        }).catch(function (e) { showError(e.message); });
    }

    function runNextChunk() {
        if (state.chunksDone >= state.chunksTotal) {
            finalize(); return;
        }
        var idx = state.chunksDone;
        postJson(EP.execute, { batch_id: state.batchId, chunk_index: idx }).then(function (resp) {
            if (!resp.ok) {
                log('فشل دفعة #' + idx + ': ' + (resp.message || ''), 'err');
            } else {
                resp.details.forEach(function (d) {
                    if (d.status === 'success') {
                        log('✓ عقد ' + d.contract_id + ' → قضية #' + d.judiciary_id, 'ok');
                    } else {
                        log('✗ عقد ' + d.contract_id + ' — ' + (d.message || 'فشل'), 'err');
                    }
                });
            }
            state.chunksDone++;
            var pct = Math.round((state.chunksDone / state.chunksTotal) * 100);
            $('#bw-progress').style.width = pct + '%';
            $('#bw-progress').textContent = pct + '%';
            runNextChunk();
        }).catch(function (e) {
            log('خطأ شبكة: ' + e.message, 'err');
            state.chunksDone++;
            runNextChunk();
        });
    }

    function finalize() {
        postJson(EP.finalize, { batch_id: state.batchId }).then(function (resp) {
            if (!resp.ok) { showError(resp.message); return; }
            log('— انتهت الدفعة. نجاح: ' + resp.success + '، فشل: ' + resp.failed, resp.failed === 0 ? 'ok' : 'err');
            $('#bw-final-actions').style.display = 'flex';

            if (resp.success > 0) {
                var printBtn = $('#bw-print-go');
                printBtn.style.display = '';
                printBtn.onclick = function () {
                    window.open(EP.printRedirect + '?batch_id=' + state.batchId, '_blank');
                };

                if (state.autoPrint) {
                    log('— سيتم فتح صفحة الطباعة خلال ثانيتين...', 'ok');
                    setTimeout(function () {
                        window.open(EP.printRedirect + '?batch_id=' + state.batchId, '_blank');
                    }, 2000);
                }
            }
        });
    }

    function log(msg, cls) {
        var line = document.createElement('div');
        if (cls) line.className = 'bw-' + cls;
        line.textContent = msg;
        var box = $('#bw-log');
        box.appendChild(line);
        box.scrollTop = box.scrollHeight;
    }

    /* ───────── Boot ───────── */

    document.addEventListener('DOMContentLoaded', function () {
        bindTabs();
        bindPaste();
        bindExcel();
        bindSelection();
        bindTemplateActions();
        bindStepNav();

        // If pre-selected ids were passed via GET, push them straight to step 2.
        if (BOOT.preselected && BOOT.preselected.length) {
            var fd = new FormData();
            fd.append('method', 'selection');
            BOOT.preselected.forEach(function (id) { fd.append('contract_ids[]', id); });
            state.entryMethod = 'selection';
            postForm(EP.parse, fd).then(handleParseResponse).catch(function (e) { showError(e.message); });
        }
    });
})();
