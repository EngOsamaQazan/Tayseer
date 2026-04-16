/**
 * Smart Media System — Document AI & WebCam
 * Handles: drag-drop upload, webcam capture, AI classification, usage stats
 */
(function($) {
    'use strict';

    var SM = {
        stream: null,
        facingMode: 'user',
        files: [],
        docTypes: {
            '0': 'هوية وطنية', '1': 'جواز سفر', '2': 'رخصة قيادة',
            '3': 'شهادة ميلاد', '4': 'شهادة تعيين', '5': 'كتاب ضمان اجتماعي',
            '6': 'كشف راتب', '7': 'شهادة تعيين عسكري', '8': 'صورة شخصية', '9': 'أخرى'
        }
    };

    $(function() {
        initDropZone();
        initWebcam();
        initGalleryActions();
        initLightbox();
        loadUsageStats();
    });

    /* ══════════════════════════════════════════
       DRAG & DROP UPLOAD
       ══════════════════════════════════════════ */
    function initDropZone() {
        var $zone = $('.sm-zone');
        var $input = $zone.find('input[type="file"]');

        // Drag events
        $zone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        }).on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        }).on('drop', function(e) {
            var files = e.originalEvent.dataTransfer.files;
            handleFiles(files);
        });

        // Click to select
        $input.on('change', function() {
            handleFiles(this.files);
            this.value = '';
        });
    }

    function handleFiles(fileList) {
        for (var i = 0; i < fileList.length; i++) {
            uploadFile(fileList[i]);
        }
    }

    function uploadFile(file) {
        // Validate
        var allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (allowed.indexOf(file.type) === -1) {
            showToast('نوع الملف غير مدعوم: ' + file.name, 'warning');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showToast('حجم الملف أكبر من 10MB: ' + file.name, 'warning');
            return;
        }

        // Create card placeholder
        var tempId = 'sm-temp-' + Date.now() + Math.random().toString(36).substr(2, 4);
        var thumbUrl = file.type.indexOf('image/') === 0
            ? URL.createObjectURL(file)
            : '/css/images/pdf-icon.png';

        var cardHtml =
            '<div class="sm-card" id="' + tempId + '" tabindex="0">' +
                '<img class="sm-card-img" src="' + thumbUrl + '" alt="">' +
                '<div class="sm-card-analyzing"><div class="spinner"></div><span>جاري الرفع...</span></div>' +
                '<div class="sm-upload-progress"><div class="sm-upload-progress-fill" style="width:0%"></div></div>' +
                '<div class="sm-card-body">' +
                    '<div class="sm-card-name">' + escapeHtml(file.name) + '</div>' +
                    '<div class="sm-card-meta"><span>' + formatSize(file.size) + '</span></div>' +
                '</div>' +
            '</div>';

        $('.sm-gallery').append(cardHtml);

        // Upload via AJAX
        var formData = new FormData();
        formData.append('file', file);
        formData.append('customer_id', getCustomerId());
        formData.append('auto_classify', '1');

        $.ajax({
            url: window.smConfig.uploadUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var pct = Math.round((e.loaded / e.total) * 100);
                        $('#' + tempId).find('.sm-card-analyzing span').text('جاري الرفع... ' + pct + '%');
                        $('#' + tempId).find('.sm-upload-progress-fill').css('width', pct + '%');
                    }
                });
                return xhr;
            },
            success: function(resp) {
                if (resp.success) {
                    updateCard(tempId, resp);
                    showToast('تم رفع: ' + file.name, 'success');
                } else {
                    $('#' + tempId).remove();
                    showToast('خطأ: ' + (resp.error || 'فشل الرفع'), 'danger');
                }
            },
            error: function() {
                $('#' + tempId).remove();
                showToast('خطأ في الاتصال أثناء رفع الملف', 'danger');
            }
        });
    }

    function updateCard(tempId, resp) {
        var $card = $('#' + tempId);
        var f = resp.file;
        var ai = resp.ai;
        var imageId = f.id || 0;

        // Update image
        if (f.thumb) {
            $card.find('.sm-card-img').attr('src', f.thumb);
        }

        // Remove analyzing overlay
        $card.find('.sm-card-analyzing').remove();

        // Build AI info
        var aiHtml = '';
        if (ai && ai.classification) {
            var c = ai.classification;
            var confClass = c.confidence >= 70 ? 'high' : (c.confidence >= 30 ? 'medium' : 'low');
            aiHtml =
                '<div class="sm-ai-type"><i class="fa fa-magic"></i> ' + escapeHtml(c.label) + '</div>' +
                '<div class="sm-ai-badge ' + confClass + '">' +
                    '<i class="fa fa-robot"></i> ثقة ' + Math.round(c.confidence) + '%' +
                '</div>';
        }

        // Build type selector
        var selectHtml = '<select class="sm-type-select" data-path="' + escapeHtml(f.path) + '" data-image-id="' + imageId + '">';
        var selectedType = (ai && ai.classification) ? ai.classification.type : '';
        $.each(SM.docTypes, function(k, v) {
            selectHtml += '<option value="' + k + '"' + (k === selectedType ? ' selected' : '') + '>' + v + '</option>';
        });
        selectHtml += '</select>';

        // Add actions (include image_id for delete/reclassify)
        var actionsHtml =
            '<div class="sm-card-actions">' +
                '<button class="sm-card-action danger sm-delete-btn" data-path="' + escapeHtml(f.path) + '" data-image-id="' + imageId + '" title="حذف"><i class="fa fa-trash"></i></button>' +
                '<button class="sm-card-action sm-reclassify-btn" data-path="' + escapeHtml(f.path) + '" data-image-id="' + imageId + '" title="إعادة تصنيف AI"><i class="fa fa-magic"></i></button>' +
            '</div>';

        $card.find('.sm-upload-progress').remove();
        $card.find('.sm-card-body').html(
            '<div class="sm-card-name">' + escapeHtml(f.name) + '</div>' +
            '<div class="sm-card-meta"><span>' + formatSize(f.size) + '</span><span>' + (ai ? ai.response_time + 'ms' : '') + '</span></div>' +
            aiHtml + selectHtml +
            '<input type="text" class="sm-doc-number-input" placeholder="رقم المستند (اختياري)" value="">'
        );

        $card.prepend(actionsHtml);

        // Store file data
        $card.data('file-info', f);
        $card.data('ai-info', ai);
        $card.data('image-id', imageId);

        // Add hidden inputs for form submission (includes image_id for linking on save)
        var idx = $('.sm-gallery .sm-card').index($card);
        var inputsHtml =
            '<input type="hidden" name="SmartMedia[' + idx + '][image_id]" value="' + imageId + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][file_path]" value="' + escapeHtml(f.path) + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][file_name]" value="' + escapeHtml(f.name) + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][file_size]" value="' + f.size + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][mime_type]" value="' + escapeHtml(f.mime) + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][capture_method]" value="' + f.capture_method + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][thumb_path]" value="' + escapeHtml(f.thumb || '') + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][group_name]" value="' + escapeHtml(f.group_name || '9') + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][ai_classification]" value="' + escapeHtml((ai && ai.classification) ? ai.classification.key : '') + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][ai_confidence]" value="' + ((ai && ai.classification) ? ai.classification.confidence : '0') + '">' +
            '<input type="hidden" name="SmartMedia[' + idx + '][ai_text]" value="' + escapeHtml((ai ? ai.text_preview : '') || '') + '">';
        $card.append(inputsHtml);

        // Refresh usage stats
        loadUsageStats();
    }

    /* ══════════════════════════════════════════
       WEBCAM
       ══════════════════════════════════════════ */
    function initWebcam() {
        $(document).on('click', '.sm-webcam-btn', function() {
            startWebcam();
        });
        $(document).on('click', '.sm-cam-close', function() {
            stopWebcam();
        });
        $(document).on('click', '.sm-cam-capture', function() {
            capturePhoto();
        });
        $(document).on('click', '.sm-cam-switch', function() {
            SM.facingMode = SM.facingMode === 'user' ? 'environment' : 'user';
            stopWebcam();
            startWebcam();
        });
    }

    function startWebcam() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showToast('الكاميرا غير مدعومة في هذا المتصفح', 'danger');
            return;
        }

        var $wc = $('.sm-webcam');
        var video = $wc.find('video')[0];

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: SM.facingMode, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        }).then(function(stream) {
            SM.stream = stream;
            video.srcObject = stream;
            video.play();
            $wc.addClass('active');
            $('.sm-webcam-btn').addClass('active');
        }).catch(function(err) {
            showToast('لا يمكن الوصول للكاميرا: ' + err.message, 'danger');
        });
    }

    function stopWebcam() {
        if (SM.stream) {
            SM.stream.getTracks().forEach(function(t) { t.stop(); });
            SM.stream = null;
        }
        $('.sm-webcam').removeClass('active');
        $('.sm-webcam-btn').removeClass('active');
    }

    function capturePhoto() {
        var $wc = $('.sm-webcam');
        var video = $wc.find('video')[0];
        var canvas = $wc.find('canvas')[0];

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        var ctx = canvas.getContext('2d');
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0);

        var imageData = canvas.toDataURL('image/jpeg', 0.92);

        // Send to server
        var tempId = 'sm-cam-' + Date.now();
        var cardHtml =
            '<div class="sm-card" id="' + tempId + '">' +
                '<img class="sm-card-img" src="' + imageData + '" alt="">' +
                '<div class="sm-card-analyzing"><div class="spinner"></div><span>جاري الحفظ...</span></div>' +
                '<div class="sm-card-body">' +
                    '<div class="sm-card-name">صورة من الكاميرا</div>' +
                    '<div class="sm-card-meta"><i class="fa fa-camera"></i> WebCam</div>' +
                '</div>' +
            '</div>';

        $('.sm-gallery').append(cardHtml);

        var photoType = $('input[name="sm_photo_type"]:checked').val() || 'webcam';

        $.ajax({
            url: window.smConfig.webcamUrl,
            method: 'POST',
            data: {
                image_data: imageData,
                customer_id: getCustomerId(),
                photo_type: photoType
            },
            success: function(resp) {
                if (resp.success) {
                    var $card = $('#' + tempId);
                    $card.find('.sm-card-analyzing').remove();

                    if (resp.photo.thumb) {
                        $card.find('.sm-card-img').attr('src', resp.photo.thumb);
                    }

                    var actionsHtml =
                        '<div class="sm-card-actions">' +
                            '<button class="sm-card-action danger sm-delete-btn" data-path="' + escapeHtml(resp.photo.path) + '" data-type="photo" title="حذف"><i class="fa fa-trash"></i></button>' +
                        '</div>';
                    $card.prepend(actionsHtml);

                    $card.find('.sm-card-meta').html(
                        '<i class="fa fa-camera"></i> <span>' + formatSize(resp.photo.size) + '</span>'
                    );

                    // Hidden inputs
                    var idx = $('.sm-gallery .sm-card').length - 1;
                    $card.append(
                        '<input type="hidden" name="SmartPhotos[' + idx + '][id]" value="' + resp.photo.id + '">' +
                        '<input type="hidden" name="SmartPhotos[' + idx + '][path]" value="' + escapeHtml(resp.photo.path) + '">' +
                        '<input type="hidden" name="SmartPhotos[' + idx + '][type]" value="' + photoType + '">'
                    );

                    showToast('تم التقاط الصورة بنجاح', 'success');
                } else {
                    $('#' + tempId).remove();
                    showToast('خطأ: ' + (resp.error || 'فشل الحفظ'), 'danger');
                }
            },
            error: function() {
                $('#' + tempId).remove();
                showToast('خطأ في الاتصال', 'danger');
            }
        });
    }

    /* ══════════════════════════════════════════
       GALLERY ACTIONS
       ══════════════════════════════════════════ */
    function initGalleryActions() {
        $(document).on('click', '.sm-delete-btn', function(e) {
            e.stopPropagation();
            var $btn = $(this);
            var $card = $btn.closest('.sm-card');
            var path = $btn.data('path');
            var imageId = $btn.data('image-id') || 0;

            if (typeof window.soModal === 'function') {
                window.soModal({
                    title: 'حذف المستند',
                    body: '<p style="text-align:right">هل أنت متأكد من حذف هذا الملف؟ لا يمكن التراجع عن هذا الإجراء.</p>',
                    confirmText: 'حذف',
                    confirmClass: 'so-btn-danger',
                    onConfirm: function($m, closeFn) {
                        closeFn();
                        doDelete($card, path, imageId);
                    }
                });
            } else {
                doDelete($card, path, imageId);
            }
        });

        function doDelete($card, path, imageId) {
            $.post(window.smConfig.deleteUrl, { file_path: path, image_id: imageId }, function(resp) {
                if (resp.success) {
                    $card.fadeOut(200, function() { $(this).remove(); });
                } else {
                    showToast('فشل الحذف: ' + (resp.error || ''), 'danger');
                }
            });
        }

        // Re-classify with AI
        $(document).on('click', '.sm-reclassify-btn', function(e) {
            e.stopPropagation();
            var $card = $(this).closest('.sm-card');
            var path = $(this).data('path');
            var imageId = $(this).data('image-id') || 0;

            $card.append('<div class="sm-card-analyzing"><div class="spinner"></div><span>تحليل AI...</span></div>');

            $.post(window.smConfig.classifyUrl, {
                file_path: path,
                customer_id: getCustomerId(),
                image_id: imageId
            }, function(resp) {
                $card.find('.sm-card-analyzing').remove();

                if (resp.success && resp.classification) {
                    var c = resp.classification;
                    $card.find('.sm-ai-type').remove();
                    $card.find('.sm-ai-badge').remove();

                    var confClass = c.confidence >= 70 ? 'high' : (c.confidence >= 30 ? 'medium' : 'low');
                    $card.find('.sm-card-body').append(
                        '<div class="sm-ai-type"><i class="fa fa-magic"></i> ' + escapeHtml(c.label) + '</div>' +
                        '<div class="sm-ai-badge ' + confClass + '"><i class="fa fa-robot"></i> ثقة ' + Math.round(c.confidence) + '%</div>'
                    );

                    $card.find('.sm-type-select').val(c.type);
                    // Update hidden group_name input
                    $card.find('input[name*="[group_name]"]').val(c.type);
                    showToast('تم التصنيف: ' + c.label + ' (ثقة ' + Math.round(c.confidence) + '%)', 'success');
                } else {
                    showToast('فشل التصنيف: ' + (resp.error || ''), 'warning');
                }

                loadUsageStats();
            });
        });

        // Type change — update groupName in os_ImageManager via AJAX
        $(document).on('change', '.sm-type-select', function() {
            var $card = $(this).closest('.sm-card');
            var newType = $(this).val();
            var imageId = $(this).data('image-id') || $card.data('image-id') || 0;

            // Update hidden group_name input
            $card.find('input[name*="[group_name]"]').val(newType);

            // Update in DB if we have an image ID
            if (imageId) {
                $.post(window.smConfig.uploadUrl.replace('/upload', '/update-type'), {
                    image_id: imageId,
                    group_name: newType
                });
            }
        });
    }

    /* ══════════════════════════════════════════
       USAGE STATS — Dual Source: Local + Google
       ══════════════════════════════════════════ */
    function loadUsageStats() {
        var $widget = $('.sm-usage');
        if (!$widget.length) return;

        // Load LOCAL stats
        $.get(window.smConfig.usageUrl, function(stats) {
            if (stats.error) return;

            var $local = $widget.find('[data-panel="local"]');
            $local.find('.sm-usage-total').text(stats.total_requests || 0);
            $local.find('.sm-usage-success').text(stats.successful || 0);
            $local.find('.sm-usage-cost').text('$' + (stats.total_cost || 0).toFixed(2));
            $local.find('.sm-usage-remaining').text(stats.free_remaining || 0);

            var usedPct = Math.min(100, ((stats.successful || 0) / (stats.free_limit || 1000)) * 100);
            $local.find('.sm-usage-bar-fill').css('width', usedPct + '%');
            $local.find('.sm-usage-hint').text(
                'استخدام الشهر: ' + (stats.successful || 0) + ' / ' + (stats.free_limit || 1000) + ' (مجاني)'
            );
        });
    }

    /**
     * Load LIVE Google Cloud data — Billing + Monitoring API
     */
    function loadGoogleStats() {
        var $widget = $('.sm-usage');
        if (!$widget.length || !window.smConfig.googleStatsUrl) return;

        var $panel = $widget.find('[data-panel="google"]');
        $panel.find('.sm-g-status').html('<i class="fa fa-spinner fa-spin"></i> جاري الاتصال بـ Google Cloud...');

        $.ajax({
            url: window.smConfig.googleStatsUrl,
            method: 'GET',
            timeout: 20000,
            success: function(data) {
                if (!data || !data.google) {
                    $panel.find('.sm-g-status').html('<i class="fa fa-exclamation-triangle" style="color:#e74c3c"></i> لا توجد بيانات');
                    return;
                }

                var g = data.google;
                var usage = g.usage || {};
                var local = data.local || {};

                // --- Billing status ---
                var billingHtml = '';
                if (g.billing_enabled) {
                    billingHtml = '<span style="color:#27ae60"><i class="fa fa-check-circle"></i> الفوترة مفعّلة</span>';
                } else if (g.billing_enabled === false) {
                    billingHtml = '<span style="color:#e74c3c"><i class="fa fa-times-circle"></i> الفوترة غير مفعّلة</span>';
                }
                if (g.billing_account) {
                    billingHtml += ' <small style="color:#888">(' + escapeHtml(g.billing_account) + ')</small>';
                }
                $panel.find('.sm-g-billing-status').html(billingHtml);

                // --- Usage from Google Monitoring API ---
                if (usage.source === 'google_monitoring') {
                    $panel.find('.sm-g-total').text(usage.total_requests || 0);
                    $panel.find('.sm-g-billable').text(usage.billable_requests || 0);
                    $panel.find('.sm-g-cost').text('$' + (usage.estimated_cost || 0).toFixed(4));
                    $panel.find('.sm-g-remaining').text(usage.free_remaining || 0);

                    var pct = Math.min(100, ((usage.total_requests || 0) / 1000) * 100);
                    $panel.find('.sm-g-bar-fill').css('width', pct + '%');

                    var statusHtml = '<i class="fa fa-check-circle" style="color:#27ae60"></i> بيانات حقيقية من Google Cloud Monitoring';

                    // Breakdown by method
                    if (usage.breakdown && usage.breakdown.length) {
                        statusHtml += '<div style="margin-top:8px"><table style="width:100%; font-size:11px; border-collapse:collapse">' +
                            '<tr style="background:#f7f7f7"><th style="padding:4px 6px; text-align:right; border:1px solid #eee">الطريقة</th>' +
                            '<th style="padding:4px 6px; text-align:center; border:1px solid #eee">الحالة</th>' +
                            '<th style="padding:4px 6px; text-align:center; border:1px solid #eee">العدد</th></tr>';

                        for (var i = 0; i < usage.breakdown.length; i++) {
                            var b = usage.breakdown[i];
                            statusHtml += '<tr>' +
                                '<td style="padding:3px 6px; border:1px solid #eee; direction:ltr; text-align:left; font-family:monospace; font-size:10px">' + escapeHtml(b.method) + '</td>' +
                                '<td style="padding:3px 6px; border:1px solid #eee; text-align:center">' + escapeHtml(b.status) + '</td>' +
                                '<td style="padding:3px 6px; border:1px solid #eee; text-align:center; font-weight:bold">' + b.count + '</td>' +
                            '</tr>';
                        }
                        statusHtml += '</table></div>';
                    }

                    $panel.find('.sm-g-status').html(statusHtml);

                } else if (usage.source === 'service_usage_api') {
                    // Fallback — less detailed
                    $panel.find('.sm-g-total').text('—');
                    $panel.find('.sm-g-billable').text('—');
                    $panel.find('.sm-g-cost').text('—');
                    $panel.find('.sm-g-remaining').text('—');
                    $panel.find('.sm-g-status').html(
                        '<i class="fa fa-info-circle" style="color:#f39c12"></i> Vision API: <b>' + (usage.service_state || '?') + '</b><br>' +
                        '<small style="color:#999">' + (usage.note || '') + '</small>'
                    );

                } else if (usage.source === 'error') {
                    $panel.find('.sm-g-status').html(
                        '<i class="fa fa-exclamation-triangle" style="color:#e74c3c"></i> خطأ: ' + escapeHtml(usage.error)
                    );
                }

                // --- Comparison: Local vs Google ---
                if (usage.total_requests !== undefined && local.total_requests !== undefined) {
                    var diff = usage.total_requests - (local.successful || 0);
                    var compHtml = '<div style="margin-top:10px; padding:8px; background:#f8f8f8; border-radius:6px; font-size:11px">' +
                        '<b>مقارنة:</b> نظامنا سجّل <b>' + (local.successful || 0) + '</b> طلب — Google سجّل <b>' + usage.total_requests + '</b> طلب';
                    if (diff > 0) {
                        compHtml += ' <span style="color:#e67e22">(' + diff + ' طلبات غير مسجلة محلياً)</span>';
                    } else if (diff === 0) {
                        compHtml += ' <span style="color:#27ae60"><i class="fa fa-check"></i> متطابق</span>';
                    }
                    compHtml += '</div>';

                    $panel.find('.sm-g-status').after(compHtml);
                }
            },
            error: function(xhr) {
                $panel.find('.sm-g-status').html(
                    '<i class="fa fa-exclamation-triangle" style="color:#e74c3c"></i> فشل الاتصال بالخادم (' + xhr.status + ')'
                );
            }
        });
    }

    /**
     * Tab switching: Local <-> Google
     */
    $(document).on('click', '.sm-tab-btn', function() {
        var tab = $(this).data('tab');
        $('.sm-tab-btn').css({ background: '#fff', color: '#555' });
        $(this).css({ background: '#800020', color: '#fff' });
        $('.sm-stats-panel').hide();
        $('[data-panel="' + tab + '"]').show();

        // Fetch Google data on first click
        if (tab === 'google' && !SM.googleLoaded) {
            SM.googleLoaded = true;
            loadGoogleStats();
        }
    });

    /* ══════════════════════════════════════════
       LIGHTBOX — Full-size image preview
       ══════════════════════════════════════════ */
    function initLightbox() {
        $('body').append(
            '<div id="smLightbox" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.88);align-items:center;justify-content:center;cursor:zoom-out">' +
                '<button id="smLbClose" style="position:absolute;top:16px;right:16px;width:40px;height:40px;border-radius:50%;border:none;background:rgba(255,255,255,.15);color:#fff;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;z-index:1">&times;</button>' +
                '<img id="smLbImg" src="" style="max-width:92vw;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 8px 40px rgba(0,0,0,.5);transition:transform .2s" alt="">' +
            '</div>'
        );

        $(document).on('click', '.sm-card-img', function(e) {
            e.stopPropagation();
            var src = $(this).attr('src');
            if (!src || src.indexOf('pdf') !== -1) return;
            var fullSrc = src.replace(/_thumb/, '');
            $('#smLbImg').attr('src', fullSrc);
            $('#smLightbox').css('display', 'flex');
        });

        $(document).on('click', '#smLightbox, #smLbClose', function() {
            $('#smLightbox').css('display', 'none');
            $('#smLbImg').attr('src', '');
        });

        $(document).on('click', '#smLbImg', function(e) {
            e.stopPropagation();
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#smLightbox').is(':visible')) {
                $('#smLightbox').css('display', 'none');
                $('#smLbImg').attr('src', '');
            }
        });
    }

    /* ══════════════════════════════════════════
       HELPERS
       ══════════════════════════════════════════ */
    function getCustomerId() {
        return $('input[name="customer_id_for_media"]').val() ||
               $('[data-customer-id]').data('customer-id') ||
               '';
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function showToast(msg, type) {
        type = type || 'info';
        var icons = { success: 'check-circle', danger: 'exclamation-triangle', warning: 'exclamation-circle', info: 'info-circle' };
        var html = '<div class="so-inline-alert so-inline-alert-' + (type === 'success' ? 'info' : type) + '" style="border-right-color:' + (type === 'success' ? '#28a745' : '') + '">' +
            '<i class="fa fa-' + (icons[type] || 'info-circle') + '"></i>' +
            '<span>' + msg + '</span>' +
            '<button class="so-inline-alert-close" type="button">&times;</button>' +
        '</div>';
        var $alert = $(html);

        if (!$('.so-inline-alerts').length) $('body').append('<div class="so-inline-alerts"></div>');
        $('.so-inline-alerts').append($alert);

        $alert.find('.so-inline-alert-close').on('click', function() { $alert.fadeOut(200, function() { $(this).remove(); }); });
        setTimeout(function() { $alert.fadeOut(300, function() { $(this).remove(); }); }, 5000);
    }

})(jQuery);
