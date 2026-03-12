<?php

namespace backend\assets;

use noam148\imagemanager\assets\ImageManagerInputAsset as BaseImageManagerInputAsset;
use yii\web\AssetBundle;

/**
 * ImageManagerInputAsset.
 */
class ImageManagerInputAsset extends BaseImageManagerInputAsset
{
    // public $sourcePath = '@backend/web';
    public $css = [
		'css/imagemanager.input.css',
    ];
    public $js = [
        'js/script.imagemanager.input.js',
    ];
    public $depends = [
		'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}