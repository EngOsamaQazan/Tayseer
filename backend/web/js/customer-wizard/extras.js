/**
 * @deprecated Phase 6 / M6.2 — extras upload primitives are now in
 *             MediaUploader (backend/web/js/media-uploader/). This
 *             file remains so already-mounted wizard pages keep
 *             functioning; new screens should use MediaUploader.attach.
 *             Removal: M8 (≈ 2026-07-19).
 *
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
     * Mirror the photo bucket's current state into a hidden form input
     * so that:
     *   1. The wizard's standard `collectStepData()` picks it up and
     *      POSTs it to the server (under the key Customers[_extras_photo_id]).
     *   2. The server-side validateStep1() can return a per-field error
     *      keyed on the same name and have core.js's renderServerErrors()
     *      attach the error message to the correct on-screen card —
     *      no special-case needed for "extras".
     *   3. Once a photo is (re-)uploaded we proactively clear any
     *      previous error styling so the user sees instant feedback.
     *
     * Called whenever a photo is added, replaced, or deleted.
     */
    function syncPhotoIdField($root) {
        if (!$root || !$root.length) return;
        if (($root.attr('data-cw-extras-uploader') || '') !== 'photo') return;

        var $hidden = $root.find('input[data-cw-extras-photo-id]').first();
        if (!$hidden.length) return;

        var $item = $root.find('[data-cw-extras-item]').first();
        var newVal = $item.length ? String($item.attr('data-image-id') || '') : '';

        if ($hidden.val() !== newVal) {
            $hidden.val(newVal).trigger('change');
        }

        // Toggle the cosmetic "filled" state on the wrapping fieldset so
        // the accent border flips from blue (mandatory) to green (done).
        var $block = $root.closest('[data-cw-extras-required-block]');
        if ($block.length) {
            $block.toggleClass('cw-extras__photo-block--filled', newVal !== '');
        }

        // If a value is now present, clear any existing required-error UI
        // — the user just satisfied the requirement, don't keep yelling.
        if (newVal !== '') {
            clearRequiredError($root);
        }
    }

    function clearRequiredError($root) {
        if (!$root || !$root.length) return;
        $root.removeClass('cw-field--error');
        $root.find('> .cw-field__error-msg').remove();
        $root.find('input[data-cw-extras-photo-id]').removeAttr('aria-invalid');
        var $block = $root.closest('[data-cw-extras-required-block]');
        if ($block.length) {
            $block.removeClass('cw-extras__photo-block--invalid');
        }
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
     * Validate + sequentially upload a list of File objects into the
     * given uploader root, rendering thumbnails as each upload succeeds.
     * Shared between the file-picker `change` event and the clipboard
     * paste pipeline (button click + Ctrl+V / Win+V).
     *
     * @param {jQuery} $root  The [data-cw-extras-uploader] root element.
     * @param {File[]} files  Files to upload (already user-selected).
     * @param {Object?} opts
     *   - source: 'file' | 'paste'  — only used to vary toast wording.
     *   - onDone(): optional cleanup hook (e.g. clearing the file input).
     */
    function processFiles($root, files, opts) {
        opts = opts || {};
        if (!files || !files.length) {
            if (typeof opts.onDone === 'function') opts.onDone();
            return;
        }

        var purpose  = $root.attr('data-cw-extras-uploader') || 'doc';
        var isPhoto  = (purpose === 'photo');
        var multi    = $root.attr('data-cw-extras-multi') === '1';
        var accept   = $root.attr('data-cw-extras-accept') || '';
        var $trigger = $root.find('[data-cw-extras-trigger]').first();
        var $list    = $root.find('[data-cw-extras-list]').first();

        // Photo slot is single-shot even if the source surfaced multiple
        // (e.g. clipboard had two images while focus was on the photo
        // bucket — keep the most recent one only).
        if (!multi && files.length > 1) files = files.slice(0, 1);

        // Pre-flight validation.
        for (var i = 0; i < files.length; i++) {
            var msg = validateFile(files[i], accept);
            if (msg) {
                toast(msg, 'error', 6000);
                if (typeof opts.onDone === 'function') opts.onDone();
                return;
            }
        }

        // Disable the trigger + flag the uploader as "busy" while uploading.
        var inflight = ($root.data('cw-extras-busy') || 0) + files.length;
        $root.data('cw-extras-busy', inflight);
        $trigger.prop('disabled', true).attr('aria-busy', 'true')
                .find('span').text('جارٍ الرفع…');
        if (opts.source === 'paste') $root.attr('data-cw-extras-pasting', '1');

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
                    var $item = renderItem(resp, isPhoto);
                    if (!multi) {
                        $list.empty().append($item);
                    } else {
                        $list.append($item);
                    }
                    if (isPhoto) syncPhotoIdField($root);
                    var prefix = (opts.source === 'paste') ? 'تمّ لصق ' : 'تمّ رفع ';
                    toast(isPhoto
                            ? prefix + 'الصورة الشخصية.'
                            : prefix + '"' + (resp.file_name || file.name) + '".',
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
            inflight = ($root.data('cw-extras-busy') || files.length) - files.length;
            if (inflight < 0) inflight = 0;
            $root.data('cw-extras-busy', inflight);
            if (inflight === 0) {
                $trigger.prop('disabled', false).removeAttr('aria-busy');
                $trigger.find('span').text(
                    isPhoto
                        ? ($list.find('[data-cw-extras-item]').length ? 'تغيير الصورة' : 'اختيار صورة')
                        : 'إضافة مستندات'
                );
                $root.removeAttr('data-cw-extras-pasting');
            }
            if (typeof opts.onDone === 'function') opts.onDone();
        });
    }

    /**
     * Resolve which uploader on the active step should receive a paste
     * event triggered from the global keyboard handler. The contract is
     * STRICT: only the uploader that currently owns focus is eligible.
     *
     * Why "focus only"? scan.js owns a competing global paste handler
     * that consumes any image paste on Step 1 and routes it to identity
     * OCR. To avoid two handlers fighting over the same screenshot we
     * scope ours by explicit focus — if the user wants the paste to
     * land in extras, they click/TAB into the photo or docs region
     * first; otherwise scan.js gets the event.
     */
    function resolveFocusedTarget() {
        var active = document.activeElement;
        if (!active) return null;
        var $focused = $(active).closest('[data-cw-extras-uploader]');
        if (!$focused.length) return null;
        // Must live in the currently-active wizard step (defensive: a
        // collapsed step still has DOM but is hidden, paste shouldn't
        // hit it).
        var $section = $focused.closest('section.cw-section--active');
        return $section.length ? $focused : null;
    }

    /**
     * Handler for the "لصق" / "لصق من الحافظة" button. Uses the modern
     * Async Clipboard API; falls back to a friendly hint when the
     * browser blocks programmatic reads (typically on http:// without
     * a recent user gesture, or in browsers that lack the API).
     */
    function onPasteButton(e) {
        e.preventDefault();
        var $root = $(this).closest('[data-cw-extras-uploader]');
        if (!$root.length) return;

        if (!navigator.clipboard || typeof navigator.clipboard.read !== 'function') {
            toast('متصفحك لا يدعم القراءة من الحافظة — اضغط Ctrl+V (أو Win+V) داخل المربع للصق.',
                  'info', 6000);
            return;
        }

        navigator.clipboard.read().then(function (clipItems) {
            var pending = [];
            (clipItems || []).forEach(function (item) {
                var imgType = (item.types || []).find(function (t) {
                    return t === 'image/png'
                        || t === 'image/jpeg'
                        || t === 'image/webp';
                });
                if (!imgType) return;
                pending.push(item.getType(imgType).then(function (blob) {
                    var ext = imgType.split('/')[1] || 'png';
                    return new File([blob],
                        'clipboard_' + Date.now() + '_' + pending.length + '.' + ext,
                        { type: imgType });
                }));
            });

            if (!pending.length) {
                toast('لا توجد صورة في الحافظة — انسخ صورة ثم أعد المحاولة.', 'warning', 5000);
                return;
            }

            Promise.all(pending).then(function (files) {
                processFiles($root, files, { source: 'paste' });
            });
        }).catch(function () {
            toast('تعذّر الوصول إلى الحافظة — اضغط Ctrl+V (أو Win+V) داخل المربع.',
                  'warning', 6500);
        });
    }

    /**
     * Global Ctrl+V / Win+V handler — only fires when focus is inside
     * one of the extras uploaders. Win+V is just the Windows clipboard
     * history picker: each pick dispatches an ordinary paste event
     * identical to Ctrl+V, so multi-paste is the natural consequence of
     * the user invoking Win+V repeatedly. Each paste appends a new item
     * to the docs bucket (or replaces the photo when focus is there).
     *
     * The handler stays inert when:
     *   • No extras uploader currently has focus (scan.js handles the
     *     ambient image paste on Step 1 instead), or
     *   • The clipboard payload contains no image files, or
     *   • The identity-scan handler in scan.js already consumed the
     *     event (defaultPrevented).
     *
     * On a successful match we call stopImmediatePropagation() to
     * prevent scan.js from also handling the same paste — this is
     * critical because both handlers listen on document.
     */
    function onGlobalExtrasPaste(e) {
        if (e.isDefaultPrevented && e.isDefaultPrevented()) return;

        var $target = resolveFocusedTarget();
        if (!$target) return;

        var clip = e.originalEvent && e.originalEvent.clipboardData;
        if (!clip || !clip.items) return;

        var files = [];
        for (var i = 0; i < clip.items.length; i++) {
            var it = clip.items[i];
            if (it.kind === 'file' && /^image\/(png|jpeg|webp)$/.test(it.type || '')) {
                var f = it.getAsFile();
                if (f) files.push(f);
            }
        }
        if (!files.length) return;

        e.preventDefault();
        e.stopImmediatePropagation();   // keep scan.js out of this one
        processFiles($target, files, { source: 'paste' });
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
        var $input   = $root.find('input[data-cw-extras-input]').first();
        var $trigger = $root.find('[data-cw-extras-trigger]').first();
        var $paste   = $root.find('[data-cw-extras-paste]').first();
        var $list    = $root.find('[data-cw-extras-list]').first();
        if (!$input.length || !$trigger.length || !$list.length) return;

        // Trigger → file picker.
        $trigger.on('click' + NS, function (e) {
            e.preventDefault();
            // Reset value so picking the SAME file twice still fires `change`
            // (e.g. user picks a photo, deletes it, picks the same one again).
            $input.val('').trigger('click');
        });

        // Per-uploader paste button.
        if ($paste.length) {
            $paste.on('click' + NS, onPasteButton);
        }

        // File picked → upload(s).
        $input.on('change' + NS, function (e) {
            var files = e.target && e.target.files
                ? Array.prototype.slice.call(e.target.files) : [];
            processFiles($root, files, {
                source: 'file',
                onDone: function () { e.target.value = ''; },
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
                            syncPhotoIdField($root);
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

    /**
     * Global Ctrl+V / Win+V → routed to the focused extras uploader.
     * Bound exactly once. We attach via the native API in CAPTURE phase
     * so that — when an extras uploader genuinely has focus — this
     * listener fires before scan.js's own bubble-phase paste handler
     * and can stopImmediatePropagation() to prevent the identity-scan
     * pipeline from also consuming the same screenshot.
     */
    function bindGlobalPasteOnce() {
        var $doc = $(document);
        if ($doc.data('cw-extras-paste-bound')) return;
        $doc.data('cw-extras-paste-bound', true);
        document.addEventListener('paste', function (nativeEvent) {
            // Wrap the native event in a jQuery Event so our handler can
            // use the same isDefaultPrevented / originalEvent surface as
            // when triggered by `$.on('paste', ...)` elsewhere.
            var jqEvent = $.Event(nativeEvent);
            jqEvent.originalEvent = nativeEvent;
            onGlobalExtrasPaste(jqEvent);
            // Mirror jQuery's preventDefault/stopImmediatePropagation
            // back onto the native event so capture-phase decisions
            // actually take effect on the bubble-phase listeners.
            if (jqEvent.isDefaultPrevented()) nativeEvent.preventDefault();
            if (jqEvent.isImmediatePropagationStopped &&
                jqEvent.isImmediatePropagationStopped()) {
                nativeEvent.stopImmediatePropagation();
            }
        }, true);
    }

    $(function () {
        bindAll();
        bindGlobalPasteOnce();
        $(document).on('cw:step:rendered cw:step:changed', function (e, payload) {
            if (payload && payload.$section) bindAll(payload.$section);
            else bindAll();
        });
    });

})(window.jQuery, window);
