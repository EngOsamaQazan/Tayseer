<?php
/**
 * شريط تبويبات مشترك — AJAX Navigation + History API
 *
 * @var array  $tabs  [['label'=>'...', 'icon'=>'fa-...', 'url'=>['/hr/...']],...]
 * @var string $group معرف المجموعة (tracking, payroll, reports)
 */

use yii\helpers\Url;

$currentUrl = '/' . ltrim(Yii::$app->request->pathInfo, '/');
$groupId = $group ?? 'default';
?>

<div class="hr-section-tabs" data-group="<?= $groupId ?>" id="hr-tabs-<?= $groupId ?>">
    <div class="hr-section-tabs__bar">
        <?php foreach ($tabs as $tab):
            $tabHref = Url::to($tab['url']);
            $tabPath = parse_url($tabHref, PHP_URL_PATH) ?: $tabHref;
            $isActive = ($currentUrl === $tabPath)
                || (isset($tab['match']) && preg_match($tab['match'], $currentUrl));
        ?>
        <a href="<?= $tabHref ?>"
           class="hr-section-tabs__item <?= $isActive ? 'active' : '' ?>"
           data-spa-tab="true">
            <i class="fa <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="hr-tab-progress" id="hr-tab-progress-<?= $groupId ?>">
        <div class="hr-tab-progress__bar"></div>
    </div>
</div>

<style>
.hr-section-tabs{
    margin:-20px -15px 0 -15px;
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    padding:0 20px;
    position:sticky;top:0;z-index:50;
}
.hr-section-tabs__bar{
    display:flex;
    gap:0;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    scrollbar-width:none;
}
.hr-section-tabs__bar::-webkit-scrollbar{display:none}
.hr-section-tabs__item{
    display:flex;
    align-items:center;
    gap:8px;
    padding:14px 20px;
    font-size:13px;
    font-weight:600;
    color:#64748b;
    text-decoration:none;
    white-space:nowrap;
    border-bottom:3px solid transparent;
    transition:all .2s ease;
    position:relative;
}
.hr-section-tabs__item:hover{
    color:#1e293b;
    background:#f8fafc;
    text-decoration:none;
}
.hr-section-tabs__item.active{
    color:var(--clr-primary,#800020);
    border-bottom-color:var(--clr-primary,#800020);
    background:transparent;
}
.hr-section-tabs__item.active:hover{
    background:rgba(128,0,32,.03);
}
.hr-section-tabs__item i{
    font-size:15px;
    width:18px;
    text-align:center;
}
.hr-tab-progress{
    height:3px;
    background:transparent;
    overflow:hidden;
    margin:0 -20px;
}
.hr-tab-progress__bar{
    height:100%;
    width:0;
    background:linear-gradient(90deg,var(--clr-primary,#800020),#c0392b,var(--clr-primary,#800020));
    background-size:200% 100%;
    transition:width .3s ease;
    animation:none;
}
.hr-tab-progress.loading .hr-tab-progress__bar{
    width:100%;
    animation:hrTabShimmer 1.5s infinite linear;
}
@keyframes hrTabShimmer{
    0%{background-position:200% 0}
    100%{background-position:-200% 0}
}

.hr-tab-content-wrap{
    transition:opacity .2s ease, transform .15s ease;
}
.hr-tab-content-wrap.loading{
    opacity:.3;
    transform:translateY(4px);
    pointer-events:none;
}

@media(max-width:768px){
    .hr-section-tabs{margin:-15px -10px 0 -10px;padding:0 10px}
    .hr-section-tabs__item{padding:12px 14px;font-size:12px;gap:6px}
    .hr-section-tabs__item i{font-size:13px}
    .hr-tab-progress{margin:0 -10px}
}
</style>

<script>
(function(){
    if(window.__hrSpaTabsBound) return;
    window.__hrSpaTabsBound = true;

    function getContentSection(){
        return document.querySelector('.content-wrapper > section.content');
    }
    function getHeaderSection(){
        return document.querySelector('.content-wrapper > section.content-header');
    }

    function activateTab(href){
        document.querySelectorAll('.hr-section-tabs__item').forEach(function(el){
            var tabPath = new URL(el.href, location.origin).pathname;
            var targetPath = new URL(href, location.origin).pathname;
            el.classList.toggle('active', tabPath === targetPath);
        });
    }

    function executeScripts(container){
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function(old){
            var s = document.createElement('script');
            if(old.src){
                s.src = old.src;
            } else {
                s.textContent = old.textContent;
            }
            old.parentNode.replaceChild(s, old);
        });
    }

    function loadTab(href, pushState){
        var progress = document.querySelector('.hr-tab-progress');
        var content = getContentSection();
        if(!content) { location.href = href; return; }

        if(progress) progress.classList.add('loading');
        content.classList.add('hr-tab-content-wrap','loading');
        activateTab(href);

        fetch(href, {
            headers: {'X-Requested-With':'XMLHttpRequest', 'X-SPA-Tab':'1'},
            credentials: 'same-origin'
        })
        .then(function(r){
            if(!r.ok) throw new Error(r.status);
            return r.text();
        })
        .then(function(html){
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var newContent = doc.querySelector('.content-wrapper > section.content');
            var newHeader = doc.querySelector('.content-wrapper > section.content-header');

            if(!newContent){ location.href = href; return; }

            var header = getHeaderSection();
            if(header && newHeader){
                header.innerHTML = newHeader.innerHTML;
            }

            content.classList.remove('loading');
            content.innerHTML = newContent.innerHTML;

            var newStyles = doc.querySelectorAll('style');
            newStyles.forEach(function(s){
                var exists = false;
                document.querySelectorAll('style').forEach(function(existing){
                    if(existing.textContent.trim() === s.textContent.trim()) exists = true;
                });
                if(!exists) document.head.appendChild(s.cloneNode(true));
            });

            var newLinks = doc.querySelectorAll('link[rel="stylesheet"]');
            newLinks.forEach(function(link){
                var href2 = link.getAttribute('href');
                if(href2 && !document.querySelector('link[href="'+href2+'"]')){
                    document.head.appendChild(link.cloneNode(true));
                }
            });

            executeScripts(content);

            if(typeof jQuery !== 'undefined'){
                jQuery(document).trigger('ready');
                jQuery(document).trigger('pjax:end');
            }

            if(pushState !== false){
                history.pushState({spaTab: true, href: href}, '', href);
            }

            content.classList.remove('hr-tab-content-wrap');
            if(progress) progress.classList.remove('loading');

            window.scrollTo({top: 0, behavior: 'smooth'});
        })
        .catch(function(err){
            console.warn('SPA tab load failed, falling back:', err);
            location.href = href;
        });
    }

    document.addEventListener('click', function(e){
        var tab = e.target.closest('[data-spa-tab]');
        if(!tab) return;

        if(e.ctrlKey || e.metaKey || e.shiftKey) return;

        e.preventDefault();
        var href = tab.getAttribute('href');

        if(new URL(href, location.origin).pathname === location.pathname) return;

        loadTab(href, true);
    });

    window.addEventListener('popstate', function(e){
        if(e.state && e.state.spaTab){
            loadTab(e.state.href, false);
        }
    });

    history.replaceState({spaTab: true, href: location.href}, '', location.href);
})();
</script>
