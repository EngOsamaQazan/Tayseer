/**
 * Tayseer ERP — Vite Entry Point
 * ===============================
 * Bundles all modern libraries + init code into a single optimized file.
 * Tabler (Bootstrap 5) + Tailwind CSS (tw- prefix) + modern libs.
 */

// ═══ Tabler CSS + Tailwind CSS ═══
// Bootstrap 5 JS loaded by Yii2 asset manager (bootstrap.bundle.js) as a regular script,
// ensuring window.bootstrap is available before inline Kartik widget scripts run.
// Tabler JS is NOT imported here to avoid bundling a duplicate Bootstrap 5 copy.
import '@tabler/core/dist/css/tabler.rtl.min.css';
import '@/css/tailwind.css';

// ═══ Library Imports ═══
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import ApexCharts from 'apexcharts';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/animations/shift-away.css';
import Sortable from 'sortablejs';
import AOS from 'aos';
import 'aos/dist/aos.css';
import htmx from 'htmx.org';

// ═══ Global Availability (for inline PHP view scripts) ═══
window.Swal = Swal;
window.ApexCharts = ApexCharts;
window.tippy = tippy;
window.Sortable = Sortable;
window.AOS = AOS;
window.htmx = htmx;

// ═══ 1. SweetAlert2 — Override Yii2 confirm ═══
if (typeof yii !== 'undefined') {
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
            customClass: { popup: 'tayseer-swal-popup', title: 'tayseer-swal-title', confirmButton: 'tayseer-swal-confirm', cancelButton: 'tayseer-swal-cancel' }
        }).then(function (result) {
            if (result.isConfirmed) { ok && ok(); } else { cancel && cancel(); }
        });
    };
}

// ═══ 1b. Override native alert/confirm ═══
window._nativeAlert = window.alert;
window.alert = function (message) {
    Swal.fire({
        title: message, icon: 'info', confirmButtonText: 'حسناً', confirmButtonColor: '#800020',
        customClass: { popup: 'tayseer-swal-popup', title: 'tayseer-swal-title' }
    });
};

window._nativeConfirm = window.confirm;
window.confirm = function (message) {
    console.warn('[Tayseer] Native confirm() called. Use TayseerConfirm() for async version.');
    return window._nativeConfirm(message);
};

window.TayseerConfirm = function (message, title) {
    return Swal.fire({
        title: title || 'تأكيد', text: message, icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check"></i> نعم',
        cancelButtonText: '<i class="fa fa-times"></i> لا',
        confirmButtonColor: '#800020', cancelButtonColor: '#6c757d',
        reverseButtons: true, customClass: { popup: 'tayseer-swal-popup' }
    }).then(function (result) { return result.isConfirmed; });
};

// ═══ 2. TayseerToast ═══
window.TayseerToast = {
    success: (msg, title) => Swal.fire({ toast: true, position: 'top-start', icon: 'success', title: title || msg, text: title ? msg : undefined, showConfirmButton: false, timer: 3000, timerProgressBar: true }),
    error:   (msg, title) => Swal.fire({ toast: true, position: 'top-start', icon: 'error',   title: title || msg, text: title ? msg : undefined, showConfirmButton: false, timer: 5000, timerProgressBar: true }),
    info:    (msg, title) => Swal.fire({ toast: true, position: 'top-start', icon: 'info',    title: title || msg, text: title ? msg : undefined, showConfirmButton: false, timer: 3000, timerProgressBar: true }),
    warning: (msg, title) => Swal.fire({ toast: true, position: 'top-start', icon: 'warning', title: title || msg, text: title ? msg : undefined, showConfirmButton: false, timer: 4000, timerProgressBar: true }),
};

// ═══ 3. AOS — Init only (no auto-add data-aos) ═══
AOS.init({ duration: 500, easing: 'ease-out-cubic', once: true, offset: 0, delay: 0 });

// CSS-based entrance animations
let stagger = 0;
document.querySelectorAll('.db-kpi, .stat-card, .info-box, .small-box').forEach(el => {
    el.style.animationDelay = stagger + 'ms';
    el.classList.add('ty-entrance');
    stagger += 60;
});
stagger = 0;
document.querySelectorAll('.box, .db-card, .card').forEach(el => {
    if (!el.closest('.modal') && !el.closest('.dropdown-menu')) {
        el.style.animationDelay = stagger + 'ms';
        el.classList.add('ty-entrance');
        stagger += 80;
    }
});
document.querySelectorAll('.content-header').forEach(el => el.classList.add('ty-entrance-down'));

