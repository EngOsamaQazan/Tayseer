'use strict';

window.isRtl = window.Helpers.isRtl();
window.isDarkStyle = window.Helpers.isDarkStyle();

let menu, animate, isHorizontalLayout = false;

if (document.getElementById('layout-menu')) {
  isHorizontalLayout = document.getElementById('layout-menu').classList.contains('menu-horizontal');
}

(function () {
  function onScroll() {
    var layoutPage = document.querySelector('.layout-page');
    if (layoutPage) {
      if (window.scrollY > 0) {
        layoutPage.classList.add('window-scrolled');
      } else {
        layoutPage.classList.remove('window-scrolled');
      }
    }
  }

  setTimeout(function () { onScroll(); }, 200);
  window.onscroll = function () { onScroll(); };
  setTimeout(function () { window.Helpers.initCustomOptionCheck(); }, 1000);

  if (typeof Waves !== 'undefined') {
    Waves.init();
    Waves.attach(".btn[class*='btn-']:not(.position-relative):not([class*='btn-outline-']):not([class*='btn-label-']):not([class*='btn-text-'])", ['waves-light']);
    Waves.attach("[class*='btn-outline-']:not(.position-relative)");
    Waves.attach("[class*='btn-label-']:not(.position-relative)");
    Waves.attach("[class*='btn-text-']:not(.position-relative)");
    Waves.attach('.pagination .page-item .page-link');
    Waves.attach('.dropdown-menu .dropdown-item');
    Waves.attach('.nav-tabs:not(.nav-tabs-widget) .nav-item .nav-link');
    Waves.attach('.nav-pills .nav-item .nav-link', ['waves-light']);
  }

  let layoutMenuEl = document.querySelectorAll('#layout-menu');
  layoutMenuEl.forEach(function (element) {
    menu = new Menu(element, {
      orientation: isHorizontalLayout ? 'horizontal' : 'vertical',
      closeChildren: isHorizontalLayout,
      showDropdownOnHover: true
    });
    window.Helpers.scrollToActive((animate = false));
    window.Helpers.mainMenu = menu;
  });

  let menuToggler = document.querySelectorAll('.layout-menu-toggle');
  menuToggler.forEach(function (item) {
    item.addEventListener('click', function (event) {
      event.preventDefault();
      window.Helpers.toggleCollapsed();
      if (config.enableMenuLocalStorage && !window.Helpers.isSmallScreen()) {
        try {
          localStorage.setItem(
            'templateCustomizer-' + templateName + '--LayoutCollapsed',
            String(window.Helpers.isCollapsed())
          );
        } catch (e) {}
      }
    });
  });

  window.Helpers.swipeIn('.drag-target', function () {
    window.Helpers.setCollapsed(false);
  });
  window.Helpers.swipeOut('#layout-menu', function () {
    if (window.Helpers.isSmallScreen()) window.Helpers.setCollapsed(true);
  });

  let menuInnerContainer = document.getElementsByClassName('menu-inner'),
    menuInnerShadow = document.getElementsByClassName('menu-inner-shadow')[0];
  if (menuInnerContainer.length > 0 && menuInnerShadow) {
    menuInnerContainer[0].addEventListener('ps-scroll-y', function () {
      if (this.querySelector('.ps__thumb-y').offsetTop) {
        menuInnerShadow.style.display = 'block';
      } else {
        menuInnerShadow.style.display = 'none';
      }
    });
  }

  window.Helpers.setTheme(window.Helpers.getPreferredTheme());

  function getScrollbarWidth() {
    var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    document.body.style.setProperty('--bs-scrollbar-width', scrollbarWidth + 'px');
  }
  getScrollbarWidth();

  window.addEventListener('DOMContentLoaded', function () {
    window.Helpers.showActiveTheme(window.Helpers.getPreferredTheme());
    getScrollbarWidth();
    window.Helpers.initSidebarToggle();

    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el, { trigger: 'hover' }); });
    }
  });

  var accordionActiveFunction = function (e) {
    if (e.type === 'show.bs.collapse') {
      e.target.closest('.accordion-item').classList.add('active');
    } else {
      e.target.closest('.accordion-item').classList.remove('active');
    }
  };
  var accordionList = [].slice.call(document.querySelectorAll('.accordion'));
  accordionList.forEach(function (el) {
    el.addEventListener('show.bs.collapse', accordionActiveFunction);
    el.addEventListener('hide.bs.collapse', accordionActiveFunction);
  });

  window.Helpers.setAutoUpdate(true);
  window.Helpers.initPasswordToggle();
  window.Helpers.initSpeechToText();
  window.Helpers.initNavbarDropdownScrollbar();

  if (isHorizontalLayout || window.Helpers.isSmallScreen()) {
    return;
  }

  if (typeof config !== 'undefined' && config.enableMenuLocalStorage) {
    try {
      if (localStorage.getItem('templateCustomizer-' + templateName + '--LayoutCollapsed') !== null) {
        window.Helpers.setCollapsed(
          localStorage.getItem('templateCustomizer-' + templateName + '--LayoutCollapsed') === 'true',
          false
        );
      }
    } catch (e) {}
  }
})();
