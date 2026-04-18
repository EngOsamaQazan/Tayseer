/* eslint-disable */
/**
 * Customer Wizard V2 — Smart Income (Social Security) statement scan.
 *
 * Companion to scan.js, but for Step 2's "كشف البيانات التفصيلي" upload.
 * Reuses the same DOM contract / CSRF / toast / field-fill conventions so
 * the two flows are consistent for users.
 *
 * Markup contract (rendered by _step_2_employment.php):
 *   <div data-cw-scan-income>
 *     <button data-cw-action="pick-income-doc">…</button>
 *     <input  data-cw-role="income-input" type="file"
 *             accept="application/pdf,image/*">
 *     <div data-cw-role="income-status"   hidden></div>
 *     <div data-cw-role="income-summary"  hidden>
 *       <div data-cw-role="income-summary-grid"></div>
 *       <details><summary>…</summary>
 *         <div data-cw-role="income-summary-tables"></div>
 *       </details>
 *     </div>
 *   </div>
 *
 * Server contract (POST /customers/wizard/scan-income, multipart):
 *   { ok, fields:{ 'Customers[…]':val }, unmapped:{ field:text },
 *     summary:{ social_security_number, latest_monthly_salary,
 *               current_employer, statement_date, salary_history,
 *               subscription_periods, … },
 *     image_id, image_url,
 *     meta:{ source, elapsed_ms } }
 *
 * Accessibility:
 *   • The hidden file input is .cw-sr-only + tabindex=-1.
 *   • Status announcements ride on a polite live region (aria-live=polite).
 *   • Filled fields get a brief .cw-field--auto-filled highlight.
 */
