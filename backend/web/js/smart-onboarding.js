/**
 * Smart Onboarding — Live Risk Assessment Panel
 * Handles: Wizard steps, live risk calculation, alerts, decision actions
 */
(function($) {
    'use strict';

    var SO = {
        currentStep: 0,
        totalSteps: 5,
        debounceTimer: null,
        riskData: null,
        panelExpanded: false,
    };

    /* ══════════════════════════════════════════
       INITIALIZATION
       ══════════════════════════════════════════ */
    $(function() {
        initWizard();
        initEditModeNav();
        initRiskPanel();
        initLiveValidation();
        initDuplicateCheck();
        initConditionalFields();
        initDocumentUploads();
        triggerRiskCalc();
    });

    /* ══════════════════════════════════════════
       WIZARD NAVIGATION
       ══════════════════════════════════════════ */
    function initWizard() {
        showStep(0);

        var isEdit = window.soConfig && window.soConfig.isEditMode;

        $(document).on('click', '.so-step', function() {
            var idx = $(this).data('step');
            if (isEdit || idx <= getMaxReachedStep()) goToStep(idx);
        });

        $(document).on('click', '.so-next-btn', function() {
            if (isEdit || validateCurrentStep()) goToStep(SO.currentStep + 1);
        });
        $(document).on('click', '.so-prev-btn', function() {
            goToStep(SO.currentStep - 1);
        });
    }

    function goToStep(idx) {
        if (idx < 0 || idx >= SO.totalSteps) return;

        // Mark previous as completed if going forward
        if (idx > SO.currentStep) {
            $('.so-step[data-step="' + SO.currentStep + '"]').addClass('completed').removeClass('active');
        }

        SO.currentStep = idx;
        showStep(idx);
        saveStepState();
        triggerRiskCalc();
    }

    function showStep(idx) {
        $('.so-section').removeClass('active');
        $('.so-section[data-step="' + idx + '"]').addClass('active');

        $('.so-step').removeClass('active');
        $('.so-step[data-step="' + idx + '"]').addClass('active');

        // In edit mode, mark all steps as completed (data already exists)
        if (window.soConfig && window.soConfig.isEditMode) {
            $('.so-step').each(function() {
                if ($(this).data('step') !== idx) {
                    $(this).addClass('completed');
                }
            });
        }

        // Update nav buttons
        $('.so-prev-btn').toggle(idx > 0);
        if (idx >= SO.totalSteps - 1) {
            $('.so-next-btn').hide();
        } else {
            $('.so-next-btn').show();
        }

        // ── Fix DynamicFormWidget in wizard steps ──
        // Re-trigger resize so widgets recalculate dimensions
        $(window).trigger('resize');

        // Refresh PhoneInput widgets when step becomes visible
        var $section = $('.so-section[data-step="' + idx + '"]');
        $section.find('.iti input[type="tel"]').each(function() {
            if (this._iti) this._iti.handleUtils();
        });

        // Scroll to top
        $('.so-form-area').scrollTop(0);
    }

    function getMaxReachedStep() {
        var max = SO.currentStep;
        $('.so-step.completed').each(function() {
            var s = $(this).data('step');
            if (s > max) max = s;
        });
        return max + 1;
    }

    function validateCurrentStep() {
        // Basic validation — highlight empty required fields
        var $section = $('.so-section[data-step="' + SO.currentStep + '"]');
        var valid = true;
        $section.find('[required]').each(function() {
            var $f = $(this);
            if (!$f.val() || $f.val() === '') {
                $f.closest('.form-group').addClass('has-error');
                valid = false;
            } else {
                $f.closest('.form-group').removeClass('has-error');
            }
        });
        if (!valid) showToast('يرجى ملء الحقول المطلوبة', 'warning');
        return valid;
    }

    function saveStepState() {
        try { localStorage.setItem('so_step', SO.currentStep); } catch(e){}
    }

    /* ══════════════════════════════════════════
       EDIT MODE — Section Navigation via Steps
       ══════════════════════════════════════════ */
    function initEditModeNav() {
        // Edit mode wizard handled by initWizard (validation skipped in edit)
    }

    /* ══════════════════════════════════════════
       RISK PANEL — LIVE CALCULATION
       ══════════════════════════════════════════ */
    function initRiskPanel() {
        // Mobile toggle
        $(document).on('click', '.rp-mobile-handle', function() {
            SO.panelExpanded = !SO.panelExpanded;
            $('.so-risk-panel').toggleClass('expanded', SO.panelExpanded);
        });

        // Reasons toggle
        $(document).on('click', '.rp-toggle-reasons', function() {
            $('.rp-reasons').toggleClass('open');
            $(this).text($('.rp-reasons').hasClass('open') ? 'إخفاء التفاصيل' : 'عرض سبب التقييم');
        });
    }

    function initLiveValidation() {
        // Debounced risk recalculation on any input change
        var $form = $('#smart-onboarding-form');
        $form.on('change input', 'input, select, textarea', function() {
            clearTimeout(SO.debounceTimer);
            SO.debounceTimer = setTimeout(triggerRiskCalc, 600);
        });
    }

    function triggerRiskCalc() {
        var data = collectFormData();
        $.ajax({
            url: window.soConfig.riskCalcUrl,
            method: 'POST',
            dataType: 'json',
            data: { data: data, '_csrf-backend': $('input[name="_csrf-backend"]').val() },
            success: function(resp) {
                if (resp.success) {
                    SO.riskData = resp.assessment;
                    renderRiskPanel(resp.assessment);
                }
            },
            error: function(xhr, status, err) {
                console.warn('Risk calc error:', status, err, xhr.responseText);
            }
        });
    }

    function collectFormData() {
        var $f = $('#smart-onboarding-form');
        var data = {};

        // Map form fields to risk engine input
        data.name            = $f.find('#customers-name').val();
        data.id_number       = $f.find('#customers-id_number').val();
        data.birth_date      = $f.find('#customers-birth_date').val();
        data.phone           = $f.find('#customers-primary_phone_number').val();
        data.email           = $f.find('#customers-email').val();
        data.city            = $f.find('#customers-city').val();
        data.total_salary    = parseFloat($f.find('#customers-total_salary').val()) || 0;
        data.additional_income = parseFloat($f.find('#fin-additional-income').val()) || 0;
        data.monthly_obligations = parseFloat($f.find('#fin-obligations').val()) || 0;
        data.employment_type = $f.find('#fin-employment-type').val();
        data.years_at_job    = parseFloat($f.find('#fin-years-at-job').val()) || 0;
        data.bank_name       = $f.find('#customers-bank_name').val();
        data.is_social_security = $f.find('#customers-is_social_security').val();
        data.has_ss_salary   = $f.find('#customers-has_social_security_salary').val();
        data.has_property    = $f.find('#customers-do_have_any_property').val();
        data.facebook        = $f.find('#customers-facebook_account').val() || $f.find('[name*="[fb_account]"]').first().val();

        // Count dynamic items
        data.documents_count = $('.customer-doc-row:visible').length || $f.find('[name*="CustomersDocument"]').length;
        data.references_count = $('.phone-number-row:visible').length || $f.find('[name*="PhoneNumbers"]').length;
        data.address_count   = $('.address-row:visible').length || $f.find('[name*="Address"]').length;

        // Previous contracts (if editing existing customer)
        data.previous_contracts = parseInt($f.data('prev-contracts')) || 0;
        data.has_defaults = $f.data('has-defaults') ? true : false;

        return data;
    }

    /* ══════════════════════════════════════════
       RENDER RISK PANEL
       ══════════════════════════════════════════ */
    function renderRiskPanel(a) {
        // Score
        updateGauge(a.final_score, a.risk_tier);
        
        // Tier badge
        var tierLabels = {
            approved: 'مقبول',
            conditional: 'مشروط',
            high_risk: 'مخاطر عالية',
            rejected: 'مرفوض'
        };
        var $badge = $('.rp-tier-badge');
        $badge.text(tierLabels[a.risk_tier] || a.risk_tier);
        $badge.attr('class', 'rp-tier-badge rp-tier-' + a.risk_tier);

        // Completeness
        $('.rp-completeness-val').text(a.profile_pct + '%');
        $('.rp-completeness-fill').css('width', a.profile_pct + '%');

        // Mobile summary
        $('.rp-mobile-score').text(a.final_score);
        $('.rp-mobile-tier').text(tierLabels[a.risk_tier] || '');
        $('.rp-mobile-tier').attr('class', 'rp-tier-badge rp-mobile-tier rp-tier-' + a.risk_tier);

        // Factors
        renderFactors(a.top_factors || []);

        // Financing
        renderFinancing(a.financing || {});

        // Alerts
        renderAlerts(a.alerts || []);

        // Reasons
        renderReasons(a.reasons || []);

        // Update score number color
        var colors = { approved: '#1a7a1a', conditional: '#9a7800', high_risk: '#c65000', rejected: '#c62828' };
        $('.rp-score-num').css('color', colors[a.risk_tier] || '#333');
    }

    function updateGauge(score, tier) {
        var circumference = 2 * Math.PI * 58;
        var offset = circumference - (score / 100) * circumference;
        
        var $fill = $('.rp-gauge-fill');
        $fill.attr('stroke-dashoffset', offset);
        $fill.attr('class', 'rp-gauge-fill ' + tier);
        
        // Animate score number
        var $num = $('.rp-score-num');
        var current = parseInt($num.text()) || 0;
        animateNumber($num, current, Math.round(score), 500);
    }

    function animateNumber($el, from, to, duration) {
        var start = performance.now();
        function step(timestamp) {
            var progress = Math.min((timestamp - start) / duration, 1);
            var val = Math.round(from + (to - from) * easeOut(progress));
            $el.text(val);
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function easeOut(t) { return 1 - Math.pow(1 - t, 3); }

    function renderFactors(factors) {
        var $container = $('.rp-factors-list');
        $container.empty();

        $.each(factors, function(i, f) {
            var pct = f.max > 0 ? Math.round((f.score / f.max) * 100) : 0;
            var icon = f.impact === 'positive' ? 'check' : (f.impact === 'negative' ? 'times' : 'minus');

            $container.append(
                '<div class="rp-factor">' +
                    '<div class="rp-factor-icon ' + f.impact + '"><i class="fa fa-' + icon + '"></i></div>' +
                    '<div class="rp-factor-text">' +
                        '<div class="rp-factor-name">' + f.label + '</div>' +
                        '<div class="rp-factor-bar"><div class="rp-factor-bar-fill ' + f.impact + '" style="width:' + pct + '%"></div></div>' +
                    '</div>' +
                    '<div class="rp-factor-val">' + f.score + '/' + f.max + '</div>' +
                '</div>'
            );
        });
    }

    function renderFinancing(fin) {
        if (!fin.max_financing) {
            $('.rp-financing').hide();
            return;
        }
        $('.rp-financing').show();
        $('#rp-fin-max').text(formatMoney(fin.max_financing));
        $('#rp-fin-installment').text(formatMoney(fin.max_installment));
        $('#rp-fin-months').text(fin.max_months + ' شهر');
        $('#rp-fin-available').text(formatMoney(fin.available_monthly));
    }

    function renderAlerts(alerts) {
        var $container = $('.rp-alerts');
        $container.empty();
        if (!alerts.length) return;

        $.each(alerts, function(i, al) {
            $container.append(
                '<div class="rp-alert rp-alert-' + al.type + '">' +
                    '<i class="fa fa-' + al.icon + '"></i>' +
                    '<span>' + al.message + '</span>' +
                '</div>'
            );
        });
    }

    function renderReasons(reasons) {
        var $container = $('.rp-reasons');
        $container.find('.rp-reason').remove();
        $.each(reasons, function(i, r) {
            $container.append('<div class="rp-reason">' + r + '</div>');
        });
    }

    /* ══════════════════════════════════════════
       DUPLICATE CHECK
       ══════════════════════════════════════════ */
    function initDuplicateCheck() {
        // تخطي فحص التكرار في وضع التعديل
        if (window.soConfig && window.soConfig.isEditMode) return;

        var $idNum = $('#customers-id_number');
        var $phone = $('#customers-primary_phone_number');

        $idNum.on('blur', function() {
            var val = $(this).val();
            if (val && val.length >= 5) checkDuplicate('id_number', val);
        });

        $phone.on('blur', function() {
            var val = $(this).val();
            if (val && val.length >= 7) checkDuplicate('phone', val);
        });
    }

    function checkDuplicate(field, value) {
        $.ajax({
            url: window.soConfig.duplicateCheckUrl,
            method: 'POST',
            dataType: 'json',
            data: { field: field, value: value, '_csrf-backend': $('input[name="_csrf-backend"]').val() },
            success: function(resp) {
                if (resp.found) {
                    var label = field === 'id_number' ? 'الرقم الوطني' : 'رقم الهاتف';
                    showDuplicateWarning(label, resp.customer_name, resp.customer_id);
                }
            }
        });
    }

    function showDuplicateWarning(label, name, id) {
        var viewUrl = window.soConfig.customerViewUrl.replace('__ID__', id);
        var html = '<div class="so-duplicate-warn">' +
            '<i class="fa fa-exclamation-triangle"></i>' +
            '<span>تحذير: ' + label + ' مسجّل مسبقًا باسم <a href="' + viewUrl + '" target="_blank">' + name + '</a></span>' +
            '<button type="button" class="close" onclick="$(this).parent().fadeOut()">&times;</button>' +
        '</div>';
        
        // Remove previous warnings
        $('.so-duplicate-warn').remove();
        $('.so-form-area .so-section.active .so-fieldset:first').before(html);
    }

    /* ══════════════════════════════════════════
       CONDITIONAL FIELDS
       ══════════════════════════════════════════ */
    function initConditionalFields() {
        // مشترك بالضمان؟ → رقم اشتراك الضمان
        $(document).on('change', '#customers-is_social_security', function() {
            var v = $(this).val();
            $('.js-social-number-row').toggle(v == 1);
            if (v != 1) $('#customers-social_security_number').val('');
            triggerRiskCalc();
        });
        // يتقاضى رواتب تقاعدية؟ → مصدر الراتب + حقول التقاعد
        $(document).on('change', '#customers-has_social_security_salary', function() {
            var v = $(this).val();
            $('.js-salary-source-row').toggle(v == 'yes');
            if (v != 'yes') {
                $('#customers-social_security_salary_source').val('');
                $('#customers-retirement_status').val('');
                $('#customers-total_retirement_income').val('');
            }
            updateRetirementFieldsVisibility();
            triggerRiskCalc();
        });
        // مصدر الراتب → إظهار حقول التقاعد عند مديرية التقاعد أو كلاهما
        $(document).on('change', '#customers-social_security_salary_source', function() {
            updateRetirementFieldsVisibility();
            triggerRiskCalc();
        });
        function updateRetirementFieldsVisibility() {
            var hasSalary = $('#customers-has_social_security_salary').val() == 'yes';
            var source = $('#customers-social_security_salary_source').val();
            var showRetirement = hasSalary && (source === 'retirement_directorate' || source === 'both');
            $('.js-retirement-fields').toggle(showRetirement);
        }
        // يملك عقارات؟ → قسم العقارات
        $(document).on('change', '#customers-do_have_any_property', function() {
            $('.js-real-estate-section').toggle($(this).val() == 1);
            triggerRiskCalc();
        });
        // تطبيق الحالة الأولية عند تحميل الصفحة
        updateRetirementFieldsVisibility();
    }

    /* ══════════════════════════════════════════
       DOCUMENT UPLOAD ZONES (per-row)
       ══════════════════════════════════════════ */
    function initDocumentUploads() {
        $(document).off('click.dropzone change.dropzone dragover.dropzone dragleave.dropzone drop.dropzone', '.sm-doc-zone');
        $(document).on('click', '.sm-doc-zone .sm-doc-placeholder', function() {
            $(this).closest('.sm-doc-zone').find('input[type="file"]').click();
        });
        $(document).on('click', '.sm-doc-remove', function(e) {
            e.stopPropagation();
            var $zone = $(this).closest('.sm-doc-zone');
            $zone.find('.sm-doc-path-input').val('');
            $zone.find('.sm-doc-placeholder').show();
            $zone.find('.sm-doc-preview').hide();
        });
        $(document).on('change', '.sm-doc-zone input[type="file"]', function() {
            var file = this.files[0];
            if (file) uploadDocFile($(this).closest('.sm-doc-zone'), file);
            this.value = '';
        });
        $(document).on('dragover dragenter', '.sm-doc-zone', function(e) {
            e.preventDefault(); e.stopPropagation();
            $(this).addClass('dragover');
        });
        $(document).on('dragleave drop', '.sm-doc-zone', function(e) {
            e.preventDefault(); e.stopPropagation();
            $(this).removeClass('dragover');
        });
        $(document).on('drop', '.sm-doc-zone', function(e) {
            var file = e.originalEvent.dataTransfer.files[0];
            if (file) uploadDocFile($(this), file);
        });
    }
    function uploadDocFile($zone, file) {
        var allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (allowed.indexOf(file.type) === -1) {
            showToast('نوع الملف غير مدعوم', 'warning');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showToast('حجم الملف أكبر من 10MB', 'warning');
            return;
        }
        $zone.addClass('uploading');
        var formData = new FormData();
        formData.append('file', file);
        formData.append('customer_id', $('input[name="customer_id_for_media"]').val() || '');
        formData.append('auto_classify', '0');
        $.ajax({
            url: window.smConfig ? window.smConfig.uploadUrl : '/customers/smart-media/upload',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                $zone.removeClass('uploading');
                if (resp.success && resp.file && resp.file.path) {
                    $zone.find('.sm-doc-path-input').val(resp.file.path);
                    var $preview = $zone.find('.sm-doc-preview');
                    var isPdf = (resp.file.mime || '').indexOf('pdf') !== -1;
                    if (isPdf) {
                        $preview.addClass('is-pdf').find('img').hide();
                        if (!$preview.find('.sm-doc-pdf-label').length) {
                            $preview.prepend('<div class="sm-doc-pdf-label"><i class="fa fa-file-pdf-o"></i> PDF</div>');
                        }
                    } else {
                        var thumb = resp.file.thumb || resp.file.path;
                        var imgSrc = (thumb.indexOf('/') === 0 ? thumb : '/' + thumb);
                        $preview.removeClass('is-pdf').find('img').attr('src', imgSrc).show();
                        $preview.find('.sm-doc-pdf-label').remove();
                    }
                    $zone.find('.sm-doc-placeholder').hide();
                    $preview.show();
                } else {
                    showToast('فشل الرفع', 'danger');
                }
            },
            error: function() {
                $zone.removeClass('uploading');
                showToast('خطأ في الاتصال', 'danger');
            }
        });
    }

    /* ══════════════════════════════════════════
       DECISION ACTIONS
       ══════════════════════════════════════════ */
    $(document).on('click', '.so-decision-btn', function() {
        var decision = $(this).data('decision');
        var $form = $('#smart-onboarding-form');

        if (decision === 'draft') {
            // Save as draft — remove required validation
            $form.find('[required]').removeAttr('required').attr('data-was-required', '1');
            $form.append('<input type="hidden" name="save_decision" value="draft">');
            $form.submit();
            return;
        }

        if (decision === 'rejected') {
            var reason = prompt('أدخل سبب الرفض:');
            if (!reason) return;
            $form.append('<input type="hidden" name="decision_notes" value="' + escapeHtml(reason) + '">');
        }

        if (decision === 'conditional') {
            var notes = prompt('أدخل الشروط المطلوبة (كفيل/مستندات/إلخ):');
            if (notes) $form.append('<input type="hidden" name="decision_notes" value="' + escapeHtml(notes) + '">');
        }

        $form.append('<input type="hidden" name="save_decision" value="' + decision + '">');

        // Store risk data
        if (SO.riskData) {
            $form.append('<input type="hidden" name="risk_assessment" value=\'' + JSON.stringify(SO.riskData) + '\'>');
        }

        $form.submit();
    });

    /* ══════════════════════════════════════════
       HELPERS
       ══════════════════════════════════════════ */
    function formatMoney(n) {
        if (!n) return '0';
        return parseFloat(n).toLocaleString('ar-JO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ══════════════════════════════════════════
       JOB TITLE: Open create page in new tab
       ══════════════════════════════════════════ */
    $(document).on('click', '.btn-add-job', function() {
        window.open('/jobs/jobs/create', '_blank');
    });

    function showToast(msg, type) {
        var html = '<div class="so-inline-alert so-inline-alert-' + type + '">' +
            '<i class="fa fa-info-circle"></i>' +
            '<span>' + msg + '</span>' +
            '<button class="so-inline-alert-close" type="button">&times;</button>' +
        '</div>';
        var $alert = $(html);
        
        if (!$('.so-inline-alerts').length) {
            $('body').append('<div class="so-inline-alerts"></div>');
        }
        $('.so-inline-alerts').append($alert);
        
        $alert.find('.so-inline-alert-close').on('click', function() { $alert.fadeOut(200, function(){$(this).remove();}); });
        setTimeout(function() { $alert.fadeOut(300, function(){$(this).remove();}); }, 4000);
    }

})(jQuery);
