
$(window).on('load', function () {
    // Removed settlement alert — no longer needed
})
$(document).on('click', '#save', function () {
    // Validate dates before saving
    if (typeof StlForm !== 'undefined' && !StlForm.validateNewDate()) {
        $('.loan-alert').css("display", "block")
            .removeClass('alert-success').addClass('alert-danger')
            .text('يرجى تصحيح تاريخ القسط الجديد قبل الحفظ');
        return;
    }
    var data = {
        contract_id:            $('#contract_id').val(),
        monthly_installment:    $('#monthly_installment').val(),
        first_installment_date: $('#first_installment_date').val(),
        new_installment_date:   $('#new_installment_date').val(),
        settlement_type:        $('#stl_settlement_type').val() || 'monthly',
        total_debt:             $('#stl_total_debt').val(),
        first_payment:          $('#stl_first_payment').val(),
        installments_count:     $('#stl_installments_count').val(),
        remaining_debt:         $('#stl_remaining_debt').val(),
        notes:                  $('#stl_notes').val()
    };
    $.post(typeof OCP_URLS !== 'undefined' ? OCP_URLS.addNewLoan : 'add-new-loan', data, function (msg) {
        $('.loan-alert').css("display", "block").text(msg);
        if (msg.indexOf('بنجاح') > -1) {
            $('.loan-alert').removeClass('alert-danger').addClass('alert-success');
            setTimeout(function(){ location.reload(); }, 1200);
        } else {
            $('.loan-alert').removeClass('alert-success').addClass('alert-danger');
        }
    });
})
$(document).on('click', '#closeModel', function () {
    location.reload(true);
})
/////
$(document).on('change', '.cant_contact', function () {
    let id = $('.cant_contact').attr('contract_id');
    let val1 = $('.cant_contact').val();
    alert(val1);
});
/////
var CiEdit = (function() {
    var originalData = {};
    var dirtyFields = {};
    var _isLoading = false;
    var requiredFields = ['name', 'id_number', 'sex', 'birth_date', 'city', 'job_title', 'primary_phone_number'];

    function _s2text(el) {
        var opt = el.find('option:selected');
        return (opt.length && opt.val() !== '') ? opt.text() : '—';
    }

    function _syncS2(el) {
        var $c = el.next('.select2-container');
        if ($c.length) {
            var t = _s2text(el);
            $c.find('.select2-selection__rendered').text(t).attr('title', t);
        }
    }

    function setVal(cls, val) {
        var el = $('.' + cls);
        if (el.is('select')) {
            el.val(val != null ? String(val) : '');
            _syncS2(el);
        } else {
            el.val(val || '');
        }
    }

    function loadCustomer(customerId) {
        dirtyFields = {};
        _isLoading = true;
        $('#ciSaveBar').removeClass('visible');
        $('#ciFooterSaveBtn').hide();
        $('#ci-customer-id').val(customerId);

        $('#customerInfoModal .ci-input').each(function() {
            var $i = $(this);
            $i.closest('.ci-field').removeClass('ci-editing');
            if ($i.is('select')) {
                $i.val('');
                _syncS2($i);
            }
            $i.prop('disabled', true);
        });

        var a = document.getElementById('cus-link');
        a.setAttribute('href', '../../customers/update/' + customerId);

        $.post(customer_info_url, { customerId: customerId }, function(msg) {
            var info = JSON.parse(msg);
            originalData = $.extend({}, info);

            $('#customerInfoTitle').html('<i class="fa fa-user-circle"></i> ' + (info.name || 'بيانات العميل'));
            setVal('cu-name', info.name);
            setVal('cu-id-number', info.id_number);
            setVal('cu-birth-date', info.birth_date);
            setVal('cu-job-number', info.job_number);
            setVal('cu-email', info.email);
            setVal('cu-account-number', info.account_number);
            setVal('cu-bank-branch', info.bank_branch);
            setVal('cu-sex', info.sex);
            setVal('cu-city', info.city);
            setVal('cu-bank-name', info.bank_name);
            setVal('cu-job-title', info.job_title);
            setVal('cu-notes', info.notes);
            setVal('cu-social-security-number', info.social_security_number);
            setVal('cu-is-social-security', info.is_social_security);
            setVal('cu-do-have-any-property', info.do_have_any_property);
            _isLoading = false;
        });
    }

    function closeField(fieldEl) {
        var $el = $(fieldEl);
        if ($el.prop('disabled')) return;
        markDirty(fieldEl);
        $el.prop('disabled', true);
        if ($el.is('select')) _syncS2($el);
        $el.closest('.ci-field').removeClass('ci-editing');
    }

    function closeAllFields() {
        $('#customerInfoModal .ci-input:not(:disabled)').each(function() {
            closeField(this);
        });
    }

    function toggleField(fieldEl) {
        var $el = $(fieldEl);
        if (!$el.prop('disabled')) {
            closeField(fieldEl);
        } else {
            closeAllFields();
            $el.prop('disabled', false);
            $el.closest('.ci-field').addClass('ci-editing');
            if ($el.data('select2')) {
                $el.select2('open');
            } else {
                $el.focus();
            }
        }
    }

    function markDirty(fieldEl) {
        if (_isLoading) return;
        var $el = $(fieldEl);
        var fieldName = $el.data('field');
        if (!fieldName) return;
        var orig = originalData[fieldName];
        var current = $el.val();

        if (requiredFields.indexOf(fieldName) !== -1 && orig && String(orig).trim() !== '' && (!current || String(current).trim() === '')) {
            $el.closest('.ci-field').css('animation', 'ciShake .4s');
            setTimeout(function() { $el.closest('.ci-field').css('animation', ''); }, 400);
            _isLoading = true;
            if ($el.is('select')) {
                $el.val(orig != null ? String(orig) : '');
                _syncS2($el);
            } else {
                $el.val(orig || '');
            }
            _isLoading = false;
            delete dirtyFields[fieldName];
            return;
        }

        if (String(current || '') !== String(orig || '')) {
            dirtyFields[fieldName] = current;
        } else {
            delete dirtyFields[fieldName];
        }
        var hasDirty = Object.keys(dirtyFields).length > 0;
        $('#ciSaveBar').toggleClass('visible', hasDirty);
        $('#ciFooterSaveBtn').toggle(hasDirty);
    }

    function cancelAll() {
        dirtyFields = {};
        _isLoading = true;
        $('#ciSaveBar').removeClass('visible');
        $('#ciFooterSaveBtn').hide();
        $('#customerInfoModal .ci-input').each(function() {
            var $el = $(this);
            $el.closest('.ci-field').removeClass('ci-editing');
            var fieldName = $el.data('field');
            if (fieldName && originalData[fieldName] !== undefined) {
                if ($el.is('select')) {
                    $el.val(originalData[fieldName] != null ? String(originalData[fieldName]) : '');
                    _syncS2($el);
                } else {
                    $el.val(originalData[fieldName] || '');
                }
            }
            $el.prop('disabled', true);
        });
        _isLoading = false;
    }

    function save() {
        if (Object.keys(dirtyFields).length === 0) return;
        closeAllFields();
        var customerId = $('#ci-customer-id').val();
        var $btn = $('.btn-ci-save');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جارٍ الحفظ...');

        var quickUpdateUrl = (typeof quick_update_customer_url !== 'undefined')
            ? quick_update_customer_url
            : customer_info_url.replace('custamer-info', 'quick-update-customer');

        $.post(quickUpdateUrl, { id: customerId, fields: dirtyFields }, function(res) {
            $btn.prop('disabled', false).html('<i class="fa fa-check"></i> حفظ التعديلات');
            if (res.success) {
                $.extend(originalData, dirtyFields);
                dirtyFields = {};
                $('#ciSaveBar').removeClass('visible');
                $('#ciFooterSaveBtn').hide();
                if (originalData.name) {
                    $('#customerInfoTitle').html('<i class="fa fa-user-circle"></i> ' + originalData.name);
                }
                var $bar = $('#ciSaveBar');
                $bar.css({ background: '#F0FDF4', borderColor: '#BBF7D0' });
                $bar.find('.ci-save-text').text('✓ ' + res.message);
                $bar.addClass('visible');
                setTimeout(function() { $bar.removeClass('visible'); }, 2500);
            } else {
                alert(res.message || 'حدث خطأ أثناء الحفظ');
            }
        }, 'json').fail(function() {
            $btn.prop('disabled', false).html('<i class="fa fa-check"></i> حفظ التعديلات');
            alert('حدث خطأ في الاتصال');
        });
    }

    return { loadCustomer: loadCustomer, toggleField: toggleField, markDirty: markDirty, cancelAll: cancelAll, save: save };
})();

