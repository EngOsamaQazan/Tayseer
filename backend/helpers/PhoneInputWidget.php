<?php

namespace backend\helpers;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

class PhoneInputWidget extends InputWidget
{
    /**
     * intl-tel-input options.
     * @see https://intl-tel-input.com/docs/options
     */
    public $jsOptions = [];

    public function run()
    {
        PhoneInputAsset::register($this->view);

        $id = $this->options['id'] ?? Html::getInputId($this->model, $this->attribute);
        $this->options['id'] = $id;
        $this->options['type'] = 'tel';

        if ($this->hasModel()) {
            $input = Html::activeInput('tel', $this->model, $this->attribute, $this->options);
        } else {
            $input = Html::input('tel', $this->name, $this->value, $this->options);
        }

        $defaults = [
            'initialCountry' => 'jo',
            'countryOrder' => ['jo', 'ps', 'sa', 'iq', 'eg', 'sy', 'lb', 'ae'],
            'separateDialCode' => true,
            'countrySearch' => true,
            'formatAsYouType' => true,
            'strictMode' => true,
            'countryNameLocale' => 'ar',
            'i18n' => [
                'searchPlaceholder' => 'بحث عن دولة...',
                'noCountrySelected' => 'اختر الدولة',
                'countryListAriaLabel' => 'قائمة الدول',
                'searchEmptyState' => 'لا توجد نتائج',
            ],
            'loadUtils' => '__UTILS_IMPORT__',
        ];

        $merged = array_replace_recursive($defaults, $this->jsOptions);

        $utilsUrl = PhoneInputAsset::register($this->view)->baseUrl . '/js/utils.js';
        $absUtilsUrl = \yii\helpers\Url::to($utilsUrl, true);
        $jsonOpts = Json::htmlEncode($merged);
        $jsonOpts = str_replace('"__UTILS_IMPORT__"', "function(){return import('$absUtilsUrl')}", $jsonOpts);

        $js = <<<JS
(function(){
    var el = document.getElementById('$id');
    if (!el) return;
    var iti = window.intlTelInput(el, $jsonOpts);
    el._iti = iti;
    var form = el.closest('form');
    if (form) {
        form.addEventListener('submit', function(){
            el.value = iti.getNumber();
        });
    }
})();
JS;

        $this->view->registerJs($js);

        return $input;
    }
}
