/**
 * Phase 6 / M6.1 — Unified MediaUploader (webcam capture).
 *
 * Mounts a getUserMedia preview into a container and POSTs each
 * captured frame through the same endpoint as MediaUploader.attach,
 * keeping the upload contract identical so server code does not branch
 * on "is this a webcam frame or a file?".
 *
 * Usage:
 *
 *   <div data-media-webcam
 *        data-entity-type="customer"
 *        data-entity-id="42"
 *        data-group-name="8"
 *        data-uploaded-via="smart_media"
 *        data-facing-mode="environment">
 *   </div>
 *
 *   <script>MediaUploader.webcam('[data-media-webcam]');</script>
 *
 * Capture button + preview are injected automatically. The same
 * onSuccess/onError callbacks accepted by attach() are honored.
 */
(function (global) {
    'use strict';

    if (!global.MediaUploader) {
        console.error('MediaUploader.webcam: core.js must load first.');
        return;
    }
    var MediaUploader = global.MediaUploader;

    function buildUI(root) {
        root.innerHTML =
            '<div class="mu-webcam-shell" style="display:flex;flex-direction:column;gap:8px;align-items:stretch;">' +
                '<video class="mu-webcam-video" autoplay playsinline ' +
                       'style="width:100%;max-width:480px;border-radius:8px;background:#000;"></video>' +
                '<canvas class="mu-webcam-canvas" style="display:none;"></canvas>' +
                '<div style="display:flex;gap:8px;flex-wrap:wrap;">' +
                    '<button type="button" class="btn btn-primary mu-webcam-capture">' +
                        '<i class="fas fa-camera"></i> التقاط' +
                    '</button>' +
                    '<button type="button" class="btn btn-default mu-webcam-stop">إيقاف الكاميرا</button>' +
                '</div>' +
                '<div class="mu-webcam-status" style="font-size:13px;color:#6b7280;"></div>' +
            '</div>';
    }

    function dataURLtoBlob(dataUrl) {
        var parts = dataUrl.split(',');
        var meta  = parts[0];
        var b64   = parts[1];
        var mime  = (meta.match(/data:([^;]+)/) || [, 'image/png'])[1];
        var bin   = atob(b64);
        var arr   = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
        return new Blob([arr], { type: mime });
    }

    function attachWebcam(root, opts) {
        if (root.__mediaWebcamAttached) return;
        root.__mediaWebcamAttached = true;
        opts = opts || {};
        var cfg = MediaUploader.readConfig(root);
        var facing = root.dataset.facingMode || 'user';

        buildUI(root);
        var video    = root.querySelector('.mu-webcam-video');
        var canvas   = root.querySelector('.mu-webcam-canvas');
        var captureB = root.querySelector('.mu-webcam-capture');
        var stopB    = root.querySelector('.mu-webcam-stop');
        var statusEl = root.querySelector('.mu-webcam-status');

        var stream = null;
        function fail(msg) {
            statusEl.textContent = msg;
            statusEl.style.color = '#dc2626';
            captureB.disabled = true;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            fail('المتصفح لا يدعم التقاط الكاميرا.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: facing }, audio: false })
            .then(function (s) {
                stream = s;
                video.srcObject = s;
                statusEl.textContent = 'الكاميرا جاهزة. اضغط "التقاط".';
            })
            .catch(function (err) {
                fail('تعذّر فتح الكاميرا: ' + err.message);
            });

        captureB.addEventListener('click', function () {
            if (!stream) return;
            var w = video.videoWidth, h = video.videoHeight;
            if (!w || !h) {
                fail('الفيديو لم يبدأ بعد، حاول مرة أخرى بعد لحظة.');
                return;
            }
            canvas.width = w;
            canvas.height = h;
            canvas.getContext('2d').drawImage(video, 0, 0, w, h);
            var dataUrl = canvas.toDataURL('image/png');
            var blob = dataURLtoBlob(dataUrl);
            var fileName = 'webcam_' + new Date().getTime() + '.png';
            var file = new File([blob], fileName, { type: blob.type });

            statusEl.textContent = 'جاري الرفع…';
            statusEl.style.color = '#6b7280';
            MediaUploader.uploadOne(file, cfg, opts, function (pct) {
                statusEl.textContent = 'الرفع: ' + pct + '%';
            }).then(function (resp) {
                statusEl.textContent = '✓ تم الالتقاط والرفع.';
                statusEl.style.color = '#16a34a';
                if (typeof opts.onSuccess === 'function') opts.onSuccess(resp, file);
            }).catch(function (err) {
                statusEl.textContent = err.message || 'فشل الرفع.';
                statusEl.style.color = '#dc2626';
                if (typeof opts.onError === 'function') opts.onError(err, file);
            });
        });

        stopB.addEventListener('click', function () {
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
            statusEl.textContent = 'الكاميرا متوقفة.';
            captureB.disabled = true;
        });
    }

    MediaUploader.webcam = function (selector, opts) {
        var nodes = (typeof selector === 'string')
            ? document.querySelectorAll(selector)
            : (selector instanceof Element ? [selector] : selector);
        Array.from(nodes || []).forEach(function (n) { attachWebcam(n, opts); });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            MediaUploader.webcam('[data-media-webcam]');
        });
    } else {
        MediaUploader.webcam('[data-media-webcam]');
    }
})(window);
