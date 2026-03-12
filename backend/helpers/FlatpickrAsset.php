<?php

namespace backend\helpers;

use yii\web\AssetBundle;

class FlatpickrAsset extends AssetBundle
{
    public $basePath = '@webroot/lib/flatpickr';
    public $baseUrl = '@web/lib/flatpickr';

    public $css = [
        'css/flatpickr.min.css',
    ];

    public $js = [
        'js/flatpickr.min.js',
        'l10n/ar.js',
        'js/auto-init.js',
    ];
}