// ═══ 4. Tippy.js ═══
tippy.setDefaultProps({ placement: 'top', animation: 'shift-away', theme: 'tayseer', delay: [200, 0], allowHTML: true });
tippy('[data-tippy-content]');

const actionTooltips = {
    'glyphicon-eye-open': 'عرض التفاصيل', 'glyphicon-pencil': 'تعديل', 'glyphicon-trash': 'حذف',
    'fa-eye': 'عرض التفاصيل', 'fa-pencil': 'تعديل', 'fa-pen': 'تعديل', 'fa-pen-to-square': 'تعديل',
    'fa-edit': 'تعديل', 'fa-trash': 'حذف', 'fa-trash-o': 'حذف', 'fa-trash-can': 'حذف',
    'fa-print': 'طباعة', 'fa-download': 'تحميل', 'fa-file-pdf-o': 'تصدير PDF',
    'fa-file-excel-o': 'تصدير Excel', 'fa-check': 'تأكيد', 'fa-times': 'إلغاء',
    'fa-ban': 'حظر', 'fa-undo': 'تراجع', 'fa-copy': 'نسخ', 'fa-link': 'ربط',
    'fa-money': 'دفعة', 'fa-calendar': 'جدولة', 'fa-gavel': 'إجراء قضائي'
};
document.querySelectorAll('.kv-grid-table .kv-action-column a, .grid-view td a[title], .kv-grid-table td a[data-pjax="0"]').forEach(link => {
    if (link._tippy) return;
    let tooltip = '';
    if (link.getAttribute('title')) {
        tooltip = link.getAttribute('title');
        link.removeAttribute('title');
    } else {
        const icon = link.querySelector('i, span.glyphicon');
        if (icon) {
            for (const [key, val] of Object.entries(actionTooltips)) {
                if (icon.className.includes(key)) { tooltip = val; break; }
            }
        }
    }
    if (tooltip) tippy(link, { content: tooltip });
});
document.querySelectorAll('.btn[title], a[title]:not(.kv-grid-table a)').forEach(el => {
    if (el._tippy) return;
    const t = el.getAttribute('title');
    if (t && t.length > 0) { el.removeAttribute('title'); tippy(el, { content: t }); }
});

// ═══ 5. HTMX — CSRF for Yii2 ═══
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const csrfParam = document.querySelector('meta[name="csrf-param"]');
if (csrfMeta && csrfParam) {
    document.body.addEventListener('htmx:configRequest', e => {
        e.detail.headers[csrfParam.getAttribute('content')] = csrfMeta.getAttribute('content');
        e.detail.headers['X-CSRF-Token'] = csrfMeta.getAttribute('content');
    });
}
htmx.config.indicatorClass = 'htmx-indicator';
htmx.config.requestClass = 'htmx-request';
htmx.config.defaultSwapStyle = 'innerHTML';

// ═══ 6. SortableJS — Auto-init [data-sortable] ═══
document.querySelectorAll('[data-sortable]').forEach(el => {
    Sortable.create(el, {
        animation: 200, ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
        handle: el.dataset.sortableHandle || undefined,
        group: el.dataset.sortableGroup || undefined,
        onEnd(evt) {
            const url = el.dataset.sortableUrl;
            if (url && typeof jQuery !== 'undefined') {
                const order = Array.from(el.children).map((child, i) => ({ id: child.dataset.id, position: i }));
                jQuery.post(url, { order: JSON.stringify(order) });
            }
        }
    });
});

// ═══ 7. GridView Auto-Enhancement ═══
document.querySelectorAll('.kv-grid-table, .grid-view table').forEach(table => {
    table.style.opacity = '0';
    table.style.transition = 'opacity 0.4s ease';
    setTimeout(() => { table.style.opacity = '1'; }, 100);
});

if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', () => {
        tippy('[data-tippy-content]');
        document.querySelectorAll('.kv-grid-table .kv-action-column a, .grid-view td a[title]').forEach(link => {
            if (link._tippy) return;
            const t = link.getAttribute('title');
            if (t) { link.removeAttribute('title'); tippy(link, { content: t }); }
        });
        AOS.refresh();
        document.querySelectorAll('.kv-grid-table, .grid-view table').forEach(table => {
            table.style.opacity = '0';
            setTimeout(() => { table.style.opacity = '1'; }, 100);
        });
    });
}

