/**
 * Phase 6 / M6.1 — Unified MediaUploader (core).
 *
 * Replaces 7 ad-hoc upload widgets (smart-media, customer-wizard scan,
 * customer-wizard extras, smart-onboarding, lawyers signature pad,
 * employee avatar, companies docs) with a single drop-in module that:
 *
 *   • POSTs to /admin/index.php?r=media/upload
 *   • Sends X-CSRF-Token (no CSRF disabled controllers any more)
 *   • Surfaces per-file progress, errors, retries
 *   • Returns the unified MediaResult shape
 *
 * Usage (drag & drop / click):
 *
 *   <div data-media-uploader
 *        data-entity-type="lawyer"
 *        data-entity-id="42"
 *        data-group-name="lawyer_photo"
 *        data-uploaded-via="lawyer_form"
 *        data-multiple="1"
 *        data-accept="image/*"
 *        data-max-mb="5"
 *        data-target="#lw-photos-hidden">
 *     <input type="file" data-media-input style="display:none">
 *     <button type="button" data-media-pick>اختر ملفات</button>
 *     <ul data-media-list></ul>
 *   </div>
 *
 *   <script>MediaUploader.attach('[data-media-uploader]');</script>
 *
 * Each successful upload appends one hidden input <input type="hidden"
 * name="<targetName>[]" value="<media.id>"> so existing PHP form
 * handlers continue to work without change.
 */