$(document).on('click', '.custmer-popup', function() {
    CiEdit.loadCustomer($(this).attr('customer-id'));
});

$(document).on('dblclick', '#customerInfoModal .ci-field', function(e) {
    var input = $(this).find('.ci-input');
    if (input.length) CiEdit.toggleField(input[0]);
});

$(document).on('change', '#customerInfoModal .ci-input', function() {
    CiEdit.markDirty(this);
});
$(document).on('select2:select select2:unselect select2:clear', '#customerInfoModal select.ci-input', function() {
    CiEdit.markDirty(this);
});
/////
function copyText(element) {
    var range, selection, worked;
    if (document.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    } else if (window.getSelection) {
        selection = window.getSelection();
        range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
    try {
        document.execCommand('copy');
        alert('text copied');
    } catch (err) {
        alert('unable to copy text');
    }
}
/////

$(document).on('click', '.statse-change', function () {
    let id = $('.statse-change').attr('contract-id');
    let statusContent = $('.status-content').val();
    $.post(change_status_url, { id: id, statusContent: statusContent }, function (e) {
        location.reload();
    })
})

function setPhoneNumebr(number) {
    $("#phone_number").val(number);
    var display = document.getElementById('ssms-phone-display');
    if (display) display.textContent = number || '—';
}

/* ══════════════════════════════════════════════════
   SmsCalc — shared SMS encoding/counting utility
   ══════════════════════════════════════════════════ */
var SmsCalc = (function () {
    var GSM7_BASIC = '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
    var GSM7_EXT = '^{}\\[~]|€';

    function countGsm7(text) {
        var count = 0;
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            if (GSM7_BASIC.indexOf(ch) !== -1) count++;
            else if (GSM7_EXT.indexOf(ch) !== -1) count += 2;
            else return { isGsm: false };
        }
        return { isGsm: true, count: count };
    }

    function calc(text) {
        if (!text || text.length === 0) {
            return { charCount: 0, parts: 1, maxSingle: 70, maxMulti: 67, remaining: 70, encoding: 'arabic' };
        }
        var gsm = countGsm7(text);
        var charCount, maxSingle, maxMulti, encoding;
        if (gsm.isGsm) {
            charCount = gsm.count; maxSingle = 160; maxMulti = 153; encoding = 'english';
        } else {
            charCount = text.length; maxSingle = 70; maxMulti = 67; encoding = 'arabic';
        }
        var parts, remaining;
        if (charCount <= maxSingle) { parts = 1; remaining = maxSingle - charCount; }
        else { parts = Math.ceil(charCount / maxMulti); remaining = (parts * maxMulti) - charCount; }
        return { charCount: charCount, parts: parts, maxSingle: maxSingle, maxMulti: maxMulti, remaining: remaining, encoding: encoding };
    }

    return { calc: calc };
})();

