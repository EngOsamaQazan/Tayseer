(function () {
    'use strict';

    var POLL_INTERVAL = 30000;
    var POLL_URL = '/notification/notification/poll';
    var MARK_READ_URL = '/notification/notification/is-read';
    var lastId = 0;
    var currentCount = 0;
    var timerId = null;
    var toastContainer = null;

    function init() {
        var badge = document.getElementById('notifBadge');
        if (badge) {
            currentCount = parseInt(badge.textContent, 10) || 0;
        }

        var items = document.querySelectorAll('.notif-item');
        if (items.length > 0) {
            var firstItem = items[0];
            var id = firstItem.getAttribute('data-notif-id');
            if (id) lastId = parseInt(id, 10);
        }

        createToastContainer();
        startPolling();
        setupVisibilityHandling();
        setupMarkReadHandlers();
    }

    function createToastContainer() {
        toastContainer = document.getElementById('notifToastContainer');
        if (toastContainer) return;

        toastContainer = document.createElement('div');
        toastContainer.id = 'notifToastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 start-0 p-3';
        toastContainer.style.zIndex = '9999';
        toastContainer.setAttribute('aria-live', 'polite');
        document.body.appendChild(toastContainer);
    }

    function poll() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', POLL_URL + '?since=' + lastId, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 403 || xhr.status === 401) {
                stopPolling();
                return;
            }
            if (xhr.status !== 200) return;
            try {
                var data = JSON.parse(xhr.responseText);
                handlePollResponse(data);
            } catch (e) { /* silent */ }
        };
        xhr.send();
    }

    function handlePollResponse(data) {
        if (data.lastId && data.lastId > lastId) {
            lastId = data.lastId;
        }

        if (data.count !== currentCount) {
            currentCount = data.count;
            updateBadge(data.count);
        }

        if (data.items && data.items.length > 0) {
            data.items.forEach(function (item) {
                showToast(item);
            });
            playSound();
        }
    }

    function updateBadge(count) {
        var badge = document.getElementById('notifBadge');
        if (count > 0) {
            var text = count > 99 ? '99+' : count.toString();
            if (badge) {
                badge.textContent = text;
                badge.style.display = '';
            } else {
                var bellLink = document.querySelector('#notifDropdown > a');
                if (bellLink) {
                    badge = document.createElement('span');
                    badge.id = 'notifBadge';
                    badge.className = 'position-absolute top-0 start-50 translate-middle-y badge rounded-pill bg-danger';
                    badge.style.fontSize = '10px';
                    badge.textContent = text;
                    bellLink.appendChild(badge);
                }
            }
        } else if (badge) {
            badge.style.display = 'none';
        }
    }

    function showToast(item) {
        if (!toastContainer) return;

        var iconColor = item.color || '#6c757d';
        var icon = item.icon || 'fa-bell';
        var title = item.title || '';
        var timeText = item.time || '';

        var toastEl = document.createElement('div');
        toastEl.className = 'toast show border-0 shadow-lg mb-2';
        toastEl.setAttribute('role', 'alert');
        toastEl.style.minWidth = '320px';
        toastEl.style.maxWidth = '400px';
        toastEl.style.borderRight = '4px solid ' + iconColor;
        toastEl.style.direction = 'rtl';

        toastEl.innerHTML =
            '<div class="toast-header border-0">' +
            '  <span class="rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width:28px;height:28px;background:' + iconColor + '20">' +
            '    <i class="fa-solid ' + icon + '" style="color:' + iconColor + ';font-size:13px"></i>' +
            '  </span>' +
            '  <strong class="me-auto" style="font-size:13px">إشعار جديد</strong>' +
            '  <small class="text-muted">' + timeText + '</small>' +
            '  <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="toast"></button>' +
            '</div>' +
            '<div class="toast-body" style="font-size:13px;padding-top:0">' +
            '  ' + escapeHtml(title) +
            '</div>';

        if (item.href) {
            toastEl.style.cursor = 'pointer';
            toastEl.addEventListener('click', function (e) {
                if (e.target.closest('.btn-close')) return;
                window.location.href = item.href;
            });
        }

        toastContainer.prepend(toastEl);

        setTimeout(function () {
            toastEl.classList.add('hiding');
            toastEl.style.transition = 'opacity 0.4s ease';
            toastEl.style.opacity = '0';
            setTimeout(function () {
                if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
            }, 400);
        }, 6000);
    }

    function playSound() {
        try {
            var audio = new Audio('/plugins/notifications/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(function () { /* autoplay blocked, ignore */ });
        } catch (e) { /* no sound support */ }
    }

    function startPolling() {
        if (timerId) clearInterval(timerId);
        timerId = setInterval(poll, POLL_INTERVAL);
    }

    function stopPolling() {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
    }

    function setupVisibilityHandling() {
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopPolling();
            } else {
                poll();
                startPolling();
            }
        });
    }

    function setupMarkReadHandlers() {
        var dropdown = document.getElementById('notifDropdown');
        if (!dropdown) return;

        document.getElementById('btnMarkAllRead')?.addEventListener('click', function () {
            var btn = this;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري...';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', MARK_READ_URL, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) {
                xhr.setRequestHeader('X-CSRF-Token', csrf.getAttribute('content'));
            }
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                currentCount = 0;
                updateBadge(0);
                document.querySelectorAll('.notif-unread').forEach(function (el) {
                    el.classList.remove('notif-unread');
                    el.style.background = 'transparent';
                });
                document.querySelectorAll('.notif-dot').forEach(function (el) {
                    el.style.background = 'var(--bs-border-color)';
                });
                btn.innerHTML = '<i class="fa fa-check"></i> تم التمييز';
                setTimeout(function () {
                    btn.innerHTML = '<i class="fa fa-check-double"></i> تمييز الجميع كمقروء';
                }, 2000);
            };
            xhr.send('_csrf-backend=' + encodeURIComponent(getCSRF()));
        });
    }

    function getCSRF() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