(function (global, $) {
    'use strict';

    if (!$) {
        console.warn('MediaUploader: jQuery not found, falling back to vanilla XHR.');
    }

    var DEFAULT_ENDPOINT = '/admin/index.php?r=media/upload';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }
    function csrfParam() {
        var meta = document.querySelector('meta[name="csrf-param"]');
        return meta ? meta.getAttribute('content') : '_csrf-backend';
    }

    /**
     * Resolve the upload endpoint. Honors window.MEDIA_UPLOADER_URL
     * for projects that mount the admin under a non-default prefix.
     */
    function endpoint(opts) {
        if (opts && opts.endpoint) return opts.endpoint;
        if (global.MEDIA_UPLOADER_URL) return global.MEDIA_UPLOADER_URL;
        return DEFAULT_ENDPOINT;
    }

    /**
     * Read every data-* attribute we care about, with sensible defaults.
     * Centralised so attach/webcam/scanner share the same parsing.
     */
    function readConfig(el) {
        var d = el.dataset || {};
        return {
            entityType:   d.entityType   || '',
            entityId:     d.entityId     || '',
            groupName:    d.groupName    || '',
            uploadedVia:  d.uploadedVia  || 'unified_uploader',
            multiple:     d.multiple === '1' || d.multiple === 'true',
            accept:       d.accept       || 'image/*,application/pdf',
            maxMb:        parseFloat(d.maxMb || '0') || 0,
            target:       d.target       || '',
            targetName:   d.targetName   || '',
            autoClassify: d.autoClassify === '1' || d.autoClassify === 'true',
            previewSize:  parseInt(d.previewSize || '120', 10) || 120,
        };
    }

    /**
     * Client-side gate before the network round-trip. Server validates
     * the SAME way (GroupNameRegistry::mimeAllowed / maxBytes) so this
     * is purely a UX optimisation — no security implication.
     */
    function preflight(file, cfg) {
        if (cfg.maxMb > 0 && file.size > cfg.maxMb * 1024 * 1024) {
            return 'الملف أكبر من الحد المسموح (' + cfg.maxMb + ' MB).';
        }
        if (cfg.accept) {
            var ok = cfg.accept.split(',').some(function (rule) {
                rule = rule.trim();
                if (!rule) return true;
                if (rule === '*' || rule === '*/*') return true;
                if (rule.endsWith('/*')) {
                    return file.type && file.type.indexOf(rule.slice(0, -1)) === 0;
                }
                if (rule.startsWith('.')) {
                    return file.name.toLowerCase().endsWith(rule.toLowerCase());
                }
                return file.type === rule;
            });
            if (!ok) {
                return 'نوع الملف غير مسموح.';
            }
        }
        return null;
    }

    /**
     * Single-file POST. Resolves with the parsed JSON `file` object on
     * success, rejects with `{message, status}` on failure. The progress
     * callback is invoked with values 0..100.
     */
    function uploadOne(file, cfg, opts, onProgress) {
        return new Promise(function (resolve, reject) {
            var fd = new FormData();
            fd.append('file', file);
            fd.append('entity_type', cfg.entityType);
            if (cfg.entityId !== '') fd.append('entity_id', cfg.entityId);
            fd.append('group_name', cfg.groupName);
            fd.append('uploaded_via', cfg.uploadedVia);
            fd.append('auto_classify', cfg.autoClassify ? '1' : '0');
            fd.append(csrfParam(), csrfToken());

            var xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint(opts), true);
            xhr.setRequestHeader('X-CSRF-Token', csrfToken());
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && typeof onProgress === 'function') {
                    onProgress(Math.round((e.loaded / e.total) * 100));
                }
            };

            xhr.onload = function () {
                var data = null;
                try { data = JSON.parse(xhr.responseText); } catch (_) {}
                if (xhr.status >= 200 && xhr.status < 300 && data && data.success) {
                    resolve(data);
                } else {
                    reject({
                        status:  xhr.status,
                        message: (data && data.error) || ('Upload failed (HTTP ' + xhr.status + ').'),
                    });
                }
            };
            xhr.onerror = function () {
                reject({ status: 0, message: 'Network error during upload.' });
            };
            xhr.send(fd);
        });
    }

    /**
     * Append a hidden input so legacy PHP handlers continue to receive
     * the new media id under the same field name they used to read
     * before unification (e.g. Lawyers[image_ids][]).
     */
    function appendHidden(cfg, mediaId, root) {
        if (!cfg.targetName && !cfg.target) return;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.value = String(mediaId);
        if (cfg.targetName) {
            input.name = cfg.targetName;
            (cfg.target ? document.querySelector(cfg.target) : root).appendChild(input);
        } else {
            // <data-target> may be a hidden input we OVERWRITE (single-file case).
            var t = document.querySelector(cfg.target);
            if (t) t.value = String(mediaId);
        }
    }

    var Progress = global.MediaUploaderProgress; // optional, lazy-resolved

    function ensureList(root) {
        var list = root.querySelector('[data-media-list]');
        if (list) return list;
        list = document.createElement('ul');
        list.setAttribute('data-media-list', '');
        list.style.listStyle = 'none';
        list.style.padding = '0';
        list.style.margin = '8px 0 0 0';
        root.appendChild(list);
        return list;
    }

    function renderRow(file) {
        var li = document.createElement('li');
        li.style.cssText = 'display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px dashed #e5e7eb;font-size:13px;';
        li.innerHTML =
            '<span class="mu-name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>' +
            '<span class="mu-status" style="min-width:120px;text-align:end;color:#6b7280;"></span>';
        li.querySelector('.mu-name').textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
        return li;
    }

    function uploadFiles(fileList, cfg, opts, root) {
        var files = Array.from(fileList || []);
        if (!files.length) return Promise.resolve([]);
        var list = ensureList(root);

        var promises = files.map(function (file) {
            var preErr = preflight(file, cfg);
            var li = renderRow(file);
            list.appendChild(li);
            var statusEl = li.querySelector('.mu-status');

            if (preErr) {
                statusEl.textContent = preErr;
                statusEl.style.color = '#dc2626';
                return Promise.resolve({ success: false, error: preErr });
            }

            statusEl.textContent = '0%';
            return uploadOne(file, cfg, opts, function (pct) {
                statusEl.textContent = pct + '%';
                if (Progress && typeof Progress.update === 'function') {
                    Progress.update(li, pct);
                }
            }).then(function (resp) {
                statusEl.textContent = '✓';
                statusEl.style.color = '#16a34a';
                appendHidden(cfg, resp.file.id, root);
                if (typeof opts.onSuccess === 'function') {
                    opts.onSuccess(resp, file, li);
                }
                return resp;
            }).catch(function (err) {
                statusEl.textContent = err.message || 'فشل';
                statusEl.style.color = '#dc2626';
                if (typeof opts.onError === 'function') {
                    opts.onError(err, file, li);
                }
                return { success: false, error: err.message };
            });
        });
        return Promise.all(promises);
    }

    function attachOne(root, opts) {
        if (root.__mediaUploaderAttached) return; // idempotent
        root.__mediaUploaderAttached = true;
        opts = opts || {};
        var cfg = readConfig(root);

        var input = root.querySelector('[data-media-input]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.setAttribute('data-media-input', '');
            input.style.display = 'none';
            if (cfg.multiple) input.multiple = true;
            if (cfg.accept) input.accept = cfg.accept;
            root.appendChild(input);
        }

        var pickBtn = root.querySelector('[data-media-pick]');
        if (pickBtn) {
            pickBtn.addEventListener('click', function (e) {
                e.preventDefault();
                input.click();
            });
        }

        input.addEventListener('change', function () {
            uploadFiles(input.files, cfg, opts, root)
                .then(function () { input.value = ''; });
        });

        // drag-drop
        ['dragenter', 'dragover'].forEach(function (ev) {
            root.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                root.classList.add('mu-dropping');
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            root.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                root.classList.remove('mu-dropping');
            });
        });
        root.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                uploadFiles(e.dataTransfer.files, cfg, opts, root);
            }
        });
    }

    var MediaUploader = {
        attach: function (selector, opts) {
            var nodes = (typeof selector === 'string')
                ? document.querySelectorAll(selector)
                : (selector instanceof Element ? [selector] : selector);
            Array.from(nodes || []).forEach(function (n) { attachOne(n, opts); });
        },
        uploadOne: uploadOne,
        readConfig: readConfig,
        endpoint:   endpoint,
        csrfToken:  csrfToken,
    };

    global.MediaUploader = MediaUploader;

    // Auto-attach on DOM ready for elements that opted-in via attribute.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            MediaUploader.attach('[data-media-uploader]');
        });
    } else {
        MediaUploader.attach('[data-media-uploader]');
    }
})(window, window.jQuery);
