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

        "css/bootstrap.min.css",
        'css/print/style.css',

    ];
    public $js = [
        "js/jquery.min.js",
        "js/bootstrap.js",
        'js/Tafqeet.js',
        'js/popper.min.js',
    ];
    public $depends = [
        /*'yii\web\JqueryAsset',
        //'yii\web\YiiAsset',
        //'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',*/
    ];
}
