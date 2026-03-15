/**
 * Bootstrap 5 / jQuery Plugin Bridge
 * ───────────────────────────────────
 * BS 5.3.x defers $.fn.modal, $.fn.tooltip, etc. to DOMContentLoaded via
 * defineJQueryPlugin(). jQuery.ready() fires BEFORE those callbacks because
 * jQuery registers its DOMContentLoaded listener earlier in the script load
 * order. This bridge registers the plugins synchronously so they are
 * available when Kartik/CrudAsset .ready() callbacks execute.
 */
(function () {
    if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') return;
    var plugins = [
        'Alert', 'Button', 'Carousel', 'Collapse', 'Dropdown',
        'Modal', 'Offcanvas', 'Popover', 'ScrollSpy', 'Tab', 'Toast', 'Tooltip'
    ];
    for (var i = 0; i < plugins.length; i++) {
        var Plugin = bootstrap[plugins[i]];
        if (Plugin && Plugin.NAME && !jQuery.fn[Plugin.NAME]) {
            jQuery.fn[Plugin.NAME] = Plugin.jQueryInterface;
            jQuery.fn[Plugin.NAME].Constructor = Plugin;
        }
    }
})();

$('.remove-image').on('click', function (e) {
    e.preventDefault();
    let deleteUrl = $(this).attr('href');
    $.post(deleteUrl, {}, function (response) {

        if (response) {
            $(this).closest('tr').remove();
        } else {
            alert('Cannot delete this file');
        }
    });
});