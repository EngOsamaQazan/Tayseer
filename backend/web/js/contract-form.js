'use strict';

var ContractForm = (function () {
    var _cfg = {};
    var _devices = {};
    var _devNum = 0;
    var _customerIsGov = false;

    /* ══════════════════════════════════════════════════
       Helpers
       ══════════════════════════════════════════════════ */
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function ajax(url, params, cb) {
        var qs = Object.keys(params).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        var sep = url.indexOf('?') === -1 ? '?' : '&';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url + sep + qs, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status === 200) {
                try { cb(JSON.parse(xhr.responseText)); } catch (e) { cb(null); }
            } else { cb(null); }
        };
        xhr.onerror = function () { cb(null); };
        xhr.send();
    }

    /* ══════════════════════════════════════════════════
       CustomerSearch — reusable AJAX autocomplete with chips
       ══════════════════════════════════════════════════ */
    function CustomerSearch(opts) {
        this.input = document.getElementById(opts.inputId);
        this.dropdown = document.getElementById(opts.dropdownId);
        this.chipsEl = document.getElementById(opts.chipsId);
        this.multiple = opts.multiple || false;
        this.inputName = opts.inputName;
        this.onSelect = opts.onSelect || null;
        this.selected = {};
        this._hlIdx = -1;

        var self = this;

        if (opts.initial && opts.initial.length) {
            for (var i = 0; i < opts.initial.length; i++) {
                this._addChip(opts.initial[i]);
            }
        }

        this.input.addEventListener('input', debounce(function () {
            self._search(self.input.value.trim());
        }, 300));

        this.input.addEventListener('focus', function () {
            if (self.input.value.trim().length >= 1) self._search(self.input.value.trim());
        });

        this.input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                e.preventDefault();
                self._handleKey(e.key);
            }
            if (e.key === 'Escape') {
                self._close();
            }
        });

        document.addEventListener('click', function (e) {
            if (!self.input.contains(e.target) && !self.dropdown.contains(e.target)) {
                self._close();
            }
        });
    }

    CustomerSearch.prototype._search = function (q) {
        if (q.length < 1) { this._close(); return; }
        var self = this;
        this.dropdown.innerHTML = '<div class="cf-dropdown-loading"><i class="fa fa-spinner fa-spin"></i> جاري البحث...</div>';
        this.dropdown.classList.add('open');
        this._hlIdx = -1;

        ajax(_cfg.searchUrl, { q: q }, function (data) {
            if (!data || !data.results) { self._showEmpty(); return; }
            var results = data.results.filter(function (r) { return !self.selected[r.id]; });
            if (results.length === 0) { self._showEmpty(); return; }

            var html = '';
            for (var i = 0; i < results.length; i++) {
                var r = results[i];
                html += '<div class="cf-dropdown-item" data-id="' + r.id + '" data-name="' + esc(r.text) + '" data-idnum="' + esc(r.id_number || '') + '" data-phone="' + esc(r.phone || '') + '">';
                html += '<span class="cf-dd-id">#' + r.id + '</span>';
                html += '<b>' + esc(r.text) + '</b>';
                if (r.id_number) html += '<small>' + esc(r.id_number) + '</small>';
                if (r.phone) html += '<small style="color:#0891b2">\u260E ' + esc(r.phone) + '</small>';
                html += '</div>';
            }
            self.dropdown.innerHTML = html;
            self.dropdown.classList.add('open');

            self.dropdown.querySelectorAll('.cf-dropdown-item').forEach(function (el) {
                el.addEventListener('click', function () {
                    self._pick(this);
                });
            });
        });
    };

    CustomerSearch.prototype._showEmpty = function () {
        this.dropdown.innerHTML = '<div class="cf-dropdown-empty"><i class="fa fa-info-circle"></i> لا توجد نتائج</div>';
        this.dropdown.classList.add('open');
    };

    CustomerSearch.prototype._close = function () {
        this.dropdown.classList.remove('open');
        this._hlIdx = -1;
    };

    CustomerSearch.prototype._handleKey = function (key) {
        var items = this.dropdown.querySelectorAll('.cf-dropdown-item');
        if (!items.length) return;
        if (key === 'ArrowDown') { this._hlIdx = Math.min(this._hlIdx + 1, items.length - 1); }
        if (key === 'ArrowUp') { this._hlIdx = Math.max(this._hlIdx - 1, 0); }
        items.forEach(function (el, i) { el.classList.toggle('highlighted', i === this._hlIdx); }.bind(this));
        if (this._hlIdx >= 0) items[this._hlIdx].scrollIntoView({ block: 'nearest' });
        if (key === 'Enter' && this._hlIdx >= 0) { this._pick(items[this._hlIdx]); }
    };

    CustomerSearch.prototype._pick = function (el) {
        var entry = {
            id: el.getAttribute('data-id'),
            name: el.getAttribute('data-name'),
            id_number: el.getAttribute('data-idnum'),
            phone: el.getAttribute('data-phone'),
        };

        if (!this.multiple) {
            this.selected = {};
            this.chipsEl.innerHTML = '';
        }

        this._addChip(entry);
        this.input.value = '';
        this._close();

        if (this.onSelect) this.onSelect(entry);
    };

    CustomerSearch.prototype._addChip = function (entry) {
        if (this.selected[entry.id]) return;
        this.selected[entry.id] = entry;

        var chip = document.createElement('span');
        chip.className = 'cf-chip';
        chip.setAttribute('data-id', entry.id);
        var label = esc(entry.name);
        if (entry.id) label = '<em class="cf-chip-id">#' + esc(entry.id) + '</em> ' + label;
        chip.innerHTML = label +
            '<input type="hidden" name="' + this.inputName + '" value="' + esc(entry.id) + '">' +
            '<i class="fa fa-times cf-chip-rm"></i>';

        var self = this;
        chip.querySelector('.cf-chip-rm').addEventListener('click', function () {
            delete self.selected[entry.id];
            chip.remove();
            if (self.onSelect) self.onSelect(null);
        });

        this.chipsEl.appendChild(chip);
    };

    CustomerSearch.prototype.clear = function () {
        this.selected = {};
        this.chipsEl.innerHTML = '';
    };

    /* ══════════════════════════════════════════════════
       TypeSwitcher — normal / solidarity toggle
       ══════════════════════════════════════════════════ */
    function initTypeSwitcher() {
        var typeEl = document.getElementById('cf-type');
        if (!typeEl) return;

        function sync() {
            var val = typeEl.value;
            var isSol = val === 'solidarity';
            document.getElementById('cf-normal-cust').style.display = isSol ? 'none' : '';
            document.getElementById('cf-sol-cust').style.display = isSol ? '' : 'none';

            if (val === 'direct_deduction') {
                applyDirectDeduction();
            } else {
                clearDirectDeduction();
            }
        }

        typeEl.addEventListener('change', sync);
        sync();
    }

    function applyDirectDeduction() {
        var saleEl = document.getElementById('contracts-date_of_sale');
        var fdEl = document.getElementById('cf-fd');
        var legalEl = document.getElementById('cf-is-legal');

        if (saleEl && saleEl.value && fdEl) {
            var saleDate = new Date(saleEl.value);
            saleDate.setDate(saleDate.getDate() + 1);
            var y = saleDate.getFullYear();
            var m = String(saleDate.getMonth() + 1).padStart(2, '0');
            var d = String(saleDate.getDate()).padStart(2, '0');
            fdEl.value = y + '-' + m + '-' + d;
            fdEl.dispatchEvent(new Event('change'));
        }

        if (legalEl) {
            legalEl.value = '1';
        }

        var mvEl = document.getElementById('cf-mv');
        if (mvEl && !mvEl.value) {
            mvEl.value = '20';
            mvEl.dispatchEvent(new Event('input'));
        }
    }

    function clearDirectDeduction() {
        var legalEl = document.getElementById('cf-is-legal');
        if (legalEl && legalEl.value === '1') {
            legalEl.value = '0';
        }
    }


    /* ══════════════════════════════════════════════════
       Scanner — serial number barcode scanning
       ══════════════════════════════════════════════════ */
    function initScanner() {
        var inp = document.getElementById('cf-serial-in');
        var btn = document.getElementById('cf-scan-btn');
        var msgEl = document.getElementById('cf-scan-msg');
        if (!inp || !btn) return;

        function scan() {
            var s = inp.value.trim();
            if (!s) return;
            if (document.querySelector('.cf-sh[data-sn="' + CSS.escape(s) + '"]')) {
                showMsg('\u0645\u0636\u0627\u0641 \u0645\u0633\u0628\u0642\u0627\u064B', false);
                inp.select();
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

            ajax(_cfg.lookupSerialUrl, { serial: s }, function (r) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-bolt"></i> \u0645\u0633\u062D';
                if (r && r.success) {
                    var d = r.data;
                    if (_devices[d.id]) {
                        showMsg('\u0627\u0644\u062C\u0647\u0627\u0632 \u0645\u0636\u0627\u0641 \u0645\u0633\u0628\u0642\u0627\u064B', false);
                    } else {
                        _devices[d.id] = 1;
                        addDeviceRow(d.id, d.item_name, d.serial_number, 'serial');
                        addHidden(d.id, d.serial_number);
                        showMsg(d.item_name + ' \u2014 \u062A\u0645\u062A \u0627\u0644\u0625\u0636\u0627\u0641\u0629', true);
                        syncDevUI();
                    }
                    inp.value = '';
                    inp.focus();
                } else {
                    showMsg(r ? r.message : '\u062E\u0637\u0623 \u0641\u064A \u0627\u0644\u0627\u062A\u0635\u0627\u0644', false);
                    inp.select();
                }
            });
        }

        inp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); scan(); }
        });
        btn.addEventListener('click', scan);

        function showMsg(text, ok) {
            msgEl.className = 'cf-scan-msg ' + (ok ? 'ok' : 'err');
            msgEl.innerHTML = '<i class="fa ' + (ok ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i> ' + text;
            clearTimeout(msgEl._t);
            msgEl._t = setTimeout(function () { msgEl.className = 'cf-scan-msg'; msgEl.innerHTML = ''; }, 4000);
        }
    }

    function addDeviceRow(id, name, serial, type) {
        _devNum++;
        var badge = type === 'serial'
            ? '<span class="cf-dev-badge serial"><i class="fa fa-barcode"></i> \u0633\u064A\u0631\u064A\u0627\u0644</span>'
            : '<span class="cf-dev-badge manual"><i class="fa fa-cube"></i> \u064A\u062F\u0648\u064A</span>';
        var sn = type === 'serial'
            ? '<span class="cf-td-serial">' + esc(serial) + '</span>'
            : '<span style="color:var(--cf-text3)">\u2014</span>';
        var tr = document.createElement('tr');
        tr.id = 'cf-row-' + id;
        tr.setAttribute('data-dev-id', id);
        tr.innerHTML =
            '<td class="cf-td-num">' + _devNum + '</td>' +
            '<td>' + esc(name) + '</td>' +
            '<td>' + sn + '</td>' +
            '<td>' + badge + '</td>' +
            '<td class="cf-td-act"><button type="button" class="cf-dev-rm" data-rid="' + id + '"><i class="fa fa-times"></i></button></td>';
        document.getElementById('cf-dev-body').appendChild(tr);

        tr.querySelector('.cf-dev-rm').addEventListener('click', function () {
            removeDevice(this.getAttribute('data-rid'));
        });
    }

    function addHidden(id, sn) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = 'serial_ids[]'; h.value = id;
        h.className = 'cf-sh';
        h.setAttribute('data-sid', id);
        h.setAttribute('data-sn', sn);
        document.getElementById('cf-dev-body').parentElement.appendChild(h);
    }

    function removeDevice(id) {
        delete _devices[id];
        var r = document.getElementById('cf-row-' + id);
        if (r) r.remove();
        document.querySelectorAll('.cf-sh[data-sid="' + id + '"]').forEach(function (h) { h.remove(); });
        _devNum = 0;
        document.querySelectorAll('#cf-dev-body tr').forEach(function (tr) {
            tr.querySelector('.cf-td-num').textContent = ++_devNum;
        });
        syncDevUI();
    }

    function syncDevUI() {
        var c = document.querySelectorAll('#cf-dev-body tr').length;
        document.getElementById('cf-dev-table').style.display = c ? '' : 'none';
        document.getElementById('cf-dev-empty').style.display = c ? 'none' : '';
        var badge = document.getElementById('cf-dev-count');
        if (badge) {
            if (c) { badge.style.display = ''; badge.textContent = c; } else { badge.style.display = 'none'; }
        }
        var sumDevs = document.getElementById('cf-sum-devs');
        if (sumDevs) sumDevs.innerHTML = '<i class="fa fa-mobile"></i> \u0627\u0644\u0623\u062C\u0647\u0632\u0629: <b>' + c + '</b>';
    }

    function initManualAdd() {
        var link = document.getElementById('cf-manual-link');
        var box = document.getElementById('cf-manual-box');
        var addBtn = document.getElementById('cf-manual-add');
        if (!link || !box || !addBtn) return;

        link.addEventListener('click', function () { box.classList.toggle('open'); });

        addBtn.addEventListener('click', function () {
            var sel = document.getElementById('cf-manual-sel');
            var v = sel.value, n = sel.options[sel.selectedIndex].text;
            if (!v) return;
            var uid = 'm' + Date.now();
            addDeviceRow(uid, n, '', 'manual');
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = 'manual_item_ids[]'; h.value = v;
            h.className = 'cf-sh'; h.setAttribute('data-sid', uid);
            document.getElementById('cf-dev-body').parentElement.appendChild(h);
            syncDevUI();
            sel.value = '';
        });
    }

    /* ══════════════════════════════════════════════════
       Calculator — installments + summary
       ══════════════════════════════════════════════════ */
    var MAX_VISIBLE_ROWS = 120;

    function initCalculator() {
        var tvEl = document.getElementById('cf-tv');
        var fvEl = document.getElementById('cf-fv');
        var mvEl = document.getElementById('cf-mv');
        var fdEl = document.getElementById('cf-fd');
        if (!tvEl || !fvEl || !mvEl || !fdEl) return;

        var _cachedEls = {
            total:     document.getElementById('cf-ns-total'),
            first:     document.getElementById('cf-ns-first'),
            remaining: document.getElementById('cf-ns-remaining'),
            monthly:   document.getElementById('cf-ns-monthly'),
            count:     document.getElementById('cf-ns-count'),
            date:      document.getElementById('cf-ns-date'),
            sec:       document.getElementById('cf-sec-schedule'),
            tbody:     document.getElementById('cf-inst-body'),
        };

        function calc() {
            var tv = parseFloat(tvEl.value) || 0;
            var fv = parseFloat(fvEl.value) || 0;
            var mv = parseFloat(mvEl.value) || 0;
            var fd = fdEl.value;
            var remaining = tv > fv ? tv - fv : tv;
            var count = mv > 0 ? Math.ceil(remaining / mv) : 0;

            setEl(_cachedEls.total, tv ? tv + ' \u062F.\u0623' : '0 \u062F.\u0623');
            setEl(_cachedEls.first, fv ? fv + ' \u062F.\u0623' : '0 \u062F.\u0623');
            setEl(_cachedEls.remaining, remaining ? remaining + ' \u062F.\u0623' : '0 \u062F.\u0623');
            setEl(_cachedEls.monthly, mv ? mv + ' \u062F.\u0623' : '0 \u062F.\u0623');
            setEl(_cachedEls.count, count || '\u2014');
            setEl(_cachedEls.date, fd || '\u2014');

            var sec = _cachedEls.sec;
            var tbody = _cachedEls.tbody;
            if (!sec || !tbody) return;

            if (tv > 0 && mv > 0 && fd && count > 0) {
                var sd = new Date(fd);
                var visible = Math.min(count, MAX_VISIBLE_ROWS);
                var frag = document.createDocumentFragment();

                for (var i = 0; i < visible; i++) {
                    var d = new Date(sd.getFullYear(), sd.getMonth() + i, sd.getDate());
                    var amt = (i === count - 1) ? (remaining - mv * (count - 1)) : mv;
                    if (amt <= 0) amt = mv;
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (i + 1) + '</td><td><b>' + amt + '</b> \u062F.\u0623</td><td>' + (d.getMonth() + 1) + '</td><td>' + d.getFullYear() + '</td>';
                    frag.appendChild(tr);
                }

                if (count > MAX_VISIBLE_ROWS) {
                    var infoTr = document.createElement('tr');
                    infoTr.className = 'cf-inst-more';
                    infoTr.innerHTML = '<td colspan="4">\u2026 \u0648' + (count - MAX_VISIBLE_ROWS) + ' \u0642\u0633\u0637 \u0622\u062E\u0631 (\u0625\u062C\u0645\u0627\u0644\u064A ' + count + ' \u0642\u0633\u0637)</td>';
                    frag.appendChild(infoTr);
                }

                tbody.textContent = '';
                tbody.appendChild(frag);
                sec.style.display = '';
            } else {
                tbody.textContent = '';
                sec.style.display = 'none';
            }
        }

        function setEl(el, val) {
            if (el) el.textContent = val;
        }

        var debouncedCalc = debounce(calc, 250);

        [tvEl, fvEl, mvEl, fdEl].forEach(function (el) {
            el.addEventListener('input', debouncedCalc);
            el.addEventListener('change', calc);
        });

        calc();
    }

    /* ══════════════════════════════════════════════════
       Customer Info Bar
       ══════════════════════════════════════════════════ */
    function autoFillFirstInstallmentDate() {
        var typeEl = document.getElementById('cf-type');
        var type = typeEl ? typeEl.value : 'normal';
        if (type === 'direct_deduction') return;

        var saleEl = document.getElementById('contracts-date_of_sale');
        var fdEl = document.getElementById('cf-fd');
        if (!saleEl || !saleEl.value || !fdEl) return;

        var saleDate = new Date(saleEl.value);
        var day = saleDate.getDate();
        var dueDay = _customerIsGov ? 23 : 28;

        var targetMonth, targetYear;
        if (day <= 15) {
            targetMonth = saleDate.getMonth();
            targetYear = saleDate.getFullYear();
        } else {
            var next = new Date(saleDate.getFullYear(), saleDate.getMonth() + 1, 1);
            targetMonth = next.getMonth();
            targetYear = next.getFullYear();
        }

        var m = String(targetMonth + 1).padStart(2, '0');
        var d = String(dueDay).padStart(2, '0');
        fdEl.value = targetYear + '-' + m + '-' + d;
        fdEl.dispatchEvent(new Event('change'));
    }

    function loadCustomerInfo(entry) {
        var bar = document.getElementById('cf-cust-bar');
        if (!bar || !entry) {
            if (bar) bar.classList.remove('active');
            _customerIsGov = false;
            return;
        }

        ajax(_cfg.customerDataUrl, { id: entry.id }, function (r) {
            if (r && r.model) {
                document.getElementById('cf-nc-dbid').textContent = '#' + (r.model.id || entry.id);
                document.getElementById('cf-nc-name').textContent = r.model.name || '\u2014';
                document.getElementById('cf-nc-id').textContent = r.model.id_number || '\u2014';
                document.getElementById('cf-nc-birth').textContent = r.model.birth_date || '\u2014';
                document.getElementById('cf-nc-job').textContent = r.job_name || '\u2014';
                document.getElementById('cf-nc-cnt').textContent = r.contracts_info ? r.contracts_info.count : '0';
                bar.classList.add('active');

                var jtName = (r.job_type_name || '').toLowerCase();
                _customerIsGov = jtName.indexOf('\u062D\u0643\u0648\u0645') !== -1
                              || jtName.indexOf('\u0642\u0637\u0627\u0639 \u0639\u0627\u0645') !== -1
                              || jtName.indexOf('\u0639\u0633\u0643\u0631') !== -1
                              || jtName.indexOf('\u0623\u0645\u0646') !== -1;

                autoFillFirstInstallmentDate();
            }
        });
    }

    /* ══════════════════════════════════════════════════
       Nav — smooth section scrolling
       ══════════════════════════════════════════════════ */
    function initNav() {
        document.querySelectorAll('.cf-nav-pill').forEach(function (p) {
            p.addEventListener('click', function (e) {
                e.preventDefault();
                var t = document.querySelector(this.getAttribute('href'));
                if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.querySelectorAll('.cf-nav-pill').forEach(function (x) { x.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    }

    /* ══════════════════════════════════════════════════
       Init — main entry point
       ══════════════════════════════════════════════════ */
    /* ══════════════════════════════════════════════════
       Validation Toast — mobile-friendly error feedback
       ══════════════════════════════════════════════════ */
    function showToast(msg, type) {
        var old = document.getElementById('cf-toast');
        if (old) old.remove();

        var t = document.createElement('div');
        t.id = 'cf-toast';
        t.className = 'cf-toast ' + (type === 'ok' ? 'cf-toast-ok' : 'cf-toast-err');
        t.innerHTML = '<i class="fa ' + (type === 'ok' ? 'fa-check-circle' : 'fa-exclamation-triangle') + '"></i> <span>' + msg + '</span>';

        var cf = document.querySelector('.cf');
        if (cf) cf.appendChild(t);

        setTimeout(function () {
            t.classList.add('cf-toast-hide');
            setTimeout(function () { t.remove(); }, 400);
        }, 6000);
    }

    function shakeElement(el) {
        if (!el) return;
        el.classList.add('cf-shake');
        setTimeout(function () { el.classList.remove('cf-shake'); }, 700);
    }

    /* ══════════════════════════════════════════════════
       Form submission handlers — AJAX with timeout
       ══════════════════════════════════════════════════ */
    var SUBMIT_TIMEOUT_MS = 30000;
    var _submitting = false;

    function setButtonsLoading($form, loading) {
        $form.find('button[type=submit]').each(function () {
            var $btn = $(this);
            if (loading) {
                $btn.prop('disabled', true);
                $btn.data('orig', $btn.html());
                $btn.html('<i class="fa fa-spinner fa-spin"></i> \u062C\u0627\u0631\u064A \u0627\u0644\u062D\u0641\u0638...');
            } else {
                $btn.prop('disabled', false);
                if ($btn.data('orig')) $btn.html($btn.data('orig'));
            }
        });
    }

    function initFormHandlers() {
        var $form = $('#contract-form');
        if (!$form.length) return;

        $form.on('afterValidate', function (e, messages, errorAttributes) {
            if (errorAttributes.length > 0) {
                var labels = [];
                for (var i = 0; i < errorAttributes.length; i++) {
                    var lbl = $form.find('[for="' + errorAttributes[i].input + '"]').text()
                           || errorAttributes[i].input;
                    labels.push(lbl.replace(/\s*\*\s*$/, ''));
                }
                showToast('\u064A\u062C\u0628 \u062A\u0639\u0628\u0626\u0629: ' + labels.join('\u060C '), 'err');

                var firstErr = $form.find('.has-error').first();
                if (firstErr.length) {
                    firstErr[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    shakeElement(firstErr[0]);
                }
            }
        });

        $form.on('beforeSubmit', function (e) {
            e.preventDefault();
            if (_submitting) return false;

            var typeEl = document.getElementById('cf-type');
            var type = typeEl ? typeEl.value : 'normal';
            var hasCustomer = false;

            if (type === 'solidarity') {
                hasCustomer = document.querySelectorAll('#cf-sol-chips .cf-chip').length > 0;
            } else {
                hasCustomer = document.querySelectorAll('#cf-cust-chips .cf-chip').length > 0;
            }

            if (!hasCustomer) {
                showToast('\u064A\u062C\u0628 \u0627\u062E\u062A\u064A\u0627\u0631 \u0627\u0644\u0639\u0645\u064A\u0644 \u0623\u0648\u0644\u0627\u064B', 'err');
                var sec = document.getElementById('cf-sec-customer');
                if (sec) {
                    sec.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    shakeElement(sec);
                }
                return false;
            }

            var tv = parseFloat((document.getElementById('cf-tv') || {}).value) || 0;
            if (tv <= 0) {
                showToast('\u064A\u062C\u0628 \u062A\u062D\u062F\u064A\u062F \u0625\u062C\u0645\u0627\u0644\u064A \u0627\u0644\u0639\u0642\u062F', 'err');
                var fin = document.getElementById('cf-sec-finance');
                if (fin) {
                    fin.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    shakeElement(fin);
                }
                return false;
            }

            _submitting = true;
            setButtonsLoading($form, true);

            var formData = new FormData($form[0]);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', $form.attr('action') || window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.timeout = SUBMIT_TIMEOUT_MS;

            xhr.onload = function () {
                _submitting = false;
                if (xhr.status === 200) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            showToast(res.message || '\u062A\u0645 \u0627\u0644\u062D\u0641\u0638 \u0628\u0646\u062C\u0627\u062D', 'ok');
                            setTimeout(function () {
                                window.location.href = res.redirect;
                            }, 300);
                            return;
                        }
                        setButtonsLoading($form, false);
                        showToast(res.message || '\u062D\u062F\u062B \u062E\u0637\u0623 \u0623\u062B\u0646\u0627\u0621 \u0627\u0644\u062D\u0641\u0638', 'err');
                    } catch (e) {
                        setButtonsLoading($form, false);
                        showToast('\u062D\u062F\u062B \u062E\u0637\u0623 \u063A\u064A\u0631 \u0645\u062A\u0648\u0642\u0639', 'err');
                    }
                } else {
                    setButtonsLoading($form, false);
                    showToast('\u062E\u0637\u0623 \u0641\u064A \u0627\u0644\u0627\u062A\u0635\u0627\u0644 \u0628\u0627\u0644\u0633\u064A\u0631\u0641\u0631 (' + xhr.status + ')', 'err');
                }
            };

            xhr.onerror = function () {
                _submitting = false;
                setButtonsLoading($form, false);
                showToast('\u0641\u0634\u0644 \u0627\u0644\u0627\u062A\u0635\u0627\u0644 \u0628\u0627\u0644\u0633\u064A\u0631\u0641\u0631', 'err');
            };

            xhr.ontimeout = function () {
                _submitting = false;
                setButtonsLoading($form, false);
                showToast('\u0627\u0646\u062A\u0647\u062A \u0645\u0647\u0644\u0629 \u0627\u0644\u0627\u0646\u062A\u0638\u0627\u0631\u060C \u062D\u0627\u0648\u0644 \u0645\u0631\u0629 \u0623\u062E\u0631\u0649', 'err');
            };

            xhr.send(formData);
            return false;
        });
    }

    function init(cfg) {
        _cfg = cfg;

        initNav();
        initTypeSwitcher();

        var custSearch = new CustomerSearch({
            inputId: 'cf-cust-search',
            dropdownId: 'cf-cust-results',
            chipsId: 'cf-cust-chips',
            multiple: false,
            inputName: 'Contracts[customer_id]',
            initial: cfg.existingCustomers && cfg.type !== 'solidarity' ? cfg.existingCustomers : [],
            onSelect: loadCustomerInfo,
        });

        new CustomerSearch({
            inputId: 'cf-sol-search',
            dropdownId: 'cf-sol-results',
            chipsId: 'cf-sol-chips',
            multiple: true,
            inputName: 'Contracts[customers_ids][]',
            initial: cfg.existingCustomers && cfg.type === 'solidarity' ? cfg.existingCustomers : [],
        });

        new CustomerSearch({
            inputId: 'cf-guar-search',
            dropdownId: 'cf-guar-results',
            chipsId: 'cf-guar-chips',
            multiple: true,
            inputName: 'Contracts[guarantors_ids][]',
            initial: cfg.existingGuarantors || [],
        });

        if (cfg.preloadedSerials && cfg.preloadedSerials.length) {
            for (var i = 0; i < cfg.preloadedSerials.length; i++) {
                var s = cfg.preloadedSerials[i];
                _devices[s.id] = 1;
                addDeviceRow(s.id, s.item_name, s.serial_number, 'serial');
            }
            syncDevUI();
        }

        initScanner();
        initManualAdd();
        initCalculator();
        initDirectDeductionListeners();
        initFormHandlers();

        if (cfg.existingCustomers && cfg.existingCustomers.length === 1 && cfg.type !== 'solidarity') {
            loadCustomerInfo(cfg.existingCustomers[0]);
        }

        if (cfg.type !== 'direct_deduction') {
            autoFillFirstInstallmentDate();
        }
    }

    function initDirectDeductionListeners() {
        var saleEl = document.getElementById('contracts-date_of_sale');
        var tvEl = document.getElementById('cf-tv');
        var typeEl = document.getElementById('cf-type');
        if (!typeEl) return;

        function reapply() {
            if (typeEl.value === 'direct_deduction') {
                applyDirectDeduction();
            } else {
                autoFillFirstInstallmentDate();
            }
        }

        if (saleEl) saleEl.addEventListener('change', reapply);
    }

    return { init: init };
})();