/* ══════════════════════════════════════════════════
   SmsDrafts — shared drafts & variable system
   (server-side storage, shared among all users)
   ══════════════════════════════════════════════════ */
var SmsDrafts = (function () {
    var _cache = null;

    function _urls() {
        var u = (typeof OCP_CONFIG !== 'undefined' && OCP_CONFIG.urls) ? OCP_CONFIG.urls : {};
        return {
            list: u.smsDraftList || '',
            save: u.smsDraftSave || '',
            del: u.smsDraftDelete || ''
        };
    }

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function _getRefreshFn(textareaId) {
        if (textareaId === 'sms_text') return function () { if (typeof SingleSms !== 'undefined') SingleSms.refreshStats(); };
        if (textareaId === 'bsms-text') return function () { if (typeof BulkSms !== 'undefined') BulkSms.refreshStats(); };
        return function () {};
    }

    function resolveVars(text) {
        var vars = window.SMS_VARS || {};
        return text.replace(/\{([^}]+)\}/g, function (match, key) {
            return vars[key] !== undefined ? vars[key] : match;
        });
    }

    function getVarKeys() {
        return Object.keys(window.SMS_VARS || {});
    }

    function insertVar(textareaId, varKey, refreshFn) {
        var ta = document.getElementById(textareaId);
        if (!ta) return;
        var start = ta.selectionStart, end = ta.selectionEnd;
        var tag = '{' + varKey + '}';
        ta.value = ta.value.substring(0, start) + tag + ta.value.substring(end);
        var pos = start + tag.length;
        ta.setSelectionRange(pos, pos);
        ta.focus();
        if (refreshFn) refreshFn();
    }

    function renderVarsPanel(containerId, textareaId) {
        var keys = getVarKeys();
        var vars = window.SMS_VARS || {};
        var el = document.getElementById(containerId);
        if (!el) return;
        var html = '';
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            var preview = vars[k].length > 20 ? vars[k].substring(0, 20) + '...' : vars[k];
            html += '<button type="button" class="sdt-var-chip" data-var="' + _esc(k) + '" data-ta="' + textareaId + '" title="' + _esc(vars[k]) + '">';
            html += '<span class="sdt-var-name">{' + _esc(k) + '}</span>';
            html += '<span class="sdt-var-val">' + _esc(preview) + '</span>';
            html += '</button>';
        }
        el.innerHTML = html;
        el.querySelectorAll('.sdt-var-chip').forEach(function (btn) {
            btn.addEventListener('click', function () {
                insertVar(this.getAttribute('data-ta'), this.getAttribute('data-var'), _getRefreshFn(this.getAttribute('data-ta')));
            });
        });
    }

    function _buildDraftHtml(list, containerId, textareaId) {
        if (!list.length) {
            return '<div class="sdt-empty"><i class="fa fa-inbox"></i> لا توجد مسودات محفوظة</div>';
        }
        var html = '';
        for (var i = 0; i < list.length; i++) {
            var d = list[i];
            var preview = d.text.length > 50 ? d.text.substring(0, 50) + '...' : d.text;
            html += '<div class="sdt-draft-item" data-id="' + d.id + '">';
            html += '<div class="sdt-draft-load" data-did="' + d.id + '" data-ta="' + textareaId + '">';
            html += '<div class="sdt-draft-name">' + _esc(d.name) + '</div>';
            html += '<div class="sdt-draft-preview">' + _esc(preview) + '</div>';
            html += '</div>';
            html += '<button type="button" class="sdt-draft-del" data-did="' + d.id + '" data-list="' + containerId + '" data-ta="' + textareaId + '" title="حذف"><i class="fa fa-trash-o"></i></button>';
            html += '</div>';
        }
        return html;
    }

    function _bindDraftEvents(el) {
        el.querySelectorAll('.sdt-draft-load').forEach(function (loadEl) {
            loadEl.addEventListener('click', function () {
                loadDraft(parseInt(this.getAttribute('data-did')), this.getAttribute('data-ta'));
            });
        });
        el.querySelectorAll('.sdt-draft-del').forEach(function (delEl) {
            delEl.addEventListener('click', function () {
                deleteDraft(parseInt(this.getAttribute('data-did')));
            });
        });
    }

    function renderDraftsList(containerId, textareaId) {
        var el = document.getElementById(containerId);
        if (!el) return;
        if (_cache) {
            el.innerHTML = _buildDraftHtml(_cache, containerId, textareaId);
            _bindDraftEvents(el);
            return;
        }
        el.innerHTML = '<div class="sdt-empty"><i class="fa fa-spinner fa-spin"></i> جاري التحميل...</div>';
        $.get(_urls().list, function (res) {
            _cache = (res && res.drafts) ? res.drafts : [];
            el.innerHTML = _buildDraftHtml(_cache, containerId, textareaId);
            _bindDraftEvents(el);
        }).fail(function () {
            el.innerHTML = '<div class="sdt-empty" style="color:#EF4444"><i class="fa fa-exclamation-triangle"></i> خطأ في تحميل المسودات</div>';
        });
    }

    function _refreshAllDraftPanels(drafts) {
        _cache = drafts;
        var panels = [
            { list: 'ssms-drafts-list', ta: 'sms_text' },
            { list: 'bsms-drafts-list', ta: 'bsms-text' }
        ];
        for (var i = 0; i < panels.length; i++) {
            var p = panels[i];
            var el = document.getElementById(p.list);
            if (el) {
                el.innerHTML = _buildDraftHtml(drafts, p.list, p.ta);
                _bindDraftEvents(el);
            }
        }
    }

    function loadDraft(id, textareaId) {
        var list = _cache || [];
        var draft = null;
        for (var i = 0; i < list.length; i++) {
            if (list[i].id === id) { draft = list[i]; break; }
        }
        if (!draft) return;
        var ta = document.getElementById(textareaId);
        if (!ta) return;
        ta.value = resolveVars(draft.text);
        ta.focus();
        _getRefreshFn(textareaId)();
    }

    function deleteDraft(id) {
        $.post(_urls().del, { id: id }, function (res) {
            if (res && res.drafts) _refreshAllDraftPanels(res.drafts);
            if (typeof WaDraftPicker !== 'undefined') WaDraftPicker.invalidateCache();
        });
    }

    // Maps textarea id -> inline save panel id (replaces the native prompt()
    // because embedded browsers like Cursor's suppress window.prompt).
    var _savePanelMap = {
        'sms_text': { panel: 'ssms-save-panel', input: 'ssms-save-name', hint: 'ssms-save-hint' },
        'bsms-text': { panel: 'bsms-save-panel', input: 'bsms-save-name', hint: 'bsms-save-hint' }
    };

    function _setSaveHint(hintId, msg, isError) {
        var el = document.getElementById(hintId);
        if (!el) return;
        el.classList.toggle('sdt-error', !!isError);
        if (isError) {
            el.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + _esc(msg);
        } else {
            el.innerHTML = '<i class="fa fa-info-circle"></i> ' + _esc(msg);
        }
    }

    function _flashInput(inputId) {
        var el = document.getElementById(inputId);
        if (!el) return;
        el.classList.add('sdt-invalid');
        setTimeout(function () { el.classList.remove('sdt-invalid'); }, 1200);
    }

    function promptSave(textareaId) {
        var ta = document.getElementById(textareaId);
        var cfg = _savePanelMap[textareaId];
        if (!ta || !cfg) return;

        if (!ta.value.trim()) {
            ta.style.borderColor = '#EF4444';
            setTimeout(function () { ta.style.borderColor = ''; }, 1500);
            return;
        }

        var panel = document.getElementById(cfg.panel);
        if (!panel) return;

        // Close sibling panels, open ours
        var parent = panel.parentNode;
        if (parent) {
            parent.querySelectorAll('.sdt-panel').forEach(function (p) { p.classList.remove('open'); });
        }
        panel.classList.add('open');

        _setSaveHint(cfg.hint, 'يتم حفظ النص الحالي كما هو — يمكن استخدام المتغيرات مثل {اسم_العميل}', false);

        var input = document.getElementById(cfg.input);
        if (input) {
            input.value = '';
            setTimeout(function () { input.focus(); }, 50);
        }
    }

    function _submitSave(textareaId, nameInputId, hintId, panelId, goBtn) {
        var ta = document.getElementById(textareaId);
        var nameEl = document.getElementById(nameInputId);
        if (!ta || !nameEl) return;

        var name = nameEl.value.trim();
        var text = ta.value.trim();

        if (!name) {
            _setSaveHint(hintId, 'يرجى إدخال اسم للمسودة', true);
            _flashInput(nameInputId);
            nameEl.focus();
            return;
        }
        if (!text) {
            _setSaveHint(hintId, 'نص الرسالة فارغ', true);
            return;
        }

        var origHtml = goBtn ? goBtn.innerHTML : '';
        if (goBtn) {
            goBtn.disabled = true;
            goBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...';
        }

        $.post(_urls().save, { name: name, text: ta.value }, function (res) {
            if (res && res.success) {
                _refreshAllDraftPanels(res.drafts || []);
                if (typeof WaDraftPicker !== 'undefined') WaDraftPicker.invalidateCache();
                nameEl.value = '';
                var panel = document.getElementById(panelId);
                if (panel) panel.classList.remove('open');
                // Open the drafts list so the user sees the newly saved item
                var draftsPanelId = panelId.replace('-save-panel', '-drafts-panel');
                var dp = document.getElementById(draftsPanelId);
                if (dp) dp.classList.add('open');
            } else {
                _setSaveHint(hintId, (res && res.message) ? res.message : 'تعذر حفظ المسودة', true);
            }
        }).fail(function () {
            _setSaveHint(hintId, 'خطأ في الاتصال بالسيرفر', true);
        }).always(function () {
            if (goBtn) {
                goBtn.disabled = false;
                goBtn.innerHTML = origHtml;
            }
        });
    }

    // Wire up inline save controls (works for both single + bulk SMS panels).
    // Convention: panel id is "{prefix}-save-panel" and hint id is "{prefix}-save-hint".
    $(document).on('click', '.sdt-save-go', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var ta = $btn.attr('data-ta');
        var nameInput = $btn.attr('data-name-input');
        var panel = $btn.attr('data-panel');
        var hint = panel ? panel.replace('-panel', '-hint') : '';
        _submitSave(ta, nameInput, hint, panel, this);
    });

    $(document).on('click', '.sdt-save-cancel', function (e) {
        e.preventDefault();
        var panelId = $(this).attr('data-panel');
        var panel = document.getElementById(panelId);
        if (panel) panel.classList.remove('open');
    });

    $(document).on('keydown', '.sdt-save-input', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            var $go = $(this).closest('.sdt-panel').find('.sdt-save-go');
            if ($go.length) $go.trigger('click');
        } else if (e.key === 'Escape' || e.keyCode === 27) {
            var panelId = $(this).attr('data-panel');
            var panel = document.getElementById(panelId);
            if (panel) panel.classList.remove('open');
        }
    });

    function togglePanel(panelId) {
        var panel = document.getElementById(panelId);
        if (!panel) return;
        var isOpen = panel.classList.contains('open');
        document.querySelectorAll('.sdt-panel').forEach(function (p) { p.classList.remove('open'); });
        if (!isOpen) panel.classList.add('open');
    }

    function invalidateCache() { _cache = null; }

    return {
        resolveVars: resolveVars,
        getVarKeys: getVarKeys,
        insertVar: insertVar,
        renderVarsPanel: renderVarsPanel,
        renderDraftsList: renderDraftsList,
        loadDraft: loadDraft,
        deleteDraft: deleteDraft,
        promptSave: promptSave,
        togglePanel: togglePanel,
        invalidateCache: invalidateCache
    };
})();

