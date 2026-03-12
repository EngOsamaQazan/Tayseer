(function(){
    var cfg = {
        dateFormat: 'Y-m-d',
        locale: 'ar',
        disableMobile: true,
        allowInput: true
    };

    function initAll(root) {
        var els = (root || document).querySelectorAll('input[type="date"]:not([data-fp-done])');
        els.forEach(function(el) {
            el.setAttribute('data-fp-done', '1');
            el.type = 'text';
            el.autocomplete = 'off';
            var opts = Object.assign({}, cfg);
            if (el.min) opts.minDate = el.min;
            if (el.max) opts.maxDate = el.max;
            var fp = flatpickr(el, opts);
            el._flatpickr = fp;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){ initAll(); });
    } else {
        initAll();
    }

    var observer = new MutationObserver(function(mutations) {
        var dominated = false;
        for (var i = 0; i < mutations.length; i++) {
            if (mutations[i].addedNodes.length > 0) { dominated = true; break; }
        }
        if (dominated) setTimeout(function(){ initAll(); }, 100);
    });
    observer.observe(document.body, { childList: true, subtree: true });

    window._fpInitAll = initAll;
})();
