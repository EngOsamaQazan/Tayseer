/**
 * Customer Wizard V2 — extras uploader (personal photo + supporting documents).
 *
 * Bound by markup contract (see _step_1_extras.php), zero direct coupling
 * to core.js / scan.js — we only depend on:
 *   • window.CW._urls.uploadExtra / .deleteExtra (from core.js bootstrap)
 *   • window.CW.toast()                          (best-effort feedback)
 *
 * Why a dedicated module (not "just extend scan.js"):
 *   • scan.js is OCR-driven (multipart upload → AI extraction → field
 *     auto-fill + per-side state machine). The extras flow uploads
 *     plain images/PDFs and never touches form fields, so cohabiting
 *     in scan.js would tangle two unrelated state machines.
 *   • The thumbnail UI here is generic ("did the file upload? show me
 *     a thumbnail and a delete button") and reused for both personal
 *     photo and ad-hoc documents — a single render path keeps the
 *     interaction model identical across both buckets.
 *
 * Idempotency:
 *   Bind on DOM-ready AND on every cw:step:rendered/changed so partial
 *   re-renders don't strand handlers. Internal `data-cw-extras-bound=1`
 *   markers on each uploader prevent double-binding.
 */
(function ($, window) {
    'use strict';
    if (!$) return;

    var NS = '.cwExtras';
    var MAX_BYTES = 10 * 1024 * 1024;   // server enforces same cap; client-side check is UX only

    function urls() {
        var u = (window.CW && window.CW._urls) ? window.CW._urls : {};
        return {
            upload: u.uploadExtra || '/customers/wizard/upload-extra',
            del:    u.deleteExtra || '/customers/wizard/delete-extra',
        };
    }

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

    /**
     * Render a single uploaded item into the dropzone list. Handles both
     * the photo (single-slot, replaces) and doc (append) semantics.
     */
    function renderItem(payload, isPhoto) {
        var isPdf = (payload.mime || '').toLowerCase() === 'application/pdf';
        var thumb = isPdf
            ? '<i class="fa fa-file-pdf-o" aria-hidden="true"></i>'
            : '<img src="' + escapeHtml(payload.url) + '" alt="' +
                escapeHtml(payload.file_name || '') + '" loading="lazy">';

        return $(
            '<div class="cw-extras__item' + (isPhoto ? ' cw-extras__item--photo' : '') +
                '" data-cw-extras-item' +
                ' data-image-id="' + escapeHtml(String(payload.image_id)) + '"' +
                ' role="listitem">' +
                '<div class="cw-extras__thumb cw-extras__thumb--' +
                    (isPdf ? 'pdf' : 'img') + '">' + thumb + '</div>' +
                '<div class="cw-extras__meta">' +
                    '<strong class="cw-extras__name">' +
                        escapeHtml(payload.file_name || '') +
                    '</strong>' +
                    (isPhoto
                        ? '<span class="cw-extras__sub">سيتم استخدامها على بطاقة العقد</span>'
                        : (payload.size
                            ? '<span class="cw-extras__sub">' +
                                Math.round(payload.size / 1024) + ' KB</span>'
                            : '')) +
                '</div>' +
                '<button type="button" class="cw-extras__del" data-cw-extras-del ' +
                    'aria-label="' + (isPhoto ? 'حذف الصورة الشخصية' : 'حذف المستند') + '">' +
                    '<i class="fa fa-trash" aria-hidden="true"></i>' +
                '</button>' +
            '</div>'
        );
    }

    /**
     * Validate a single File against the uploader's accept list + size cap.
     * Returns null on success or an Arabic error message string on failure.
     */
    function validateFile(file, accept) {
        if (file.size > MAX_BYTES) {
            return 'حجم الملف "' + file.name + '" أكبر من 10 ميجابايت.';
        }
        if (accept && file.type) {
            var allowed = accept.split(',').map(function (s) { return s.trim().toLowerCase(); });
            if (allowed.indexOf(file.type.toLowerCase()) === -1) {
                return 'نوع الملف "' + file.name + '" غير مدعوم.';
            }
        }
        return null;
    }

    /**
     * Send one file to the server. Returns a jQuery Deferred resolving with
     * the server's parsed JSON payload. Network/transport failures reject.
     */
    function uploadOne(file, purpose) {
        var fd = new FormData();
        fd.append('file', file, file.name);
        fd.append('purpose', purpose);
        fd.append('_csrf-backend', csrfToken());

        return $.ajax({
            url:         urls().upload,
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            dataType:    'json',
            timeout:     90000,
            headers:     { 'X-CSRF-Token': csrfToken() },
        });
    }

    function deleteOne(imageId, purpose) {
        return $.ajax({
            url:      urls().del,
            type:     'POST',
            dataType: 'json',
            data:     {
                image_id:        imageId,
                purpose:         purpose,
                '_csrf-backend': csrfToken(),
            },
        });
    }

    /**
     * Wire one uploader. Idempotent — second call is a no-op.
     *
     * State held on the root element via $.data:
     *   • cw-extras-busy: in-flight upload count (so we can disable the
     *     trigger button during sequential uploads of multiple files).
     */
    function bindUploader(root) {
        var $root = $(root);
        if ($root.attr('data-cw-extras-bound') === '1') return;
        $root.attr('data-cw-extras-bound', '1');

        var purpose  = $root.attr('data-cw-extras-uploader') || 'doc';
        var isPhoto  = (purpose === 'photo');
        var multi    = $root.attr('data-cw-extras-multi') === '1';
        var accept   = $root.attr('data-cw-extras-accept') || '';
        var $input   = $root.find('input[data-cw-extras-input]').first();
        var $trigger = $root.find('[data-cw-extras-trigger]').first();
        var $list    = $root.find('[data-cw-extras-list]').first();
        if (!$input.length || !$trigger.length || !$list.length) return;

        // Trigger → file picker.
        $trigger.on('click' + NS, function (e) {
            e.preventDefault();
            // Reset value so picking the SAME file twice still fires `change`
            // (e.g. user picks a photo, deletes it, picks the same one again).
            $input.val('').trigger('click');
        });

        // File picked → upload(s).
        $input.on('change' + NS, function (e) {
            var files = e.target && e.target.files ? Array.prototype.slice.call(e.target.files) : [];
            if (!files.length) return;

            // Photo slot accepts only one file even if the user multi-selected
            // (the input doesn't carry `multiple` but defensive nonetheless).
            if (!multi && files.length > 1) files = files.slice(0, 1);

            // Pre-flight validation.
            for (var i = 0; i < files.length; i++) {
                var msg = validateFile(files[i], accept);
                if (msg) {
                    toast(msg, 'error', 6000);
                    e.target.value = '';
                    return;
                }
            }

            // For the photo slot, replacing means: upload the new one then,
            // on success, the server-side "remember" already evicts the old
            // photo entry from the draft and deletes its orphan Media row.
            // The client only needs to swap the rendered thumbnail.

            // Disable the trigger while uploading. We update the in-flight
            // counter so back-to-back picks don't race.
            var inflight = ($root.data('cw-extras-busy') || 0) + files.length;
            $root.data('cw-extras-busy', inflight);
            $trigger.prop('disabled', true).attr('aria-busy', 'true')
                    .find('span').text('جارٍ الرفع…');

            var seq = $.Deferred().resolve();
            files.forEach(function (file) {
                seq = seq.then(function () {
                    return uploadOne(file, purpose).then(function (resp) {
                        if (!resp || !resp.ok) {
                            toast((resp && resp.error) ||
                                  'تعذّر رفع الملف "' + file.name + '".',
                                  'error', 6000);
                            return;
                        }
                        // Render & insert.
                        var $item = renderItem(resp, isPhoto);
                        if (!multi) {
                            // Single-slot uploader → replace.
                            $list.empty().append($item);
                        } else {
                            $list.append($item);
                        }
                        toast(isPhoto
                                ? 'تمّ رفع الصورة الشخصية.'
                                : 'تمّ رفع "' + (resp.file_name || file.name) + '".',
                              'success', 3500);
                    }, function (xhr) {
                        var serverMsg = '';
                        try {
                            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                                serverMsg = xhr.responseJSON.error;
                            }
                        } catch (_) { /* noop */ }
                        toast(serverMsg ||
                              'فشل الاتصال أثناء رفع "' + file.name + '".',
                              'error', 6000);
                    });
                });
            });

            seq.always(function () {
                inflight = ($root.data('cw-extras-busy') || 1) - files.length;
                if (inflight < 0) inflight = 0;
                $root.data('cw-extras-busy', inflight);
                if (inflight === 0) {
                    $trigger.prop('disabled', false).removeAttr('aria-busy');
                    $trigger.find('span').text(
                        isPhoto
                            ? ($list.find('[data-cw-extras-item]').length ? 'تغيير الصورة' : 'اختيار صورة')
                            : 'إضافة مستندات'
                    );
                }
                e.target.value = '';     // allow re-pick of the same file
            });
        });

        // Delegated delete handler. We rely on event-delegation so newly
        // rendered items (uploaded after page load) don't need rebinding.
        $list.on('click' + NS, '[data-cw-extras-del]', function (e) {
            e.preventDefault();
            var $btn  = $(this);
            var $item = $btn.closest('[data-cw-extras-item]');
            var imageId = parseInt($item.attr('data-image-id'), 10);
            if (!imageId) return;

            // Optimistic UI: dim immediately, undo on failure. Faster
            // perceived responsiveness for what's a 99%-success operation.
            $item.addClass('cw-extras__item--deleting');
            $btn.prop('disabled', true);

            deleteOne(imageId, purpose).done(function (resp) {
                if (resp && resp.ok) {
                    $item.slideUp(160, function () {
                        $item.remove();
                        if (isPhoto) {
                            $trigger.find('span').text('اختيار صورة');
                        }
                    });
                } else {
                    $item.removeClass('cw-extras__item--deleting');
                    $btn.prop('disabled', false);
                    toast((resp && resp.error) || 'تعذّر حذف الملف.', 'error', 5000);
                }
            }).fail(function () {
                $item.removeClass('cw-extras__item--deleting');
                $btn.prop('disabled', false);
                toast('تعذّر الاتصال بالخادم لحذف الملف.', 'error', 5000);
            });
        });
    }

    function bindAll($root) {
        var $scope = $root && $root.length ? $root : $(document);
        $scope.find('[data-cw-extras-uploader]').each(function () {
            bindUploader(this);
        });
    }

    $(function () {
        bindAll();
        $(document).on('cw:step:rendered cw:step:changed', function (e, payload) {
            if (payload && payload.$section) bindAll(payload.$section);
            else bindAll();
        });
    });

})(window.jQuery, window);