// ═══ 8. Form Enhancement ═══
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
        const btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (btn && !btn.classList.contains('no-loading')) {
            btn.disabled = true;
            const origHtml = btn.innerHTML || btn.value;
            if (btn.tagName === 'BUTTON') btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...';
            setTimeout(() => { btn.disabled = false; if (btn.tagName === 'BUTTON') btn.innerHTML = origHtml; }, 8000);
        }
    });
});

// ═══ 9. Fieldset legend icons ═══
document.querySelectorAll('.jadal-fieldset legend, .so-fieldset legend, fieldset legend').forEach(legend => {
    if (!legend.querySelector('i') && !legend.querySelector('svg')) {
        legend.insertAdjacentHTML('afterbegin', '<i class="fa fa-folder-open" style="margin-left:6px;opacity:0.7"></i> ');
    }
});

// ═══ 10. BS4 → BS5 Data Attribute Shim ═══
const bs4to5Map = {
    'data-toggle': 'data-bs-toggle',
    'data-dismiss': 'data-bs-dismiss',
    'data-target': 'data-bs-target',
    'data-slide': 'data-bs-slide',
    'data-slide-to': 'data-bs-slide-to',
    'data-ride': 'data-bs-ride',
    'data-parent': 'data-bs-parent',
    'data-spy': 'data-bs-spy',
    'data-offset': 'data-bs-offset',
};

function shimBs4Attributes(root) {
    for (const [old, neu] of Object.entries(bs4to5Map)) {
        const scope = root || document;
        scope.querySelectorAll('[' + old + ']').forEach(el => {
            if (!el.hasAttribute(neu)) {
                el.setAttribute(neu, el.getAttribute(old));
            }
        });
        if (root && root.nodeType === 1 && root.hasAttribute && root.hasAttribute(old) && !root.hasAttribute(neu)) {
            root.setAttribute(neu, root.getAttribute(old));
        }
    }
    const scope = root || document;
    scope.querySelectorAll('button.close[aria-hidden]').forEach(el => {
        el.removeAttribute('aria-hidden');
    });
    if (root && root.nodeType === 1 && root.matches && root.matches('button.close[aria-hidden]')) {
        root.removeAttribute('aria-hidden');
    }
}
shimBs4Attributes();

if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', () => shimBs4Attributes());
}

const bs4Observer = new MutationObserver(mutations => {
    for (const m of mutations) {
        m.addedNodes.forEach(n => {
            if (n.nodeType === 1) shimBs4Attributes(n);
        });
    }
});
bs4Observer.observe(document.body, { childList: true, subtree: true });

// Strip aria-hidden from visible modals to prevent accessibility warnings
new MutationObserver(function(mutations) {
    mutations.forEach(function(m) {
        if (m.type === 'attributes' && m.attributeName === 'aria-hidden') {
            const el = m.target;
            if (el.classList && el.classList.contains('modal') && el.getAttribute('aria-hidden') === 'true') {
                if (el.style.display === 'block' || el.classList.contains('show')) {
                    el.removeAttribute('aria-hidden');
                }
            }
        }
    });
}).observe(document.body, { attributes: true, attributeFilter: ['aria-hidden'], subtree: true });

// ═══ 11. Sidebar Toggle (full hide/show on desktop) ═══
(function() {
    const page = document.getElementById('tayseerPage');
    if (!page) return;

    const KEY = 'tayseer-sidebar-hidden';

    function hideSidebar() {
        page.classList.add('sidebar-hidden');
        localStorage.setItem(KEY, '1');
    }
    function showSidebar() {
        page.classList.remove('sidebar-hidden');
        localStorage.setItem(KEY, '0');
    }

    const hideBtn = document.getElementById('sidebarMiniToggle');
    if (hideBtn) {
        hideBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideSidebar();
        });
    }

    const showBtn = document.getElementById('sidebarShowBtn');
    if (showBtn) {
        showBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showSidebar();
        });
    }
})();

// ═══ 12. Alpine.js — Start (must be last) ═══
window.Alpine = Alpine;
Alpine.start();

console.log('[Tayseer] Vite bundle loaded — Tabler + Tailwind + all libraries initialized.');
