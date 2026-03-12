<?php

namespace backend\helpers;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

class FlatpickrWidget extends InputWidget
{
    /**
     * Flatpickr options.
     * @see https://flatpickr.js.org/options/
     */
    public $pluginOptions = [];

    /**
     * Flatpickr event callbacks (onChange, onClose, etc.)
     * @var array ['onChange' => 'js:function(dates,str){...}']
     */
    public $pluginEvents = [];

    public function run()
    {
        FlatpickrAsset::register($this->view);

        $id = $this->options['id'] ?? Html::getInputId($this->model, $this->attribute);
        $this->options['id'] = $id;
        $this->options['autocomplete'] = 'off';

        if ($this->hasModel()) {
            $input = Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            $input = Html::textInput($this->name, $this->value, $this->options);
        }

        $defaults = [
            'dateFormat' => 'Y-m-d',
            'locale' => 'ar',
            'disableMobile' => true,
            'allowInput' => true,
            'clickOpens' => true,
        ];

        $merged = array_merge($defaults, $this->pluginOptions);
        $jsonOpts = Json::htmlEncode($merged);

        $events = '';
        foreach ($this->pluginEvents as $event => $handler) {
            $fn = preg_replace('/^js:/', '', $handler);
            $events .= "fp.config.$event.push($fn);";
        }

        $js = <<<JS
(function(){
    var el = document.getElementById('$id');
    if (!el) return;
    var fp = flatpickr(el, $jsonOpts);
    $events
    el._flatpickr = fp;
})();
JS;

        $this->view->registerJs($js);

        $icon = '<div class="fp-wrap" style="position:relative">'
            . $input
            . '<span class="fp-icon" onclick="document.getElementById(\'' . $id . '\')._flatpickr.toggle()" '
            . 'style="position:absolute;left:8px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94A3B8;font-size:14px">'
            . '<i class="fa fa-calendar"></i></span></div>';

        return $icon;
    }
}
