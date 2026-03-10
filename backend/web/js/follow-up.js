
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
    var requiredFields = ['name', 'id_number', 'sex', 'birth_date', 'city', 'job_title', 'primary_phone_number'];

    function setVal(cls, val) {
        var el = $('.' + cls);
        if (el.is('select')) {
            el.val(val != null ? String(val) : '');
        } else {
            el.val(val || '');
        }
    }

    function loadCustomer(customerId) {
        dirtyFields = {};
        $('#ciSaveBar').removeClass('visible');
        $('#ciFooterSaveBtn').hide();
        $('#ci-customer-id').val(customerId);

        $('#customerInfoModal .ci-input').each(function() {
            $(this).prop('disabled', true).closest('.ci-field').removeClass('ci-editing');
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
        });
    }

    function closeField(fieldEl) {
        var $el = $(fieldEl);
        if ($el.prop('disabled')) return;
        markDirty(fieldEl);
        $el.prop('disabled', true);
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
            $el.prop('disabled', false).focus();
            $el.closest('.ci-field').addClass('ci-editing');
        }
    }

    function markDirty(fieldEl) {
        var $el = $(fieldEl);
        var fieldName = $el.data('field');
        if (!fieldName) return;
        var orig = originalData[fieldName];
        var current = $el.val();

        if (requiredFields.indexOf(fieldName) !== -1 && orig && String(orig).trim() !== '' && (!current || String(current).trim() === '')) {
            $el.closest('.ci-field').css('animation', 'ciShake .4s');
            setTimeout(function() { $el.closest('.ci-field').css('animation', ''); }, 400);
            if ($el.is('select')) {
                $el.val(orig != null ? String(orig) : '');
            } else {
                $el.val(orig || '');
            }
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
        $('#ciSaveBar').removeClass('visible');
        $('#ciFooterSaveBtn').hide();
        $('#customerInfoModal .ci-input').each(function() {
            var $el = $(this);
            $el.prop('disabled', true);
            $el.closest('.ci-field').removeClass('ci-editing');
            var fieldName = $el.data('field');
            if (fieldName && originalData[fieldName] !== undefined) {
                if ($el.is('select')) {
                    $el.val(originalData[fieldName] != null ? String(originalData[fieldName]) : '');
                } else {
                    $el.val(originalData[fieldName] || '');
                }
            }
        });
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

$(document).on('click', '#send_sms', function () {
    let phone_number = $('#phone_number').val();
    let text = $('#sms_text').val();
    $.post(send_sms, { text: text, phone_number: phone_number }, function (data) {
        let msg = JSON.parse(data)
        if (msg.message == '') {
            alert('تم ارسال الرسالة بنجاح');
        } else
            alert(msg.message);
    })
})
///////
$(document).ready(function () {
    var textarea = $("#sms_text");
    textarea.keydown(function (event) {
        var numbOfchars = textarea.val();
        var len = numbOfchars.length;
        $("#char_count").text(len);
    });
});
//////
$(document).on('click', '.statse-change', function () {
    let id = $('.statse-change').attr('contract-id');
    let statusContent = $('.status-content').val();
    $.post(change_status_url, { id: id, statusContent: statusContent }, function (e) {
        location.reload();
    })
})
////
function setPhoneNumebr(number) {
    $("#phone_number").val(number);
}