/* ══════════════════════════════════════════════════
   SingleSms — individual SMS modal logic
   ══════════════════════════════════════════════════ */
var SingleSms = (function () {
    function refreshStats() {
        var raw = document.getElementById('sms_text').value;
        var resolved = SmsDrafts.resolveVars(raw);
        var s = SmsCalc.calc(resolved);
        document.getElementById('ssms-s-parts').textContent = s.parts;
        document.getElementById('ssms-s-used').textContent = s.charCount;
        document.getElementById('ssms-s-remain').textContent = s.remaining;
        document.getElementById('ssms-s-encoding').textContent = s.encoding === 'arabic' ? 'عربي (70)' : 'إنجليزي (160)';
    }

    function toggleEmoji() {
        var panel = document.getElementById('ssms-emoji-panel');
        panel.classList.toggle('open');
    }

    function insertEmoji(emoji) {
        var ta = document.getElementById('sms_text');
        var start = ta.selectionStart, end = ta.selectionEnd;
        ta.value = ta.value.substring(0, start) + emoji + ta.value.substring(end);
        var pos = start + emoji.length;
        ta.setSelectionRange(pos, pos);
        ta.focus();
        refreshStats();
    }

    function clearText() {
        document.getElementById('sms_text').value = '';
        document.getElementById('sms_text').focus();
        refreshStats();
    }

    $(document).on('input', '#sms_text', function () { refreshStats(); });

    $(document).on('click', '#send_sms', function () {
        var phone_number = $('#phone_number').val();
        var text = SmsDrafts.resolveVars($('#sms_text').val().trim());
        if (!text) {
            $('#sms_text').css('border-color', '#EF4444').focus();
            setTimeout(function () { $('#sms_text').css('border-color', ''); }, 2000);
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الإرسال...');
        $.post(send_sms, { text: text, phone_number: phone_number }, function (data) {
            var msg = typeof data === 'string' ? JSON.parse(data) : data;
            $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> إرسال');
            if (msg.message === '' || msg.message === undefined) {
                $btn.html('<i class="fa fa-check"></i> تم الإرسال').css({ background: 'linear-gradient(135deg,#16A34A,#15803D)', borderColor: '#16A34A' });
                setTimeout(function () {
                    $btn.html('<i class="fa fa-paper-plane"></i> إرسال').css({ background: '', borderColor: '' });
                    $('#smsModal').modal('hide');
                }, 1500);
            } else {
                alert(msg.message);
            }
        }).fail(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> إرسال');
            alert('خطأ في الاتصال');
        });
    });

    function initDrafts() {
        SmsDrafts.invalidateCache();
        SmsDrafts.renderVarsPanel('ssms-vars-list', 'sms_text');
        SmsDrafts.renderDraftsList('ssms-drafts-list', 'sms_text');
    }

    $('#smsModal').on('show.bs.modal', function () {
        var panel = document.getElementById('ssms-emoji-panel');
        if (panel) panel.classList.remove('open');
        document.querySelectorAll('#smsModal .sdt-panel').forEach(function (p) { p.classList.remove('open'); });
        refreshStats();
        initDrafts();
    });

    return { toggleEmoji: toggleEmoji, insertEmoji: insertEmoji, clearText: clearText, refreshStats: refreshStats };
})();

