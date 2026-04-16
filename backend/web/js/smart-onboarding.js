/**
 * Smart Onboarding — Live Risk Assessment Panel
 * Handles: Wizard steps, live risk calculation, alerts, decision actions
 */
(function($) {
    'use strict';

    var isEditMode = window.soConfig && window.soConfig.isEditMode;

    var SO = {
        currentStep: 0,
        totalSteps: isEditMode ? 5 : 6,
        debounceTimer: null,
        saveTimer: null,
        riskData: null,
        panelExpanded: false,
        scanData: [],
        scanFileIds: [],
        scanFileTypes: {},
    };

    var STORAGE_KEY = 'so_form_draft';

    /* ══════════════════════════════════════════
       INITIALIZATION
       ══════════════════════════════════════════ */
    $(function() {
        initWizard();
        if (!isEditMode) initDocumentScan();
        initEditModeNav();
        initRiskPanel();
        initLiveValidation();
        initDuplicateCheck();
        initConditionalFields();
        initDocumentUploads();
        initFormPersistence();
        triggerRiskCalc();
        if (isEditMode) initEditWarnings();
        initJobAjaxSelect2();
        initDocChecklist();
        initHelpTooltips();
    });

    /* ══════════════════════════════════════════
       WIZARD NAVIGATION
       ══════════════════════════════════════════ */
    function initWizard() {
        showStep(0);

        $(document).on('click', '.so-step', function() {
            var idx = $(this).data('step');
            if (isEditMode || idx <= getMaxReachedStep()) goToStep(idx);
        });

        $(document).on('click', '.so-next-btn', function() {
            if (isEditMode || validateCurrentStep()) goToStep(SO.currentStep + 1);
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
        var $newSection = $('.so-section[data-step="' + idx + '"]');
        $newSection.addClass('active');

        $('.so-step').removeClass('active');
        var $activeStep = $('.so-step[data-step="' + idx + '"]');
        $activeStep.addClass('active');

        // ARIA: update step indicators
        $('.so-step').attr('aria-current', 'false');
        $activeStep.attr('aria-current', 'step');

        // In edit mode, mark all steps as completed (data already exists)
        if (window.soConfig && window.soConfig.isEditMode) {
            $('.so-step').each(function() {
                if ($(this).data('step') !== idx) {
                    $(this).addClass('completed');
                }
            });
        }

        // Focus management: move focus to the section heading or first input
        setTimeout(function() {
            var $title = $newSection.find('.so-fieldset-title').first();
            if ($title.length) {
                if (!$title.attr('tabindex')) $title.attr('tabindex', '-1');
                $title[0].focus({ preventScroll: true });
            } else {
                var $firstInput = $newSection.find('input:not([type=hidden]),select,textarea').first();
                if ($firstInput.length) $firstInput[0].focus({ preventScroll: true });
            }
            $newSection[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);

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

        // Fix Leaflet maps inside newly visible step
        setTimeout(function() {
            $section.find('.leaflet-container').each(function() {
                var mapInstance = $(this).data('leafletMap');
                if (mapInstance) {
                    mapInstance.invalidateSize();
                } else if (this._leaflet_id && L && L.Map) {
                    // Fallback: trigger map:show on the parent panel
                    $(this).closest('.addrres-item').trigger('map:show');
                }
            });
        }, 350);

        // Update progress indicator
        updateProgressBar(idx);

        // Sync scanned docs to hub when entering Step 5 (or 4 in edit mode)
        var docHubStep = isEditMode ? 4 : 5;
        if (idx === docHubStep) {
            syncScanToHub();
            updateDocChecklist();
        }

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
        if (SO.currentStep === 0 && !isEditMode) return true;

        var $section = $('.so-section[data-step="' + SO.currentStep + '"]');
        var valid = true;
        var missingLabels = [];
        $section.find('[required]').each(function() {
            var $f = $(this);
            if (!$f.val() || $f.val() === '') {
                $f.closest('.form-group').addClass('has-error');
                var label = $f.closest('.form-group').find('label').first().text().replace(/\s*\*\s*$/, '').trim();
                if (label) missingLabels.push(label);
                valid = false;
            } else {
                $f.closest('.form-group').removeClass('has-error');
            }
        });
        if (!valid) {
            var msg = missingLabels.length
                ? 'الحقول المطلوبة: ' + missingLabels.join('، ')
                : 'يرجى ملء الحقول المطلوبة';
            showToast(msg, 'warning');
            $section.find('.has-error input,.has-error select,.has-error textarea').first().focus();
        }
        return valid;
    }

    function saveStepState() {
        try { localStorage.setItem('so_step', SO.currentStep); } catch(e){}
    }

    /* ══════════════════════════════════════════
       DOCUMENT SCAN — Step 0 (Create mode only)
       ══════════════════════════════════════════ */
    function initDocumentScan() {
        var $zone = $('#scanDropZone');
        var $input = $('#scanFileInput');
        var $gallery = $('#scanGallery');
        var $results = $('#scanResults');
        var $processing = $('#scanProcessing');

        if (!$zone.length) return;

        var DOC_LABELS = {
            '0': 'هوية', '1': 'جواز سفر', '2': 'رخصة قيادة',
            '3': 'شهادة ميلاد', '4': 'شهادة تعيين'
        };

        var FIELD_LABELS = {
            name: 'الاسم', id_number: 'الرقم الوطني', birth_date: 'تاريخ الميلاد',
            sex: 'الجنس', birth_place: 'مكان الولادة', nationality_text: 'الجنسية',
            mother_name: 'اسم الأم', address: 'العنوان', name_en: 'الاسم (إنجليزي)',
            job_number: 'الرقم الوظيفي', rank: 'الرتبة', employer_name: 'جهة العمل',
            doc_type: 'نوع الوثيقة', doc_number: 'رقم الوثيقة'
        };

        // Drag & drop
        $zone.on('dragover dragenter', function(e) {
            e.preventDefault(); e.stopPropagation();
            $(this).addClass('dragover');
        }).on('dragleave drop', function(e) {
            e.preventDefault(); e.stopPropagation();
            $(this).removeClass('dragover');
        }).on('drop', function(e) {
            scanFiles(e.originalEvent.dataTransfer.files);
        });

        // Click / keyboard — stop propagation from $input to prevent infinite loop
        $input.on('click', function(e) { e.stopPropagation(); });
        $zone.on('click', function() { $input.click(); });
        $zone.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $input.click(); }
        });
        $input.on('change', function() {
            scanFiles(this.files);
            this.value = '';
        });

        // Camera button → reuse smart-media webcam
        $('#scanCameraBtn').on('click', function() {
            $input.attr('capture', 'environment');
            $input.click();
            setTimeout(function() { $input.removeAttr('capture'); }, 500);
        });

        // Clipboard paste — Ctrl+V anywhere on page, or via button
        $(document).on('paste', function(e) {
            if (SO.currentStep !== 0) return;
            var items = (e.originalEvent.clipboardData || e.clipboardData || {}).items;
            if (!items) return;
            var files = [];
            for (var i = 0; i < items.length; i++) {
                if (items[i].kind === 'file') {
                    var f = items[i].getAsFile();
                    if (f) files.push(f);
                }
            }
            if (files.length) {
                e.preventDefault();
                scanFiles(files);
                showToast('تم لصق ' + files.length + ' ملف من الحافظة', 'info');
            }
        });

        $('#scanPasteBtn').on('click', function() {
            if (navigator.clipboard && navigator.clipboard.read) {
                navigator.clipboard.read().then(function(clipItems) {
                    var processed = 0;
                    clipItems.forEach(function(item) {
                        var imgType = item.types.find(function(t) { return t.startsWith('image/'); });
                        if (imgType) {
                            item.getType(imgType).then(function(blob) {
                                var ext = imgType.split('/')[1] || 'png';
                                var file = new File([blob], 'clipboard_' + Date.now() + '.' + ext, { type: imgType });
                                scanFiles([file]);
                                processed++;
                                if (processed === 1) showToast('تم لصق الصورة من الحافظة', 'info');
                            });
                        }
                    });
                    if (!clipItems.length) {
                        showToast('لا توجد صور في الحافظة', 'warning');
                    }
                }).catch(function() {
                    showToast('اضغط Ctrl+V للصق — أو امنح صلاحية الحافظة', 'warning');
                });
            } else {
                showToast('اضغط Ctrl+V للصق الصورة من الحافظة', 'info');
            }
        });

        // Apply extracted data
        $('#scanApplyBtn').on('click', function() {
            applyExtractedFields();
            goToStep(1);
            $('#scanContinueBtn').show();
        });

        // Add more docs
        $('#scanAddMoreBtn').on('click', function() {
            $zone.show();
            $('.scan-actions').show();
        });

        function scanFiles(fileList) {
            for (var i = 0; i < fileList.length; i++) {
                processScanFile(fileList[i]);
            }
        }

        var scanSeq = 0; // unique ID per card (avoids Date.now collisions)

        function processScanFile(file) {
            var allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (allowed.indexOf(file.type) === -1) {
                showToast('نوع الملف غير مدعوم', 'error');
                return;
            }

            var cardId = 'scan_' + (++scanSeq) + '_' + Date.now();
            var isPdf = file.type === 'application/pdf';
            var thumbSrc = isPdf ? '/css/images/pdf-icon.png' : URL.createObjectURL(file);

            var $card = $('<div class="scan-card processing" id="' + cardId + '">' +
                '<img class="scan-card-img" src="' + thumbSrc + '" alt="">' +
                '<div class="scan-card-spinner"><i class="fa fa-spinner fa-spin"></i></div>' +
                '<div class="scan-card-body">' +
                '<div class="scan-card-type">جاري التحليل...</div>' +
                '<div class="scan-card-status">0%</div>' +
                '</div></div>');
            $gallery.append($card);

            var formData = new FormData();
            formData.append('file', file);
            formData.append('auto_classify', '1');

            $.ajax({
                url: window.smConfig.extractFieldsUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var pct = Math.round((e.loaded / e.total) * 30);
                            $('#' + cardId + ' .scan-card-status').text(pct + '%');
                        }
                    });
                    return xhr;
                },
                success: function(resp) {
                    var $c = $('#' + cardId);
                    if (resp.success) {
                        var src = (resp.extraction && resp.extraction.meta) ? resp.extraction.meta.source : '?';
                        console.log('[Scan] ' + file.name + ' → ' + src);
                        var ext = resp.extraction || {};
                        var docLabel = DOC_LABELS[ext.document && ext.document.type] || 'مستند';

                        $c.removeClass('processing').addClass('success');
                        $c.find('.scan-card-spinner').remove();
                        $c.find('.scan-card-type').text(docLabel);
                        $c.find('.scan-card-status').text(ext.meta ? ext.meta.fields_extracted + ' حقل' : '');
                        $c.prepend('<button type="button" class="scan-card-remove" title="حذف"><i class="fa fa-times"></i></button>');

                        if (resp.file && resp.file.id) {
                            SO.scanFileIds.push(resp.file.id);
                            $c.attr('data-image-id', resp.file.id);
                            var detectedType = (ext.document && ext.document.type) || '9';
                            SO.scanFileTypes[resp.file.id] = {
                                type: detectedType,
                                thumb: (resp.file && resp.file.thumb) || thumbSrc,
                                fileName: file.name
                            };
                        }

                        mergeExtractionData(ext);
                        renderScanResults();

                        if (!isPdf) URL.revokeObjectURL(thumbSrc);
                        if (resp.file && resp.file.thumb) {
                            $c.find('.scan-card-img').attr('src', resp.file.thumb);
                        }

                        showToast('تم تحليل: ' + file.name, 'success');
                    } else {
                        $c.removeClass('processing').addClass('error');
                        $c.find('.scan-card-spinner').remove();
                        $c.find('.scan-card-type').text('فشل');
                        $c.find('.scan-card-status').text(resp.error || 'خطأ');
                        showToast(resp.error || 'فشل في تحليل الوثيقة', 'error');
                    }

                    updateGlobalProcessingState();
                },
                error: function() {
                    var $c = $('#' + cardId);
                    $c.removeClass('processing').addClass('error');
                    $c.find('.scan-card-spinner').remove();
                    $c.find('.scan-card-type').text('خطأ في الاتصال');
                    updateGlobalProcessingState();
                }
            });
        }

        function updateGlobalProcessingState() {
            var processing = $gallery.find('.scan-card.processing').length;
            if (processing > 0) {
                $processing.show().find('.scan-processing-text').text('جاري تحليل ' + processing + ' وثيقة...');
            } else {
                $processing.hide();
            }
        }

        // Processing step indicator removed — replaced by per-card spinner + updateGlobalProcessingState

        function mergeExtractionData(ext) {
            SO.scanData.push(ext);
        }

        function getMergedFields() {
            var merged = { customer: {}, document: [], job: {} };

            for (var i = 0; i < SO.scanData.length; i++) {
                var d = SO.scanData[i];

                // Customer fields: first non-empty wins
                if (d.customer) {
                    for (var k in d.customer) {
                        if (d.customer[k] && (!merged.customer[k] || merged.customer[k] === '')) {
                            merged.customer[k] = d.customer[k];
                        }
                    }
                }

                // Job fields: first non-empty wins
                if (d.job) {
                    for (var j in d.job) {
                        if (d.job[j] && (!merged.job[j] || merged.job[j] === '')) {
                            merged.job[j] = d.job[j];
                        }
                    }
                }

                // Documents: accumulate each as separate record
                if (d.document && (d.document.type !== undefined && d.document.type !== '')) {
                    merged.document.push({
                        type: d.document.type,
                        type_label: d.document.type_label || '',
                        number: d.document.number || d.document.certificate_number || '',
                    });
                }
            }

            return merged;
        }

        function renderScanResults() {
            var merged = getMergedFields();
            var html = '';
            var fieldCount = 0;

            // Customer fields
            var sexVal = merged.customer.sex;
            var sexDisplay = (sexVal !== undefined && sexVal !== null && sexVal !== '') ? (sexVal == 1 ? 'أنثى' : 'ذكر') : '';
            var custFields = [
                ['name', merged.customer.name],
                ['id_number', merged.customer.id_number],
                ['birth_date', merged.customer.birth_date],
                ['sex', sexDisplay],
                ['birth_place', merged.customer.birth_place],
                ['nationality_text', merged.customer.nationality_text],
                ['mother_name', merged.customer.mother_name],
                ['name_en', merged.customer.name_en],
            ];
            for (var i = 0; i < custFields.length; i++) {
                var key = custFields[i][0], val = custFields[i][1];
                if (val !== undefined && val !== null && val !== '') {
                    var isArabic = /[\u0600-\u06FF]/.test(val);
                    html += '<div class="scan-field">' +
                        '<span class="scan-field-icon"><i class="fa fa-check-circle"></i></span>' +
                        '<span class="scan-field-label">' + (FIELD_LABELS[key] || key) + '</span>' +
                        '<span class="scan-field-value' + (isArabic ? ' rtl-val' : '') + '">' + escapeHtml(val) + '</span>' +
                        '</div>';
                    fieldCount++;
                }
            }

            // Job fields
            if (merged.job.employer_name) {
                html += '<div class="scan-field"><span class="scan-field-icon"><i class="fa fa-check-circle"></i></span>' +
                    '<span class="scan-field-label">' + FIELD_LABELS.employer_name + '</span>' +
                    '<span class="scan-field-value rtl-val">' + escapeHtml(merged.job.employer_name) + '</span></div>';
                fieldCount++;
            }
            if (merged.job.job_number) {
                html += '<div class="scan-field"><span class="scan-field-icon"><i class="fa fa-check-circle"></i></span>' +
                    '<span class="scan-field-label">' + FIELD_LABELS.job_number + '</span>' +
                    '<span class="scan-field-value">' + escapeHtml(merged.job.job_number) + '</span></div>';
                fieldCount++;
            }

            // Document records
            for (var d = 0; d < merged.document.length; d++) {
                var doc = merged.document[d];
                html += '<div class="scan-field"><span class="scan-field-icon"><i class="fa fa-file-o"></i></span>' +
                    '<span class="scan-field-label">' + (doc.type_label || 'مستند') + '</span>' +
                    '<span class="scan-field-value">' + escapeHtml(doc.number) + '</span></div>';
                fieldCount++;
            }

            $('#scanFieldsList').html(html);
            $('#scanResultsStatus').text('تم استخراج ' + fieldCount + ' حقل من ' + SO.scanData.length + ' وثيقة');
            $results.show();
        }

        function applyExtractedFields() {
            var merged = getMergedFields();
            var c = merged.customer;

            // Customer model fields
            if (c.name) $('#customers-name').val(c.name).trigger('change');
            if (c.id_number) $('#customers-id_number').val(c.id_number).trigger('change');
            if (c.birth_date) {
                var $bd = $('#customers-birth_date');
                $bd.val(c.birth_date);
                if ($bd[0] && $bd[0]._flatpickr) $bd[0]._flatpickr.setDate(c.birth_date);
            }
            if (c.sex !== undefined) $('#customers-sex').val(c.sex).trigger('change');

            // City matching (birth_place → city dropdown)
            if (c.birth_place) {
                matchDropdownByText('#customers-city', c.birth_place);
            }

            // Nationality matching (nationality_text → citizen dropdown)
            if (c.nationality_text) {
                matchDropdownByText('#customers-citizen', c.nationality_text);
            } else if (c.nationality === 'JOR') {
                matchDropdownByText('#customers-citizen', 'أردني');
            }

            // Job fields
            if (merged.job.job_number) {
                $('#customers-job_number').val(merged.job.job_number).trigger('change');
            }

            // Job title (employer) — try to find matching option in Select2
            if (merged.job.employer_name) {
                matchJobByName(merged.job.employer_name);
            }

            // Document records — fill first available CustomersDocument row
            for (var d = 0; d < merged.document.length; d++) {
                var doc = merged.document[d];
                fillDocumentRow(d, doc.type, doc.number);
            }

            // Also add scanned images to SmartMedia hidden inputs
            for (var f = 0; f < SO.scanFileIds.length; f++) {
                addSmartMediaId(SO.scanFileIds[f]);
            }

            showToast('تم تعبئة ' + Object.keys(c).length + ' حقل تلقائياً — يرجى المراجعة', 'success');
        }

        function matchDropdownByText(selector, text) {
            var $sel = $(selector);
            var textLower = text.trim();
            $sel.find('option').each(function() {
                var optText = $(this).text().trim();
                if (optText === textLower || optText.indexOf(textLower) !== -1 || textLower.indexOf(optText) !== -1) {
                    $sel.val($(this).val()).trigger('change');
                    return false;
                }
            });
        }

        function matchJobByName(name) {
            // Search for job in the AJAX Select2
            var $jobSel = $('#customers-job_title');
            if (!$jobSel.length) return;

            $.ajax({
                url: window.soConfig.searchListUrl,
                data: { q: name },
                dataType: 'json',
                success: function(data) {
                    if (data && data.results && data.results.length > 0) {
                        var match = data.results[0];
                        var newOption = new Option(match.text, match.id, true, true);
                        $jobSel.append(newOption).trigger('change');
                    }
                }
            });
        }

        function fillDocumentRow(index, type, number) {
            var $rows = $('.customer-documents-item');
            var $row;

            if (index < $rows.length) {
                $row = $rows.eq(index);
            } else {
                // Click "add" button to create new row
                $('.customer-documents-add-item').click();
                $row = $('.customer-documents-item').last();
            }

            if ($row.length) {
                $row.find('select[name*="document_type"]').val(type).trigger('change');
                $row.find('input[name*="document_number"]').val(number).trigger('change');
            }
        }

        function addSmartMediaId(imageId) {
            var $form = $('#smart-onboarding-form');
            if (!$form.find('input[name="SmartMedia[]"][value="' + imageId + '"]').length) {
                $form.append('<input type="hidden" name="SmartMedia[]" value="' + imageId + '">');
            }
        }

        // Lightbox — click card image to preview full size
        $(document).on('click', '.scan-card-img', function(e) {
            e.stopPropagation();
            var src = $(this).attr('src');
            if (!src || src.indexOf('pdf-icon') !== -1) return;

            var $overlay = $('#scanLightbox');
            if (!$overlay.length) {
                $overlay = $(
                    '<div id="scanLightbox" class="scan-lightbox">' +
                        '<div class="scan-lightbox-backdrop"></div>' +
                        '<div class="scan-lightbox-content">' +
                            '<img src="" alt="">' +
                            '<button type="button" class="scan-lightbox-close"><i class="fa fa-times"></i></button>' +
                        '</div>' +
                    '</div>'
                );
                $('body').append($overlay);
                $overlay.on('click', '.scan-lightbox-backdrop, .scan-lightbox-close', function() {
                    $overlay.removeClass('open');
                });
                $(document).on('keydown.scanLightbox', function(ev) {
                    if (ev.key === 'Escape') $overlay.removeClass('open');
                });
            }
            $overlay.find('img').attr('src', src);
            $overlay.addClass('open');
        });

        // Remove scanned card
        $(document).on('click', '.scan-card-remove', function(e) {
            e.stopPropagation();
            var $card = $(this).closest('.scan-card');
            var imgId = $card.attr('data-image-id');
            var idx = $card.index();

            $card.remove();

            // Remove from data arrays
            if (idx >= 0 && idx < SO.scanData.length) {
                SO.scanData.splice(idx, 1);
            }
            if (imgId) {
                var pos = SO.scanFileIds.indexOf(parseInt(imgId));
                if (pos !== -1) SO.scanFileIds.splice(pos, 1);
            }

            if (SO.scanData.length > 0) {
                renderScanResults();
            } else {
                $results.hide();
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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
        data.job_title       = $f.find('#customers-job_title').val();
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
       ACCESSIBLE MODAL
       ══════════════════════════════════════════ */
    window.soModal = soModal;
    function soModal(opts) {
        var id = 'soModal_' + Date.now();
        var html = '<div class="so-modal-overlay" id="' + id + '" role="dialog" aria-modal="true" aria-labelledby="' + id + '_title">' +
            '<div class="so-modal">' +
                '<div class="so-modal-header">' +
                    '<h4 class="so-modal-title" id="' + id + '_title">' + (opts.title || '') + '</h4>' +
                    '<button type="button" class="so-modal-close" aria-label="إغلاق">&times;</button>' +
                '</div>' +
                '<div class="so-modal-body">' + (opts.body || '') + '</div>' +
                '<div class="so-modal-footer">' +
                    '<button type="button" class="so-btn so-btn-outline so-modal-cancel">' + (opts.cancelText || 'إلغاء') + '</button>' +
                    '<button type="button" class="so-btn ' + (opts.confirmClass || 'so-btn-primary') + ' so-modal-confirm">' + (opts.confirmText || 'تأكيد') + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
        var $overlay = $(html).appendTo('body');
        var $modal = $overlay.find('.so-modal');
        var $trigger = $(document.activeElement);

        setTimeout(function() { $overlay.addClass('visible'); }, 10);

        var $focusable = $modal.find('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        var $first = $focusable.first(), $last = $focusable.last();
        $modal.find('input,textarea,select').first().focus();
        if (!$modal.find('input,textarea,select').length) $modal.find('.so-modal-confirm').focus();

        function close() {
            $overlay.removeClass('visible');
            setTimeout(function() { $overlay.remove(); }, 200);
            if ($trigger.length) $trigger.focus();
        }

        $modal.on('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === $first[0]) { e.preventDefault(); $last.focus(); }
                else if (!e.shiftKey && document.activeElement === $last[0]) { e.preventDefault(); $first.focus(); }
            }
            if (e.key === 'Escape') close();
        });

        $overlay.on('click', '.so-modal-close, .so-modal-cancel', close);
        $overlay.on('click', function(e) { if (e.target === $overlay[0]) close(); });
        $overlay.on('click', '.so-modal-confirm', function() {
            if (opts.onConfirm) opts.onConfirm($modal, close);
            else close();
        });
    }

    /* ══════════════════════════════════════════
       DECISION ACTIONS
       ══════════════════════════════════════════ */
    $(document).on('click', '.so-decision-btn', function() {
        var decision = $(this).data('decision');
        var $form = $('#smart-onboarding-form');

        if (decision === 'draft') {
            $form.find('[required]').removeAttr('required').attr('data-was-required', '1');
            $form.append('<input type="hidden" name="save_decision" value="draft">');
            $form.submit();
            return;
        }

        if (decision === 'rejected') {
            soModal({
                title: 'سبب الرفض',
                body: '<textarea id="soModalReason" class="form-control" rows="3" placeholder="أدخل سبب الرفض..." dir="rtl"></textarea>',
                confirmText: 'تأكيد الرفض',
                confirmClass: 'so-btn-danger',
                onConfirm: function($m, closeFn) {
                    var reason = $m.find('#soModalReason').val();
                    if (!reason) { $m.find('#soModalReason').focus(); return; }
                    $form.append('<input type="hidden" name="decision_notes" value="' + escapeHtml(reason) + '">');
                    submitDecision(decision, $form);
                    closeFn();
                }
            });
            return;
        }

        if (decision === 'conditional') {
            soModal({
                title: 'الشروط المطلوبة',
                body: '<textarea id="soModalNotes" class="form-control" rows="3" placeholder="أدخل الشروط المطلوبة (كفيل/مستندات/إلخ)..." dir="rtl"></textarea>',
                confirmText: 'حفظ بشروط',
                onConfirm: function($m, closeFn) {
                    var notes = $m.find('#soModalNotes').val();
                    if (notes) $form.append('<input type="hidden" name="decision_notes" value="' + escapeHtml(notes) + '">');
                    submitDecision(decision, $form);
                    closeFn();
                }
            });
            return;
        }

        submitDecision(decision, $form);
    });

    function submitDecision(decision, $form) {
        $form.append('<input type="hidden" name="save_decision" value="' + decision + '">');
        if (SO.riskData) {
            $form.append('<input type="hidden" name="risk_assessment" value=\'' + JSON.stringify(SO.riskData) + '\'>');
        }
        $form.submit();
    }

    /* ══════════════════════════════════════════
       EDIT MODE — SOFT WARNINGS (non-blocking)
       ══════════════════════════════════════════ */
    var WARN_FIELDS = [
        {id: 'customers-name',                 label: 'اسم العميل'},
        {id: 'customers-id_number',            label: 'الرقم الوطني'},
        {id: 'customers-birth_date',           label: 'تاريخ الميلاد'},
        {id: 'customers-primary_phone_number', label: 'الهاتف الرئيسي'},
        {id: 'customers-city',                 label: 'مدينة الولادة'},
        {id: 'customers-citizen',              label: 'الجنسية'},
        {id: 'customers-job_title',            label: 'المسمى الوظيفي'},
    ];

    function initEditWarnings() {
        WARN_FIELDS.forEach(function(f) {
            var el = document.getElementById(f.id);
            if (!el) return;
            applyWarning(el, f.label);
            $(el).on('change input', function() { applyWarning(el, f.label); });
        });

        $('#smart-onboarding-form').on('submit', function() {
            var missing = [];
            WARN_FIELDS.forEach(function(f) {
                var el = document.getElementById(f.id);
                if (el && !$(el).val()) missing.push(f.label);
            });
            if (missing.length) {
                showToast('تنبيه: الحقول التالية غير مكتملة: ' + missing.join('، '), 'warning');
            }
        });
    }

    function applyWarning(el, label) {
        var $group = $(el).closest('.form-group');
        var val = $(el).val();
        if (!val || val === '') {
            if (!$group.hasClass('so-warn')) {
                $group.addClass('so-warn');
                if (!$group.find('.so-warn-hint').length) {
                    $group.append('<span class="so-warn-hint"><i class="fa fa-exclamation-triangle"></i> يُفضّل تعبئة هذا الحقل</span>');
                }
            }
        } else {
            $group.removeClass('so-warn');
            $group.find('.so-warn-hint').remove();
        }
    }

    /* ══════════════════════════════════════════
       FORM PERSISTENCE — localStorage auto-draft
       ══════════════════════════════════════════ */
    function initFormPersistence() {
        if (window.soConfig && window.soConfig.isEditMode) return;

        var $form = $('#smart-onboarding-form');

        restoreFormData();

        $form.on('change input', 'input, select, textarea', function() {
            if (this.type === 'file') return;
            clearTimeout(SO.saveTimer);
            SO.saveTimer = setTimeout(saveFormData, 400);
        });

        $form.on('submit', function() {
            clearFormData();
            if (SO.scanFileIds.length) {
                $('#scanFileIdsInput').val(SO.scanFileIds.join(','));
            }
        });

        $(document).on('click', '.so-reset-btn', function() {
            soModal({
                title: 'إعادة تعيين النموذج',
                body: '<p style="font-size:14px;color:#991b1b;text-align:right"><i class="fa fa-exclamation-triangle"></i> هل أنت متأكد من إعادة تعيين جميع الحقول؟<br>سيتم حذف جميع البيانات المدخلة.</p>',
                confirmText: 'نعم، إعادة التعيين',
                confirmClass: 'so-btn-danger',
                onConfirm: function($m, closeFn) {
                    closeFn();
                    clearFormData();
                    $form[0].reset();
                    $form.find('select').each(function() {
                        $(this).val('');
                        if ($(this).data('select2')) $(this).trigger('change.select2');
                    });
                    $form.find('.form-group').removeClass('has-error has-success so-warn');
                    $form.find('.so-warn-hint, .so-duplicate-warn').remove();
                    $('#customers-is_social_security').trigger('change');
                    $('#customers-has_social_security_salary').trigger('change');
                    $('#customers-do_have_any_property').trigger('change');
                    goToStep(0);
                    triggerRiskCalc();
                    showToast('تم إعادة تعيين النموذج بالكامل', 'warning');
                }
            });
        });
    }

    function saveFormData() {
        if (window.soConfig && window.soConfig.isEditMode) return;
        try {
            var $form = $('#smart-onboarding-form');
            var data = {};
            var SKIP = {'_csrf-backend':1, save_decision:1, risk_assessment:1, decision_notes:1, customer_id_for_media:1};

            $form.find('input, select, textarea').each(function() {
                var name = this.name;
                if (!name || SKIP[name]) return;
                if (this.type === 'file' || this.type === 'hidden' && name.indexOf('[') === -1 && !this.id) return;

                if (this.type === 'checkbox') {
                    data[name] = this.checked ? this.value : '';
                } else if (this.type === 'radio') {
                    if (this.checked) data[name] = this.value;
                } else {
                    data[name] = $(this).val();
                }
            });

            data['__so_step'] = SO.currentStep;
            data['__so_ts'] = Date.now();

            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            updateDraftBadge(true);
        } catch(e) {
            console.warn('Form save error:', e);
        }
    }

    function restoreFormData() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;

            var data = JSON.parse(raw);
            if (!data || typeof data !== 'object') return;

            var age = Date.now() - (data['__so_ts'] || 0);
            if (age > 7 * 24 * 60 * 60 * 1000) {
                clearFormData();
                return;
            }

            var $form = $('#smart-onboarding-form');
            var restored = 0;

            var elMap = {};
            $form.find('input, select, textarea').each(function() {
                if (this.name && this.type !== 'file') {
                    if (!elMap[this.name]) elMap[this.name] = [];
                    elMap[this.name].push(this);
                }
            });

            Object.keys(data).forEach(function(name) {
                if (name.charAt(0) === '_' && name.charAt(1) === '_') return;
                var val = data[name];
                if (val === null || val === undefined) return;

                var els = elMap[name];
                if (!els || !els.length) return;

                var el = els[0];
                if (el.type === 'checkbox') {
                    el.checked = !!val && val !== '';
                } else if (el.type === 'radio') {
                    els.forEach(function(r) { r.checked = (r.value === val); });
                } else {
                    $(el).val(val);
                }
                restored++;
            });

            if (restored > 0) {
                setTimeout(function() {
                    $form.find('select').each(function() {
                        if ($(this).data('select2')) {
                            $(this).trigger('change.select2');
                        }
                    });

                    $('#customers-is_social_security').trigger('change');
                    $('#customers-has_social_security_salary').trigger('change');
                    $('#customers-do_have_any_property').trigger('change');
                }, 300);

                var savedStep = parseInt(data['__so_step']) || 0;
                if (savedStep > 0 && savedStep < SO.totalSteps) {
                    goToStep(savedStep);
                }

                updateDraftBadge(true);
                showToast('تم استعادة البيانات المحفوظة تلقائياً — يمكنك المتابعة من حيث توقفت', 'info');
            }
        } catch(e) {
            console.warn('Form restore error:', e);
        }
    }

    function clearFormData() {
        try {
            localStorage.removeItem(STORAGE_KEY);
            localStorage.removeItem('so_step');
            updateDraftBadge(false);
        } catch(e) {}
    }

    function updateDraftBadge(hasDraft) {
        var $badge = $('.so-draft-badge');
        if (hasDraft) {
            if (!$badge.length) {
                $badge = $('<span class="so-draft-badge"><i class="fa fa-floppy-o"></i> مسودة محفوظة</span>');
                $('.so-header h1').after($badge);
            }
            $badge.show();
        } else {
            $badge.hide();
        }
    }

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
       JOB TITLE: AJAX Select2 initialization
       ══════════════════════════════════════════ */
    function initJobAjaxSelect2() {
        var $sel = $('#customers-job_title');
        if (!$sel.length) return;

        var searchUrl = (window.soConfig && window.soConfig.searchListUrl) || '/jobs/jobs/search-list';

        setTimeout(function() {
            if ($sel.hasClass('select2-hidden-accessible')) { try { $sel.select2('destroy'); } catch(e) {} }
            $sel.select2({
                theme: 'bootstrap4',
                placeholder: '-- ابحث عن جهة العمل --',
                allowClear: true,
                dir: 'rtl',
                minimumInputLength: 0,
                ajax: {
                    url: searchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return {q: params.term}; },
                    processResults: function(data) { return data; }
                }
            });
        }, 150);
    }

    /* ══════════════════════════════════════════
       JOB TITLE: Open full create page + cross-tab sync
       ══════════════════════════════════════════ */
    $(document).on('click', '.btn-add-job', function() {
        window.open('/jobs/jobs/create', '_blank');
    });

    function selectJobInDropdown(id, text) {
        var $sel = $('#customers-job_title');
        var newOption = new Option(text, id, true, true);
        $sel.append(newOption).trigger('change');
        fetchJobInfo(id);
    }

    // BroadcastChannel: auto-select job when created/updated in another tab
    try {
        var jobChannel = new BroadcastChannel('tayseer_jobs');
        jobChannel.onmessage = function(e) {
            if (e.data && e.data.id && e.data.name) {
                selectJobInDropdown(e.data.id, e.data.name);
                showToast('تم إضافة "' + e.data.name + '" — تم اختيارها تلقائياً', 'success');
            }
        };
    } catch(e) {}

    // localStorage fallback for browsers without BroadcastChannel
    window.addEventListener('storage', function(e) {
        if (e.key === 'tayseer_job_saved') {
            try {
                var d = JSON.parse(e.newValue);
                if (d && d.id && d.name) {
                    selectJobInDropdown(d.id, d.name);
                    showToast('تم إضافة "' + d.name + '" — تم اختيارها تلقائياً', 'success');
                }
            } catch(e2) {}
        }
    });

    /* ══════════════════════════════════════════
       JOB INFO: fetch updated_at / created_at + staleness warning
       ══════════════════════════════════════════ */
    function fetchJobInfo(jobId) {
        var $info  = $('#job-update-info');
        var $badge = $('#job-update-badge');

        if (!jobId) {
            $info.hide();
            return;
        }

        var url = (window.soConfig && window.soConfig.jobInfoUrl)
            ? window.soConfig.jobInfoUrl.replace('__JOB_ID__', jobId)
            : '/jobs/jobs/job-info?id=' + jobId;

        var editUrl = '/jobs/jobs/update?id=' + jobId;
        var editBtn = '<a href="' + editUrl + '" target="_blank" class="job-update-btn">' +
            '<i class="fa fa-pencil"></i> تحديث بيانات الجهة</a>';

        $.getJSON(url).done(function(res) {
            if (!res.success) { $info.hide(); return; }

            var dateLabel = res.date_label || 'آخر تحديث';
            var dateVal   = res.display_date;
            var months    = res.months_since || 0;

            if (!dateVal) {
                $badge.html('<i class="fa fa-question-circle"></i> لا تتوفر بيانات تاريخية لهذه الجهة');
                $badge.attr('class', 'job-update-badge job-update-stale');
            } else if (months >= 3) {
                $badge.html(
                    '<i class="fa fa-exclamation-triangle"></i> ' +
                    dateLabel + ': <strong>' + escHtml(dateVal) + '</strong>' +
                    ' — <span class="job-stale-warn">مضى ' + months + ' شهر، يُنصح بتحديث البيانات</span>'
                );
                $badge.attr('class', 'job-update-badge job-update-stale');
            } else {
                $badge.html(
                    '<i class="fa fa-check-circle"></i> ' +
                    dateLabel + ': <strong>' + escHtml(dateVal) + '</strong>'
                );
                $badge.attr('class', 'job-update-badge job-update-fresh');
            }

            $info.find('.job-update-btn').remove();
            $info.append(editBtn);
            $info.show();
        });
    }

    $(document).on('change', '#customers-job_title', function() {
        fetchJobInfo($(this).val());
    });

    $(function() {
        var initial = $('#customers-job_title').val();
        if (initial) fetchJobInfo(initial);
    });

    function showToast(msg, type) {
        var html = '<div class="so-inline-alert so-inline-alert-' + type + '" role="alert">' +
            '<i class="fa fa-info-circle"></i>' +
            '<span>' + msg + '</span>' +
            '<button class="so-inline-alert-close" type="button" aria-label="إغلاق">&times;</button>' +
        '</div>';
        var $alert = $(html);
        
        if (!$('.so-inline-alerts').length) {
            $('body').append('<div class="so-inline-alerts" aria-live="assertive"></div>');
        }
        $('.so-inline-alerts').append($alert);
        
        $alert.find('.so-inline-alert-close').on('click', function() { $alert.fadeOut(200, function(){$(this).remove();}); });
        setTimeout(function() { $alert.fadeOut(300, function(){$(this).remove();}); }, 4000);
    }

    /* ══════════════════════════════════════════
       SCAN → DOCUMENT HUB AUTO-LINK
       ══════════════════════════════════════════ */
    function syncScanToHub() {
        if (!SO.scanFileIds.length) return;
        var $gallery = $('.sm-gallery');
        var existingIds = [];
        $gallery.find('.sm-card[data-image-id]').each(function() {
            existingIds.push(String($(this).data('image-id')));
        });

        var DOC_TYPES_MAP = {
            '0': 'هوية وطنية', '1': 'جواز سفر', '2': 'رخصة قيادة',
            '3': 'شهادة ميلاد', '4': 'شهادة تعيين', '5': 'كتاب ضمان اجتماعي',
            '6': 'كشف راتب', '7': 'شهادة تعيين عسكري', '8': 'صورة شخصية', '9': 'أخرى'
        };

        SO.scanFileIds.forEach(function(fileId) {
            if (existingIds.indexOf(String(fileId)) !== -1) return;
            var info = SO.scanFileTypes[fileId] || {};
            var typeCode = info.type || '9';
            var typeLabel = DOC_TYPES_MAP[typeCode] || 'أخرى';
            var thumb = info.thumb || '';
            var fileName = info.fileName || '';

            var optionsHtml = '';
            Object.keys(DOC_TYPES_MAP).forEach(function(k) {
                optionsHtml += '<option value="' + k + '"' + (k === typeCode ? ' selected' : '') + '>' + DOC_TYPES_MAP[k] + '</option>';
            });

            var $card = $(
                '<div class="sm-card" data-image-id="' + fileId + '" data-from-scan="1" tabindex="0">' +
                    '<span class="sm-scan-badge"><i class="fa fa-magic"></i> مسح ذكي</span>' +
                    '<div class="sm-card-actions">' +
                        '<button type="button" class="sm-card-action danger sm-delete-btn" data-image-id="' + fileId + '" title="حذف"><i class="fa fa-trash"></i></button>' +
                        '<button type="button" class="sm-card-action sm-reclassify-btn" data-image-id="' + fileId + '" title="إعادة تصنيف AI"><i class="fa fa-magic"></i></button>' +
                    '</div>' +
                    '<img class="sm-card-img" src="' + escapeHtml(thumb) + '" alt="">' +
                    '<div class="sm-card-body">' +
                        '<div class="sm-card-name">' + escapeHtml(fileName) + '</div>' +
                        '<div class="sm-card-meta"><span>' + typeLabel + '</span></div>' +
                        '<select class="sm-type-select" data-image-id="' + fileId + '">' + optionsHtml + '</select>' +
                        '<input type="text" class="sm-doc-number-input" placeholder="رقم المستند (اختياري)" value="">' +
                    '</div>' +
                '</div>'
            );
            $gallery.prepend($card);
            existingIds.push(String(fileId));
        });

        updateDocChecklist();
    }

    /* ══════════════════════════════════════════
       DOCUMENT CHECKLIST
       ══════════════════════════════════════════ */
    function initDocChecklist() {
        $(document).on('change', '.sm-type-select', function() {
            updateDocChecklist();
        });
        updateDocChecklistConditions();
        $(document).on('change', '#customers-is_social_security', function() {
            updateDocChecklistConditions();
        });
    }

    function updateDocChecklistConditions() {
        var hasSS = $('#customers-is_social_security').val() === '1' ||
                    $('#customers-is_social_security').is(':checked');
        var $ssItem = $('.so-doc-check-item[data-condition="social_security"]');
        if (hasSS) {
            $ssItem.show();
        } else {
            $ssItem.hide();
        }
        updateDocChecklist();
    }

    function updateDocChecklist() {
        var uploadedTypes = {};
        $('.sm-gallery .sm-type-select').each(function() {
            uploadedTypes[$(this).val()] = true;
        });
        $('.scan-gallery .scan-card[data-image-id]').each(function() {
            var imgId = String($(this).data('image-id'));
            if (SO.scanFileTypes[imgId]) {
                uploadedTypes[SO.scanFileTypes[imgId].type] = true;
            }
        });

        var total = 0, uploaded = 0;
        $('#docChecklist .so-doc-check-item:visible').each(function() {
            total++;
            var docType = $(this).data('doc-type');
            if (uploadedTypes[String(docType)]) {
                $(this).removeClass('missing').addClass('uploaded');
                uploaded++;
            } else {
                $(this).removeClass('uploaded').addClass('missing');
            }
        });
        $('#docCheckUploaded').text(uploaded);
        $('#docCheckTotal').text(total);
    }

    /* ══════════════════════════════════════════
       PROGRESS BAR
       ══════════════════════════════════════════ */
    var STEP_NAMES = ['رفع المستندات', 'البيانات الشخصية', 'الوظيفة والدخل', 'البنك والضمانات', 'المعرّفون والعناوين', 'مركز المستندات'];
    var EDIT_STEP_NAMES = ['البيانات الشخصية', 'الوظيفة والدخل', 'البنك والضمانات', 'المعرّفون والعناوين', 'مركز المستندات'];

    function updateProgressBar(idx) {
        var $bar = $('.so-progress-bar');
        if (!$bar.length) return;
        var names = isEditMode ? EDIT_STEP_NAMES : STEP_NAMES;
        var total = names.length;
        var pct = Math.round(((idx + 1) / total) * 100);
        var label = 'خطوة ' + (idx + 1) + ' من ' + total + ' — ' + (names[idx] || '');
        $bar.attr({'aria-valuenow': idx + 1, 'aria-valuemax': total});
        $bar.find('.so-progress-text').text(label);
        $bar.find('.so-progress-fill').css('width', pct + '%');
    }

    /* ══════════════════════════════════════════
       CONTEXTUAL HELP TOOLTIPS
       ══════════════════════════════════════════ */
    function initHelpTooltips() {
        $(document).on('click', '.so-help-btn', function(e) {
            e.stopPropagation();
            var $btn = $(this);
            var isOpen = $btn.attr('aria-expanded') === 'true';
            $('.so-help-btn').attr('aria-expanded', 'false');
            if (!isOpen) $btn.attr('aria-expanded', 'true');
        });
        $(document).on('click', function() {
            $('.so-help-btn').attr('aria-expanded', 'false');
        });
    }

    /* ══════════════════════════════════════════
       SAVE & EXIT
       ══════════════════════════════════════════ */
    $(document).on('click', '.so-save-exit-btn', function() {
        try {
            var $form = $('#smart-onboarding-form');
            var data = {};
            $form.find('input, select, textarea').each(function() {
                if (!this.name || this.type === 'file') return;
                data[this.name] = $(this).val();
            });
            data['__so_step'] = SO.currentStep;
            data['__so_ts'] = Date.now();
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch(e) {}
        showToast('تم حفظ المسودة — يمكنك المتابعة لاحقاً', 'success');
        setTimeout(function() {
            var idx = window.location.href.indexOf('/customers/');
            if (idx !== -1) window.location.href = window.location.href.substring(0, idx) + '/customers/customers/index';
            else window.history.back();
        }, 800);
    });

})(jQuery);
