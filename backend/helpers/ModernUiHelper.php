<?php

namespace backend\helpers;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/**
 * ModernUiHelper — PHP helpers for modern frontend libraries
 *
 * Provides easy-to-use methods for integrating Alpine.js, HTMX,
 * ApexCharts, SweetAlert2, Tippy.js, SortableJS, and AOS
 * from Yii2 PHP views.
 *
 * @example
 *   // في أي view:
 *   use backend\helpers\ModernUiHelper as UI;
 *
 *   // Tippy tooltip على زر
 *   echo UI::tooltip('هذا زر حذف خطير', Html::a('حذف', '#', ['class'=>'btn btn-danger']));
 *
 *   // ApexCharts
 *   echo UI::chart('sales-chart', [...options...]);
 *
 *   // HTMX button
 *   echo UI::htmxButton('تحميل المزيد', '/api/load-more', '#results');
 */
class ModernUiHelper
{
    /**
     * Wrap an element with a Tippy.js tooltip
     */
    public static function tooltip(string $content, string $element, array $options = []): string
    {
        $theme = $options['theme'] ?? 'tayseer';
        $placement = $options['placement'] ?? 'top';

        return str_replace(
            '>',
            ' data-tippy-content="' . Html::encode($content) . '"'
            . ' data-tippy-theme="' . $theme . '"'
            . ' data-tippy-placement="' . $placement . '"'
            . '>',
            $element,
            1
        );
    }

    /**
     * Render an ApexCharts container + initialization script
     */
    public static function chart(string $id, array $options, View $view): string
    {
        $jsonOptions = Json::encode($options);

        $js = <<<JS
(function(){
    var el = document.getElementById('{$id}');
    if (el && typeof ApexCharts !== 'undefined') {
        var chart = new ApexCharts(el, {$jsonOptions});
        chart.render();
    }
})();
JS;
        $view->registerJs($js, View::POS_READY, 'apex-' . $id);

        return Html::tag('div', '', ['id' => $id, 'class' => 'apex-chart-container']);
    }

    /**
     * Create an HTMX-powered button
     */
    public static function htmxButton(string $label, string $url, string $target, array $options = []): string
    {
        $method = $options['method'] ?? 'get';
        $swap = $options['swap'] ?? 'innerHTML';
        $trigger = $options['trigger'] ?? 'click';
        $cssClass = $options['class'] ?? 'btn btn-secondary btn-sm';
        $confirm = $options['confirm'] ?? null;

        $attrs = [
            'class' => $cssClass,
            'hx-' . $method => $url,
            'hx-target' => $target,
            'hx-swap' => $swap,
            'hx-trigger' => $trigger,
        ];

        if ($confirm) {
            $attrs['hx-confirm'] = $confirm;
        }

        $indicator = '<span class="htmx-indicator"><i class="fa fa-spinner fa-spin"></i></span>';

        return Html::tag('button', $label . ' ' . $indicator, $attrs);
    }

    /**
     * Create an HTMX lazy-load container
     */
    public static function htmxLazy(string $url, string $placeholder = ''): string
    {
        if (empty($placeholder)) {
            $placeholder = '<div class="text-center p-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
        }

        return Html::tag('div', $placeholder, [
            'hx-get' => $url,
            'hx-trigger' => 'revealed',
            'hx-swap' => 'outerHTML',
        ]);
    }

    /**
     * Wrap content with AOS animation attribute
     */
    public static function aos(string $content, string $animation = 'fade-up', array $options = []): string
    {
        $delay = $options['delay'] ?? 0;
        $duration = $options['duration'] ?? 600;

        return Html::tag('div', $content, [
            'data-aos' => $animation,
            'data-aos-delay' => $delay,
            'data-aos-duration' => $duration,
        ]);
    }

    /**
     * Create an Alpine.js toggle component
     */
    public static function alpineToggle(string $buttonLabel, string $content, array $options = []): string
    {
        $btnClass = $options['buttonClass'] ?? 'btn btn-secondary btn-sm';
        $icon = $options['icon'] ?? 'fa fa-chevron-down';

        return <<<HTML
<div x-data="{ open: false }">
    <button @click="open = !open" class="{$btnClass}" :aria-expanded="open">
        <i class="{$icon}" :class="{'fa-chevron-up': open, 'fa-chevron-down': !open}"></i>
        {$buttonLabel}
    </button>
    <div x-show="open" x-transition:enter="alpine-enter" x-cloak class="mt-2">
        {$content}
    </div>
</div>
HTML;
    }

    /**
     * Create a SortableJS list container
     */
    public static function sortableList(string $id, array $items, string $saveUrl = '', array $options = []): string
    {
        $handle = $options['handle'] ?? '';

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemId = Html::encode($item['id'] ?? '');
            $label = Html::encode($item['label'] ?? '');
            $handleHtml = $handle ? '<span class="' . $handle . '" style="cursor:grab"><i class="fa fa-grip-vertical"></i></span> ' : '';
            $itemsHtml .= '<li class="list-group-item" data-id="' . $itemId . '">' . $handleHtml . $label . '</li>';
        }

        $attrs = [
            'id' => $id,
            'class' => 'list-group',
            'data-sortable' => 'true',
        ];

        if ($saveUrl) {
            $attrs['data-sortable-url'] = $saveUrl;
        }
        if ($handle) {
            $attrs['data-sortable-handle'] = '.' . $handle;
        }

        return Html::tag('ul', $itemsHtml, $attrs);
    }
}