/* ══════════════════════════════════════════════════
   BulkSms — Send SMS to multiple numbers at once
   with accurate GSM-7 / UCS-2 character counting
   ══════════════════════════════════════════════════ */
var BulkSms = (function () {
    var phones = [];
    var sending = false;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function refreshStats() {
        var raw = document.getElementById('bsms-text').value;
        var resolved = SmsDrafts.resolveVars(raw);
        var s = SmsCalc.calc(resolved);
        var selectedCount = document.querySelectorAll('#bsms-list input[type=checkbox]:checked').length;

        document.getElementById('bsms-s-parts').textContent = s.parts;
        document.getElementById('bsms-s-used').textContent = s.charCount;
        document.getElementById('bsms-s-remain').textContent = s.remaining;
        document.getElementById('bsms-s-encoding').textContent = s.encoding === 'arabic' ? 'عربي (70)' : 'إنجليزي (160)';
        document.getElementById('bsms-s-total').textContent = s.parts * selectedCount;
    }

    function renderList(preserveState) {
        // preserveState: when true, keep existing checkbox states / excluded flags
        // for phones that still exist (matched by `number`). Used on live refresh.
        var prevState = {};
        if (preserveState) {
            document.querySelectorAll('#bsms-list .bsms-item').forEach(function (item) {
                var idx = parseInt(item.getAttribute('data-idx'));
                var cb = item.querySelector('input[type=checkbox]');
                var oldP = (typeof prevPhones !== 'undefined' && prevPhones[idx]) ? prevPhones[idx] : null;
                if (oldP && cb) prevState[oldP.number] = cb.checked;
            });
        }

        var html = '';
        for (var i = 0; i < phones.length; i++) {
            var p = phones[i];
            var tagCls = p.primary ? 'primary' : 'extra';
            var tagText = p.primary ? 'رئيسي' : p.label;
            var checked = preserveState && prevState.hasOwnProperty(p.number) ? prevState[p.number] : true;
            html += '<label class="bsms-item' + (checked ? '' : ' excluded') + '" data-idx="' + i + '">';
            html += '<input type="checkbox"' + (checked ? ' checked' : '') + ' data-idx="' + i + '">';
            html += '<span class="bsms-num">' + esc(p.local) + '</span>';
            html += '<span class="bsms-name">' + esc(p.name) + '</span>';
            html += '<span class="bsms-tag ' + tagCls + '">' + esc(tagText) + '</span>';
            html += '<button type="button" class="bsms-wa-btn" data-idx="' + i + '"><i class="fa fa-whatsapp"></i></button>';
            html += '</label>';
        }
        document.getElementById('bsms-list').innerHTML = html;
        document.getElementById('bsms-total-count').textContent = phones.length;
        updateCount();
        refreshStats();

        document.querySelectorAll('#bsms-list input[type=checkbox]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                this.closest('.bsms-item').classList.toggle('excluded', !this.checked);
                updateCount();
                refreshStats();
            });
        });

        document.querySelectorAll('#bsms-list .bsms-wa-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var idx = parseInt(this.getAttribute('data-idx'));
                var p = phones[idx];
                var text = SmsDrafts.resolveVars(document.getElementById('bsms-text').value.trim());
                var url = 'whatsapp://send?phone=' + encodeURIComponent(p.number);
                if (text) url += '&text=' + encodeURIComponent(text);
                window.location.href = url;
            });
        });
    }

    var prevPhones = [];

    // Pulls the latest phone list from the server so the modal stays in sync
    // with adds/edits/deletes done on the page without needing a reload.
    function fetchLivePhones(callback) {
        var urls = (typeof OCP_CONFIG !== 'undefined' && OCP_CONFIG.urls) ? OCP_CONFIG.urls : {};
        var url = urls.contractPhones || '';
        if (!url || typeof $ === 'undefined' || !$.get) {
            callback(window._bulkSmsPhones || []);
            return;
        }
        $.get(url).done(function (res) {
            var list = (res && res.phones) ? res.phones : (window._bulkSmsPhones || []);
            window._bulkSmsPhones = list;
            callback(list);
        }).fail(function () {
            callback(window._bulkSmsPhones || []);
        });
    }

    function syncFromWindow() {
        fetchLivePhones(function (list) {
            prevPhones = phones.slice();
            phones = list.slice();
            renderList(true);
        });
    }

    function open() {
        phones = (window._bulkSmsPhones || []).slice();

        sending = false;
        document.getElementById('bsms-text').value = '';
        document.getElementById('bsms-progress').style.display = 'none';
        document.getElementById('bsms-results').style.display = 'none';
        document.getElementById('bsms-results').innerHTML = '';
        document.getElementById('bsms-send-btn').disabled = false;
        document.getElementById('bsms-send-btn').innerHTML = '<i class="fa fa-paper-plane"></i> إرسال للمحددين';
        var emojiPanel = document.getElementById('bsms-emoji-panel');
        if (emojiPanel) emojiPanel.classList.remove('open');

        renderList(false);

        SmsDrafts.invalidateCache();
        SmsDrafts.renderVarsPanel('bsms-vars-list', 'bsms-text');
        SmsDrafts.renderDraftsList('bsms-drafts-list', 'bsms-text');
        document.querySelectorAll('#bulkSmsModal .sdt-panel').forEach(function (p) { p.classList.remove('open'); });

        $('#bulkSmsModal').modal('show');

        // Pull fresh data from server so any recent add/edit/delete is reflected
        // immediately without needing a page reload.
        fetchLivePhones(function (list) {
            prevPhones = phones.slice();
            phones = list.slice();
            renderList(true);
        });
    }

    function updateCount() {
        var checked = document.querySelectorAll('#bsms-list input[type=checkbox]:checked').length;
        document.getElementById('bsms-sel-count').textContent = checked;
        var btn = document.getElementById('bsms-send-btn');
        if (!sending) {
            btn.disabled = checked === 0;
        }
        var allChecked = checked === phones.length;
        document.getElementById('bsms-toggle-icon').className = 'fa ' + (allChecked ? 'fa-check-square-o' : 'fa-square-o');
        document.getElementById('bsms-toggle-text').textContent = allChecked ? 'إلغاء تحديد الكل' : 'تحديد الكل';
    }

    function toggleAll() {
        var cbs = document.querySelectorAll('#bsms-list input[type=checkbox]');
        var allChecked = document.querySelectorAll('#bsms-list input[type=checkbox]:checked').length === cbs.length;
        cbs.forEach(function (cb) {
            cb.checked = !allChecked;
            cb.closest('.bsms-item').classList.toggle('excluded', allChecked);
        });
        updateCount();
        refreshStats();
    }

    function toggleEmoji() {
        var panel = document.getElementById('bsms-emoji-panel');
        panel.classList.toggle('open');
    }

    function insertEmoji(emoji) {
        var ta = document.getElementById('bsms-text');
        var start = ta.selectionStart;
        var end = ta.selectionEnd;
        var before = ta.value.substring(0, start);
        var after = ta.value.substring(end);
        ta.value = before + emoji + after;
        var newPos = start + emoji.length;
        ta.setSelectionRange(newPos, newPos);
        ta.focus();
        refreshStats();
    }

    function clearText() {
        document.getElementById('bsms-text').value = '';
        document.getElementById('bsms-text').focus();
        refreshStats();
    }

    var CONCURRENCY = 3;

    function send() {
        var text = SmsDrafts.resolveVars(document.getElementById('bsms-text').value.trim());
        if (!text) {
            document.getElementById('bsms-text').style.borderColor = '#EF4444';
            document.getElementById('bsms-text').focus();
            setTimeout(function () { document.getElementById('bsms-text').style.borderColor = ''; }, 2000);
            return;
        }

        var selected = [];
        document.querySelectorAll('#bsms-list input[type=checkbox]:checked').forEach(function (cb) {
            selected.push(phones[parseInt(cb.getAttribute('data-idx'))]);
        });
        if (!selected.length) return;

        sending = true;
        var btn = document.getElementById('bsms-send-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الإرسال...';

        document.getElementById('bsms-progress').style.display = 'block';
        document.getElementById('bsms-results').style.display = 'block';
        document.getElementById('bsms-results').innerHTML = '';

        var total = selected.length;
        var done = 0;
        var successCount = 0;
        var failCount = 0;
        var nextIdx = 0;
        var smsUrl = (typeof OCP_CONFIG !== 'undefined' && OCP_CONFIG.urls && OCP_CONFIG.urls.bulkSendSms)
            ? OCP_CONFIG.urls.bulkSendSms
            : (typeof bulk_send_sms !== 'undefined' ? bulk_send_sms : send_sms);

        function onComplete() {
            if (done >= total) {
                btn.innerHTML = '<i class="fa fa-check"></i> تم الإرسال';
                document.getElementById('bsms-progress-text').innerHTML =
                    '<i class="fa fa-check-circle" style="color:#16A34A"></i> اكتمل — ' +
                    '<b style="color:#16A34A">' + successCount + ' نجح</b>' +
                    (failCount > 0 ? ' · <b style="color:#EF4444">' + failCount + ' فشل</b>' : '');
                sending = false;
            }
        }

        function appendResult(p, ok, errMsg) {
            done++;
            var pct = Math.round((done / total) * 100);
            document.getElementById('bsms-progress-fill').style.width = pct + '%';
            document.getElementById('bsms-progress-text').textContent =
                'تم ' + done + ' من ' + total;
            if (ok) successCount++; else failCount++;
            var icon = ok ? 'fa-check-circle' : 'fa-times-circle';
            var html = '<div class="bsms-result-item"><i class="fa ' + icon + '"></i>';
            html += '<span class="bsms-r-num">' + esc(p.local) + '</span>';
            html += '<span>' + esc(p.name) + '</span>';
            if (!ok && errMsg) html += ' <span style="color:#EF4444;font-size:11px">(' + esc(errMsg) + ')</span>';
            html += '</div>';
            var resultsDiv = document.getElementById('bsms-results');
            resultsDiv.innerHTML += html;
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
            launchNext();
            onComplete();
        }

        function launchNext() {
            if (nextIdx >= total) return;
            var idx = nextIdx++;
            var p = selected[idx];
            $.post(smsUrl, { text: text, phone_number: p.number }, function (data) {
                var res;
                if (typeof data === 'string') {
                    try { res = JSON.parse(data); } catch (e) { res = { success: false, message: data }; }
                } else {
                    res = data;
                }
                var ok = res.success || res.message === '' || res.message === undefined;
                appendResult(p, ok, ok ? '' : res.message);
            }).fail(function () {
                appendResult(p, false, 'خطأ في الاتصال');
            });
        }

        var initial = Math.min(CONCURRENCY, total);
        for (var i = 0; i < initial; i++) {
            launchNext();
        }
    }

    function sendWhatsApp() {
        var text = SmsDrafts.resolveVars(document.getElementById('bsms-text').value.trim());

        var selected = [];
        document.querySelectorAll('#bsms-list input[type=checkbox]:checked').forEach(function (cb) {
            selected.push(phones[parseInt(cb.getAttribute('data-idx'))]);
        });
        if (!selected.length) return;

        var waBtn = document.getElementById('bsms-wa-send-btn');
        var total = selected.length;
        var sentCount = 0;

        $('#bulkSmsModal').modal('hide');

        function _done(icon, title, count) {
            Swal.fire({
                icon: icon,
                title: title,
                html: '<b>' + count + '</b> من <b>' + total + '</b> تم فتح واتساب لهم',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#25D366',
                customClass: { popup: 'tayseer-swal-popup' }
            }).then(function () {
                waBtn.disabled = false;
                waBtn.innerHTML = '<i class="fa fa-whatsapp"></i> إرسال واتساب للمحددين';
                $('#bulkSmsModal').modal('show');
            });
        }

        function openFor(idx) {
            if (idx >= total) {
                _done('success', 'تم الانتهاء', sentCount);
                return;
            }

            var p = selected[idx];
            var url = 'whatsapp://send?phone=' + encodeURIComponent(p.number);
            if (text) url += '&text=' + encodeURIComponent(text);
            window.location.href = url;
            sentCount++;

            if (idx + 1 < total) {
                var nextP = selected[idx + 1];
                Swal.fire({
                    icon: 'question',
                    title: 'التالي: ' + nextP.name,
                    html: '<div style="direction:ltr;font-family:monospace;font-size:15px;margin:6px 0">' + esc(nextP.local) + '</div>' +
                          '<div style="color:#64748B;font-size:12px">' + sentCount + ' من ' + total + ' تم</div>',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: '<i class="fa fa-arrow-left"></i> التالي',
                    denyButtonText: '<i class="fa fa-forward"></i> تخطي',
                    cancelButtonText: '<i class="fa fa-stop"></i> إيقاف',
                    confirmButtonColor: '#25D366',
                    denyButtonColor: '#F59E0B',
                    cancelButtonColor: '#6B7280',
                    reverseButtons: true,
                    allowOutsideClick: false,
                    customClass: { popup: 'tayseer-swal-popup' }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        openFor(idx + 1);
                    } else if (result.isDenied) {
                        openFor(idx + 2);
                    } else {
                        _done('info', 'تم الإيقاف', sentCount);
                    }
                });
            } else {
                openFor(idx + 1);
            }
        }

        waBtn.disabled = true;
        waBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الإرسال...';
        openFor(0);
    }

    $(document).on('input', '#bsms-text', function () {
        refreshStats();
    });

    return {
        open: open,
        toggleAll: toggleAll,
        toggleEmoji: toggleEmoji,
        insertEmoji: insertEmoji,
        clearText: clearText,
        send: send,
        sendWhatsApp: sendWhatsApp,
        refreshStats: refreshStats,
        syncFromWindow: syncFromWindow
    };
})();