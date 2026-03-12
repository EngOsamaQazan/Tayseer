<?php

namespace backend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/**
 * Reusable autocomplete search widget.
 *
 * Renders a unified search input with AJAX-powered suggestions dropdown.
 * Can be used with or without an ActiveForm — works with any search model
 * that has a `q` attribute.
 *
 * Usage:
 *   <?= UnifiedSearchWidget::widget([
 *       'name'        => 'ContractsSearch[q]',
 *       'value'       => $model->q,
 *       'searchUrl'   => Url::to(['search-suggest']),
 *       'placeholder' => 'رقم العقد، اسم العميل...',
 *   ]) ?>
 */
class UnifiedSearchWidget extends Widget
{
    /** @var string HTML name attribute */
    public $name;

    /** @var string|null Current value */
    public $value;

    /** @var string AJAX URL that returns JSON suggestions */
    public $searchUrl;

    /** @var string Placeholder text */
    public $placeholder = 'بحث...';

    /** @var string CSS id (auto-generated if empty) */
    public $inputId;

    /** @var int Minimum characters before triggering search */
    public $minChars = 2;

    /** @var int Debounce delay in ms */
    public $delay = 300;

    /** @var string Form selector to submit on suggestion click (empty = no auto-submit) */
    public $formSelector = '';

    /** @var bool Whether clicking a suggestion navigates to its URL */
    public $navigateOnSelect = false;

    /** @var array Extra HTML options for the input */
    public $inputOptions = [];

    private static $_assetsRegistered = false;

    public function run()
    {
        if (empty($this->inputId)) {
            $this->inputId = 'us-' . $this->getId();
        }

        $this->registerAssets();

        $opts = array_merge([
            'type'         => 'text',
            'id'           => $this->inputId,
            'name'         => $this->name,
            'value'        => $this->value,
            'placeholder'  => $this->placeholder,
            'class'        => 'us-input',
            'autocomplete' => 'off',
            'aria-label'   => $this->placeholder,
        ], $this->inputOptions);

        $cfg = Json::encode([
            'inputId'          => $this->inputId,
            'url'              => $this->searchUrl,
            'minChars'         => $this->minChars,
            'delay'            => $this->delay,
            'formSelector'     => $this->formSelector,
            'navigateOnSelect' => $this->navigateOnSelect,
        ]);

        $this->view->registerJs("UnifiedSearch.init($cfg);", View::POS_READY);

        $html  = '<div class="us-wrap" id="' . Html::encode($this->inputId) . '-wrap">';
        $html .= '<i class="fa fa-search us-icon"></i>';
        $html .= Html::tag('input', '', $opts);
        $html .= '<span class="us-spinner" style="display:none"><i class="fa fa-circle-o-notch fa-spin"></i></span>';
        $html .= '<div class="us-dropdown" style="display:none"></div>';
        $html .= '</div>';

        return $html;
    }

    private function registerAssets()
    {
        if (self::$_assetsRegistered) {
            return;
        }
        self::$_assetsRegistered = true;

        $base = \Yii::$app->request->baseUrl;
        $v = time();
        $this->view->registerCssFile("$base/css/unified-search.css?v=$v");
        $this->view->registerJsFile("$base/js/unified-search.js?v=$v", ['position' => View::POS_HEAD]);
    }
}
