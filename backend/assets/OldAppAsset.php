<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class OldAppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        // 'css/bootstrap-fileinput.css',
        // 'plugins/iCheck/square/blue.css',
        // 'plugins/multiselect/multiselect.min.css',
        // 'plugins/toastr/toastr.min.css',
        // 'css/site.css',
        // 'css/custom.css?v=11',
        // 'css/custom_2.css?v=13',
    ];
    public $js = [
        // 'js/script.js'
    ];
    public $depends = [
        // 'yii\web\YiiAsset',
        // 'yii\bootstrap\BootstrapAsset',
        // 'yii\bootstrap\BootstrapPluginAsset',
    ];
}
