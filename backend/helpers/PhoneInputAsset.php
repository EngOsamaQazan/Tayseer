<?php

namespace backend\helpers;

use yii\web\AssetBundle;

class PhoneInputAsset extends AssetBundle
{
    public $basePath = '@webroot/lib/intl-tel-input';
    public $baseUrl = '@web/lib/intl-tel-input';

    public $css = [
        'css/intlTelInput.min.css',
    ];

    public $js = [
        'js/intlTelInput.min.js',
    ];
}