(function ($, window) {
    'use strict';
    if (!$) return;

    var NS = '.cwScanIncome';

    // ── Endpoint discovery ─────────────────────────────────────────────
    function scanUrl() {
        try {
            if (window.CW && window.CW._urls && window.CW._urls.scanIncome) {
                return window.CW._urls.scanIncome;
            }
        } catch (e) { /* noop */ }
        return '/customers/wizard/scan-income';
    }

    // ── Tiny helpers ───────────────────────────────────────────────────
    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }
    function cssEscape(s) {
        return String(s).replace(/(["\\\[\]])/g, '\\$1');
    }
    function toast(msg, type, ttl) {
        if (window.CW && typeof window.CW.toast === 'function') {
            window.CW.toast(msg, type || 'info', ttl);
        }
    }
    function fmtNum(n) {
        if (n === null || n === undefined || n === '') return '—';
        var v = parseFloat(n);
        if (!isFinite(v)) return String(n);
        // Integer rendering when it really is an integer (months, year).
        if (Math.abs(v - Math.round(v)) < 0.001) return String(Math.round(v));
        return v.toFixed(2);
    }
    function fmtDate(s) {
        if (!s) return '—';
        if (typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s)) {
            return s.split('-').reverse().join('/');
        }
        return String(s);
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Apply the field map to the form (same shape used by scan.js).
     * Returns the count of fields actually changed for the toast.
     */
    function applyFields(fieldMap) {
        if (!fieldMap || typeof fieldMap !== 'object') return 0;
        var changed = 0;

        Object.keys(fieldMap).forEach(function (name) {
            var value = fieldMap[name];
            if (value === null || value === undefined) return;

            var $all = $('[name="' + cssEscape(name) + '"]');
            if (!$all.length) return;

            var next  = String(value).trim();
            var first = $all.first();
            var type  = (first.attr('type') || '').toLowerCase();
            var didChange = false;

            if (type === 'radio' || type === 'checkbox') {
                var $target = $all.filter('[value="' + cssEscape(next) + '"]').first();
                if (!$target.length) return;
                if ($target.is(':checked')) return;
                if (type === 'radio') $all.prop('checked', false);
                $target.prop('checked', true).trigger('change');
                didChange = true;
                first = $target;
            } else {
                var current = String(first.val() || '').trim();
                if (current === next) return;
                first.val(next).trigger('change').trigger('input');
                didChange = true;
            }

            if (!didChange) return;

            var $wrap = first.closest('[data-cw-field]');
            if ($wrap.length) {
                $wrap.addClass('cw-field--auto-filled');
                window.setTimeout((function ($w) {
                    return function () { $w.removeClass('cw-field--auto-filled'); };
                })($wrap), 3000);
            }
            changed++;
        });
        return changed;
    }

    // ── Status pill ────────────────────────────────────────────────────
    function setStatus($host, kind, message) {
        var $s = $host.find('[data-cw-role="income-status"]');
        if (!$s.length) return;
        if (!kind) { $s.attr('hidden', '').empty(); return; }

        var icon = ({
            uploading: 'fa-spinner fa-spin',
            success:   'fa-check-circle',
            error:     'fa-exclamation-triangle',
            info:      'fa-info-circle'
        })[kind] || 'fa-info-circle';

        $s.removeAttr('hidden')
          .removeClass('cw-scan-doc__status--uploading cw-scan-doc__status--success cw-scan-doc__status--error cw-scan-doc__status--info')
          .addClass('cw-scan-doc__status--' + kind)
          .html('<i class="fa ' + icon + '" aria-hidden="true"></i> <span>' + escapeHtml(message) + '</span>');
    }

    // ── Summary renderer ───────────────────────────────────────────────
    function renderSummary($host, summary) {
        var $sum = $host.find('[data-cw-role="income-summary"]');
        var $grid = $host.find('[data-cw-role="income-summary-grid"]');
        var $tables = $host.find('[data-cw-role="income-summary-tables"]');
        if (!$sum.length || !summary) return;

        // ── Key/value grid — only for fields that have a value. ──
        var rows = [
            { label: 'الاسم',                 value: summary.name },
            { label: 'رقم التأمين',           value: summary.social_security_number,
              dir: 'ltr' },
            { label: 'الرقم الوطني',          value: summary.id_number,
              dir: 'ltr' },
            { label: 'تاريخ الكشف',           value: fmtDate(summary.statement_date) },
            { label: 'تاريخ الالتحاق بالضمان', value: fmtDate(summary.join_date) },
            { label: 'حالة الاشتراك',
              value: summary.active_subscription === true ? 'نشط حالياً'
                   : summary.active_subscription === false ? 'متوقف' : null,
              tone: summary.active_subscription === true ? 'success' : 'warn' },
            { label: 'مجموع شهور الاشتراك',   value: summary.total_subscription_months
                  ? fmtNum(summary.total_subscription_months) + ' شهراً' : null },
            { label: 'جهة العمل الحالية',     value: summary.current_employer },
            { label: 'منشأة الخضوع',          value: summary.subjection_employer },
            // Smart-picked salary — what actually populates total_salary.
            // Server's `selected_salary*` fields cross both tables (subscription
            // periods + salary history) and pick the most recent evidence; we
            // surface the SAME value here so "آخر راتب شهري" never disagrees
            // with the input below. Falls back to latest_monthly_salary on
            // older payloads (resumed drafts that predate the smart picker).
            { label: 'آخر راتب شهري',
              value: (function () {
                  var sal = (summary.selected_salary != null && summary.selected_salary > 0)
                          ? summary.selected_salary
                          : summary.latest_monthly_salary;
                  if (!sal) return null;
                  // Prefer the full as-of date when we have it (e.g.
                  // "2026-02-01") so the user can see the period the
                  // wage came from; fall back to the year-only label
                  // for the legacy salary_history-only case.
                  var asOf = summary.selected_salary_date
                          || (summary.latest_salary_year ? String(summary.latest_salary_year) : '');
                  return fmtNum(sal) + ' د.أ'
                       + (asOf ? ' (' + asOf + ')' : '')
                       + (summary.selected_salary_active ? ' — نشط' : '');
              })(),
              highlight: true },
            { label: 'راتب الخضوع',
              value: summary.subjection_salary ? (fmtNum(summary.subjection_salary) + ' د.أ') : null }
        ].filter(function (r) { return r.value && r.value !== '—'; });

        $grid.html(rows.map(function (r) {
            var cls = 'cw-scan-doc__cell'
                    + (r.highlight ? ' cw-scan-doc__cell--highlight' : '')
                    + (r.tone ? ' cw-scan-doc__cell--' + r.tone : '');
            var dir = r.dir ? ' dir="' + r.dir + '"' : '';
            return '<div class="' + cls + '">'
                 +   '<dt>' + escapeHtml(r.label) + '</dt>'
                 +   '<dd' + dir + '>' + escapeHtml(r.value) + '</dd>'
                 + '</div>';
        }).join(''));

        // ── Tables (subscription periods + salary history). ──
        var html = '';

        if (summary.subscription_periods && summary.subscription_periods.length) {
            html += '<h6 class="cw-scan-doc__tbl-title"><i class="fa fa-history"></i> فترات الاشتراك</h6>';
            html += '<div class="cw-scan-doc__tbl-wrap"><table class="cw-scan-doc__tbl">'
                  + '<thead><tr>'
                  + '<th>تاريخ السريان</th>'
                  + '<th>تاريخ الإيقاف</th>'
                  + '<th>الراتب</th>'
                  + '<th>سبب الإيقاف</th>'
                  + '<th>الأشهر</th>'
                  + '<th>المنشأة</th>'
                  + '</tr></thead><tbody>';
            summary.subscription_periods.forEach(function (p) {
                var isActive = !p.to;
                html += '<tr' + (isActive ? ' class="cw-scan-doc__row--active"' : '') + '>'
                     + '<td dir="ltr">' + escapeHtml(fmtDate(p.from)) + '</td>'
                     + '<td dir="ltr">' + (p.to ? escapeHtml(fmtDate(p.to))
                                                : '<span class="cw-scan-doc__chip cw-scan-doc__chip--success">نشط</span>') + '</td>'
                     + '<td dir="ltr">' + escapeHtml(fmtNum(p.salary)) + '</td>'
                     + '<td>' + escapeHtml(p.reason || '—') + '</td>'
                     + '<td dir="ltr">' + escapeHtml(p.months || '—') + '</td>'
                     + '<td>' + escapeHtml(p.name || '—') + '</td>'
                     + '</tr>';
            });
            html += '</tbody></table></div>';
        }

        if (summary.salary_history && summary.salary_history.length) {
            html += '<h6 class="cw-scan-doc__tbl-title"><i class="fa fa-money"></i> الرواتب المالية</h6>';
            html += '<div class="cw-scan-doc__tbl-wrap"><table class="cw-scan-doc__tbl">'
                  + '<thead><tr>'
                  + '<th>السنة</th><th>الأجر</th><th>المنشأة</th>'
                  + '</tr></thead><tbody>';
            summary.salary_history.forEach(function (r, i) {
                html += '<tr' + (i === 0 ? ' class="cw-scan-doc__row--latest"' : '') + '>'
                     + '<td dir="ltr">' + escapeHtml(r.year || '—') + '</td>'
                     + '<td dir="ltr">' + escapeHtml(fmtNum(r.salary)) + '</td>'
                     + '<td>' + escapeHtml(r.name || '—') + '</td>'
                     + '</tr>';
            });
            html += '</tbody></table></div>';
        }

        $tables.html(html);
        $sum.removeAttr('hidden');
    }

    // ── Network ────────────────────────────────────────────────────────
    function uploadKashf(file) {
        var fd = new FormData();
        fd.append('file', file, file.name);
        fd.append('_csrf-backend', csrfToken());

        return $.ajax({
            url:         scanUrl(),
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            cache:       false,
            timeout:     90000, // Gemini PDF + multi-page can take a while
            dataType:    'json'
        });
    }

    // ── Main click handler ─────────────────────────────────────────────
    function handleFileChange($host, $btn, fileInput) {
        if (!fileInput.files || !fileInput.files.length) return;
        var file = fileInput.files[0];

        var maxBytes = 10 * 1024 * 1024;
        if (file.size > maxBytes) {
            setStatus($host, 'error', 'حجم الملف أكبر من 10 ميجابايت — اختر ملفاً أصغر.');
            toast('حجم الملف كبير جداً', 'error', 5000);
            fileInput.value = '';
            return;
        }

        var origHtml = $btn.html();
        $btn.prop('disabled', true).attr('aria-busy', 'true')
            .html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> <span>جارٍ التحليل…</span>');
        setStatus($host, 'uploading', 'يتم رفع الملف وقراءة الجداول… قد تستغرق العملية حتى دقيقة.');

        uploadKashf(file)
            .done(function (resp) {
                if (!resp || resp.ok !== true) {
                    var err = (resp && resp.error) ? resp.error : 'تعذّر تحليل الملف.';
                    setStatus($host, 'error', err);
                    toast(err, 'error', 6000);
                    return;
                }

                // 1. Auto-fill matched fields.
                var changed = applyFields(resp.fields || {});

                // 2. Render summary cards + tables.
                renderSummary($host, resp.summary || {});

                // 3. Status + toast.
                var msg = changed > 0
                    ? 'تمت قراءة الكشف وتعبئة ' + changed + ' حقلاً تلقائياً.'
                    : 'تمت قراءة الكشف، لكن جميع الحقول معبّأة مسبقاً.';
                setStatus($host, 'success', msg);
                toast(msg, 'success', 5000);

                // 4. Handle the employer field — either the server already
                //    mapped it to an existing id (combobox shows it now via
                //    the change-sync path) or we need to surface the raw
                //    text in the combobox so the user confirms-then-adds.
                handleEmployerHint(resp.unmapped || {}, resp.fields || {});
            })
            .fail(function (xhr) {
                var msg = 'تعذّر الاتصال بالخادم أثناء تحليل الكشف.';
                if (xhr && xhr.status === 413) {
                    msg = 'حجم الملف أكبر مما يقبله الخادم — جرّب صورة بدقة أقل أو PDF مُصغَّر.';
                } else if (xhr && xhr.statusText && xhr.statusText !== 'error') {
                    msg = 'خطأ شبكة: ' + xhr.statusText;
                }
                setStatus($host, 'error', msg);
                toast(msg, 'error', 7000);
            })
            .always(function () {
                $btn.prop('disabled', false).removeAttr('aria-busy').html(origHtml);
                fileInput.value = '';
            });
    }

    /**
     * Handle the employer hint coming back from the scan endpoint.
     *
     *   • If `Customers[job_title]` was already auto-set by applyFields()
     *     (server found an exact match in the lookup), nothing else to do —
     *     CWCombo's change-sync already mirrored the label into the input.
     *
     *   • Otherwise, push the raw extracted text into the job combobox via
     *     CWCombo.prefillSearch(). That opens the dropdown with the
     *     "إضافة «X» كجهة عمل جديدة" CTA highlighted at position 0, mirroring
     *     the city/nationality "instant add" UX the user already knows. We
     *     deliberately STOP making a silent add-job AJAX call — silent
     *     writes felt magical when they worked but invisible when they
     *     failed (e.g. the legacy add-job endpoint used to drop the
     *     required job_type column on insert).
     */
    function handleEmployerHint(unmapped, fields) {
        var rawText = (unmapped && (unmapped.job_title_text || unmapped.job_title)) || '';
        var matchedId = fields ? fields['Customers[job_title]'] : null;
        var $jobSelect = $('select[data-cw-combo="job"]').first();
        if (!$jobSelect.length) return;

        // Server matched — flash the field and let the change-sync handle the
        // visible label. Trigger meta-refresh so the alert renders.
        if (matchedId) {
            var $wrap = $jobSelect.closest('[data-cw-field]');
            if ($wrap.length) {
                $wrap.addClass('cw-field--auto-filled');
                window.setTimeout(function () { $wrap.removeClass('cw-field--auto-filled'); }, 3000);
            }
            return;
        }

        if (!rawText) return;

        // No match — bridge the text into the combobox so the user can
        // confirm-then-add with a single click on the highlighted CTA.
        if (window.CWCombo && typeof window.CWCombo.prefillSearch === 'function') {
            window.CWCombo.prefillSearch($jobSelect, rawText);
            toast('تمت قراءة جهة العمل: «' + rawText + '». اضغط «إضافة» في القائمة لتسجيلها.', 'info', 7000);
        }
    }

    // ── Bootstrap (delegated — works for SSR sections that load later) ─
    function bind($root) {
        var $hosts = ($root || $(document)).find('[data-cw-scan-income]');
        $hosts.each(function () {
            var $host = $(this);
            if ($host.data('cwIncomeBound')) return;
            $host.data('cwIncomeBound', true);

            var $btn   = $host.find('[data-cw-action="pick-income-doc"]').first();
            var $input = $host.find('[data-cw-role="income-input"]').first();
            if (!$btn.length || !$input.length) return;

            $btn.on('click' + NS, function (e) {
                e.preventDefault();
                $input.trigger('click');
            });
            $input.on('change' + NS, function () {
                handleFileChange($host, $btn, this);
            });
        });
    }

    $(function () {
        bind();
        $(document).on('cw:step:rendered cw:step:changed', function (e, payload) {
            if (payload && payload.$section) bind(payload.$section);
            else bind();
        });
    });

})(window.jQuery, window);
