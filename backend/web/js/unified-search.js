/**
 * Unified Search Widget — Autocomplete
 *
 * Expected JSON response from the server:
 *   { results: [ { id, title, sub?, icon?, url? }, ... ] }
 */
var UnifiedSearch = (function () {
    'use strict';

    var instances = {};

    function init(cfg) {
        var el   = document.getElementById(cfg.inputId);
        var wrap = document.getElementById(cfg.inputId + '-wrap');
        if (!el || !wrap) return;

        var dd      = wrap.querySelector('.us-dropdown');
        var spinner = wrap.querySelector('.us-spinner');
        var timer   = null;
        var xhr     = null;
        var activeIdx = -1;
        var items   = [];

        el.addEventListener('input', function () {
            var q = el.value.trim();
            clearTimeout(timer);
            if (q.length < cfg.minChars) {
                hide();
                return;
            }
            timer = setTimeout(function () { search(q); }, cfg.delay);
        });

        el.addEventListener('keydown', function (e) {
            if (dd.style.display === 'none') return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                move(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                move(-1);
            } else if (e.key === 'Enter' && activeIdx >= 0) {
                e.preventDefault();
                select(items[activeIdx]);
            } else if (e.key === 'Escape') {
                hide();
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) hide();
        });

        function search(q) {
            if (xhr) xhr.abort();
            spinner.style.display = '';
            xhr = new XMLHttpRequest();
            xhr.open('GET', cfg.url + (cfg.url.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q));
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function () {
                spinner.style.display = 'none';
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        render(data.results || [], q);
                    } catch (e) {
                        hide();
                    }
                }
            };
            xhr.onerror = function () { spinner.style.display = 'none'; };
            xhr.send();
        }

        function render(results, q) {
            items = results;
            activeIdx = -1;
            if (!results.length) {
                dd.innerHTML = '<div class="us-empty">لا توجد نتائج</div>';
                dd.style.display = '';
                return;
            }
            var html = '';
            for (var i = 0; i < results.length; i++) {
                var r = results[i];
                var icon = r.icon || 'fa-file-text-o';
                html += '<div class="us-item" data-idx="' + i + '">';
                html += '<div class="us-item-icon"><i class="fa ' + icon + '"></i></div>';
                html += '<div class="us-item-body">';
                html += '<div class="us-item-title">' + highlight(esc(r.title), q) + '</div>';
                if (r.sub) html += '<div class="us-item-sub">' + highlight(esc(r.sub), q) + '</div>';
                html += '</div>';
                if (r.id) html += '<span class="us-item-id">#' + esc(String(r.id)) + '</span>';
                html += '</div>';
            }
            dd.innerHTML = html;
            dd.style.display = '';

            var itemEls = dd.querySelectorAll('.us-item');
            for (var j = 0; j < itemEls.length; j++) {
                (function (idx) {
                    itemEls[idx].addEventListener('click', function () {
                        select(results[idx]);
                    });
                    itemEls[idx].addEventListener('mouseenter', function () {
                        setActive(idx);
                    });
                })(j);
            }
        }

        function select(item) {
            el.value = item.title;
            hide();

            if (cfg.navigateOnSelect && item.url) {
                window.location.href = item.url;
                return;
            }

            if (cfg.formSelector) {
                var form = document.querySelector(cfg.formSelector);
                if (form) form.submit();
            }
        }

        function move(dir) {
            var newIdx = activeIdx + dir;
            if (newIdx < 0) newIdx = items.length - 1;
            if (newIdx >= items.length) newIdx = 0;
            setActive(newIdx);
        }

        function setActive(idx) {
            activeIdx = idx;
            var els = dd.querySelectorAll('.us-item');
            for (var i = 0; i < els.length; i++) {
                els[i].classList.toggle('us-active', i === idx);
            }
            if (els[idx]) els[idx].scrollIntoView({ block: 'nearest' });
        }

        function hide() {
            dd.style.display = 'none';
            dd.innerHTML = '';
            activeIdx = -1;
            items = [];
        }

        function esc(s) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(s));
            return d.innerHTML;
        }

        function highlight(text, q) {
            if (!q) return text;
            var words = q.split(/\s+/).filter(Boolean);
            for (var i = 0; i < words.length; i++) {
                var re = new RegExp('(' + words[i].replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                text = text.replace(re, '<span class="us-highlight">$1</span>');
            }
            return text;
        }

        instances[cfg.inputId] = { el: el, hide: hide };
    }

    return { init: init, instances: instances };
})();
