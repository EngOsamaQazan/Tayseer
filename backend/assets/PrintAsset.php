<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class PrintAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public function init()
    {
        parent::init();
        //  $this->publishOptions['forceCopy'] = true;
    }


    public $css = [
        "css/bootstrap.css",
        'css/print/style.css',
    ];
    public $js = [
        "js/bootstrap.bundle.js",
        'js/Tafqeet.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
