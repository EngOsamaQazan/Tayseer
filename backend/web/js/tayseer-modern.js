/**
 * Tayseer ERP — Modern Libraries Initialization & Auto-Enhancement
 * =================================================================
 * Covers: SweetAlert2, AOS, Tippy, HTMX, SortableJS, Alpine.js
 * Auto-enhances: GridViews, boxes, stat cards, action buttons, forms
 */

(function () {
    'use strict';

    /* ================================================================
       1. SweetAlert2 — Override Yii2 native confirm() globally
       ================================================================ */
    if (typeof yii !== 'undefined' && typeof Swal !== 'undefined') {
        yii.confirm = function (message, ok, cancel) {
            Swal.fire({
                title: 'تأكيد العملية',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-check"></i> نعم، تأكيد',
                cancelButtonText: '<i class="fa fa-times"></i> إلغاء',
                confirmButtonColor: '#800020',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'tayseer-swal-popup',
                    title: 'tayseer-swal-title',
                    confirmButton: 'tayseer-swal-confirm',
                    cancelButton: 'tayseer-swal-cancel'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    ok && ok();
                } else {
                    cancel && cancel();
                }
            });
        };
    }

    /* ================================================================
       1b. SweetAlert2 — Override native window.alert() & window.confirm()
       Covers the 29 files using alert()/confirm() directly in JS
       ================================================================ */
    if (typeof Swal !== 'undefined') {
        window._nativeAlert = window.alert;
        window.alert = function (message) {
            Swal.fire({
                title: message,
                icon: 'info',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#800020',
                customClass: { popup: 'tayseer-swal-popup', title: 'tayseer-swal-title' }
            });
        };

        window._nativeConfirm = window.confirm;
        window.confirm = function (message) {
            // For synchronous calls we can't fully replace, but we log a warning
            // and return true. Async version available via TayseerConfirm.
            console.warn('[Tayseer] Native confirm() called. Use TayseerConfirm() for async version.');
            return window._nativeConfirm(message);
        };

        /**
         * Async confirm replacement for use in new code:
         *   TayseerConfirm('هل أنت متأكد؟').then(ok => { if(ok) doSomething(); });
         */
        window.TayseerConfirm = function (message, title) {
            return Swal.fire({
                title: title || 'تأكيد',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-check"></i> نعم',
                cancelButtonText: '<i class="fa fa-times"></i> لا',
                confirmButtonColor: '#800020',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                customClass: { popup: 'tayseer-swal-popup' }
            }).then(function (result) { return result.isConfirmed; });
        };
    }

    /* ================================================================
       2. Tayseer Toast — Global notification helper
       ================================================================ */
    window.TayseerToast = {
        success: function (message, title) {
            Swal.fire({ toast: true, position: 'top-start', icon: 'success', title: title || message, text: title ? message : undefined, showConfirmButton: false, timer: 3000, timerProgressBar: true });
        },
        error: function (message, title) {
            Swal.fire({ toast: true, position: 'top-start', icon: 'error', title: title || message, text: title ? message : undefined, showConfirmButton: false, timer: 5000, timerProgressBar: true });
        },
        info: function (message, title) {
            Swal.fire({ toast: true, position: 'top-start', icon: 'info', title: title || message, text: title ? message : undefined, showConfirmButton: false, timer: 3000, timerProgressBar: true });
        },
        warning: function (message, title) {
            Swal.fire({ toast: true, position: 'top-start', icon: 'warning', title: title || message, text: title ? message : undefined, showConfirmButton: false, timer: 4000, timerProgressBar: true });
        }
    };

    /* ================================================================
       3. AOS — Initialize (explicit data-aos only, no auto-add)
       ================================================================
       IMPORTANT: Do NOT auto-add data-aos attributes to elements.
       AOS CSS sets [data-aos]{opacity:0} which hides elements permanently
       if AOS.refresh() fails. Use CSS animations below instead. */
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 500, easing: 'ease-out-cubic', once: true, offset: 0, delay: 0 });
    }

    /* ================================================================
       3b. CSS-based entrance animations (safe, no hiding)
       ================================================================ */
    var stagger = 0;
    document.querySelectorAll('.db-kpi, .stat-card, .info-box, .small-box').forEach(function (el) {
        el.style.animationDelay = stagger + 'ms';
        el.classList.add('ty-entrance');
        stagger += 60;
    });

    stagger = 0;
    document.querySelectorAll('.box, .db-card, .card').forEach(function (el) {
        if (!el.closest('.modal') && !el.closest('.dropdown-menu')) {
            el.style.animationDelay = stagger + 'ms';
            el.classList.add('ty-entrance');
            stagger += 80;
        }
    });

    document.querySelectorAll('.content-header').forEach(function (el) {
        el.classList.add('ty-entrance-down');
    });

    /* ================================================================
       4. Tippy.js — Set defaults + Auto-enhance GridView actions
       ================================================================ */
    if (typeof tippy !== 'undefined') {
        tippy.setDefaultProps({
            placement: 'top',
            animation: 'shift-away',
            theme: 'tayseer',
            delay: [200, 0],
            allowHTML: true
        });

        // Init explicit tooltips
        tippy('[data-tippy-content]');

        // Auto-enhance GridView action buttons with tooltips
        var actionTooltips = {
            'glyphicon-eye-open': 'عرض التفاصيل',
            'glyphicon-pencil': 'تعديل',
            'glyphicon-trash': 'حذف',
            'fa-eye': 'عرض التفاصيل',
            'fa-pencil': 'تعديل',
            'fa-pen': 'تعديل',
            'fa-pen-to-square': 'تعديل',
            'fa-edit': 'تعديل',
            'fa-trash': 'حذف',
            'fa-trash-o': 'حذف',
            'fa-trash-can': 'حذف',
            'fa-print': 'طباعة',
            'fa-download': 'تحميل',
            'fa-file-pdf-o': 'تصدير PDF',
            'fa-file-excel-o': 'تصدير Excel',
            'fa-check': 'تأكيد',
            'fa-times': 'إلغاء',
            'fa-ban': 'حظر',
            'fa-undo': 'تراجع',
            'fa-copy': 'نسخ',
            'fa-link': 'ربط',
            'fa-money': 'دفعة',
            'fa-calendar': 'جدولة',
            'fa-gavel': 'إجراء قضائي'
        };

        document.querySelectorAll('.kv-grid-table .kv-action-column a, .grid-view td a[title], .kv-grid-table td a[data-pjax="0"]').forEach(function (link) {
            if (link._tippy) return;

            var tooltip = '';
            // Check if link has a title attribute
            if (link.getAttribute('title')) {
                tooltip = link.getAttribute('title');
                link.removeAttribute('title');
            } else {
                // Detect from icon class
                var icon = link.querySelector('i, span.glyphicon');
                if (icon) {
                    var classes = icon.className;
                    for (var key in actionTooltips) {
                        if (classes.indexOf(key) !== -1) {
                            tooltip = actionTooltips[key];
                            break;
                        }
                    }
                }
            }

            if (tooltip) {
                tippy(link, { content: tooltip });
            }
        });

        // Auto-enhance buttons with title attributes
        document.querySelectorAll('.btn[title], a[title]:not(.kv-grid-table a)').forEach(function (el) {
            if (el._tippy) return;
            var t = el.getAttribute('title');
            if (t && t.length > 0) {
                el.removeAttribute('title');
                tippy(el, { content: t });
            }
        });
    }

    /* ================================================================
       5. HTMX — CSRF config for Yii2
       ================================================================ */
    if (typeof htmx !== 'undefined') {
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfParam = document.querySelector('meta[name="csrf-param"]');

        if (csrfMeta && csrfParam) {
            document.body.addEventListener('htmx:configRequest', function (event) {
                event.detail.headers[csrfParam.getAttribute('content')] = csrfMeta.getAttribute('content');
                event.detail.headers['X-CSRF-Token'] = csrfMeta.getAttribute('content');
            });
        }

        htmx.config.indicatorClass = 'htmx-indicator';
        htmx.config.requestClass = 'htmx-request';
        htmx.config.defaultSwapStyle = 'innerHTML';
    }

    /* ================================================================
       6. SortableJS — Auto-init [data-sortable]
       ================================================================ */
    if (typeof Sortable !== 'undefined') {
        document.querySelectorAll('[data-sortable]').forEach(function (el) {
            Sortable.create(el, {
                animation: 200,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                handle: el.dataset.sortableHandle || undefined,
                onEnd: function (evt) {
                    var url = el.dataset.sortableUrl;
                    if (url && typeof jQuery !== 'undefined') {
                        var order = Array.from(el.children).map(function (child, index) {
                            return { id: child.dataset.id, position: index };
                        });
                        jQuery.post(url, { order: JSON.stringify(order) });
                    }
                }
            });
        });
    }

    /* ================================================================
       7. GridView Auto-Enhancement — Smooth loading & row interactions
       ================================================================ */
    (function enhanceGridViews() {
        // Add fade-in animation to GridView tables
        document.querySelectorAll('.kv-grid-table, .grid-view table').forEach(function (table) {
            table.style.opacity = '0';
            table.style.transition = 'opacity 0.4s ease';
            setTimeout(function () { table.style.opacity = '1'; }, 100);
        });

        // Enhance Pjax-loaded GridViews: re-init Tippy/AOS after Pjax reload
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('pjax:complete', function () {
                // Re-init Tippy on new action buttons
                if (typeof tippy !== 'undefined') {
                    document.querySelectorAll('.kv-grid-table .kv-action-column a, .grid-view td a[title]').forEach(function (link) {
                        if (link._tippy) return;
                        var t = link.getAttribute('title');
                        if (t) {
                            link.removeAttribute('title');
                            tippy(link, { content: t });
                        }
                    });
                }
                // Re-init AOS
                if (typeof AOS !== 'undefined') AOS.refresh();
                // Re-add fade-in to tables
                document.querySelectorAll('.kv-grid-table, .grid-view table').forEach(function (table) {
                    table.style.opacity = '0';
                    setTimeout(function () { table.style.opacity = '1'; }, 100);
                });
            });
        }
    })();

    /* ================================================================
       8. Form Enhancement — Loading state for submit buttons
       ================================================================ */
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (btn && !btn.classList.contains('no-loading')) {
                btn.disabled = true;
                var origHtml = btn.innerHTML || btn.value;
                if (btn.tagName === 'BUTTON') {
                    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...';
                }
                setTimeout(function () {
                    btn.disabled = false;
                    if (btn.tagName === 'BUTTON') btn.innerHTML = origHtml;
                }, 8000);
            }
        });
    });

    /* ================================================================
       9. Auto-enhance: Fieldset legends with icons (if missing)
       ================================================================ */
    document.querySelectorAll('.jadal-fieldset legend, .so-fieldset legend, fieldset legend').forEach(function (legend) {
        if (!legend.querySelector('i') && !legend.querySelector('svg')) {
            legend.insertAdjacentHTML('afterbegin', '<i class="fa fa-folder-open" style="margin-left:6px;opacity:0.7"></i> ');
        }
    });

})();
